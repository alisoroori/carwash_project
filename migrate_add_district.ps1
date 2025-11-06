# ===============================
# File: migrate_add_district.ps1
# Purpose: Add 'district' column to carwash_profiles table safely
# ===============================

# === Database Settings ===
$DBHost = "localhost"
$DBUser = "root"
$DBPass = ""             # set your MySQL password if you have one
$DBName = "carwash_db"   # change this to your actual database name

# === Path to mysql.exe (XAMPP default) ===
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"

# === Check mysql.exe exists ===
if (!(Test-Path $mysqlPath)) {
    Write-Host "ERROR: mysql.exe not found at path $mysqlPath"
    exit 1
}

# === SQL command to check and add column if missing ===
$sqlCommand = @"
ALTER TABLE carwash_profiles 
ADD COLUMN IF NOT EXISTS district VARCHAR(191) NULL AFTER address;
"@

Write-Host "Running migration to add 'district' column to carwash_profiles..."

# === Run MySQL command ===
$arguments = @(
    "-u", $DBUser,
    "--password=$DBPass",
    "-h", $DBHost,
    $DBName,
    "-e", $sqlCommand
)

& $mysqlPath @arguments

if ($LASTEXITCODE -eq 0) {
    Write-Host "SUCCESS: Migration completed. Column 'district' added or already exists."
}
else {
    Write-Host "ERROR: Migration failed. Please check database connection or credentials."
}
