Migration: 20251119_add_missing_columns.sql

Purpose:
- Adds several commonly-missing columns observed in `logs/app.log` (e.g., `district`, `postal_code`, `status`, `workplace_status`), and creates a simple `user_vehicles` table if missing.

File:
- `database/migrations/20251119_add_missing_columns.sql`

Notes:
- The migration uses `ADD COLUMN IF NOT EXISTS` which requires MySQL 8.0+.
- If your MySQL version is older, run each ALTER TABLE only after verifying the column does not exist in `information_schema.COLUMNS`.

How to run (Windows PowerShell, XAMPP):
1. Open PowerShell as Administrator and ensure MySQL/XAMPP is running.
2. From project root run:

```powershell
# Change these values if your DB user/password differ
$DB_USER = 'root'
$DB_PASS = ''
$DB_NAME = 'carwash_db'
# Run migration
mysql -u $DB_USER -p$DB_PASS $DB_NAME < database\migrations\20251119_add_missing_columns.sql
```

3. Verify columns were added:
```sql
# Connect to MySQL then run
USE carwash_db;
SHOW COLUMNS FROM carwashes LIKE 'district';
SHOW COLUMNS FROM users LIKE 'workplace_status';
```

Rollback:
- This migration is additive (adds columns / table). To undo, manually DROP the added columns and table after careful review.

If you prefer a PHP scripted migration that checks `information_schema` before altering (safer for older MySQL), ask me and I will generate it.
