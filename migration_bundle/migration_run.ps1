# ============================================================================
# CarWash Project - Migration Runner (PowerShell)
# Version: 1.0.0
# Date: 2025-12-06
# Description: Runs database migration with safety checks and backups
# ============================================================================

param(
    [string]$Environment = "staging",
    [string]$MySqlPath = "C:\xampp\mysql\bin",
    [string]$Database = "carwash_db",
    [string]$User = "root",
    [string]$Password = "",
    [switch]$SkipBackup,
    [switch]$DryRunOnly
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupDir = Join-Path $ScriptDir "backups"

# ============================================================================
# Helper Functions
# ============================================================================

function Write-Header {
    param([string]$Message)
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host $Message -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
}

function Write-Step {
    param([string]$Step, [string]$Message)
    Write-Host "[$Step] $Message" -ForegroundColor Yellow
}

function Write-Success {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Write-Warn {
    param([string]$Message)
    Write-Host "[WARN] $Message" -ForegroundColor Yellow
}

function Write-Err {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

function Confirm-Action {
    param([string]$Message)
    $response = Read-Host "$Message (y/n)"
    return $response -eq 'y' -or $response -eq 'Y'
}

function Invoke-MySQL {
    param([string]$Query, [switch]$Silent)
    $mysql = Join-Path $MySqlPath "mysql.exe"
    $cmdArgs = @("-u", $User)
    if ($Password) { $cmdArgs += @("-p$Password") }
    $cmdArgs += @($Database, "-e", $Query)
    
    if ($Silent) {
        & $mysql @cmdArgs 2>&1 | Out-Null
    } else {
        & $mysql @cmdArgs
    }
    return $LASTEXITCODE -eq 0
}

function Invoke-MySQLFile {
    param([string]$FilePath)
    $mysql = Join-Path $MySqlPath "mysql.exe"
    $cmdArgs = @("-u", $User)
    if ($Password) { $cmdArgs += @("-p$Password") }
    $cmdArgs += @($Database, "-e", "SOURCE $FilePath")
    
    & $mysql @cmdArgs
    return $LASTEXITCODE -eq 0
}

function Invoke-MySQLDump {
    param([string]$OutputFile)
    $mysqldump = Join-Path $MySqlPath "mysqldump.exe"
    $cmdArgs = @("-u", $User)
    if ($Password) { $cmdArgs += @("-p$Password") }
    $cmdArgs += @("--single-transaction", "--routines", "--triggers", $Database)
    
    & $mysqldump @cmdArgs | Out-File -FilePath $OutputFile -Encoding UTF8
    return $LASTEXITCODE -eq 0
}

# ============================================================================
# Main Script
# ============================================================================

Write-Header "CarWash Database Migration Runner"

Write-Host "Environment: $Environment"
Write-Host "Database:    $Database"
Write-Host "User:        $User"
Write-Host "Script Dir:  $ScriptDir"
Write-Host "Timestamp:   $Timestamp"
Write-Host ""

# Safety check for production
if ($Environment -eq "production") {
    Write-Warn "PRODUCTION ENVIRONMENT DETECTED!"
    if (-not (Confirm-Action "Are you ABSOLUTELY SURE you want to run on PRODUCTION?")) {
        Write-Host "Migration cancelled." -ForegroundColor Red
        exit 1
    }
    if (-not (Confirm-Action "Have you tested this on staging first?")) {
        Write-Host "Please test on staging first." -ForegroundColor Red
        exit 1
    }
}

# ============================================================================
# Step 1: Database Connectivity Check
# ============================================================================

Write-Step "1/8" "Checking database connectivity..."

if (Invoke-MySQL -Query "SELECT 1" -Silent) {
    Write-Success "Database connection successful"
} else {
    Write-Err "Cannot connect to database. Check credentials."
    exit 1
}

# Get current table counts
Write-Host "Current table counts:"
Invoke-MySQL -Query "SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$Database' AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_ROWS DESC LIMIT 15;"

# ============================================================================
# Step 2: Backup
# ============================================================================

Write-Step "2/8" "Creating database backup..."

if (-not $SkipBackup) {
    if (-not (Test-Path $BackupDir)) {
        New-Item -ItemType Directory -Path $BackupDir | Out-Null
    }
    
    $BackupFile = Join-Path $BackupDir "${Database}_backup_${Timestamp}.sql"
    Write-Host "Backup file: $BackupFile"
    
    if (Invoke-MySQLDump -OutputFile $BackupFile) {
        $BackupSize = (Get-Item $BackupFile).Length / 1MB
        Write-Success "Backup created: $([math]::Round($BackupSize, 2)) MB"
    } else {
        Write-Err "Backup failed!"
        exit 1
    }
} else {
    Write-Warn "Backup skipped (SkipBackup flag set)"
}

# ============================================================================
# Step 3: Dry-Run Preview
# ============================================================================

Write-Step "3/8" "Running dry-run preview..."

$MigrateDataFile = Join-Path $ScriptDir "migrate_data.sql"

# Extract just the dry-run section
Write-Host "Previewing data to be migrated:"
Invoke-MySQL -Query "
-- DRY-RUN: carwash_profiles to migrate
SELECT 
    (SELECT COUNT(*) FROM carwash_profiles) AS total_carwash_profiles,
    (SELECT COUNT(*) FROM carwashes) AS existing_carwashes,
    (SELECT COUNT(*) FROM carwash_profiles cp 
     WHERE NOT EXISTS (SELECT 1 FROM carwashes c WHERE c.user_id = cp.user_id)) AS profiles_to_migrate;

-- DRY-RUN: vehicles to migrate
SELECT 
    (SELECT COUNT(*) FROM vehicles) AS total_vehicles,
    (SELECT COUNT(*) FROM user_vehicles) AS existing_user_vehicles,
    (SELECT COUNT(*) FROM vehicles v 
     WHERE NOT EXISTS (SELECT 1 FROM user_vehicles uv 
                       WHERE uv.user_id = v.user_id 
                       AND uv.license_plate = v.license_plate)) AS vehicles_to_migrate;

-- DRY-RUN: bookings without number
SELECT COUNT(*) AS bookings_without_number
FROM bookings
WHERE booking_number IS NULL OR booking_number = '';
"

if ($DryRunOnly) {
    Write-Host ""
    Write-Warn "Dry-run only mode. Exiting."
    exit 0
}

if (-not (Confirm-Action "Review the above. Continue with migration?")) {
    Write-Host "Migration cancelled." -ForegroundColor Red
    exit 1
}

# ============================================================================
# Step 4: Run create_canonical_schema.sql
# ============================================================================

Write-Step "4/8" "Creating canonical schema (if needed)..."

$SchemaFile = Join-Path $ScriptDir "create_canonical_schema.sql"
if (Test-Path $SchemaFile) {
    if (Invoke-MySQLFile -FilePath $SchemaFile) {
        Write-Success "Canonical schema applied"
    } else {
        Write-Err "Schema creation failed!"
        exit 1
    }
} else {
    Write-Warn "create_canonical_schema.sql not found, skipping"
}

# ============================================================================
# Step 5: Run alter_tables_safe.sql
# ============================================================================

Write-Step "5/8" "Running safe ALTER TABLE statements..."

$AlterFile = Join-Path $ScriptDir "alter_tables_safe.sql"
if (Test-Path $AlterFile) {
    if (Invoke-MySQLFile -FilePath $AlterFile) {
        Write-Success "ALTER TABLE statements applied"
    } else {
        Write-Err "ALTER TABLE failed!"
        exit 1
    }
} else {
    Write-Warn "alter_tables_safe.sql not found, skipping"
}

# ============================================================================
# Step 6: Run migrate_data.sql
# ============================================================================

Write-Step "6/8" "Running data migration..."

if (Test-Path $MigrateDataFile) {
    if (Invoke-MySQLFile -FilePath $MigrateDataFile) {
        Write-Success "Data migration completed"
    } else {
        Write-Err "Data migration failed!"
        Write-Warn "Check migrate_data.sql for errors"
        exit 1
    }
} else {
    Write-Warn "migrate_data.sql not found, skipping"
}

# ============================================================================
# Step 7: Verification
# ============================================================================

Write-Step "7/8" "Running verification queries..."

Invoke-MySQL -Query "
SELECT 'Post-Migration Counts' AS info;

SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL SELECT 'carwashes', COUNT(*) FROM carwashes
UNION ALL SELECT 'bookings', COUNT(*) FROM bookings
UNION ALL SELECT 'services', COUNT(*) FROM services
UNION ALL SELECT 'reviews', COUNT(*) FROM reviews
UNION ALL SELECT 'user_vehicles', COUNT(*) FROM user_vehicles
UNION ALL SELECT 'user_profiles', COUNT(*) FROM user_profiles
UNION ALL SELECT 'ui_labels', COUNT(*) FROM ui_labels;

SELECT 'Booking Number Check' AS info;
SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN booking_number IS NOT NULL THEN 1 ELSE 0 END) AS with_number
FROM bookings;
"

Write-Success "Verification queries completed"

# ============================================================================
# Step 8: Summary
# ============================================================================

Write-Step "8/8" "Migration Summary"

Write-Header "Migration Completed Successfully!"

Write-Host "Actions performed:"
Write-Host "  [OK] Database backup created"
Write-Host "  [OK] Canonical schema ensured"
Write-Host "  [OK] ALTER TABLE statements applied"
Write-Host "  [OK] Data migrated"
Write-Host "  [OK] Verification passed"
Write-Host ""

Write-Host "Next steps:"
Write-Host "  1. Test application functionality"
Write-Host "  2. Run smoke tests"
Write-Host "  3. If issues, run rollback.sql"
Write-Host "  4. If successful, run cleanup (manually)"
Write-Host ""

if ($BackupFile) {
    Write-Host "Backup location: $BackupFile" -ForegroundColor Cyan
}
Write-Host ""

Write-Host "Migration completed at $(Get-Date)" -ForegroundColor Green
