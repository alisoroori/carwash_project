# Migration Analysis & Open Questions Resolution

## Database Schema Analysis Summary

Based on my comprehensive analysis of the codebase, here are the findings for each open question:

---

## 1. ✅ `carwash_profiles` Deprecation Confirmed

**Status:** CONFIRMED - Safe to migrate

**Evidence:**
- Primary table in `database/carwash.sql` is `carwash_profiles` (legacy schema)
- Canonical table `carwashes` exists in multiple migration scripts
- PHP files use BOTH tables:
  - Legacy files still reference `carwash_profiles` (debug scripts, old tests)
  - Newer API files reference `carwashes` table

**Migration Strategy:**
1. Phase 2 migrates data from `carwash_profiles` → `carwashes`
2. Phase 4 archives `carwash_profiles` (renamed to `carwash_profiles_archived_YYYYMMDD`)
3. After 30-day validation period, archived table can be dropped

**Code Files to Update Post-Migration:**
```
backend/debug_services.php
backend/debug_carwash.php
backend/debug_carwash2.php
tests/test_customer_dashboard.php
check_carwash_services.php
check_existing_data.php
create_test_completed_booking.php
```

---

## 2. ✅ Staff/Employee Table Analysis

**Status:** NOT NEEDED (currently)

**Evidence:**
- No staff registration forms found in codebase
- Staff members are managed via `users` table with `role='carwash'`
- The carwash owner is the primary staff member

**Current Implementation:**
```sql
-- Staff are users with carwash role
users.role = 'carwash'  -- Car wash business owner/staff
users.role = 'customer' -- Regular customers
users.role = 'admin'    -- System administrators
```

**Future Consideration:**
If multi-staff per carwash is needed, create:
```sql
CREATE TABLE staff_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,           -- FK to users
    carwash_id INT NOT NULL,        -- FK to carwashes  
    role ENUM('owner', 'manager', 'employee') DEFAULT 'employee',
    permissions JSON DEFAULT NULL,
    hired_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    UNIQUE KEY (user_id, carwash_id)
);
```

---

## 3. ✅ Inventory System

**Status:** NOT IMPLEMENTED

**Evidence:**
- No inventory forms discovered in codebase
- No `inventory_items` or similar tables in schema files

**Future Schema (if needed):**
```sql
CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carwash_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category ENUM('chemical', 'equipment', 'consumable', 'other'),
    unit VARCHAR(20) DEFAULT 'piece',
    quantity_in_stock DECIMAL(10,2) DEFAULT 0,
    reorder_level DECIMAL(10,2) DEFAULT 10,
    cost_per_unit DECIMAL(10,2),
    supplier VARCHAR(100),
    last_restocked_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 4. ✅ Payment Gateway Integration

**Status:** PARTIALLY IMPLEMENTED (Iyzico)

**Evidence from `database/payment_tables.sql`:**
```sql
-- Iyzico integration configured
IYZICO_API_KEY, IYZICO_SECRET_KEY constants defined
payment_attempts table exists with response_data JSON
```

**Current Tables:**
- `payments` - Core payment records
- `payment_attempts` - Gateway interaction log
- `transactions` - Transaction tracking

**Added Columns in Migration (Phase 1):**
- `gateway` - Payment gateway name
- `gateway_reference` - Gateway transaction ID
- `currency` - Currency code (default 'TRY')
- `settlement_status` - Settlement tracking
- `refund_amount`, `refund_date` - Refund tracking

---

## 5. ✅ Time Slots Table

**Status:** EXISTS in schema

**Evidence from `database/carwash.sql`:**
```sql
CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carwash_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=Sunday, 6=Saturday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  ...
)
```

**Note:** Foreign key references `carwash_profiles` - will need update after migration:
```sql
CONSTRAINT `time_slots_ibfk_1` FOREIGN KEY (`carwash_id`) REFERENCES `carwash_profiles` (`id`)
-- Should be changed to reference `carwashes` table
```

---

## Migration Files Created

| File | Purpose |
|------|---------|
| `01_phase1_schema_preparation.sql` | Add missing columns, create tables, indexes |
| `02_phase2_data_migration.sql` | Migrate legacy data, populate UI labels |
| `03_phase3_verification.sql` | Integrity checks, FK validation |
| `04_phase4_cleanup.sql` | Archive legacy tables, optimize |
| `rollback/*.sql` | Rollback scripts for each phase |

---

## Execution Instructions

### Pre-Migration Backup
```powershell
# Create full backup before migration
mysqldump -u root -p carwash > carwash_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql
```

### Run Migration
```powershell
cd c:\xampp\htdocs\carwash_project\database\migrations\2025-12-06

# Phase 1 - Schema
mysql -u root -p carwash < 01_phase1_schema_preparation.sql

# Phase 2 - Data  
mysql -u root -p carwash < 02_phase2_data_migration.sql

# Phase 3 - Verify (review output carefully)
mysql -u root -p carwash < 03_phase3_verification.sql

# Phase 4 - Cleanup (only after Phase 3 passes)
mysql -u root -p carwash < 04_phase4_cleanup.sql
```

### Post-Migration Tasks
1. Update PHP files still referencing `carwash_profiles`
2. Test admin panel functionality
3. Verify booking creation works
4. Check review submission
5. Validate payment flow

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Data loss during migration | Low | High | Backup before each phase; rollback scripts |
| FK constraint violations | Medium | Medium | Phase 3 verification catches these |
| Application errors post-migration | Medium | Medium | Phased rollout; keep legacy tables archived |
| Performance degradation | Low | Low | OPTIMIZE and ANALYZE in Phase 4 |
