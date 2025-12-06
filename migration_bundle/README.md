# CarWash Database Migration Bundle

## Overview

This bundle contains all files needed to consolidate and standardize the CarWash project database into a single, clean, canonical schema.

## Quick Start

### Prerequisites
- MySQL/MariaDB 5.7+ or 10.3+
- MySQL command-line tools (mysql, mysqldump)
- Read/write access to the database

### Running on Staging (Recommended First)

**Windows (PowerShell):**
```powershell
cd migration_bundle
.\migration_run.ps1 -Environment staging
```

**Linux/Mac (Bash):**
```bash
cd migration_bundle
chmod +x migration_run.sh
./migration_run.sh staging
```

### Manual Step-by-Step Execution

1. **Backup the database:**
   ```powershell
   c:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --routines --triggers carwash_db > backup_20251206.sql
   ```

2. **Run dry-run preview:**
   ```powershell
   c:\xampp\mysql\bin\mysql.exe -u root carwash_db -e "SOURCE migrate_data.sql" 2>&1 | Select-String "DRY-RUN"
   ```

3. **Apply canonical schema:**
   ```powershell
   c:\xampp\mysql\bin\mysql.exe -u root carwash_db -e "SOURCE create_canonical_schema.sql"
   ```

4. **Apply ALTER TABLE statements:**
   ```powershell
   c:\xampp\mysql\bin\mysql.exe -u root carwash_db -e "SOURCE alter_tables_safe.sql"
   ```

5. **Run data migration:**
   ```powershell
   c:\xampp\mysql\bin\mysql.exe -u root carwash_db -e "SOURCE migrate_data.sql"
   ```

6. **Verify results:**
   ```sql
   SELECT 'users' AS tbl, COUNT(*) AS cnt FROM users
   UNION ALL SELECT 'carwashes', COUNT(*) FROM carwashes
   UNION ALL SELECT 'bookings', COUNT(*) FROM bookings;
   ```

## File Descriptions

| File | Purpose | Danger Level |
|------|---------|--------------|
| `db_migration_plan.md` | Complete migration plan and schema documentation | None |
| `create_canonical_schema.sql` | CREATE TABLE IF NOT EXISTS for all canonical tables | LOW |
| `alter_tables_safe.sql` | Safe ADD COLUMN statements with existence checks | LOW |
| `migrate_data.sql` | Data migration with dry-run, transforms, verification | MEDIUM |
| `rollback.sql` | Rollback scripts (all destructive ops commented) | HIGH |
| `mapping_table.csv` | Column mapping from old to new schema | None |
| `migration_run.ps1` | PowerShell runner with backup & confirmations | MEDIUM |
| `migration_run.sh` | Bash runner with backup & confirmations | MEDIUM |
| `pr_template.md` | Pull request template and checklist | None |

## Migration Order

Execute in this order:
1. `create_canonical_schema.sql` - Creates missing tables
2. `alter_tables_safe.sql` - Adds missing columns
3. `migrate_data.sql` - Migrates data from legacy tables
4. (Manual) Test application
5. (Manual) Run cleanup if successful

## Verification Checklist

After migration, verify:

- [ ] All table row counts match or increased
- [ ] Booking numbers are generated (format: BK2025XXXXXX)
- [ ] Carwash ratings are calculated from reviews
- [ ] UI labels are populated (Turkish + English)
- [ ] Application login works
- [ ] Booking creation works
- [ ] Review submission works

## Rollback Procedure

If issues occur:

1. **Restore from backup:**
   ```powershell
   c:\xampp\mysql\bin\mysql.exe -u root carwash_db < backup_20251206.sql
   ```

2. **Or run specific rollback sections:**
   - Open `rollback.sql`
   - Uncomment the specific rollback section needed
   - Execute the script

## Production Deployment

**DO NOT run directly on production. Follow this process:**

1. Test on staging environment
2. Get approval from team lead
3. Schedule maintenance window
4. Take full backup
5. Run migration
6. Verify all functionality
7. Monitor for 24 hours

## Support

For issues:
- Check `db_migration_plan.md` for schema details
- Review `mapping_table.csv` for column mappings
- Check application logs for errors

## Changelog

- **2025-12-06**: Initial migration bundle created
  - Consolidated carwash_profiles → carwashes
  - Consolidated vehicles → user_vehicles
  - Added booking_number generation
  - Added ui_labels population
  - Added rating statistics calculation
