-- Add booking_status.name if missing
SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS 
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking_status' AND COLUMN_NAME = 'name');
SET @s = IF(@c = 0,
            'ALTER TABLE `booking_status` ADD COLUMN `name` VARCHAR(100) NULL AFTER `label`',
            'SELECT "booking_status.name already exists"');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'add_booking_status_name.sql executed' AS _message;
