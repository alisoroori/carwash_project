# ================================
# auto_migrate_district.ps1
# Check and auto-create 'district' column in `carwashes` (canonical table)
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
        Write-Host "❌ Could not load MySQL Connector at: $dllPath" -ForegroundColor Red
        exit
    }
} else {
    Write-Host "❌ MySQL Connector DLL not found. Please install Connector/NET 9.4." -ForegroundColor Red
    exit
}

# --- Database connection settings ---
$server = "localhost"
$user = "root"
$password = ""
$database = "carwash_db"

# --- Connection string ---
$connString = "server=$server;user id=$user;password=$password;database=$database"

try {
    $conn = New-Object MySql.Data.MySqlClient.MySqlConnection($connString)
    $conn.Open()
    Write-Host "✅ Connected to database '$database' successfully." -ForegroundColor Green
}
catch {
    Write-Host "❌ Failed to connect to database: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

# --- Check if table exists ---
$tableCheckQuery = @"
SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = 'carwashes';
"@

$cmd = New-Object MySql.Data.MySqlClient.MySqlCommand($tableCheckQuery, $conn)
$tableExists = $cmd.ExecuteScalar()

if ($tableExists -eq 0) {
    Write-Host "❌ Table 'carwash_profiles' does not exist in database '$database'." -ForegroundColor Red
    $conn.Close()
    exit
} else {
    Write-Host "✅ Table 'carwashes' exists." -ForegroundColor Green
}

# --- Check if 'district' column exists ---
$columnCheckQuery = @"
SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'district';
"@

$cmd = New-Object MySql.Data.MySqlClient.MySqlCommand($columnCheckQuery, $conn)
$columnExists = $cmd.ExecuteScalar()

if ($columnExists -gt 0) {
    Write-Host "✅ Column 'district' already exists in 'carwashes'." -ForegroundColor Green
} else {
    Write-Host "⚠ Column 'district' not found. Creating column..." -ForegroundColor Yellow
    try {
        $alterQuery = "ALTER TABLE carwashes ADD COLUMN district VARCHAR(255) NULL;"
        $cmd = New-Object MySql.Data.MySqlClient.MySqlCommand($alterQuery, $conn)
        $cmd.ExecuteNonQuery()
        Write-Host "✅ Column 'district' has been successfully created." -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to create column: $($_.Exception.Message)" -ForegroundColor Red
    }
}

$conn.Close()
Write-Host "=== Done ===" -ForegroundColor Cyan
