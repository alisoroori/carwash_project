# E2E runner for carwash approve/reject actions
param(
    [int]$CarwashId = 1,
    [int]$UserId = 1000,
    [string]$BaseUrl = 'http://localhost/carwash_project'
)

$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$setSessionUrl = "$BaseUrl/backend/test/set_session.php"
Write-Host "Setting test session for carwash_id=$CarwashId..."
$response = Invoke-RestMethod -Uri $setSessionUrl -Method POST -Body @{ carwash_id = $CarwashId; user_id = $UserId } -WebSession $session -ErrorAction Stop
if (-not $response.success) { Write-Error "Failed to set session: $($response | ConvertTo-Json -Compress)"; exit 1 }
Write-Host "Session set. session_id:" $response.session_id

# Start background job to tail app.log into temp file
$logFile = Join-Path $PSScriptRoot "..\logs\app.log" | Resolve-Path -ErrorAction SilentlyContinue
if (-not $logFile) { $logFile = Join-Path $PSScriptRoot "..\logs\app.log" }
$captureFile = Join-Path $PSScriptRoot "..\backend\logs\e2e_log_capture.txt"
if (Test-Path $captureFile) { Remove-Item $captureFile -Force }

$job = Start-Job -ScriptBlock {
    param($lf,$out)
    Get-Content -Path $lf -Tail 0 -Wait | ForEach-Object { $_ | Out-File -FilePath $out -Append }
} -ArgumentList $logFile,$captureFile
Start-Sleep -Seconds 1
Write-Host "Log capture started (job id=$($job.Id))."

# Fetch reservations list
$listUrl = "$BaseUrl/backend/carwash/reservations/list.php"
Write-Host "Fetching booking list..."
try {
    $resp = Invoke-RestMethod -Uri $listUrl -Method GET -WebSession $session -Headers @{ Accept = 'application/json' } -ErrorAction Stop
} catch {
    Write-Error "Failed to fetch booking list: $_"; Stop-Job $job; Receive-Job $job | Out-Null; exit 1
}

if (-not $resp.success) {
    Write-Error "API returned error: $($resp | ConvertTo-Json -Compress)"; Stop-Job $job; Receive-Job $job | Out-Null; exit 1
}

$rows = $resp.data
if (-not $rows -or $rows.Count -eq 0) {
    Write-Host "No bookings found. Stopping log capture."; Stop-Job $job; Receive-Job $job | Out-Null; exit 0
}

# Prepare DB connection (use PDO via CLI php script to query DB)
$phpQueryScript = @"
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Classes\Database;

function queryBooking($id) {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $stmt = $pdo->prepare('SELECT id,status,carwash_id FROM bookings WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($r ?: null);
}

if (isset(
    getenv('E2E_BOOKING_ID')
)) {
    $id = (int)getenv('E2E_BOOKING_ID');
    queryBooking($id);
}
"@

$phpFile = Join-Path $PSScriptRoot "e2e_db_query.php"
$phpFilePath = Resolve-Path -Path $phpFile -ErrorAction SilentlyContinue
Set-Content -Path $phpFile -Value $phpQueryScript -Force

$results = @()

foreach ($r in $rows) {
    $bookingId = $r.id
    Write-Host "Testing booking id: $bookingId"

    # Approve
    $approveUrl = "$BaseUrl/backend/carwash/reservations/approve.php"
    try {
        $approveResp = Invoke-RestMethod -Uri $approveUrl -Method POST -Body @{ booking_id = $bookingId } -WebSession $session -ErrorAction Stop
        $approveCode = 200
    } catch {
        $approveResp = $_.Exception.Response | Select-Object -ExpandProperty StatusCode -ErrorAction SilentlyContinue
        $approveCode = $_.Exception.Response.StatusCode.Value__
    }

    Start-Sleep -Milliseconds 300

    # Query DB for status after approve
    $env:E2E_BOOKING_ID = $bookingId
    $phpOut = php -f $phpFile
    $dbBefore = $phpOut | ConvertFrom-Json

    # Now reject (toggle back to cancelled)
    $rejectUrl = "$BaseUrl/backend/carwash/reservations/reject.php"
    try {
        $rejectResp = Invoke-RestMethod -Uri $rejectUrl -Method POST -Body @{ booking_id = $bookingId } -WebSession $session -ErrorAction Stop
        $rejectCode = 200
    } catch {
        $rejectResp = $_.Exception.Response | Select-Object -ExpandProperty StatusCode -ErrorAction SilentlyContinue
        $rejectCode = $_.Exception.Response.StatusCode.Value__
    }

    Start-Sleep -Milliseconds 300

    # Query DB for status after reject
    $env:E2E_BOOKING_ID = $bookingId
    $phpOut2 = php -f $phpFile
    $dbAfter = $phpOut2 | ConvertFrom-Json

    # Refresh frontend list for this booking
    $listRef = Invoke-RestMethod -Uri $listUrl -Method GET -WebSession $session -Headers @{ Accept = 'application/json' }
    $found = $false
    $frontendStatus = $null
    foreach ($x in $listRef.data) { if ($x.id -eq $bookingId) { $found = $true; $frontendStatus = $x.status } }

    # Read log snippets related to booking id
    Start-Sleep -Milliseconds 200
    $logContent = Get-Content -Path $captureFile -Raw -ErrorAction SilentlyContinue
    $lines = @()
    if ($logContent) {
        $lines = ($logContent -split "`n") | Where-Object { $_ -match "booking.*$bookingId|$bookingId|carwash_id" } | Select-Object -Last 20
    }

    $entry = [PSCustomObject]@{
        booking_id = $bookingId
        carwash_id = $CarwashId
        action_taken = 'approve_then_reject'
        approve_response = @{ http_code = $approveCode; body = $approveResp }
        reject_response = @{ http_code = $rejectCode; body = $rejectResp }
        db_status_after_approve = $dbBefore.status
        db_status_after_reject = $dbAfter.status
        frontend_status = $frontendStatus
        log_snippets = $lines
        errors_found = @()
        suggested_fix = $null
    }

    # Analyze mismatches
    if ($dbBefore.status -ne 'confirmed') { $entry.errors_found += "After approve DB status != confirmed (was: $($dbBefore.status))" }
    if ($dbAfter.status -ne 'cancelled') { $entry.errors_found += "After reject DB status != cancelled (was: $($dbAfter.status))" }
    if (-not $found) { $entry.errors_found += 'Booking not found in frontend list after actions' }
    if ($frontendStatus -ne $dbAfter.status) { $entry.errors_found += "Frontend status ($frontendStatus) != DB status ($($dbAfter.status))" }

    if ($entry.errors_found.Count -gt 0) { $entry.suggested_fix = 'Investigate server-side handlers and ensure Response::success uses same status mapping; verify frontend mapping of status strings.' }

    $results += $entry
}

# Stop log capture job and collect output
Stop-Job $job | Out-Null
Receive-Job $job | Out-Null
Remove-Job $job

# Output results as JSON
$results | ConvertTo-Json -Depth 6
