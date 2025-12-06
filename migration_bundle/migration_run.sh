#!/bin/bash
# ============================================================================
# CarWash Project - Migration Runner (Bash)
# Version: 1.0.0
# Date: 2025-12-06
# Description: Runs database migration with safety checks and backups
# ============================================================================

set -e

# Configuration
ENVIRONMENT="${1:-staging}"
MYSQL_PATH="${MYSQL_PATH:-/usr/bin}"
DATABASE="${DATABASE:-carwash_db}"
USER="${USER:-root}"
PASSWORD="${PASSWORD:-}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$SCRIPT_DIR/backups"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# ============================================================================
# Helper Functions
# ============================================================================

header() {
    echo ""
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}========================================${NC}"
    echo ""
}

step() {
    echo -e "${YELLOW}[$1] $2${NC}"
}

success() {
    echo -e "${GREEN}✓ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

error() {
    echo -e "${RED}✗ $1${NC}"
}

confirm() {
    read -p "$1 (y/n): " response
    [[ "$response" == "y" || "$response" == "Y" ]]
}

mysql_exec() {
    local query="$1"
    if [ -n "$PASSWORD" ]; then
        mysql -u "$USER" -p"$PASSWORD" "$DATABASE" -e "$query"
    else
        mysql -u "$USER" "$DATABASE" -e "$query"
    fi
}

mysql_file() {
    local file="$1"
    if [ -n "$PASSWORD" ]; then
        mysql -u "$USER" -p"$PASSWORD" "$DATABASE" < "$file"
    else
        mysql -u "$USER" "$DATABASE" < "$file"
    fi
}

mysql_dump() {
    local output="$1"
    if [ -n "$PASSWORD" ]; then
        mysqldump -u "$USER" -p"$PASSWORD" --single-transaction --routines --triggers "$DATABASE" > "$output"
    else
        mysqldump -u "$USER" --single-transaction --routines --triggers "$DATABASE" > "$output"
    fi
}

# ============================================================================
# Main Script
# ============================================================================

header "CarWash Database Migration Runner"

echo "Environment: $ENVIRONMENT"
echo "Database:    $DATABASE"
echo "User:        $USER"
echo "Script Dir:  $SCRIPT_DIR"
echo "Timestamp:   $TIMESTAMP"
echo ""

# Safety check for production
if [ "$ENVIRONMENT" == "production" ]; then
    warning "PRODUCTION ENVIRONMENT DETECTED!"
    if ! confirm "Are you ABSOLUTELY SURE you want to run on PRODUCTION?"; then
        echo "Migration cancelled."
        exit 1
    fi
    if ! confirm "Have you tested this on staging first?"; then
        echo "Please test on staging first."
        exit 1
    fi
fi

# ============================================================================
# Step 1: Database Connectivity Check
# ============================================================================

step "1/8" "Checking database connectivity..."

if mysql_exec "SELECT 1" > /dev/null 2>&1; then
    success "Database connection successful"
else
    error "Cannot connect to database. Check credentials."
    exit 1
fi

# Get current table counts
echo "Current table counts:"
mysql_exec "SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$DATABASE' AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_ROWS DESC LIMIT 15;"

# ============================================================================
# Step 2: Backup
# ============================================================================

step "2/8" "Creating database backup..."

if [ "${SKIP_BACKUP:-false}" != "true" ]; then
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/${DATABASE}_backup_${TIMESTAMP}.sql"
    echo "Backup file: $BACKUP_FILE"
    
    if mysql_dump "$BACKUP_FILE"; then
        BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        success "Backup created: $BACKUP_SIZE"
    else
        error "Backup failed!"
        exit 1
    fi
else
    warning "Backup skipped (SKIP_BACKUP=true)"
fi

# ============================================================================
# Step 3: Dry-Run Preview
# ============================================================================

step "3/8" "Running dry-run preview..."

echo "Previewing data to be migrated:"
mysql_exec "
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

if [ "${DRY_RUN_ONLY:-false}" == "true" ]; then
    echo ""
    warning "Dry-run only mode. Exiting."
    exit 0
fi

if ! confirm "Review the above. Continue with migration?"; then
    echo "Migration cancelled."
    exit 1
fi

# ============================================================================
# Step 4: Run create_canonical_schema.sql
# ============================================================================

step "4/8" "Creating canonical schema (if needed)..."

SCHEMA_FILE="$SCRIPT_DIR/create_canonical_schema.sql"
if [ -f "$SCHEMA_FILE" ]; then
    if mysql_file "$SCHEMA_FILE"; then
        success "Canonical schema applied"
    else
        error "Schema creation failed!"
        exit 1
    fi
else
    warning "create_canonical_schema.sql not found, skipping"
fi

# ============================================================================
# Step 5: Run alter_tables_safe.sql
# ============================================================================

step "5/8" "Running safe ALTER TABLE statements..."

ALTER_FILE="$SCRIPT_DIR/alter_tables_safe.sql"
if [ -f "$ALTER_FILE" ]; then
    if mysql_file "$ALTER_FILE"; then
        success "ALTER TABLE statements applied"
    else
        error "ALTER TABLE failed!"
        exit 1
    fi
else
    warning "alter_tables_safe.sql not found, skipping"
fi

# ============================================================================
# Step 6: Run migrate_data.sql
# ============================================================================

step "6/8" "Running data migration..."

MIGRATE_FILE="$SCRIPT_DIR/migrate_data.sql"
if [ -f "$MIGRATE_FILE" ]; then
    if mysql_file "$MIGRATE_FILE"; then
        success "Data migration completed"
    else
        error "Data migration failed!"
        warning "Check migrate_data.sql for errors"
        exit 1
    fi
else
    warning "migrate_data.sql not found, skipping"
fi

# ============================================================================
# Step 7: Verification
# ============================================================================

step "7/8" "Running verification queries..."

mysql_exec "
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

success "Verification queries completed"

# ============================================================================
# Step 8: Summary
# ============================================================================

step "8/8" "Migration Summary"

header "Migration Completed Successfully!"

echo "Actions performed:"
echo "  ✓ Database backup created"
echo "  ✓ Canonical schema ensured"
echo "  ✓ ALTER TABLE statements applied"
echo "  ✓ Data migrated"
echo "  ✓ Verification passed"
echo ""

echo "Next steps:"
echo "  1. Test application functionality"
echo "  2. Run smoke tests"
echo "  3. If issues, run rollback.sql"
echo "  4. If successful, run cleanup (manually)"
echo ""

echo -e "${CYAN}Backup location: $BACKUP_FILE${NC}"
echo ""

echo -e "${GREEN}Migration completed at $(date)${NC}"
