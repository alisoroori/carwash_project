-- =====================================================
-- Phase 1: Schema Preparation (Non-destructive)
-- Migration Date: 2025-12-06
-- Purpose: Add missing columns, create new tables, add indexes
-- =====================================================

-- Ensure we're using the correct database
USE `carwash`;

SET @start_time = NOW();
SELECT CONCAT('Phase 1 Started: ', @start_time) AS 'Migration Status';

-- =====================================================
-- 1.1 Create UI Labels Table for Internationalization
-- =====================================================

CREATE TABLE IF NOT EXISTS `ui_labels` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `table_name` VARCHAR(64) NOT NULL COMMENT 'Source table name',
    `column_name` VARCHAR(64) NOT NULL COMMENT 'Source column name',
    `form_name` VARCHAR(100) DEFAULT NULL COMMENT 'Form identifier (e.g., customer_registration)',
    `field_name` VARCHAR(100) NOT NULL COMMENT 'HTML field name attribute',
    `label_tr` VARCHAR(255) NOT NULL COMMENT 'Turkish label',
    `label_en` VARCHAR(255) DEFAULT NULL COMMENT 'English label',
    `label_fa` VARCHAR(255) DEFAULT NULL COMMENT 'Farsi/Persian label',
    `placeholder_tr` VARCHAR(255) DEFAULT NULL COMMENT 'Turkish placeholder',
    `placeholder_en` VARCHAR(255) DEFAULT NULL COMMENT 'English placeholder',
    `validation_rules` VARCHAR(500) DEFAULT NULL COMMENT 'JSON validation rules',
    `input_type` VARCHAR(50) DEFAULT 'text' COMMENT 'HTML input type',
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_form_field` (`form_name`, `field_name`),
    KEY `idx_table_column` (`table_name`, `column_name`),
    KEY `idx_form_name` (`form_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores UI form labels and translations for internationalization';

SELECT 'Created ui_labels table' AS 'Status';

-- =====================================================
-- 1.2 Ensure carwashes table exists with all required columns
-- =====================================================

-- Create carwashes table if it doesn't exist
CREATE TABLE IF NOT EXISTS `carwashes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL COMMENT 'FK to users table for owner account',
    `name` VARCHAR(255) NOT NULL COMMENT 'Business name',
    `owner_name` VARCHAR(150) DEFAULT NULL COMMENT 'Owner full name',
    `owner_phone` VARCHAR(20) DEFAULT NULL COMMENT 'Owner personal phone',
    `owner_birth_date` DATE DEFAULT NULL COMMENT 'Owner birth date',
    `description` TEXT DEFAULT NULL COMMENT 'Business description',
    `address` TEXT DEFAULT NULL COMMENT 'Full business address',
    `city` VARCHAR(100) DEFAULT NULL COMMENT 'City name',
    `district` VARCHAR(100) DEFAULT NULL COMMENT 'District/ilçe name',
    `postal_code` VARCHAR(20) DEFAULT NULL COMMENT 'Postal/ZIP code',
    `country` VARCHAR(100) DEFAULT 'Turkey' COMMENT 'Country name',
    `latitude` DECIMAL(10,8) DEFAULT NULL COMMENT 'GPS latitude',
    `longitude` DECIMAL(11,8) DEFAULT NULL COMMENT 'GPS longitude',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT 'Business phone',
    `mobile_phone` VARCHAR(20) DEFAULT NULL COMMENT 'Business mobile phone',
    `email` VARCHAR(150) DEFAULT NULL COMMENT 'Business email',
    `website` VARCHAR(255) DEFAULT NULL COMMENT 'Website URL',
    `tax_number` VARCHAR(50) DEFAULT NULL COMMENT 'Tax/vergi number',
    `license_number` VARCHAR(100) DEFAULT NULL COMMENT 'Business license/ruhsat number',
    `profile_image_path` VARCHAR(500) DEFAULT NULL COMMENT 'Owner profile image path',
    `logo_path` VARCHAR(500) DEFAULT NULL COMMENT 'Business logo path',
    `featured_image` VARCHAR(500) DEFAULT NULL COMMENT 'Main business image',
    `gallery_images` JSON DEFAULT NULL COMMENT 'Array of gallery image paths',
    `working_hours` JSON DEFAULT NULL COMMENT 'Working hours by day of week',
    `social_media` JSON DEFAULT NULL COMMENT 'Social media links',
    `average_rating` DECIMAL(3,2) DEFAULT NULL COMMENT 'Calculated average rating',
    `total_reviews` INT NOT NULL DEFAULT 0 COMMENT 'Total review count',
    `status` ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending' COMMENT 'Business status',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Active flag',
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Verification status',
    `verified_at` DATETIME DEFAULT NULL COMMENT 'Verification timestamp',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_city` (`city`),
    KEY `idx_district` (`district`),
    KEY `idx_city_district` (`city`, `district`),
    KEY `idx_status` (`status`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_rating` (`average_rating`),
    KEY `idx_verified` (`is_verified`),
    FULLTEXT KEY `ft_search` (`name`, `description`, `address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Primary car wash business profiles';

SELECT 'Created/verified carwashes table' AS 'Status';

-- =====================================================
-- 1.3 Add missing columns to carwashes if they don't exist
-- =====================================================

-- Helper procedure to safely add columns
DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;
DELIMITER //
CREATE PROCEDURE `add_column_if_not_exists`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    DECLARE col_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO col_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND COLUMN_NAME = p_column;
    
    IF col_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('Added column ', p_table, '.', p_column) AS 'Column Added';
    ELSE
        SELECT CONCAT('Column ', p_table, '.', p_column, ' already exists') AS 'Column Skipped';
    END IF;
END //
DELIMITER ;

-- Add missing columns to carwashes
CALL add_column_if_not_exists('carwashes', 'owner_name', "VARCHAR(150) DEFAULT NULL COMMENT 'Owner full name' AFTER `name`");
CALL add_column_if_not_exists('carwashes', 'owner_phone', "VARCHAR(20) DEFAULT NULL COMMENT 'Owner personal phone' AFTER `owner_name`");
CALL add_column_if_not_exists('carwashes', 'owner_birth_date', "DATE DEFAULT NULL COMMENT 'Owner birth date' AFTER `owner_phone`");
CALL add_column_if_not_exists('carwashes', 'tax_number', "VARCHAR(50) DEFAULT NULL COMMENT 'Tax/vergi number' AFTER `email`");
CALL add_column_if_not_exists('carwashes', 'license_number', "VARCHAR(100) DEFAULT NULL COMMENT 'Business license/ruhsat number' AFTER `tax_number`");
CALL add_column_if_not_exists('carwashes', 'profile_image_path', "VARCHAR(500) DEFAULT NULL COMMENT 'Owner profile image' AFTER `license_number`");
CALL add_column_if_not_exists('carwashes', 'is_active', "TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Active flag' AFTER `status`");
CALL add_column_if_not_exists('carwashes', 'is_verified', "TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Verification status' AFTER `is_active`");
CALL add_column_if_not_exists('carwashes', 'verified_at', "DATETIME DEFAULT NULL COMMENT 'Verification timestamp' AFTER `is_verified`");

-- =====================================================
-- 1.4 Ensure users table has all required columns
-- =====================================================

CALL add_column_if_not_exists('users', 'name', "VARCHAR(100) DEFAULT NULL COMMENT 'Display name' AFTER `id`");
CALL add_column_if_not_exists('users', 'workplace_status', "VARCHAR(16) DEFAULT 'open' COMMENT 'For carwash owners'");

-- =====================================================
-- 1.5 Ensure bookings table has all required columns
-- =====================================================

CALL add_column_if_not_exists('bookings', 'customer_name', "VARCHAR(150) DEFAULT NULL COMMENT 'Customer name for guest bookings'");
CALL add_column_if_not_exists('bookings', 'customer_phone', "VARCHAR(20) DEFAULT NULL COMMENT 'Customer phone'");
CALL add_column_if_not_exists('bookings', 'notes', "TEXT DEFAULT NULL COMMENT 'Special instructions'");
CALL add_column_if_not_exists('bookings', 'service_id', "INT(11) DEFAULT NULL COMMENT 'Primary service'");

-- =====================================================
-- 1.6 Ensure user_vehicles table exists with correct columns
-- =====================================================

CREATE TABLE IF NOT EXISTS `user_vehicles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT 'FK to users table',
    `brand` VARCHAR(100) NOT NULL COMMENT 'Vehicle make/brand',
    `model` VARCHAR(100) NOT NULL COMMENT 'Vehicle model',
    `year` YEAR DEFAULT NULL COMMENT 'Model year',
    `color` VARCHAR(50) DEFAULT NULL COMMENT 'Vehicle color',
    `license_plate` VARCHAR(20) DEFAULT NULL COMMENT 'License plate number',
    `vehicle_type` ENUM('sedan', 'suv', 'hatchback', 'truck', 'van', 'motorcycle', 'compact', 'other') DEFAULT NULL COMMENT 'Vehicle category',
    `image_path` VARCHAR(500) DEFAULT NULL COMMENT 'Vehicle photo path',
    `notes` TEXT DEFAULT NULL COMMENT 'Additional notes',
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Primary vehicle flag',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_license_plate` (`license_plate`),
    KEY `idx_brand_model` (`brand`, `model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer vehicles';

SELECT 'Created/verified user_vehicles table' AS 'Status';

-- Add missing columns to user_vehicles if table already existed
CALL add_column_if_not_exists('user_vehicles', 'brand', "VARCHAR(100) DEFAULT NULL COMMENT 'Vehicle make/brand'");
CALL add_column_if_not_exists('user_vehicles', 'model', "VARCHAR(100) DEFAULT NULL COMMENT 'Vehicle model'");
CALL add_column_if_not_exists('user_vehicles', 'year', "YEAR DEFAULT NULL COMMENT 'Model year'");
CALL add_column_if_not_exists('user_vehicles', 'color', "VARCHAR(50) DEFAULT NULL COMMENT 'Vehicle color'");
CALL add_column_if_not_exists('user_vehicles', 'license_plate', "VARCHAR(20) DEFAULT NULL COMMENT 'License plate number'");
CALL add_column_if_not_exists('user_vehicles', 'image_path', "VARCHAR(500) DEFAULT NULL COMMENT 'Vehicle photo path'");

-- =====================================================
-- 1.7 Ensure payments table has all required columns
-- =====================================================

CALL add_column_if_not_exists('payments', 'carwash_id', "INT(11) DEFAULT NULL COMMENT 'FK to carwashes for settlement'");
CALL add_column_if_not_exists('payments', 'user_id', "INT(11) DEFAULT NULL COMMENT 'FK to users for customer'");
CALL add_column_if_not_exists('payments', 'currency', "VARCHAR(3) DEFAULT 'TRY' COMMENT 'Currency code'");
CALL add_column_if_not_exists('payments', 'gateway', "VARCHAR(50) DEFAULT NULL COMMENT 'Payment gateway name'");
CALL add_column_if_not_exists('payments', 'gateway_reference', "VARCHAR(100) DEFAULT NULL COMMENT 'Gateway transaction ID'");
CALL add_column_if_not_exists('payments', 'refund_amount', "DECIMAL(10,2) DEFAULT NULL COMMENT 'Refund amount if applicable'");
CALL add_column_if_not_exists('payments', 'refund_date', "DATETIME DEFAULT NULL COMMENT 'Refund timestamp'");
CALL add_column_if_not_exists('payments', 'settlement_status', "ENUM('pending', 'settled', 'hold') DEFAULT 'pending' COMMENT 'Settlement to carwash'");
CALL add_column_if_not_exists('payments', 'settled_at', "DATETIME DEFAULT NULL COMMENT 'Settlement timestamp'");

-- =====================================================
-- 1.8 Ensure reviews table has correct structure
-- =====================================================

CALL add_column_if_not_exists('reviews', 'carwash_id', "INT(11) DEFAULT NULL COMMENT 'FK to carwashes'");
CALL add_column_if_not_exists('reviews', 'comment', "TEXT DEFAULT NULL COMMENT 'Review text'");
CALL add_column_if_not_exists('reviews', 'is_approved', "TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Moderation status'");

-- =====================================================
-- 1.9 Ensure time_slots table exists
-- =====================================================

CREATE TABLE IF NOT EXISTS `time_slots` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `carwash_id` INT(11) NOT NULL COMMENT 'FK to carwashes',
    `day_of_week` TINYINT(1) NOT NULL COMMENT '0=Sunday, 6=Saturday',
    `start_time` TIME NOT NULL COMMENT 'Slot start time',
    `end_time` TIME NOT NULL COMMENT 'Slot end time',
    `capacity` INT NOT NULL DEFAULT 1 COMMENT 'Max bookings per slot',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Active flag',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_carwash_id` (`carwash_id`),
    KEY `idx_day_time` (`day_of_week`, `start_time`, `end_time`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Available time slots for each car wash';

SELECT 'Created/verified time_slots table' AS 'Status';

-- =====================================================
-- 1.10 Create audit_logs table if not exists
-- =====================================================

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL COMMENT 'User who performed action',
    `action` VARCHAR(100) NOT NULL COMMENT 'Action performed',
    `table_name` VARCHAR(64) DEFAULT NULL COMMENT 'Affected table',
    `record_id` INT UNSIGNED DEFAULT NULL COMMENT 'Affected record ID',
    `old_values` JSON DEFAULT NULL COMMENT 'Values before change',
    `new_values` JSON DEFAULT NULL COMMENT 'Values after change',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP address',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'Browser user agent',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_table_record` (`table_name`, `record_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='System audit trail for security and compliance';

SELECT 'Created/verified audit_logs table' AS 'Status';

-- =====================================================
-- 1.11 Create favorites table if not exists
-- =====================================================

CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT 'FK to users',
    `carwash_id` INT(11) NOT NULL COMMENT 'FK to carwashes',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_carwash` (`user_id`, `carwash_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_carwash_id` (`carwash_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer favorite car washes';

SELECT 'Created/verified favorites table' AS 'Status';

-- =====================================================
-- 1.12 Create booking_status lookup table if not exists
-- =====================================================

CREATE TABLE IF NOT EXISTS `booking_status` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(30) NOT NULL COMMENT 'Status code',
    `name_tr` VARCHAR(100) NOT NULL COMMENT 'Turkish name',
    `name_en` VARCHAR(100) DEFAULT NULL COMMENT 'English name',
    `color` VARCHAR(20) DEFAULT NULL COMMENT 'UI color code',
    `icon` VARCHAR(50) DEFAULT NULL COMMENT 'FontAwesome icon class',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Booking status lookup table';

-- Insert default statuses if table is empty
INSERT IGNORE INTO `booking_status` (`code`, `name_tr`, `name_en`, `color`, `icon`, `display_order`) VALUES
('pending', 'Beklemede', 'Pending', '#f59e0b', 'fa-clock', 1),
('confirmed', 'Onaylandı', 'Confirmed', '#10b981', 'fa-check-circle', 2),
('in_progress', 'İşlemde', 'In Progress', '#3b82f6', 'fa-spinner', 3),
('completed', 'Tamamlandı', 'Completed', '#22c55e', 'fa-check-double', 4),
('cancelled', 'İptal Edildi', 'Cancelled', '#ef4444', 'fa-times-circle', 5),
('no_show', 'Gelmedi', 'No Show', '#6b7280', 'fa-user-slash', 6);

SELECT 'Created/verified booking_status table' AS 'Status';

-- =====================================================
-- 1.13 Add performance indexes
-- =====================================================

-- Safe index creation procedure
DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;
DELIMITER //
CREATE PROCEDURE `add_index_if_not_exists`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns VARCHAR(500)
)
BEGIN
    DECLARE idx_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO idx_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND INDEX_NAME = p_index;
    
    IF idx_exists = 0 THEN
        SET @sql = CONCAT('CREATE INDEX `', p_index, '` ON `', p_table, '` (', p_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('Added index ', p_table, '.', p_index) AS 'Index Added';
    ELSE
        SELECT CONCAT('Index ', p_table, '.', p_index, ' already exists') AS 'Index Skipped';
    END IF;
END //
DELIMITER ;

-- Add indexes to bookings
CALL add_index_if_not_exists('bookings', 'idx_booking_date_status', '`booking_date`, `status`');
CALL add_index_if_not_exists('bookings', 'idx_carwash_date', '`carwash_id`, `booking_date`');
CALL add_index_if_not_exists('bookings', 'idx_user_status', '`user_id`, `status`');

-- Add indexes to services
CALL add_index_if_not_exists('services', 'idx_carwash_active', '`carwash_id`, `is_active`');

-- Add indexes to reviews
CALL add_index_if_not_exists('reviews', 'idx_carwash_approved', '`carwash_id`, `is_approved`');

-- Add indexes to payments
CALL add_index_if_not_exists('payments', 'idx_settlement', '`settlement_status`, `settled_at`');

SELECT CONCAT('Phase 1 Completed: ', NOW(), ' (Duration: ', TIMEDIFF(NOW(), @start_time), ')') AS 'Migration Status';

-- =====================================================
-- End of Phase 1
-- =====================================================
