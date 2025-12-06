# Database Merge Report: carwash → carwash_db

## Executive Summary

This document describes the complete merge of `carwash` and `carwash_db` databases into a single unified database named `carwash_db`.

**Generated:** 2025-12-06  
**Target Database:** `carwash_db`  
**Source Database:** `carwash` (to be dropped after merge)

---

## Current State Analysis

### carwash Database (Source - Empty Schema)
| Table | Record Count |
|-------|--------------|
| users | 1 |
| bookings | 0 |
| carwashes | 0 |
| services | 0 |
| reviews | 0 |
| user_vehicles | 0 |
| payments | 0 |
| favorites | 0 |
| booking_status | 0 |
| time_slots | 0 |
| staff_members | 0 |
| ui_labels | 0 |
| audit_logs | 0 |
| security_settings | 0 |
| booking_services | 0 |
| **TOTAL** | **1** |

### carwash_db Database (Target - Production Data)
| Table | Record Count |
|-------|--------------|
| users | 9 |
| bookings | 38 |
| carwashes | 2 |
| services | 6 |
| reviews | 7 |
| user_vehicles | 7 |
| payments | 0 |
| favorites | 0 |
| booking_status | 0 |
| time_slots | 0 |
| ui_labels | 84 |
| notifications | 0 |
| promotions | 0 |
| customer_profiles | 0 |
| carwash_profiles | 0 |
| user_profiles | ? |
| vehicles | ? |
| settings | ? |
| service_categories | ? |
| **TOTAL** | **153+** |

---

## Schema Comparison Matrix

### Tables in Both Databases

| Table | carwash Columns | carwash_db Columns | Action |
|-------|-----------------|-------------------|--------|
| `users` | 17 | 21 | Merge schemas, add missing columns |
| `bookings` | 32 | 24 | Add 8 missing columns to carwash_db |
| `carwashes` | 32 | 49 | Add slug, amenities, cover_image_path |
| `services` | 11 | 15 | Add category, sort_order |
| `reviews` | 12 | 8 | Add title, response, is_verified, is_visible |
| `payments` | 17 | 10 | Add total_amount, currency, metadata |
| `favorites` | 4 | 4 | No changes needed |
| `booking_status` | 6 | 4 | Add description, color, sort_order |
| `time_slots` | 10 | 10 | Fix FK from carwash_profiles to carwashes |
| `staff_members` | 11 | 11 | No changes needed |
| `ui_labels` | 7 | 7 | No changes needed |
| `audit_logs` | 13 | 13 | No changes needed |
| `user_vehicles` | 12 | 13 | Add notes column |

### Tables Only in carwash (Need to Create)

| Table | Purpose | Action |
|-------|---------|--------|
| `security_settings` | App security config | Create in carwash_db |
| `booking_services` | Itemized billing junction | Create in carwash_db |

### Tables Only in carwash_db (Keep As-Is)

| Table | Purpose |
|-------|---------|
| `carwash_profiles` | Extended carwash profile data |
| `carwash_status_backup` | Backup table |
| `customer_profiles` | Extended customer profile data |
| `notifications` | User notifications |
| `promotions` | Promotional campaigns |
| `service_categories` | Service category reference |
| `settings` | Application settings |
| `user_profiles` | Extended user profile data |
| `vehicles` | Alternative vehicles table |
| `active_bookings_view` | View for active bookings |
| `carwash_stats_view` | View for carwash statistics |

---

## Column-Level Comparison

### users Table

| Column | carwash | carwash_db | Merge Action |
|--------|---------|------------|--------------|
| id | ✓ | ✓ | Keep |
| email | ✓ | ✓ | Keep |
| password | ✓ | ✓ | Keep |
| role | ✓ | ✓ | Keep |
| name | ✓ | ✓ | Keep |
| phone | ✓ | ✓ | Keep |
| profile_image_path | ✓ | ✓ | Keep |
| created_at | ✓ | ✓ | Keep |
| updated_at | ✓ | ✓ | Keep |
| last_login_at | ✓ | ✓ | Keep |
| email_verified_at | ✓ | ✓ | Keep |
| username | ✗ | ✓ | Keep (carwash_db) |
| full_name | ✗ | ✓ | Keep (carwash_db) |
| home_phone | ✗ | ✓ | Keep (carwash_db) |
| national_id | ✗ | ✓ | Keep (carwash_db) |
| driver_license | ✗ | ✓ | Keep (carwash_db) |
| is_active | ✗ | ✓ | Keep (carwash_db) |
| profile_image | ✗ | ✓ | Keep (carwash_db) |
| address | ✗ | ✓ | Keep (carwash_db) |
| status | ✓ | ✗ | **ADD to carwash_db** |
| login_attempts | ✓ | ✗ | **ADD to carwash_db** |
| last_login_attempt | ✓ | ✗ | **ADD to carwash_db** |
| password_reset_token | ✓ | ✗ | **ADD to carwash_db** |
| password_reset_expires | ✓ | ✗ | **ADD to carwash_db** |
| remember_token | ✓ | ✗ | **ADD to carwash_db** |

### bookings Table

| Column | carwash | carwash_db | Merge Action |
|--------|---------|------------|--------------|
| booking_number | ✓ | ✓ | Keep |
| user_id | ✓ | ✓ | Keep |
| carwash_id | ✓ | ✓ | Keep |
| service_id | ✓ | ✓ | Keep |
| vehicle_id | ✓ | ✓ | Keep |
| booking_date | ✓ | ✓ | Keep |
| booking_time | ✓ | ✓ | Keep |
| status | ✓ | ✓ | Keep |
| total_price | ✓ | ✓ | Keep |
| payment_status | ✓ | ✓ | Keep |
| payment_method | ✓ | ✓ | Keep |
| notes | ✓ | ✓ | Keep |
| vehicle_type | ✓ | ✓ | Keep |
| vehicle_plate | ✓ | ✓ | Keep |
| created_at | ✓ | ✓ | Keep |
| updated_at | ✓ | ✓ | Keep |
| time_slot_id | ✓ | ✗ | **ADD to carwash_db** |
| end_time | ✓ | ✗ | **ADD to carwash_db** |
| customer_name | ✓ | ✗ | **ADD to carwash_db** |
| customer_phone | ✓ | ✗ | **ADD to carwash_db** |
| customer_email | ✓ | ✗ | **ADD to carwash_db** |
| vehicle_model | ✓ | ✗ | **ADD to carwash_db** |
| vehicle_color | ✓ | ✗ | **ADD to carwash_db** |
| review_status | ✓ | ✗ | **ADD to carwash_db** |
| discount_amount | ✓ | ✗ | **ADD to carwash_db** |
| special_requests | ✓ | ✗ | **ADD to carwash_db** |
| cancellation_reason | ✓ | ✗ | **ADD to carwash_db** |
| cancelled_at | ✓ | ✗ | **ADD to carwash_db** |
| confirmed_at | ✓ | ✗ | **ADD to carwash_db** |
| started_at | ✓ | ✗ | **ADD to carwash_db** |
| completed_at | ✓ | ✗ | **ADD to carwash_db** |

### carwashes Table

| Column | carwash | carwash_db | Merge Action |
|--------|---------|------------|--------------|
| slug | ✓ | ✗ | **ADD to carwash_db** |
| cover_image_path | ✓ | ✗ | **ADD to carwash_db** |
| amenities | ✓ | ✗ | **ADD to carwash_db** |
| is_featured | ✓ | ✗ | **ADD to carwash_db** |
| rating_average | ✓ | rating | Align naming |
| rating_count | ✓ | total_reviews | Align naming |

---

## Merge Script Sections

The `MERGE_DATABASES.sql` script contains 22 sections:

1. **USERS TABLE ENHANCEMENTS** - Add status, login_attempts, password reset columns
2. **BOOKINGS TABLE ENHANCEMENTS** - Add 16 missing columns for complete booking lifecycle
3. **CARWASHES TABLE ENHANCEMENTS** - Add slug, cover_image_path, amenities, is_featured
4. **BOOKING_STATUS TABLE ENHANCEMENTS** - Add description, color, sort_order + populate defaults
5. **SERVICES TABLE ENHANCEMENTS** - Add category, sort_order
6. **REVIEWS TABLE ENHANCEMENTS** - Add title, response, is_verified, is_visible
7. **PAYMENTS TABLE ENHANCEMENTS** - Add total_amount, currency, payment_gateway, metadata
8. **USER_VEHICLES TABLE ENHANCEMENTS** - Add notes column
9. **TIME_SLOTS TABLE** - Fix incorrect FK from carwash_profiles to carwashes
10. **SECURITY_SETTINGS TABLE** - Create if not exists + populate defaults
11. **BOOKING_SERVICES TABLE** - Create for itemized billing
12. **AUDIT_LOGS TABLE** - Ensure comments
13. **STAFF_MEMBERS TABLE** - Ensure FK consistency
14. **FAVORITES TABLE** - Add comments
15. **UI_LABELS TABLE** - Add comments
16. **DATA MIGRATION** - Migrate user from carwash if not duplicate
17. **STANDARDIZE COLLATION** - Convert all tables to utf8mb4_unicode_ci
18. **UPDATE AUTO_INCREMENT** - Ensure safe increment values
19. **RECREATE VIEWS** - Update views with enhanced columns
20. **VERIFY MERGE RESULTS** - Output table counts
21. **FINAL CLEANUP** - Re-enable foreign keys
22. **DROP OLD DATABASE** - Commented DROP command for safety

---

## Execution Instructions

### Step 1: Backup Both Databases
```powershell
cd C:\xampp\htdocs\carwash_project\migration_bundle
C:\xampp\mysql\bin\mysqldump -u root --databases carwash carwash_db > pre_merge_backup.sql
```

### Step 2: Review the Merge Script
Open and review `MERGE_DATABASES.sql` to understand all changes.

### Step 3: Execute the Merge Script
```powershell
C:\xampp\mysql\bin\mysql -u root < MERGE_DATABASES.sql
```

### Step 4: Verify the Results
```powershell
C:\xampp\mysql\bin\mysql -u root -e "SELECT 'users' as tbl, COUNT(*) as cnt FROM carwash_db.users UNION ALL SELECT 'bookings', COUNT(*) FROM carwash_db.bookings UNION ALL SELECT 'carwashes', COUNT(*) FROM carwash_db.carwashes"
```

### Step 5: Test Application
Navigate to http://localhost/carwash_project and verify all features work.

### Step 6: Drop Old Database (After Verification)
Only after confirming everything works:
```powershell
C:\xampp\mysql\bin\mysql -u root -e "DROP DATABASE IF EXISTS carwash"
```

---

## Post-Merge Validation Checklist

- [ ] All users accessible and can login
- [ ] Bookings display correctly in admin panel
- [ ] Carwash profiles load with all fields
- [ ] Services management works
- [ ] Reviews are visible
- [ ] Payments can be processed
- [ ] Booking status workflow functions
- [ ] Time slots scheduling works
- [ ] All views return correct data
- [ ] No foreign key errors in logs

---

## Rollback Procedure

If issues occur, restore from backup:
```powershell
C:\xampp\mysql\bin\mysql -u root < pre_merge_backup.sql
```

---

## Files Generated

| File | Purpose |
|------|---------|
| `MERGE_DATABASES.sql` | Complete merge script |
| `DATABASE_MERGE_REPORT.md` | This documentation |
| `carwash_schema.sql` | Original carwash schema dump |
| `carwash_db_schema.sql` | Original carwash_db schema dump |
