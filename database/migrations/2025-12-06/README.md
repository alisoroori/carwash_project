# Database Migration - December 6, 2025

## Overview
Complete database remediation and migration for the CarWash Admin Panel project.

## Fresh Installation vs Migration

### Option A: Fresh Installation (New Database)
If starting with an empty database, use the canonical schema:
```powershell
mysql -u root -p carwash < ../create_tables.sql
```
This creates all 15 tables with proper structure, foreign keys, and seed data.

### Option B: Migration (Existing Database)
If migrating from legacy schema with `carwash_profiles`, follow the phased approach below.

---

## Migration Phases

### Phase 1: Schema Preparation (Non-destructive)
- Creates `ui_labels` table for internationalization
- Adds missing columns to existing tables
- Creates indexes for performance

### Phase 2: Data Migration
- Migrates data from `carwash_profiles` → `carwashes`
- Populates `ui_labels` with discovered field labels

### Phase 3: Verification
- Runs integrity checks
- Verifies foreign key relationships
- Tests application functionality

### Phase 4: Cleanup (Post-verification, optional)
- Archives deprecated tables
- Removes redundant columns

## Execution Order

```bash
# From database/migrations/2025-12-06 directory:

# Phase 1 - Schema Preparation
mysql -u root -p carwash < 01_phase1_schema_preparation.sql

# Phase 2 - Data Migration
mysql -u root -p carwash < 02_phase2_data_migration.sql

# Phase 3 - Verification (run checks)
mysql -u root -p carwash < 03_phase3_verification.sql

# Phase 4 - Cleanup (ONLY after verification passes)
mysql -u root -p carwash < 04_phase4_cleanup.sql
```

## Rollback

In case of issues:
```bash
# Rollback in reverse order
mysql -u root -p carwash < rollback/04_rollback_cleanup.sql
mysql -u root -p carwash < rollback/03_rollback_verification.sql
mysql -u root -p carwash < rollback/02_rollback_data_migration.sql
mysql -u root -p carwash < rollback/01_rollback_schema_preparation.sql
```

## Files

| File | Description | Rollback |
|------|-------------|----------|
| `01_phase1_schema_preparation.sql` | Schema changes, new tables, indexes | `rollback/01_rollback_schema_preparation.sql` |
| `02_phase2_data_migration.sql` | Data migration from legacy tables | `rollback/02_rollback_data_migration.sql` |
| `03_phase3_verification.sql` | Integrity checks and validation | `rollback/03_rollback_verification.sql` |
| `04_phase4_cleanup.sql` | Archive deprecated tables | `rollback/04_rollback_cleanup.sql` |

## Important Notes

1. **BACKUP FIRST**: Always create a full database backup before running migrations
2. **Test Environment**: Run in staging/development first
3. **Transaction Safety**: Each phase is wrapped in transactions where possible
4. **Idempotent**: Scripts use `IF NOT EXISTS` and `IF EXISTS` for safe re-runs
