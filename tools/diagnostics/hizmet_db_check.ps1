<#
PowerShell Diagnostic Script for Hizmet Yönetimi (MySQL)
- Tests TCP connection to the DB host/port
- Verifies database and `services` table exists
- Finds a `carwash` id to use for test INSERT
- Performs INSERT -> UPDATE -> DELETE on `services` and logs results

Usage (PowerShell):
  cd c:\xampp\htdocs\carwash_project\tools\diagnostics
  .\hizmet_db_check.ps1 -DbHost localhost -DbPort 3306 -DbName carwash_db -DbUser root -DbPass ""

Notes:
- Requires `mysql.exe` on PATH (XAMPP's MySQL). If not available, provide full path to mysql.exe using -MySqlCliPath.
- Script executes SQL via mysql CLI and captures stdout/stderr.
- It will not modify production data beyond a single test row that is cleaned up.
#>

param(
    [string]$DbHost = 'localhost',
    [int]$DbPort = 3306,
    [string]$DbName = 'carwash_db',
    [string]$DbUser = 'root',
    [string]$DbPass = '',
    [string]$MySqlCliPath = 'mysql.exe'
)

function Write-Log { param($msg) Write-Host "[$(Get-Date -Format o)] $msg" }
function Run-MySql($sql) {
    $passArg = "--password="
    $args = @('-h', $DbHost, '-P', $DbPort.ToString(), '-u', $DbUser, $passArg, '-D', $DbName, '-e', $sql)
    try {
        $output = & $MySqlCliPath @args 2>&1
        $rc = $LASTEXITCODE
        return @{ ExitCode = $rc; Output = $output }
    } catch {
        return @{ ExitCode = 999; Output = $_.Exception.Message }
    }
}

Write-Log "Starting Hizmet Yönetimi DB diagnostic"

# 1) TCP check
Write-Log "Testing TCP connection to $DbHost:$DbPort ..."
$tn = Test-NetConnection -ComputerName $DbHost -Port $DbPort -WarningAction SilentlyContinue
if ($tn.TcpTestSucceeded) { Write-Log "TCP port $DbPort reachable" } else { Write-Log "TCP port $DbPort NOT reachable; aborting further checks"; exit 2 }

# 2) Check mysql client
try {
    $ver = & $MySqlCliPath --version 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Log "mysql client not found at '$MySqlCliPath' or returned error: $ver"; Write-Log "Please ensure mysql.exe is in PATH or set -MySqlCliPath to its full path"; exit 3 }
    Write-Log "mysql client detected: $ver"
} catch {
    Write-Log "mysql client invocation failed: $_"; exit 3
}

# 3) Check database exists (attempt to USE it via a simple query)
Write-Log "Checking database '$DbName' accessibility..."
$res = Run-MySql('SELECT SCHEMA() as db')
if ($res.ExitCode -ne 0) { Write-Log "Failed to access database. mysql output:\n$($res.Output -join "`n")"; exit 4 }
Write-Log "Database access OK. mysql output:\n$($res.Output -join "`n")"

# 4) Check services table exists
Write-Log "Checking for 'services' table..."
$res = Run-MySql("SHOW TABLES LIKE 'services';")
if ($res.ExitCode -ne 0) { Write-Log "Error running SHOW TABLES: $($res.Output -join "`n")"; exit 5 }
if (($res.Output -join "`).Trim() -eq '') {
    Write-Log "No 'services' table found in database '$DbName'."; exit 6
} else {
    Write-Log "'services' table found:\n$($res.Output -join "`n")"
}

# 5) Find carwash id to use
Write-Log "Finding a carwash id to use for test insert..."
$res = Run-MySql("SELECT id FROM carwashes LIMIT 1;")
if ($res.ExitCode -ne 0) { Write-Log "Error querying carwashes: $($res.Output -join "`n")"; exit 7 }
$carwashId = ($res.Output | Select-Object -Skip 1 | Select-Object -First 1).Trim()
if (-not $carwashId) { Write-Log "No carwash found in 'carwashes' table. Please create one or use an existing carwash id."; exit 8 }
Write-Log "Using carwash_id = $carwashId"

# 6) Test INSERT
$testName = "E2E_TEST_SERVICE_$(Get-Random)_$(Get-Date -UFormat %s)"
$insertSql = "INSERT INTO services (carwash_id, name, description, price, duration, status, created_at) VALUES ($carwashId, ' $testName', 'test insertion', 10.00, 15, 'active', NOW()); SELECT LAST_INSERT_ID() as lastid;"
Write-Log "Attempting INSERT for test service name: $testName"
$res = Run-MySql($insertSql)
if ($res.ExitCode -ne 0) { Write-Log "INSERT failed: $($res.Output -join "`n")"; exit 9 }
# parse last id
$lastIdLine = ($res.Output | Select-Object -Last 1).Trim()
if (-not ($lastIdLine -match '^[0-9]+$')) {
    # Sometimes SELECT output includes header; try to parse numeric line
    $numeric = $res.Output | Where-Object { $_ -match '^[0-9]+$' } | Select-Object -First 1
    $newId = $numeric.Trim()
} else { $newId = $lastIdLine }
if (-not $newId) { Write-Log "Could not determine inserted id. mysql output:\n$($res.Output -join "`n")"; exit 10 }
Write-Log "Insert succeeded. New service id = $newId"

# 7) Test UPDATE
$updateSql = "UPDATE services SET price = price + 1 WHERE id = $newId; SELECT price FROM services WHERE id = $newId;"
Write-Log "Attempting UPDATE on id $newId"
$res = Run-MySql($updateSql)
if ($res.ExitCode -ne 0) { Write-Log "UPDATE failed: $($res.Output -join "`n")"; exit 11 }
Write-Log "UPDATE output:\n$($res.Output -join "`n")"

# 8) Test DELETE
$deleteSql = "DELETE FROM services WHERE id = $newId; SELECT COUNT(*) FROM services WHERE id = $newId as cnt;"
Write-Log "Attempting DELETE on id $newId"
$res = Run-MySql($deleteSql)
if ($res.ExitCode -ne 0) { Write-Log "DELETE failed: $($res.Output -join "`n")"; exit 12 }
Write-Log "DELETE output:\n$($res.Output -join "`n")"

Write-Log "All tests completed successfully. INSERT/UPDATE/DELETE for services are working."
exit 0
