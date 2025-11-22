param(
    [int]$CarwashId = 1,
    [int]$UserId = 1,
    [string]$BaseUrl = 'http://localhost/carwash_project'
)

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$cookieJar = Join-Path $root 'cookies.txt'
$listFile = Join-Path $root 'list.json'
$logCapture = Join-Path $root '..\logs\e2e_capture.txt'
$logFile = Join-Path $root '..\logs\app.log'

if (Test-Path $cookieJar) { Remove-Item $cookieJar -Force }
if (Test-Path $listFile) { Remove-Item $listFile -Force }
if (Test-Path $logCapture) { Remove-Item $logCapture -Force }

# Start log tail job
$job = Start-Job -ScriptBlock { param($lf,$out) Get-Content -Path $lf -Tail 0 -Wait | ForEach-Object { $_ | Out-File -FilePath $out -Append } } -ArgumentList $logFile,$logCapture
Start-Sleep -Seconds 1
Write-Host "Started log capture job id=$($job.Id)"

# Set session via set_session.php
$setUrl = "$BaseUrl/backend/test/set_session.php"
Write-Host "Calling set_session.php..."
& C:\Windows\System32\curl.exe -s -c $cookieJar -d "carwash_id=$CarwashId&user_id=$UserId" "$setUrl" | Out-Null
Start-Sleep -Milliseconds 200

# Get list
$listUrl = "$BaseUrl/backend/carwash/reservations/list.php"
Write-Host "Fetching list..."
& C:\Windows\System32\curl.exe -s -b $cookieJar -H "Accept: application/json" -o $listFile "$listUrl"

if (-not (Test-Path $listFile)) { Write-Error "List file not found"; Stop-Job $job; Receive-Job $job | Out-Null; exit 1 }
$json = Get-Content $listFile -Raw | ConvertFrom-Json
if (-not $json.success) { Write-Error "List API error: $($json | ConvertTo-Json -Compress)"; Stop-Job $job; Receive-Job $job | Out-Null; exit 1 }

# Extract booking objects
$bookings = @()
foreach ($prop in $json.PSObject.Properties) {
    $val = $prop.Value
    if ($val -is [System.Management.Automation.PSCustomObject]) {
        if ($val.PSObject.Properties.Name -contains 'id') { $bookings += $val }
    }
}

if ($bookings.Count -eq 0) {
    Write-Host "No bookings found. Stopping capture.";
    Stop-Job $job; Receive-Job $job | Out-Null; Remove-Job $job; exit 0
}

$results = @()

foreach ($b in $bookings) {
    $id = $b.id
    Write-Host "Processing booking id $id"

    # Approve
    $approveUrl = "$BaseUrl/backend/carwash/reservations/approve.php"
    $tmpApprove = Join-Path $root "approve_resp_$id.txt"
    $codeApprove = & C:\Windows\System32\curl.exe -s -w "%{http_code}" -b $cookieJar -o $tmpApprove -d "booking_id=$id" "$approveUrl"
    $bodyApprove = Get-Content $tmpApprove -Raw -ErrorAction SilentlyContinue
    $approveJson = $null
    try { $approveJson = $bodyApprove | ConvertFrom-Json } catch { $approveJson = $bodyApprove }

    Start-Sleep -Milliseconds 300

    # DB status after approve
    $dbBeforeRaw = php .\db_query.php $id
    $dbBefore = $dbBeforeRaw | ConvertFrom-Json

    # Reject
    $rejectUrl = "$BaseUrl/backend/carwash/reservations/reject.php"
    $tmpReject = Join-Path $root "reject_resp_$id.txt"
    $codeReject = & C:\Windows\System32\curl.exe -s -w "%{http_code}" -b $cookieJar -o $tmpReject -d "booking_id=$id" "$rejectUrl"
    $bodyReject = Get-Content $tmpReject -Raw -ErrorAction SilentlyContinue
    $rejectJson = $null
    try { $rejectJson = $bodyReject | ConvertFrom-Json } catch { $rejectJson = $bodyReject }

    Start-Sleep -Milliseconds 300

    # DB status after reject
    $dbAfterRaw = php .\db_query.php $id
    $dbAfter = $dbAfterRaw | ConvertFrom-Json

    # Refresh frontend list
    & C:\Windows\System32\curl.exe -s -b $cookieJar -H "Accept: application/json" -o $listFile "$listUrl"
    $listRef = Get-Content $listFile -Raw | ConvertFrom-Json
    $found = $false; $frontendStatus = $null
    foreach ($prop in $listRef.PSObject.Properties) {
        $val = $prop.Value
        if ($val -is [System.Management.Automation.PSCustomObject] -and $val.id -eq $id) { $found = $true; $frontendStatus = $val.status }
    }

    # Read recent log snippets
    $logs = @()
    if (Test-Path $logCapture) {
        $all = Get-Content $logCapture -Raw -ErrorAction SilentlyContinue
        if ($all) {
            $lines = ($all -split "`n") | Where-Object { $_ -match "$id|carwash_id=$CarwashId|approve request|reject request" } | Select-Object -Last 40
            $logs = $lines
        }
    }

    $entry = [PSCustomObject]@{
        booking_id = $id
        carwash_id = $CarwashId
        action_taken = 'approve_then_reject'
        approve_response = @{ http_code = [int]$codeApprove; body = $approveJson }
        reject_response = @{ http_code = [int]$codeReject; body = $rejectJson }
        db_status_after_approve = $dbBefore.status
        db_status_after_reject = $dbAfter.status
        frontend_status = $frontendStatus
        log_snippets = $logs
        errors_found = @()
        suggested_fix = $null
    }

    if ($entry.db_status_after_approve -ne 'confirmed') { $entry.errors_found += "After approve DB status != confirmed (was: $($entry.db_status_after_approve))" }
    if ($entry.db_status_after_reject -ne 'cancelled') { $entry.errors_found += "After reject DB status != cancelled (was: $($entry.db_status_after_reject))" }
    if (-not $found) { $entry.errors_found += 'Booking not found in frontend list after actions' }
    if ($frontendStatus -ne $entry.db_status_after_reject) { $entry.errors_found += "Frontend status ($frontendStatus) != DB status ($($entry.db_status_after_reject))" }
    if ($entry.errors_found.Count -gt 0) { $entry.suggested_fix = 'Verify endpoint handlers and frontend mapping for status strings.' }

    $results += $entry
}

# Stop log capture
Stop-Job $job | Out-Null
Receive-Job $job | Out-Null
Remove-Job $job

# Output JSON
$results | ConvertTo-Json -Depth 6
