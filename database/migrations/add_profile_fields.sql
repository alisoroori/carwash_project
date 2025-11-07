-- Migration: Add new profile fields
-- Date: 2025-11-07
-- Description: Adds home_phone, national_id, driver_license columns to users table

-- Add new columns to users table
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `home_phone` VARCHAR(20) DEFAULT NULL COMMENT 'Home phone number' AFTER `phone`,
ADD COLUMN IF NOT EXISTS `national_id` VARCHAR(20) DEFAULT NULL COMMENT 'National ID number' AFTER `home_phone`,
ADD COLUMN IF NOT EXISTS `driver_license` VARCHAR(20) DEFAULT NULL COMMENT 'Driver license number (optional)' AFTER `national_id`;

-- Add indexes for better query performance
ALTER TABLE `users`
ADD INDEX IF NOT EXISTS `idx_national_id` (`national_id`),
ADD INDEX IF NOT EXISTS `idx_driver_license` (`driver_license`);

-- Ensure user_profiles table has proper timestamp columns
-- Check if last_updated exists and rename/replace with updated_at
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user_profiles' 
    AND COLUMN_NAME = 'last_updated'
);

-- If last_updated exists, drop it
SET @drop_sql = IF(@col_exists > 0, 
    'ALTER TABLE `user_profiles` DROP COLUMN `last_updated`',
    'SELECT ''Column last_updated does not exist'' AS message'
);

PREPARE stmt FROM @drop_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add updated_at if it doesn't exist
SET @updated_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user_profiles' 
    AND COLUMN_NAME = 'updated_at'
);

SET @add_updated_sql = IF(@updated_exists = 0, 
    'ALTER TABLE `user_profiles` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT ''Column updated_at already exists'' AS message'
);

PREPARE stmt FROM @add_updated_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add created_at if it doesn't exist
SET @created_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user_profiles' 
    AND COLUMN_NAME = 'created_at'
);

SET @add_created_sql = IF(@created_exists = 0, 
    'ALTER TABLE `user_profiles` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT ''Column created_at already exists'' AS message'
);

PREPARE stmt FROM @add_created_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
