# Pull Request: Database Migration Bundle

## Description

This PR introduces the database migration bundle for consolidating and standardizing the CarWash project database schema.

**Related Issue:** <!-- Link to issue if applicable -->

## Type of Change

- [ ] Bug fix (non-breaking change that fixes an issue)
- [x] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [x] Database schema change
- [x] Documentation update

## Changes Made

### Migration Files Added

| File | Description |
|------|-------------|
| `db_migration_plan.md` | Complete migration planning document |
| `create_canonical_schema.sql` | Canonical table creation (IF NOT EXISTS) |
| `alter_tables_safe.sql` | Safe column additions with guards |
| `migrate_data.sql` | Data migration with dry-run support |
| `rollback.sql` | Rollback scripts (destructive ops commented) |
| `mapping_table.csv` | Column mapping reference |
| `migration_run.ps1` | PowerShell execution script |
| `migration_run.sh` | Bash execution script |
| `README.md` | Bundle documentation |

### Database Changes

#### Tables Modified
- `users` - Added: email_verified_at, last_login_at, is_active, remember_token, updated_at
- `carwashes` - Added: average_rating, total_reviews, calculated statistics
- `bookings` - Added: booking_number (auto-generated)
- `user_vehicles` - Consolidated from vehicles table

#### Data Migrations
- Carwash profiles merged into carwashes table
- Vehicle records consolidated into user_vehicles
- Booking numbers generated (format: BK2025XXXXXX)
- UI labels populated for Turkish and English

## Testing Checklist

### Pre-Merge Testing (Required)

- [ ] Ran migration on empty database - success
- [ ] Ran migration on staging database - success
- [ ] Verified all dry-run outputs match expectations
- [ ] Verified row counts before and after migration
- [ ] Tested rollback procedure

### Application Testing (Required)

- [ ] User login works
- [ ] Admin login works (kral@gmail.com)
- [ ] Booking creation works
- [ ] Booking list displays correctly
- [ ] Carwash profile displays correctly
- [ ] Review submission works
- [ ] Vehicle management works

### Edge Cases (Required)

- [ ] User with no vehicles can still access dashboard
- [ ] Carwash with no reviews shows 0 rating
- [ ] Booking without booking_number gets one assigned
- [ ] Duplicate migration run is idempotent (no errors)

## Deployment Notes

### Staging Deployment Steps

1. Take backup: `mysqldump -u root carwash_db > backup.sql`
2. Run: `.\migration_run.ps1 -Environment staging`
3. Verify application functionality
4. Run test suite if available

### Production Deployment Steps

1. Schedule maintenance window (30 minutes recommended)
2. Notify stakeholders
3. Take full backup with routines and triggers
4. Run migration during off-peak hours
5. Verify all functionality
6. Monitor logs for 24 hours

### Rollback Plan

1. Restore from backup: `mysql -u root carwash_db < backup.sql`
2. Or use `rollback.sql` for specific changes

## Breaking Changes

**None** - All changes are additive and backward compatible.

Deprecated tables (not removed):
- `carwash_profiles` - Now redundant (data in carwashes)
- `vehicles` - Now redundant (data in user_vehicles)
- `customer_profiles` - Now redundant (data in users)
- `user_profiles` - Now redundant (data in users)

## Documentation

- [x] README.md updated
- [x] Migration plan documented
- [x] Column mappings documented
- [x] Rollback procedures documented

## Reviewer Checklist

### Code Review

- [ ] SQL syntax is correct for MySQL/MariaDB
- [ ] All ALTER TABLE statements have IF NOT EXISTS guards
- [ ] No destructive operations in main migration scripts
- [ ] Rollback operations are all commented out by default
- [ ] Data transformations preserve data integrity

### Security Review

- [ ] No sensitive data in migration scripts
- [ ] Password hashing preserved
- [ ] CSRF tokens not exposed
- [ ] API keys not in scripts

### Performance Review

- [ ] Large table operations have appropriate batch sizes
- [ ] Indexes added where needed
- [ ] No full table scans in transforms

---

## Screenshots (if applicable)

<!-- Add before/after screenshots if UI changes -->

## Additional Notes

<!-- Any additional context for reviewers -->

---

**Tested by:** _________________  
**Reviewed by:** _________________  
**Approved for production by:** _________________  
**Deployed on:** _________________
