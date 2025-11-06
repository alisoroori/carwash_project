# ================================
# test_district_column.ps1
# Check if 'district' column exists in carwash_profiles
# Auto-detect MySQL .NET Connector (v9.4 recommended)
# ================================

Write-Host "=== Checking MySQL connection and schema ===" -ForegroundColor Cyan

# --- Auto-detect MySQL Connector DLL ---
$possiblePaths = @(
    "C:\Program Files (x86)\MySQL\MySQL Connector NET 9.4\Assemblies\v4.5.2\MySql.Data.dll",
    "C:\Program Files (x86)\MySQL\MySQL Connector NET 9.4\Assemblies\v4.8\MySql.Data.dll",
    "C:\Program Files (x86)\MySQL\MySQL Connector NET 9.4\MySql.Data.dll",
    "C:\Program Files\MySQL\MySQL Connector NET 9.4\MySql.Data.dll"
)

$dllPath = $possiblePaths | Where-Object { Test-Path $_ } | Select-Object -First 1

if ($dllPath) {
    try {
        [System.Reflection.Assembly]::LoadFrom($dllPath) | Out-Null
        Write-Host "Loaded MySQL Connector from: $dllPath" -ForegroundColor Yellow
    } catch {
        Write-Host "⚠ Could not load MySQL Connector at: $dllPath" -ForegroundColor Red
        exit
    }
} else {
    Write-Host "❌ MySQL Connector DLL not found. Please check your installation path." -ForegroundColor Red
    exit
}

# --- Connection settings ---
$connectionString = "server=localhost;user id=root;password=;database=carwash_db"

# --- Query to check column existence ---
$query = @"
SELECT COUNT(*) AS col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'carwash_project'
  AND TABLE_NAME = 'carwash_profiles'
  AND COLUMN_NAME = 'district';
"@

# --- Execute query ---
try {
    $conn = New-Object MySql.Data.MySqlClient.MySqlConnection($connectionString)
    $conn.Open()
    $cmd = New-Object MySql.Data.MySqlClient.MySqlCommand($query, $conn)
    $exists = $cmd.ExecuteScalar()
    $conn.Close()

    if ($exists -gt 0) {
        Write-Host "✅ SUCCESS: 'district' column exists in carwash_profiles." -ForegroundColor Green
    } else {
        Write-Host "❌ FAIL: 'district' column NOT found in carwash_profiles!" -ForegroundColor Red
    }
}
catch {
    Write-Host "❌ Error while checking database: $($_.Exception.Message)" -ForegroundColor Red
}
