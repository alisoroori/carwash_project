-- Idempotent fixes for dashboard diagnostic
-- Adds missing columns and tables if they do not exist.

-- Add users.name if missing
SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'name');
SET @s = IF(@c = 0,
            'ALTER TABLE `users` ADD COLUMN `name` VARCHAR(255) NULL AFTER `email`',
            'SELECT "users.name already exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add payments.total_amount if missing
SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'total_amount');
SET @s = IF(@c = 0,
            'ALTER TABLE `payments` ADD COLUMN `total_amount` DECIMAL(10,2) NULL AFTER `amount`',
            'SELECT "payments.total_amount already exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add payments.status if missing
SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'status');
SET @s = IF(@c = 0,
            'ALTER TABLE `payments` ADD COLUMN `status` VARCHAR(50) NULL AFTER `total_amount`',
            'SELECT "payments.status already exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Create booking_status table if missing
CREATE TABLE IF NOT EXISTS booking_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE,
  label VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add services.status if missing
SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND COLUMN_NAME = 'status');
SET @s = IF(@c = 0,
            'ALTER TABLE `services` ADD COLUMN `status` VARCHAR(50) NULL AFTER `price`',
            'SELECT "services.status already exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Insert a booking for user 14 if none exists (use first carwash and service as placeholders)
INSERT INTO bookings (user_id, status, carwash_id, service_id, created_at)
SELECT 14, 'pending', COALESCE((SELECT id FROM carwash_profiles LIMIT 1), NULL), COALESCE((SELECT id FROM services LIMIT 1), NULL), NOW()
WHERE NOT EXISTS (SELECT 1 FROM bookings WHERE user_id = 14 LIMIT 1);

-- Done
SELECT 'apply_schema_fixes_for_dashboard.sql executed' AS _message;
