# CarWash Database Migration Plan

**Version:** 1.0.0  
**Date:** 2025-12-06  
**Author:** Database Migration Agent  
**Target Database:** MySQL/MariaDB (InnoDB, utf8mb4)

---

## Executive Summary

This document outlines the comprehensive database migration plan to consolidate and standardize the CarWash project database. The migration addresses:

1. **Dual database issue**: Two databases exist (`carwash` and `carwash_db`) - consolidating to `carwash_db` as canonical
2. **Duplicate tables**: `carwash_profiles` → `carwashes`, `vehicles` + `user_vehicles` merge, `customer_profiles` + `user_profiles` merge
3. **Schema standardization**: English ASCII snake_case identifiers throughout
4. **Data integrity**: Safe, reversible migrations with verification queries

---

## A) Discovery Findings

### A.1 Databases Found

| Database | Tables | Status | Recommendation |
|----------|--------|--------|----------------|
| `carwash_db` | 26 tables | **Active** (used by application) | Keep as canonical |
| `carwash` | 15 tables | Subset schema | Archive after migration |

**Decision**: Use `carwash_db` as the canonical database. The application's `config.php` references `carwash_db`.

### A.2 Tables Inventory (carwash_db)

| Table | Rows | Status | Notes |
|-------|------|--------|-------|
| `users` | 4 | ✅ Canonical | Extended with profile fields |
| `carwashes` | 2 | ✅ Canonical | Primary carwash entity |
| `carwash_profiles` | 0 | ⚠️ Legacy | Merge into `carwashes` |
| `bookings` | 38 | ✅ Canonical | Core booking entity |
| `booking_services` | 0 | ✅ Keep | Junction table |
| `services` | 6 | ✅ Canonical | Service catalog |
| `service_categories` | 0 | ✅ Keep | Needs population |
| `payments` | 0 | ✅ Canonical | Payment records |
| `reviews` | 6 | ✅ Canonical | Customer reviews |
| `favorites` | 0 | ✅ Keep | User favorites |
| `user_vehicles` | 7 | ✅ Canonical | User's vehicles |
| `vehicles` | 0 | ⚠️ Legacy | Merge into `user_vehicles` |
| `customer_profiles` | 0 | ⚠️ Legacy | Merge into `user_profiles` |
| `user_profiles` | 1 | ✅ Canonical | Extended user data |
| `time_slots` | 0 | ✅ Keep | Scheduling |
| `staff_members` | 0 | ✅ Keep | Staff roster |
| `promotions` | 0 | ✅ Keep | Promo codes |
| `notifications` | 0 | ✅ Keep | User notifications |
| `audit_logs` | 0 | ✅ Keep | Audit trail |
| `settings` | 12 | ✅ Keep | App settings |
| `security_settings` | 5 | ✅ Keep | Security config |
| `ui_labels` | 50 | ✅ Canonical | i18n labels |
| `booking_status` | 0 | ⚠️ Review | May be redundant |
| `carwash_status_backup` | 0 | 🗑️ Archive | Backup table |

### A.3 Duplicate/Overlapping Structures

#### 1. Carwash Entities
- `carwashes` (2 rows) - Main entity with 47 columns
- `carwash_profiles` (0 rows) - Legacy entity with 26 columns
- **Resolution**: Keep `carwashes`, migrate any orphan data from `carwash_profiles`

#### 2. Vehicle Entities
- `user_vehicles` (7 rows) - 12 columns, has `vehicle_type` enum
- `vehicles` (0 rows) - 12 columns, similar structure
- **Resolution**: Keep `user_vehicles`, archive `vehicles`

#### 3. Profile Entities
- `user_profiles` (1 row) - Extended user data
- `customer_profiles` (0 rows) - Similar purpose
- **Resolution**: Keep `user_profiles`, archive `customer_profiles`

### A.4 Files Scanned

**Models:**
- `backend/models/User_Model.php`
- `backend/models/Service_Model.php`
- `backend/models/Payment_Model.php`
- `backend/models/Booking_Model.php`

**Controllers:**
- `backend/controllers/RegistrationController.php`

**API Endpoints:**
- `backend/api/carwash/*.php` - Carwash operations
- `backend/api/bookings/*.php` - Booking operations
- `backend/api/users/*.php` - User operations
- `backend/api/vehicles/*.php` - Vehicle operations

**SQL Files:**
- `database/create_tables.sql` - Canonical schema
- `database/schema.sql` - Alternative schema
- `database/carwash.sql` - Legacy schema
- `database/migrations/2025-12-06/*.sql` - Recent migrations

---

## B) Canonical Schema Design

### B.1 Entity Relationship Overview

```
users (1) ──────< user_profiles (1)
  │
  ├──────< user_vehicles (*)
  ├──────< bookings (*)
  ├──────< reviews (*)
  ├──────< favorites (*)
  └──────< notifications (*)

carwashes (1) ──< services (*)
  │              ├──< booking_services (*)
  │              └──< service_categories (*)
  ├──────< bookings (*)
  ├──────< reviews (*)
  ├──────< time_slots (*)
  ├──────< staff_members (*)
  └──────< promotions (*)

bookings (*) ──< booking_services (*)
  └──────< payments (*)
```

### B.2 Canonical Tables

#### users
| Column | Type | Null | Key | Default | Notes |
|--------|------|------|-----|---------|-------|
| id | INT(11) | NO | PK | AUTO_INCREMENT | |
| username | VARCHAR(50) | NO | UNI | | |
| email | VARCHAR(100) | NO | UNI | | |
| password | VARCHAR(255) | NO | | | bcrypt hash |
| name | VARCHAR(255) | YES | | NULL | Display name |
| full_name | VARCHAR(100) | NO | | | Legal name |
| phone | VARCHAR(20) | YES | | NULL | Mobile |
| home_phone | VARCHAR(20) | YES | | NULL | |
| national_id | VARCHAR(20) | YES | MUL | NULL | TC Kimlik |
| driver_license | VARCHAR(20) | YES | MUL | NULL | |
| role | ENUM | YES | MUL | 'customer' | admin,customer,staff,carwash |
| profile_image | VARCHAR(255) | YES | | NULL | |
| profile_image_path | VARCHAR(255) | YES | | NULL | Alternative path |
| is_active | TINYINT(1) | YES | | 1 | |
| email_verified | TINYINT(1) | YES | | 0 | |
| email_verified_at | TIMESTAMP | YES | | NULL | |
| address | VARCHAR(255) | YES | | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | ON UPDATE | |
| last_login | TIMESTAMP | YES | | NULL | |
| last_login_at | DATETIME | YES | | NULL | |

**Expected rows:** ~100-1000 (low volume)

#### carwashes
| Column | Type | Null | Key | Default | Notes |
|--------|------|------|-----|---------|-------|
| id | INT(11) | NO | PK | AUTO_INCREMENT | |
| user_id | INT(11) | YES | FK | NULL | Owner user |
| owner_id | INT(11) | YES | FK | NULL | Legacy owner ref |
| name | VARCHAR(100) | NO | | | Business name |
| description | TEXT | YES | | NULL | |
| address | VARCHAR(255) | NO | | | |
| city | VARCHAR(100) | NO | MUL | | |
| district | VARCHAR(100) | YES | | NULL | |
| state | VARCHAR(100) | YES | | NULL | |
| zip_code | VARCHAR(20) | YES | | NULL | |
| postal_code | VARCHAR(20) | YES | | NULL | |
| country | VARCHAR(100) | YES | | 'USA' | |
| latitude | DECIMAL(10,8) | YES | | NULL | |
| longitude | DECIMAL(11,8) | YES | | NULL | |
| phone | VARCHAR(20) | YES | | NULL | |
| mobile_phone | VARCHAR(50) | YES | | NULL | |
| email | VARCHAR(100) | YES | | NULL | |
| website | VARCHAR(255) | YES | | NULL | |
| owner_name | VARCHAR(100) | YES | | NULL | |
| owner_phone | VARCHAR(20) | YES | | NULL | |
| owner_birth_date | DATE | YES | | NULL | |
| birth_date | DATE | YES | | NULL | Legacy |
| tax_number | VARCHAR(50) | YES | | NULL | Vergi No |
| license_number | VARCHAR(50) | YES | | NULL | Ruhsat No |
| tc_kimlik | VARCHAR(11) | YES | | NULL | |
| opening_hours | LONGTEXT | YES | | NULL | JSON |
| working_hours | LONGTEXT | YES | | NULL | JSON |
| opening_time | TIME | YES | | NULL | |
| closing_time | TIME | YES | | NULL | |
| image | VARCHAR(255) | YES | | NULL | |
| logo_path | VARCHAR(255) | YES | | NULL | |
| logo_image | VARCHAR(255) | YES | | NULL | |
| profile_image | VARCHAR(255) | YES | | NULL | |
| profile_image_path | VARCHAR(255) | YES | | NULL | |
| certificate_path | VARCHAR(255) | YES | | NULL | |
| social_media | LONGTEXT | YES | | NULL | JSON |
| services | LONGTEXT | YES | | NULL | JSON |
| exterior_price | DECIMAL(10,2) | YES | | 0.00 | |
| interior_price | DECIMAL(10,2) | YES | | 0.00 | |
| detailing_price | DECIMAL(10,2) | YES | | 0.00 | |
| capacity | INT(11) | YES | | 0 | |
| rating | DECIMAL(3,2) | YES | MUL | 0.00 | |
| rating_average | DECIMAL(3,2) | YES | | 0.00 | |
| rating_count | INT(11) | YES | | 0 | |
| total_reviews | INT(11) | YES | | 0 | |
| is_active | TINYINT(1) | YES | MUL | 1 | |
| status | VARCHAR(20) | YES | | 'pending' | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | ON UPDATE | |

**Expected rows:** ~50-500 (medium volume)

#### bookings
| Column | Type | Null | Key | Default | Notes |
|--------|------|------|-----|---------|-------|
| id | INT(11) | NO | PK | AUTO_INCREMENT | |
| booking_number | VARCHAR(20) | YES | | NULL | BK2025XXXXXX |
| user_id | INT(11) | NO | FK/MUL | | Customer |
| carwash_id | INT(11) | NO | FK/MUL | | Service provider |
| service_id | INT(11) | NO | FK/MUL | | Primary service |
| booking_date | DATE | NO | MUL | | |
| booking_time | TIME | NO | | | |
| vehicle_type | ENUM | NO | | | sedan,suv,truck,van,motorcycle |
| vehicle_plate | VARCHAR(20) | YES | | NULL | |
| vehicle_model | VARCHAR(100) | YES | | NULL | |
| vehicle_color | VARCHAR(50) | YES | | NULL | |
| customer_name | VARCHAR(100) | YES | | NULL | |
| customer_phone | VARCHAR(20) | YES | | NULL | |
| status | ENUM | YES | MUL | 'pending' | pending,confirmed,in_progress,completed,cancelled |
| review_status | ENUM | YES | MUL | 'pending' | pending,reviewed |
| total_price | DECIMAL(10,2) | NO | | | |
| payment_status | ENUM | YES | | 'pending' | pending,paid,refunded |
| payment_method | ENUM | YES | | NULL | cash,card,online |
| notes | TEXT | YES | | NULL | |
| cancellation_reason | TEXT | YES | | NULL | |
| completed_at | TIMESTAMP | YES | | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | ON UPDATE | |

**Expected rows:** ~1000-10000 (high volume)

#### services
| Column | Type | Null | Key | Default | Notes |
|--------|------|------|-----|---------|-------|
| id | INT(11) | NO | PK | AUTO_INCREMENT | |
| carwash_id | INT(11) | NO | FK/MUL | | |
| name | VARCHAR(100) | NO | | | |
| description | TEXT | YES | | NULL | |
| price | DECIMAL(10,2) | NO | MUL | | |
| duration | INT(11) | NO | | | Minutes |
| category | ENUM | YES | MUL | 'basic' | basic,standard,premium,deluxe |
| status | VARCHAR(50) | YES | | NULL | |
| image | VARCHAR(255) | YES | | NULL | |
| features | LONGTEXT | YES | | NULL | JSON |
| is_available | TINYINT(1) | YES | | 1 | |
| sort_order | INT(11) | YES | | 0 | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | ON UPDATE | |

**Expected rows:** ~50-200 (low-medium volume)

#### payments
| Column | Type | Null | Key | Default | Notes |
|--------|------|------|-----|---------|-------|
| id | INT(11) | NO | PK | AUTO_INCREMENT | |
| booking_id | INT(11) | NO | FK/MUL | | |
| transaction_id | VARCHAR(100) | YES | MUL | NULL | External ref |
| amount | DECIMAL(10,2) | NO | | | |
| total_amount | DECIMAL(10,2) | YES | | NULL | |
| payment_method | ENUM | NO | | | credit_card,cash,online_transfer,mobile_payment |
| status | ENUM | NO | MUL | 'pending' | pending,completed,failed,refunded |
| payment_date | DATETIME | YES | | NULL | |
| notes | TEXT | YES | | NULL | |
| receipt_url | VARCHAR(255) | YES | | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | ON UPDATE | |

**Expected rows:** ~1000-10000 (high volume)

#### reviews
| Column | Type | Null | Key | Default | Notes |
|--------|------|------|-----|---------|-------|
| id | INT(11) | NO | PK | AUTO_INCREMENT | |
| user_id | INT(11) | NO | FK/MUL | | Reviewer |
| carwash_id | INT(11) | NO | FK/MUL | | |
| booking_id | INT(11) | YES | FK/MUL | NULL | Associated booking |
| rating | INT(11) | NO | MUL | | 1-5 |
| title | VARCHAR(100) | YES | | NULL | |
| comment | TEXT | YES | | NULL | |
| response | TEXT | YES | | NULL | Owner response |
| responded_at | TIMESTAMP | YES | | NULL | |
| is_verified | TINYINT(1) | YES | | 0 | |
| is_visible | TINYINT(1) | YES | | 1 | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | ON UPDATE | |

**Expected rows:** ~500-5000 (medium-high volume)

#### user_vehicles
| Column | Type | Null | Key | Default | Notes |
|--------|------|------|-----|---------|-------|
| id | INT(11) | NO | PK | AUTO_INCREMENT | |
| user_id | INT(11) | NO | FK/MUL | | Owner |
| brand | VARCHAR(100) | YES | | NULL | |
| model | VARCHAR(100) | YES | | NULL | |
| year | INT(11) | YES | | NULL | |
| color | VARCHAR(50) | YES | | NULL | |
| license_plate | VARCHAR(50) | YES | | NULL | |
| vehicle_type | ENUM | YES | | 'sedan' | sedan,suv,hatchback,pickup,van,motorcycle,other |
| image_path | VARCHAR(255) | YES | | NULL | |
| is_default | TINYINT(1) | YES | | 0 | |
| created_at | DATETIME | YES | | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | YES | | NULL | |

**Expected rows:** ~200-2000 (medium volume)

#### user_profiles
| Column | Type | Null | Key | Default | Notes |
|--------|------|------|-----|---------|-------|
| id | INT(11) | NO | PK | AUTO_INCREMENT | |
| user_id | INT(11) | NO | UNI/FK | | |
| address | TEXT | YES | | NULL | |
| city | VARCHAR(100) | YES | | NULL | |
| state | VARCHAR(100) | YES | | NULL | |
| country | VARCHAR(100) | YES | | NULL | |
| postal_code | VARCHAR(20) | YES | | NULL | |
| birth_date | DATE | YES | | NULL | |
| gender | ENUM | YES | | NULL | male,female,other |
| preferences | LONGTEXT | YES | | NULL | JSON |
| notification_settings | LONGTEXT | YES | | NULL | JSON |
| phone | VARCHAR(20) | YES | | NULL | |
| home_phone | VARCHAR(20) | YES | | NULL | |
| national_id | VARCHAR(20) | YES | | NULL | |
| driver_license | VARCHAR(20) | YES | | NULL | |
| profile_image | VARCHAR(255) | YES | | NULL | |
| last_login | DATETIME | YES | | NULL | |
| created_at | TIMESTAMP | NO | | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | NO | | ON UPDATE | |

**Expected rows:** ~100-1000 (matches users)

---

## C) Migration Phases

### Phase 1: Schema Preparation (Non-Destructive)
- Add missing columns to canonical tables
- Create indexes
- No data modification

### Phase 2: Data Migration
- Migrate `carwash_profiles` → `carwashes` (if any orphans)
- Migrate `vehicles` → `user_vehicles` (if any data)
- Migrate `customer_profiles` → `user_profiles` (if any data)
- Generate booking numbers
- Update rating statistics

### Phase 3: Verification
- Row count comparisons
- Data integrity checks
- Sample data validation

### Phase 4: Cleanup (Manual Approval Required)
- Rename legacy tables with `_backup_YYYYMMDD` suffix
- Archive duplicate databases
- Remove deprecated code references

---

## D) Open Questions & Assumptions

### Assumptions Made
| # | Assumption | Confidence | Action if Wrong |
|---|------------|------------|-----------------|
| 1 | `carwash_db` is the active database | HIGH | Update config.php |
| 2 | `carwash_profiles` has no unique data | HIGH | Run dry-run SELECT to verify |
| 3 | `vehicles` table is unused | HIGH | Check for orphan records |
| 4 | `customer_profiles` is redundant | HIGH | Merge any unique fields |
| 5 | User roles are: admin, customer, staff, carwash | HIGH | Verify ENUM values |

### Action Required
1. **Confirm canonical database**: Run `SELECT DATABASE()` from application
2. **Verify backup strategy**: Confirm `mysqldump` path and credentials
3. **Check for triggers**: Run `SHOW TRIGGERS` to preserve any existing triggers
4. **Review FK constraints**: Some may be missing - verify referential integrity

---

## E) Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Data loss | LOW | HIGH | Full backup before each step |
| FK violations | MEDIUM | MEDIUM | Dry-run orphan detection |
| Application errors | MEDIUM | HIGH | Staging environment testing |
| Performance degradation | LOW | MEDIUM | Index verification |

---

## F) Timeline Estimate

| Phase | Duration | Prerequisites |
|-------|----------|---------------|
| Backup | 5 min | Database access |
| Schema prep | 2 min | Backup complete |
| Data migration | 5 min | Schema prep |
| Verification | 10 min | Data migration |
| Cleanup | 5 min | Manual approval |
| **Total** | **~30 min** | |

---

## G) Files in This Bundle

```
migration_bundle/
├── db_migration_plan.md          # This document
├── create_canonical_schema.sql   # CREATE TABLE IF NOT EXISTS
├── alter_tables_safe.sql         # Safe ALTER statements
├── migrate_data.sql              # Data migration with dry-run
├── rollback.sql                  # Rollback scripts
├── mapping_table.csv             # Column mapping
├── migration_run.sh              # Bash wrapper script
├── migration_run.ps1             # PowerShell wrapper script
├── README.md                     # Quick start guide
├── pr_template.md                # PR description template
└── laravel_migrations/           # Laravel migration files (if applicable)
```

---

*Document generated: 2025-12-06*
