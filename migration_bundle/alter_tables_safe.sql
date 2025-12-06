-- ============================================================================
-- CarWash Project - Safe ALTER TABLE Statements
-- Version: 1.0.0
-- Date: 2025-12-06
-- Description: Adds missing columns to existing tables (non-destructive)
-- Danger Level: LOW - Only adds columns if they don't exist
-- Expected Runtime: < 5 seconds (no data touched)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- HELPER EXPLANATION:
-- MySQL doesn't support "ADD COLUMN IF NOT EXISTS" directly.
-- We use information_schema checks + prepared statements to achieve this.
-- Pattern:
--   1. Check if column exists in information_schema.COLUMNS
--   2. If missing (count = 0), execute ALTER TABLE ADD COLUMN
--   3. If exists, print info message
-- ============================================================================

-- ============================================================================
-- 1. USERS TABLE - Add missing columns
-- ============================================================================

-- Add profile_image_path if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_image_path');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `users` ADD COLUMN `profile_image_path` VARCHAR(255) NULL AFTER `profile_image`',
    'SELECT "users.profile_image_path already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add email_verified_at if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `users` ADD COLUMN `email_verified_at` TIMESTAMP NULL AFTER `email_verified`',
    'SELECT "users.email_verified_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add last_login_at if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login_at');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `users` ADD COLUMN `last_login_at` DATETIME NULL AFTER `last_login`',
    'SELECT "users.last_login_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add address if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'address');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `users` ADD COLUMN `address` VARCHAR(255) NULL',
    'SELECT "users.address already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 2. CARWASHES TABLE - Add missing columns
-- ============================================================================

-- Add user_id if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `user_id` INT(11) NULL AFTER `id`',
    'SELECT "carwashes.user_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

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

-- Add mobile_phone if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'mobile_phone');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `mobile_phone` VARCHAR(50) NULL AFTER `phone`',
    'SELECT "carwashes.mobile_phone already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add tc_kimlik if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'tc_kimlik');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `tc_kimlik` VARCHAR(11) NULL',
    'SELECT "carwashes.tc_kimlik already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add logo_path if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'logo_path');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `logo_path` VARCHAR(255) NULL',
    'SELECT "carwashes.logo_path already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add profile_image_path if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'profile_image_path');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `profile_image_path` VARCHAR(255) NULL AFTER `logo_path`',
    'SELECT "carwashes.profile_image_path already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add certificate_path if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'certificate_path');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `certificate_path` VARCHAR(255) NULL',
    'SELECT "carwashes.certificate_path already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add working_hours if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'working_hours');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `working_hours` LONGTEXT NULL',
    'SELECT "carwashes.working_hours already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add social_media if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'social_media');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `social_media` LONGTEXT NULL',
    'SELECT "carwashes.social_media already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add exterior_price if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'exterior_price');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `exterior_price` DECIMAL(10,2) DEFAULT 0.00',
    'SELECT "carwashes.exterior_price already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add interior_price if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'interior_price');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `interior_price` DECIMAL(10,2) DEFAULT 0.00',
    'SELECT "carwashes.interior_price already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add detailing_price if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'detailing_price');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `detailing_price` DECIMAL(10,2) DEFAULT 0.00',
    'SELECT "carwashes.detailing_price already exists" AS info');
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

-- Add status if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'status');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `carwashes` ADD COLUMN `status` VARCHAR(20) DEFAULT ''pending''',
    'SELECT "carwashes.status already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 3. BOOKINGS TABLE - Add missing columns
-- ============================================================================

-- Add booking_number if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'booking_number');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `booking_number` VARCHAR(20) NULL AFTER `id`',
    'SELECT "bookings.booking_number already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add vehicle_id if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'vehicle_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `vehicle_id` INT(11) NULL AFTER `service_id`',
    'SELECT "bookings.vehicle_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add customer_name if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'customer_name');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `customer_name` VARCHAR(100) NULL AFTER `vehicle_color`',
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
    'ALTER TABLE `bookings` ADD COLUMN `payment_method` ENUM(''cash'',''card'',''online'') NULL AFTER `payment_status`',
    'SELECT "bookings.payment_method already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add cancellation_reason if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'cancellation_reason');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `cancellation_reason` TEXT NULL',
    'SELECT "bookings.cancellation_reason already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add completed_at if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'completed_at');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `bookings` ADD COLUMN `completed_at` TIMESTAMP NULL',
    'SELECT "bookings.completed_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 4. SERVICES TABLE - Add missing columns
-- ============================================================================

-- Add category_id if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND COLUMN_NAME = 'category_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `services` ADD COLUMN `category_id` INT(11) NULL AFTER `carwash_id`',
    'SELECT "services.category_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add category if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND COLUMN_NAME = 'category');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `services` ADD COLUMN `category` ENUM(''basic'',''standard'',''premium'',''deluxe'') DEFAULT ''basic'' AFTER `duration`',
    'SELECT "services.category already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add sort_order if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND COLUMN_NAME = 'sort_order');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `services` ADD COLUMN `sort_order` INT(11) DEFAULT 0',
    'SELECT "services.sort_order already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add features if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND COLUMN_NAME = 'features');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `services` ADD COLUMN `features` LONGTEXT NULL',
    'SELECT "services.features already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 5. PAYMENTS TABLE - Add missing columns
-- ============================================================================

-- Add total_amount if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'total_amount');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `payments` ADD COLUMN `total_amount` DECIMAL(10,2) NULL AFTER `amount`',
    'SELECT "payments.total_amount already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add receipt_url if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'receipt_url');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `payments` ADD COLUMN `receipt_url` VARCHAR(255) NULL',
    'SELECT "payments.receipt_url already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 6. REVIEWS TABLE - Add missing columns
-- ============================================================================

-- Add title if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'title');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `reviews` ADD COLUMN `title` VARCHAR(100) NULL AFTER `rating`',
    'SELECT "reviews.title already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add response if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'response');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `reviews` ADD COLUMN `response` TEXT NULL AFTER `comment`',
    'SELECT "reviews.response already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add responded_at if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'responded_at');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `reviews` ADD COLUMN `responded_at` TIMESTAMP NULL AFTER `response`',
    'SELECT "reviews.responded_at already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 7. USER_VEHICLES TABLE - Add missing columns
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

-- Add notes if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_vehicles' AND COLUMN_NAME = 'notes');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `user_vehicles` ADD COLUMN `notes` TEXT NULL',
    'SELECT "user_vehicles.notes already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 8. USER_PROFILES TABLE - Add missing columns
-- ============================================================================

-- Add phone if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_profiles' AND COLUMN_NAME = 'phone');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `user_profiles` ADD COLUMN `phone` VARCHAR(20) NULL',
    'SELECT "user_profiles.phone already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add home_phone if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_profiles' AND COLUMN_NAME = 'home_phone');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `user_profiles` ADD COLUMN `home_phone` VARCHAR(20) NULL',
    'SELECT "user_profiles.home_phone already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add national_id if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_profiles' AND COLUMN_NAME = 'national_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `user_profiles` ADD COLUMN `national_id` VARCHAR(20) NULL',
    'SELECT "user_profiles.national_id already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add driver_license if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_profiles' AND COLUMN_NAME = 'driver_license');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `user_profiles` ADD COLUMN `driver_license` VARCHAR(20) NULL',
    'SELECT "user_profiles.driver_license already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add profile_image if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_profiles' AND COLUMN_NAME = 'profile_image');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `user_profiles` ADD COLUMN `profile_image` VARCHAR(255) NULL',
    'SELECT "user_profiles.profile_image already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 9. ADD INDEXES (Safe - won't fail if exists)
-- ============================================================================

-- Note: MySQL will error if index already exists, but we check first

-- Index on bookings.booking_number
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'idx_bookings_booking_number');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `bookings` ADD INDEX `idx_bookings_booking_number` (`booking_number`)',
    'SELECT "idx_bookings_booking_number already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index on carwashes.status
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes' AND INDEX_NAME = 'idx_carwashes_status');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `carwashes` ADD INDEX `idx_carwashes_status` (`status`)',
    'SELECT "idx_carwashes_status already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index on users.is_active
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_is_active');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `users` ADD INDEX `idx_users_is_active` (`is_active`)',
    'SELECT "idx_users_is_active already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT '=== ALTER TABLES VERIFICATION ===' AS info;

SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('users', 'carwashes', 'bookings', 'services', 'payments', 'reviews', 'user_vehicles', 'user_profiles')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT 'alter_tables_safe.sql completed successfully' AS result;

-- ============================================================================
-- End of alter_tables_safe.sql
-- ============================================================================
