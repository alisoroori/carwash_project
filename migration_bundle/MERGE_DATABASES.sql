-- ==============================================================================
-- DATABASE MERGE SCRIPT: carwash → carwash_db
-- ==============================================================================
-- Generated: 2025-12-06
-- Purpose: Merge schemas and data from 'carwash' database into 'carwash_db'
-- Target: Single unified database named 'carwash_db'
-- 
-- CURRENT STATE:
--   carwash     = Clean schema, 1 user only, no other data
--   carwash_db  = Active database with all production data
--
-- STRATEGY:
--   1. Enhance carwash_db schema with improvements from carwash
--   2. Add missing columns to align schemas
--   3. Migrate data from carwash (if unique)
--   4. DROP carwash database at the end
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;

-- Use the target database
USE `carwash_db`;

-- ==============================================================================
-- SECTION 1: USERS TABLE ENHANCEMENTS
-- ==============================================================================
-- carwash.users has cleaner schema with: status enum, login_attempts, 
-- password_reset_token, password_reset_expires, remember_token
-- carwash_db.users has: username, full_name, is_active, home_phone, national_id, driver_license

-- Add missing columns from carwash schema to carwash_db.users
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `status` ENUM('active','inactive','banned','pending') DEFAULT 'active' AFTER `role`,
    ADD COLUMN IF NOT EXISTS `login_attempts` INT(11) DEFAULT 0 AFTER `email_verified_at`,
    ADD COLUMN IF NOT EXISTS `last_login_attempt` DATETIME DEFAULT NULL AFTER `login_attempts`,
    ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(100) DEFAULT NULL AFTER `last_login_at`,
    ADD COLUMN IF NOT EXISTS `password_reset_expires` DATETIME DEFAULT NULL AFTER `password_reset_token`,
    ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(100) DEFAULT NULL AFTER `password_reset_expires`;

-- Add comments for clarity
ALTER TABLE `users` COMMENT = 'User accounts for customers, carwash owners, admins, and staff';

-- Create index on status if not exists
CREATE INDEX IF NOT EXISTS `idx_status` ON `users` (`status`);

-- ==============================================================================
-- SECTION 2: BOOKINGS TABLE ENHANCEMENTS
-- ==============================================================================
-- carwash.bookings has more complete schema with:
-- booking_number, vehicle_id, time_slot_id, end_time, customer_name, customer_phone,
-- customer_email, vehicle_model, vehicle_color, review_status, discount_amount,
-- special_requests, cancellation_reason, cancelled_at, confirmed_at, started_at, completed_at

-- Add missing columns from carwash schema to carwash_db.bookings
ALTER TABLE `bookings`
    ADD COLUMN IF NOT EXISTS `time_slot_id` INT(11) DEFAULT NULL AFTER `vehicle_id`,
    ADD COLUMN IF NOT EXISTS `end_time` TIME DEFAULT NULL AFTER `booking_time`,
    ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(100) DEFAULT NULL COMMENT 'For guest bookings' AFTER `end_time`,
    ADD COLUMN IF NOT EXISTS `customer_phone` VARCHAR(20) DEFAULT NULL AFTER `customer_name`,
    ADD COLUMN IF NOT EXISTS `customer_email` VARCHAR(100) DEFAULT NULL AFTER `customer_phone`,
    ADD COLUMN IF NOT EXISTS `vehicle_model` VARCHAR(100) DEFAULT NULL AFTER `vehicle_plate`,
    ADD COLUMN IF NOT EXISTS `vehicle_color` VARCHAR(30) DEFAULT NULL AFTER `vehicle_model`,
    ADD COLUMN IF NOT EXISTS `review_status` ENUM('pending','reviewed') DEFAULT 'pending' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `total_price`,
    ADD COLUMN IF NOT EXISTS `special_requests` TEXT DEFAULT NULL AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `cancellation_reason` TEXT DEFAULT NULL AFTER `special_requests`,
    ADD COLUMN IF NOT EXISTS `cancelled_at` TIMESTAMP NULL DEFAULT NULL AFTER `cancellation_reason`,
    ADD COLUMN IF NOT EXISTS `confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `cancelled_at`,
    ADD COLUMN IF NOT EXISTS `started_at` TIMESTAMP NULL DEFAULT NULL AFTER `confirmed_at`,
    ADD COLUMN IF NOT EXISTS `completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `started_at`;

-- Add unique key on booking_number if not exists
-- First check if the key exists, if not create it
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = 'carwash_db' 
               AND TABLE_NAME = 'bookings' 
               AND INDEX_NAME = 'uk_booking_number');

SET @query = IF(@exists = 0, 
    'CREATE UNIQUE INDEX `uk_booking_number` ON `bookings` (`booking_number`)', 
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add comment
ALTER TABLE `bookings` COMMENT = 'Customer booking/reservation records';

-- ==============================================================================
-- SECTION 3: CARWASHES TABLE ENHANCEMENTS
-- ==============================================================================
-- carwash.carwashes has cleaner columns: slug, cover_image_path, amenities, 
-- is_featured, rating_average, rating_count
-- carwash_db has more columns but messier

-- Add slug column (URL-friendly name)
ALTER TABLE `carwashes`
    ADD COLUMN IF NOT EXISTS `slug` VARCHAR(150) DEFAULT NULL COMMENT 'URL-friendly name' AFTER `name`,
    ADD COLUMN IF NOT EXISTS `cover_image_path` VARCHAR(255) DEFAULT NULL COMMENT 'Cover image for profile' AFTER `profile_image_path`,
    ADD COLUMN IF NOT EXISTS `amenities` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Available amenities (JSON)' AFTER `social_media`,
    ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) DEFAULT 0 AFTER `status`,
    ADD COLUMN IF NOT EXISTS `rating_average` DECIMAL(3,2) DEFAULT 0.00 AFTER `is_featured`,
    ADD COLUMN IF NOT EXISTS `rating_count` INT(11) DEFAULT 0 AFTER `rating_average`;

-- Add unique key on slug if not exists
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = 'carwash_db' 
               AND TABLE_NAME = 'carwashes' 
               AND INDEX_NAME = 'uk_slug');

SET @query = IF(@exists = 0 AND EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'carwash_db' AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'slug'), 
    'CREATE UNIQUE INDEX `uk_slug` ON `carwashes` (`slug`)', 
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add JSON check constraint for amenities (MariaDB 10.4.3+)
-- ALTER TABLE `carwashes` ADD CONSTRAINT `chk_amenities_json` CHECK (JSON_VALID(`amenities`) OR `amenities` IS NULL);

-- Add comment
ALTER TABLE `carwashes` COMMENT = 'Car wash business profiles';

-- ==============================================================================
-- SECTION 4: BOOKING_STATUS TABLE ENHANCEMENTS
-- ==============================================================================
-- carwash.booking_status has: description, color, sort_order
-- carwash_db.booking_status is minimal

ALTER TABLE `booking_status`
    ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL AFTER `label`,
    ADD COLUMN IF NOT EXISTS `color` VARCHAR(20) DEFAULT NULL COMMENT 'UI color class or hex' AFTER `description`,
    ADD COLUMN IF NOT EXISTS `sort_order` INT(11) DEFAULT 0 AFTER `color`;

-- Populate booking_status with standard values if empty
INSERT INTO `booking_status` (`code`, `label`, `description`, `color`, `sort_order`)
SELECT * FROM (
    SELECT 'pending' as code, 'Beklemede' as label, 'Booking awaiting confirmation' as description, 'warning' as color, 1 as sort_order UNION ALL
    SELECT 'confirmed', 'Onaylandı', 'Booking has been confirmed', 'info', 2 UNION ALL
    SELECT 'in_progress', 'Devam Ediyor', 'Service is in progress', 'primary', 3 UNION ALL
    SELECT 'completed', 'Tamamlandı', 'Service completed successfully', 'success', 4 UNION ALL
    SELECT 'cancelled', 'İptal Edildi', 'Booking was cancelled', 'danger', 5 UNION ALL
    SELECT 'no_show', 'Gelmedi', 'Customer did not show up', 'secondary', 6
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `booking_status` WHERE `code` = tmp.code);

-- Add comment
ALTER TABLE `booking_status` COMMENT = 'Booking status reference data';

-- ==============================================================================
-- SECTION 5: SERVICES TABLE ENHANCEMENTS
-- ==============================================================================
-- carwash.services has: category, sort_order columns

ALTER TABLE `services`
    ADD COLUMN IF NOT EXISTS `category` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., exterior, interior, full, premium' AFTER `description`,
    ADD COLUMN IF NOT EXISTS `sort_order` INT(11) DEFAULT 0 AFTER `status`;

-- Add index on category if not exists
CREATE INDEX IF NOT EXISTS `idx_category` ON `services` (`category`);

-- Add comment
ALTER TABLE `services` COMMENT = 'Services offered by each carwash';

-- ==============================================================================
-- SECTION 6: REVIEWS TABLE ENHANCEMENTS
-- ==============================================================================
-- carwash.reviews has: title, response, responded_at, is_verified, is_visible

ALTER TABLE `reviews`
    ADD COLUMN IF NOT EXISTS `title` VARCHAR(100) DEFAULT NULL AFTER `rating`,
    ADD COLUMN IF NOT EXISTS `response` TEXT DEFAULT NULL COMMENT 'Business owner response' AFTER `comment`,
    ADD COLUMN IF NOT EXISTS `responded_at` TIMESTAMP NULL DEFAULT NULL AFTER `response`,
    ADD COLUMN IF NOT EXISTS `is_verified` TINYINT(1) DEFAULT 0 AFTER `responded_at`,
    ADD COLUMN IF NOT EXISTS `is_visible` TINYINT(1) DEFAULT 1 AFTER `is_verified`;

-- Add rating check constraint
-- ALTER TABLE `reviews` ADD CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5);

-- Add index on rating
CREATE INDEX IF NOT EXISTS `idx_rating` ON `reviews` (`rating`);

-- Add comment
ALTER TABLE `reviews` COMMENT = 'Customer reviews and ratings';

-- ==============================================================================
-- SECTION 7: PAYMENTS TABLE ENHANCEMENTS
-- ==============================================================================
-- carwash.payments has more complete schema: total_amount, currency, payment_gateway,
-- refunded_at, refund_amount, metadata

ALTER TABLE `payments`
    ADD COLUMN IF NOT EXISTS `total_amount` DECIMAL(10,2) DEFAULT NULL AFTER `amount`,
    ADD COLUMN IF NOT EXISTS `currency` VARCHAR(3) DEFAULT 'TRY' AFTER `total_amount`,
    ADD COLUMN IF NOT EXISTS `payment_gateway` VARCHAR(50) DEFAULT NULL AFTER `payment_method`,
    ADD COLUMN IF NOT EXISTS `refunded_at` TIMESTAMP NULL DEFAULT NULL AFTER `paid_at`,
    ADD COLUMN IF NOT EXISTS `refund_amount` DECIMAL(10,2) DEFAULT NULL AFTER `refunded_at`,
    ADD COLUMN IF NOT EXISTS `metadata` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL AFTER `notes`;

-- Add index on transaction_id
CREATE INDEX IF NOT EXISTS `idx_transaction_id` ON `payments` (`transaction_id`);

-- Add comment
ALTER TABLE `payments` COMMENT = 'Payment transactions';

-- ==============================================================================
-- SECTION 8: USER_VEHICLES TABLE ENHANCEMENTS
-- ==============================================================================
-- carwash.user_vehicles has: notes column

ALTER TABLE `user_vehicles`
    ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL AFTER `is_default`;

-- Add index on license_plate
CREATE INDEX IF NOT EXISTS `idx_license_plate` ON `user_vehicles` (`license_plate`);

-- Add comment
ALTER TABLE `user_vehicles` COMMENT = 'Customer vehicles';

-- ==============================================================================
-- SECTION 9: TIME_SLOTS TABLE - FIX FOREIGN KEY
-- ==============================================================================
-- carwash_db.time_slots references carwash_profiles instead of carwashes
-- This is incorrect - should reference carwashes

-- First check and drop the incorrect FK if it exists
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = 'carwash_db' 
                  AND TABLE_NAME = 'time_slots' 
                  AND CONSTRAINT_NAME = 'time_slots_ibfk_1');

-- Drop the incorrect FK and add correct one
SET @drop_fk = IF(@fk_exists > 0, 
    'ALTER TABLE `time_slots` DROP FOREIGN KEY `time_slots_ibfk_1`', 
    'SELECT 1');
PREPARE stmt FROM @drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add max_bookings column (from carwash schema)
ALTER TABLE `time_slots`
    ADD COLUMN IF NOT EXISTS `max_bookings` INT(11) DEFAULT 1 AFTER `capacity`;

-- Add correct FK to carwashes
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = 'carwash_db' 
                  AND TABLE_NAME = 'time_slots' 
                  AND CONSTRAINT_NAME = 'fk_timeslots_carwash');

SET @add_fk = IF(@fk_exists = 0, 
    'ALTER TABLE `time_slots` ADD CONSTRAINT `fk_timeslots_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE', 
    'SELECT 1');
PREPARE stmt FROM @add_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add comment
ALTER TABLE `time_slots` COMMENT = 'Available time slots for each carwash';

-- ==============================================================================
-- SECTION 10: SECURITY_SETTINGS TABLE - CREATE IF NOT EXISTS
-- ==============================================================================
-- This table exists in carwash but may not exist in carwash_db

CREATE TABLE IF NOT EXISTS `security_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Application security configuration';

-- Populate with default security settings if empty
INSERT INTO `security_settings` (`setting_key`, `setting_value`, `description`)
SELECT * FROM (
    SELECT 'max_login_attempts' as setting_key, '5' as setting_value, 'Maximum login attempts before lockout' as description UNION ALL
    SELECT 'lockout_duration', '900', 'Lockout duration in seconds (15 minutes)' UNION ALL
    SELECT 'session_timeout', '3600', 'Session timeout in seconds (1 hour)' UNION ALL
    SELECT 'password_min_length', '8', 'Minimum password length' UNION ALL
    SELECT 'require_email_verification', 'true', 'Require email verification for new accounts'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `security_settings` WHERE `setting_key` = tmp.setting_key);

-- ==============================================================================
-- SECTION 11: BOOKING_SERVICES TABLE - CREATE IF NOT EXISTS
-- ==============================================================================
-- Junction table for itemized billing

CREATE TABLE IF NOT EXISTS `booking_services` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `service_id` INT(11) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `quantity` INT(11) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking_id` (`booking_id`),
    KEY `idx_service_id` (`service_id`),
    CONSTRAINT `fk_booking_services_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_booking_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Services included in each booking (for itemized billing)';

-- ==============================================================================
-- SECTION 12: AUDIT_LOGS TABLE - ENSURE IT EXISTS
-- ==============================================================================
-- Add comment if table exists
ALTER TABLE `audit_logs` COMMENT = 'Audit trail for security and compliance';

-- ==============================================================================
-- SECTION 13: STAFF_MEMBERS TABLE - ENSURE FK CONSISTENCY
-- ==============================================================================
-- Add comment
ALTER TABLE `staff_members` COMMENT = 'Staff members per carwash location';

-- ==============================================================================
-- SECTION 14: FAVORITES TABLE - ADD COMMENTS
-- ==============================================================================
ALTER TABLE `favorites` COMMENT = 'Customer favorite carwashes';

-- ==============================================================================
-- SECTION 15: UI_LABELS TABLE - ADD COMMENTS
-- ==============================================================================
ALTER TABLE `ui_labels` COMMENT = 'UI labels and translations - keeps technical identifiers separate from display text';

-- ==============================================================================
-- SECTION 16: DATA MIGRATION FROM CARWASH TO CARWASH_DB
-- ==============================================================================
-- Migrate the 1 user from carwash if not already in carwash_db

-- Check if admin user from carwash needs to be migrated
INSERT INTO `users` (`email`, `password`, `role`, `name`, `phone`, `status`, `created_at`, `updated_at`)
SELECT 
    c.`email`, 
    c.`password`, 
    c.`role`, 
    c.`name`, 
    c.`phone`, 
    c.`status`,
    c.`created_at`, 
    c.`updated_at`
FROM `carwash`.`users` c
WHERE NOT EXISTS (
    SELECT 1 FROM `carwash_db`.`users` u WHERE u.`email` = c.`email`
);

-- Migrate security_settings if any exist in carwash
INSERT INTO `security_settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
SELECT 
    c.`setting_key`, 
    c.`setting_value`, 
    c.`description`, 
    c.`updated_at`
FROM `carwash`.`security_settings` c
WHERE NOT EXISTS (
    SELECT 1 FROM `carwash_db`.`security_settings` s WHERE s.`setting_key` = c.`setting_key`
);

-- Migrate booking_status entries if any exist
INSERT INTO `booking_status` (`code`, `label`, `description`, `color`, `sort_order`)
SELECT 
    c.`code`, 
    c.`label`, 
    c.`description`, 
    c.`color`, 
    c.`sort_order`
FROM `carwash`.`booking_status` c
WHERE NOT EXISTS (
    SELECT 1 FROM `carwash_db`.`booking_status` s WHERE s.`code` = c.`code`
);

-- ==============================================================================
-- SECTION 17: STANDARDIZE COLLATION
-- ==============================================================================
-- Ensure all tables use utf8mb4_unicode_ci for consistency

-- Update tables with mixed collation
ALTER TABLE `user_profiles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_vehicles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `vehicles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `time_slots` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ==============================================================================
-- SECTION 18: UPDATE AUTO_INCREMENT VALUES
-- ==============================================================================
-- Ensure auto_increment values are high enough to avoid conflicts

-- Get max IDs and update auto_increment (these are safe - they only increase)
SET @max_user = (SELECT COALESCE(MAX(id), 0) + 100 FROM `users`);
SET @sql = CONCAT('ALTER TABLE `users` AUTO_INCREMENT = ', @max_user);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @max_booking = (SELECT COALESCE(MAX(id), 0) + 100 FROM `bookings`);
SET @sql = CONCAT('ALTER TABLE `bookings` AUTO_INCREMENT = ', @max_booking);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @max_carwash = (SELECT COALESCE(MAX(id), 0) + 100 FROM `carwashes`);
SET @sql = CONCAT('ALTER TABLE `carwashes` AUTO_INCREMENT = ', @max_carwash);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ==============================================================================
-- SECTION 19: RECREATE VIEWS WITH UPDATED SCHEMA
-- ==============================================================================

-- Drop and recreate active_bookings_view with enhanced columns
DROP VIEW IF EXISTS `active_bookings_view`;
CREATE VIEW `active_bookings_view` AS
SELECT 
    b.`id`,
    b.`booking_number`,
    b.`booking_date`,
    b.`booking_time`,
    b.`end_time`,
    b.`status`,
    b.`review_status`,
    b.`total_price`,
    b.`discount_amount`,
    b.`vehicle_type`,
    b.`vehicle_plate`,
    b.`vehicle_model`,
    b.`vehicle_color`,
    COALESCE(b.`customer_name`, u.`full_name`, u.`name`) AS `customer_name`,
    COALESCE(b.`customer_email`, u.`email`) AS `customer_email`,
    COALESCE(b.`customer_phone`, u.`phone`) AS `customer_phone`,
    c.`name` AS `carwash_name`,
    c.`address` AS `carwash_address`,
    c.`city` AS `carwash_city`,
    s.`name` AS `service_name`,
    s.`duration` AS `service_duration`,
    s.`category` AS `service_category`,
    b.`created_at`,
    b.`updated_at`
FROM `bookings` b
LEFT JOIN `users` u ON b.`user_id` = u.`id`
LEFT JOIN `carwashes` c ON b.`carwash_id` = c.`id`
LEFT JOIN `services` s ON b.`service_id` = s.`id`
WHERE b.`status` IN ('pending', 'confirmed', 'in_progress');

-- Drop and recreate carwash_stats_view with enhanced columns
DROP VIEW IF EXISTS `carwash_stats_view`;
CREATE VIEW `carwash_stats_view` AS
SELECT 
    c.`id`,
    c.`name`,
    c.`slug`,
    c.`city`,
    c.`district`,
    c.`status`,
    c.`is_featured`,
    COALESCE(c.`rating_average`, c.`rating`, 0) AS `rating`,
    COALESCE(c.`rating_count`, c.`total_reviews`, 0) AS `total_reviews`,
    COUNT(DISTINCT b.`id`) AS `total_bookings`,
    COUNT(DISTINCT CASE WHEN b.`status` = 'completed' THEN b.`id` END) AS `completed_bookings`,
    COUNT(DISTINCT CASE WHEN b.`status` = 'pending' THEN b.`id` END) AS `pending_bookings`,
    COALESCE(SUM(CASE WHEN b.`status` = 'completed' THEN b.`total_price` END), 0) AS `total_revenue`,
    COUNT(DISTINCT s.`id`) AS `total_services`,
    COUNT(DISTINCT r.`id`) AS `review_count`,
    AVG(r.`rating`) AS `avg_review_rating`
FROM `carwashes` c
LEFT JOIN `bookings` b ON c.`id` = b.`carwash_id`
LEFT JOIN `services` s ON c.`id` = s.`carwash_id`
LEFT JOIN `reviews` r ON c.`id` = r.`carwash_id`
GROUP BY c.`id`, c.`name`, c.`slug`, c.`city`, c.`district`, c.`status`, 
         c.`is_featured`, c.`rating_average`, c.`rating`, c.`rating_count`, c.`total_reviews`;

-- ==============================================================================
-- SECTION 20: VERIFY MERGE RESULTS
-- ==============================================================================

-- Output table counts for verification
SELECT 'MERGE VERIFICATION REPORT' AS `Report`;
SELECT '=========================' AS ``;

SELECT 'users' AS `Table`, COUNT(*) AS `Count` FROM `users`
UNION ALL SELECT 'bookings', COUNT(*) FROM `bookings`
UNION ALL SELECT 'carwashes', COUNT(*) FROM `carwashes`
UNION ALL SELECT 'services', COUNT(*) FROM `services`
UNION ALL SELECT 'reviews', COUNT(*) FROM `reviews`
UNION ALL SELECT 'user_vehicles', COUNT(*) FROM `user_vehicles`
UNION ALL SELECT 'payments', COUNT(*) FROM `payments`
UNION ALL SELECT 'favorites', COUNT(*) FROM `favorites`
UNION ALL SELECT 'booking_status', COUNT(*) FROM `booking_status`
UNION ALL SELECT 'time_slots', COUNT(*) FROM `time_slots`
UNION ALL SELECT 'ui_labels', COUNT(*) FROM `ui_labels`
UNION ALL SELECT 'notifications', COUNT(*) FROM `notifications`
UNION ALL SELECT 'security_settings', COUNT(*) FROM `security_settings`
UNION ALL SELECT 'audit_logs', COUNT(*) FROM `audit_logs`
UNION ALL SELECT 'staff_members', COUNT(*) FROM `staff_members`;

-- ==============================================================================
-- SECTION 21: FINAL CLEANUP
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;

-- ==============================================================================
-- SECTION 22: DROP OLD DATABASE (EXECUTE SEPARATELY AFTER VERIFICATION)
-- ==============================================================================
-- WARNING: Only execute this after verifying the merge was successful!
-- This is commented out for safety - uncomment and run manually when ready.

-- DROP DATABASE IF EXISTS `carwash`;

-- ==============================================================================
-- END OF MERGE SCRIPT
-- ==============================================================================
