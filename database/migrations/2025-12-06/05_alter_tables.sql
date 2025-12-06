-- ============================================================================
-- CarWash Project - Safe ALTER TABLE Statements
-- Version: 1.0.0
-- Date: 2025-12-05
-- Description: Adds missing columns to existing tables (non-destructive)
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- Helper: Check if column exists before adding
-- ============================================================================

-- 1. USERS TABLE - Add missing columns
-- ============================================================================

-- Add profile_image_path if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_image_path');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `users` ADD COLUMN `profile_image_path` VARCHAR(255) NULL AFTER `phone`',
    'SELECT "users.profile_image_path already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add email_verified_at if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `users` ADD COLUMN `email_verified_at` TIMESTAMP NULL AFTER `status`',
    'SELECT "users.email_verified_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add last_login_at if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login_at');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `users` ADD COLUMN `last_login_at` DATETIME NULL AFTER `last_login_attempt`',
    'SELECT "users.last_login_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. CARWASHES TABLE - Add missing columns
-- ============================================================================

-- Add owner_name if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'owner_name');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `owner_name` VARCHAR(100) NULL AFTER `email`',
    'SELECT "carwashes.owner_name already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add owner_phone if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'owner_phone');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `owner_phone` VARCHAR(20) NULL AFTER `owner_name`',
    'SELECT "carwashes.owner_phone already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add owner_birth_date if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'owner_birth_date');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `owner_birth_date` DATE NULL AFTER `owner_phone`',
    'SELECT "carwashes.owner_birth_date already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add tax_number if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'tax_number');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `tax_number` VARCHAR(50) NULL AFTER `owner_birth_date`',
    'SELECT "carwashes.tax_number already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add license_number if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'license_number');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `license_number` VARCHAR(50) NULL AFTER `tax_number`',
    'SELECT "carwashes.license_number already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add district if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'district');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `district` VARCHAR(100) NULL AFTER `city`',
    'SELECT "carwashes.district already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add profile_image_path if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'profile_image_path');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `profile_image_path` VARCHAR(255) NULL AFTER `logo_path`',
    'SELECT "carwashes.profile_image_path already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add rating_average if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'rating_average');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `rating_average` DECIMAL(3,2) DEFAULT 0.00',
    'SELECT "carwashes.rating_average already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add rating_count if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'rating_count');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `rating_count` INT(11) DEFAULT 0',
    'SELECT "carwashes.rating_count already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. BOOKINGS TABLE - Add missing columns
-- ============================================================================

-- Add booking_number if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'booking_number');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `booking_number` VARCHAR(20) NULL AFTER `id`',
    'SELECT "bookings.booking_number already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add customer_name if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'customer_name');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `customer_name` VARCHAR(100) NULL AFTER `end_time`',
    'SELECT "bookings.customer_name already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add customer_phone if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'customer_phone');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `customer_phone` VARCHAR(20) NULL AFTER `customer_name`',
    'SELECT "bookings.customer_phone already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add review_status if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'review_status');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `review_status` ENUM(''pending'', ''reviewed'') DEFAULT ''pending'' AFTER `status`',
    'SELECT "bookings.review_status already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add payment_status if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'payment_status');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `payment_status` ENUM(''pending'', ''paid'', ''refunded'', ''failed'') DEFAULT ''pending'' AFTER `total_price`',
    'SELECT "bookings.payment_status already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add payment_method if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'payment_method');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `payment_method` VARCHAR(50) NULL AFTER `payment_status`',
    'SELECT "bookings.payment_method already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add completed_at if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'completed_at');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `completed_at` TIMESTAMP NULL',
    'SELECT "bookings.completed_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. SERVICES TABLE - Add missing columns
-- ============================================================================

-- Add category if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND COLUMN_NAME = 'category');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `services` ADD COLUMN `category` VARCHAR(50) NULL AFTER `description`',
    'SELECT "services.category already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add sort_order if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND COLUMN_NAME = 'sort_order');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `services` ADD COLUMN `sort_order` INT(11) DEFAULT 0',
    'SELECT "services.sort_order already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. PAYMENTS TABLE - Add missing columns
-- ============================================================================

-- Add booking_id if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'booking_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `payments` ADD COLUMN `booking_id` INT(11) NULL AFTER `id`',
    'SELECT "payments.booking_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add total_amount if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'total_amount');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `payments` ADD COLUMN `total_amount` DECIMAL(10,2) NULL AFTER `amount`',
    'SELECT "payments.total_amount already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add status if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'status');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `payments` ADD COLUMN `status` VARCHAR(50) NULL',
    'SELECT "payments.status already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. USER_VEHICLES TABLE - Add missing columns
-- ============================================================================

-- Add vehicle_type if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_vehicles' AND COLUMN_NAME = 'vehicle_type');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `user_vehicles` ADD COLUMN `vehicle_type` ENUM(''sedan'', ''suv'', ''hatchback'', ''pickup'', ''van'', ''motorcycle'', ''other'') DEFAULT ''sedan''',
    'SELECT "user_vehicles.vehicle_type already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add is_default if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_vehicles' AND COLUMN_NAME = 'is_default');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `user_vehicles` ADD COLUMN `is_default` TINYINT(1) DEFAULT 0',
    'SELECT "user_vehicles.is_default already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- End of alter_tables.sql
-- ============================================================================

SELECT 'alter_tables.sql completed successfully' AS result;
