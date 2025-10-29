-- Migration: fix dashboard schema mismatches (2025-10-28)
-- Adds missing columns/tables required by the Customer Dashboard diagnostics
-- NOTE: Run this on a development or staging database first.

-- 1) Ensure users.name exists (populate from full_name or username when possible)
-- Use guarded prepared statements so this migration works on older MySQL versions
-- Add `name` column if missing
SET @col_count = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'name'
);
SET @sql = IF(@col_count = 0, 'ALTER TABLE users ADD COLUMN `name` VARCHAR(100) NULL', 'SELECT "users.name already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- populate name from existing columns if empty
UPDATE users
SET `name` = COALESCE(full_name, username, name)
WHERE (name IS NULL OR name = '')
  AND (full_name IS NOT NULL OR username IS NOT NULL);

-- 2) Payments: add booking_id, total_amount, status (guarded)
SET @c1 = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'payments' AND column_name = 'booking_id'
);
SET @sql = IF(@c1 = 0, 'ALTER TABLE payments ADD COLUMN booking_id INT NULL', 'SELECT "payments.booking_id exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c2 = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'payments' AND column_name = 'total_amount'
);
SET @sql = IF(@c2 = 0, 'ALTER TABLE payments ADD COLUMN total_amount DECIMAL(10,2) NULL', 'SELECT "payments.total_amount exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c3 = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'payments' AND column_name = 'status'
);
SET @sql = IF(@c3 = 0, 'ALTER TABLE payments ADD COLUMN status VARCHAR(50) NULL', 'SELECT "payments.status exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) booking_status table: create if missing
CREATE TABLE IF NOT EXISTS booking_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) services.status column
-- Add services.status if missing (guarded)
SET @scol = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'services' AND column_name = 'status'
);
SET @sql = IF(@scol = 0, 'ALTER TABLE services ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT ''active''', 'SELECT "services.status exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) Insert a sample booking for testing if no booking for user 14 exists
-- (Adjust user id as needed for your environment)
INSERT INTO bookings (user_id, carwash_id, service_id, status, created_at)
SELECT 14, 1, 1, 'pending', NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM bookings WHERE user_id = 14 LIMIT 1
);

-- 6) Ensure at least one booking_status row exists
INSERT INTO booking_status (name, description)
SELECT 'pending', 'Pending confirmation'
WHERE NOT EXISTS (SELECT 1 FROM booking_status WHERE name = 'pending' LIMIT 1);

-- End of migration
