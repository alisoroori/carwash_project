-- ============================================================================
-- CarWash Project - Canonical Schema Creation
-- Version: 1.0.0
-- Date: 2025-12-06
-- Description: CREATE TABLE IF NOT EXISTS for all canonical tables
-- Danger Level: LOW - Only creates tables if they don't exist
-- Expected Runtime: < 1 second (no data touched)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. USERS TABLE
-- Core user entity for all roles (admin, customer, staff, carwash)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) DEFAULT NULL,
    `full_name` VARCHAR(100) NOT NULL DEFAULT '',
    `phone` VARCHAR(20) DEFAULT NULL,
    `home_phone` VARCHAR(20) DEFAULT NULL,
    `national_id` VARCHAR(20) DEFAULT NULL,
    `driver_license` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('admin','customer','staff','carwash') DEFAULT 'customer',
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `profile_image_path` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `email_verified` TINYINT(1) DEFAULT 0,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_national_id` (`national_id`),
    KEY `idx_users_driver_license` (`driver_license`),
    KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. USER_PROFILES TABLE
-- Extended user profile data (1:1 with users)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `birth_date` DATE DEFAULT NULL,
    `gender` ENUM('male','female','other') DEFAULT NULL,
    `preferences` LONGTEXT DEFAULT NULL,
    `notification_settings` LONGTEXT DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `home_phone` VARCHAR(20) DEFAULT NULL,
    `national_id` VARCHAR(20) DEFAULT NULL,
    `driver_license` VARCHAR(20) DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_profiles_user_id` (`user_id`),
    CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. CARWASHES TABLE
-- Car wash business entities
-- ============================================================================

CREATE TABLE IF NOT EXISTS `carwashes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `owner_id` INT(11) DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `address` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `district` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `zip_code` VARCHAR(20) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'USA',
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `mobile_phone` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `owner_name` VARCHAR(100) DEFAULT NULL,
    `owner_phone` VARCHAR(20) DEFAULT NULL,
    `owner_birth_date` DATE DEFAULT NULL,
    `birth_date` DATE DEFAULT NULL,
    `tax_number` VARCHAR(50) DEFAULT NULL,
    `license_number` VARCHAR(50) DEFAULT NULL,
    `tc_kimlik` VARCHAR(11) DEFAULT NULL,
    `opening_hours` LONGTEXT DEFAULT NULL,
    `working_hours` LONGTEXT DEFAULT NULL,
    `opening_time` TIME DEFAULT NULL,
    `closing_time` TIME DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `logo_path` VARCHAR(255) DEFAULT NULL,
    `logo_image` VARCHAR(255) DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `profile_image_path` VARCHAR(255) DEFAULT NULL,
    `certificate_path` VARCHAR(255) DEFAULT NULL,
    `social_media` LONGTEXT DEFAULT NULL,
    `services` LONGTEXT DEFAULT NULL,
    `exterior_price` DECIMAL(10,2) DEFAULT 0.00,
    `interior_price` DECIMAL(10,2) DEFAULT 0.00,
    `detailing_price` DECIMAL(10,2) DEFAULT 0.00,
    `capacity` INT(11) DEFAULT 0,
    `rating` DECIMAL(3,2) DEFAULT 0.00,
    `rating_average` DECIMAL(3,2) DEFAULT 0.00,
    `rating_count` INT(11) DEFAULT 0,
    `total_reviews` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `status` VARCHAR(20) DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_carwashes_city` (`city`),
    KEY `idx_carwashes_rating` (`rating`),
    KEY `idx_carwashes_is_active` (`is_active`),
    KEY `idx_carwashes_owner_id` (`owner_id`),
    KEY `idx_carwashes_user_id` (`user_id`),
    KEY `idx_carwashes_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. SERVICE_CATEGORIES TABLE
-- Service category lookup table
-- ============================================================================

CREATE TABLE IF NOT EXISTS `service_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `icon` VARCHAR(100) DEFAULT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_service_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. SERVICES TABLE
-- Car wash services offered by each carwash
-- ============================================================================

CREATE TABLE IF NOT EXISTS `services` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `carwash_id` INT(11) NOT NULL,
    `category_id` INT(11) DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `duration` INT(11) NOT NULL COMMENT 'Duration in minutes',
    `category` ENUM('basic','standard','premium','deluxe') DEFAULT 'basic',
    `status` VARCHAR(50) DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `features` LONGTEXT DEFAULT NULL,
    `is_available` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_services_carwash_id` (`carwash_id`),
    KEY `idx_services_price` (`price`),
    KEY `idx_services_category` (`category`),
    KEY `idx_services_is_available` (`is_available`),
    CONSTRAINT `fk_services_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. USER_VEHICLES TABLE
-- Vehicles owned by customers
-- ============================================================================

CREATE TABLE IF NOT EXISTS `user_vehicles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `brand` VARCHAR(100) DEFAULT NULL,
    `model` VARCHAR(100) DEFAULT NULL,
    `year` INT(11) DEFAULT NULL,
    `color` VARCHAR(50) DEFAULT NULL,
    `license_plate` VARCHAR(50) DEFAULT NULL,
    `vehicle_type` ENUM('sedan','suv','hatchback','pickup','van','motorcycle','other') DEFAULT 'sedan',
    `image_path` VARCHAR(255) DEFAULT NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_vehicles_user_id` (`user_id`),
    KEY `idx_user_vehicles_license_plate` (`license_plate`),
    CONSTRAINT `fk_user_vehicles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. BOOKINGS TABLE
-- Service bookings/appointments
-- ============================================================================

CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_number` VARCHAR(20) DEFAULT NULL,
    `user_id` INT(11) NOT NULL,
    `carwash_id` INT(11) NOT NULL,
    `service_id` INT(11) NOT NULL,
    `vehicle_id` INT(11) DEFAULT NULL,
    `booking_date` DATE NOT NULL,
    `booking_time` TIME NOT NULL,
    `vehicle_type` ENUM('sedan','suv','truck','van','motorcycle') NOT NULL,
    `vehicle_plate` VARCHAR(20) DEFAULT NULL,
    `vehicle_model` VARCHAR(100) DEFAULT NULL,
    `vehicle_color` VARCHAR(50) DEFAULT NULL,
    `customer_name` VARCHAR(100) DEFAULT NULL,
    `customer_phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
    `review_status` ENUM('pending','reviewed') DEFAULT 'pending',
    `total_price` DECIMAL(10,2) NOT NULL,
    `payment_status` ENUM('pending','paid','refunded') DEFAULT 'pending',
    `payment_method` ENUM('cash','card','online') DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `cancellation_reason` TEXT DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bookings_user_id` (`user_id`),
    KEY `idx_bookings_carwash_id` (`carwash_id`),
    KEY `idx_bookings_service_id` (`service_id`),
    KEY `idx_bookings_booking_date` (`booking_date`),
    KEY `idx_bookings_status` (`status`),
    KEY `idx_bookings_review_status` (`review_status`),
    CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. BOOKING_SERVICES TABLE
-- Junction table for bookings with multiple services
-- ============================================================================

CREATE TABLE IF NOT EXISTS `booking_services` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `service_id` INT(11) NOT NULL,
    `quantity` INT(11) DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking_services_booking_id` (`booking_id`),
    KEY `idx_booking_services_service_id` (`service_id`),
    CONSTRAINT `fk_booking_services_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_booking_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. PAYMENTS TABLE
-- Payment transactions
-- ============================================================================

CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `transaction_id` VARCHAR(100) DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `total_amount` DECIMAL(10,2) DEFAULT NULL,
    `payment_method` ENUM('credit_card','cash','online_transfer','mobile_payment') NOT NULL,
    `status` ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    `payment_date` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `receipt_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payments_booking_id` (`booking_id`),
    KEY `idx_payments_transaction_id` (`transaction_id`),
    KEY `idx_payments_status` (`status`),
    CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. REVIEWS TABLE
-- Customer reviews and ratings
-- ============================================================================

CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `carwash_id` INT(11) NOT NULL,
    `booking_id` INT(11) DEFAULT NULL,
    `rating` INT(11) NOT NULL,
    `title` VARCHAR(100) DEFAULT NULL,
    `comment` TEXT DEFAULT NULL,
    `response` TEXT DEFAULT NULL,
    `responded_at` TIMESTAMP NULL DEFAULT NULL,
    `is_verified` TINYINT(1) DEFAULT 0,
    `is_visible` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reviews_user_id` (`user_id`),
    KEY `idx_reviews_carwash_id` (`carwash_id`),
    KEY `idx_reviews_booking_id` (`booking_id`),
    KEY `idx_reviews_rating` (`rating`),
    CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. FAVORITES TABLE
-- User's favorite carwashes
-- ============================================================================

CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `carwash_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_favorites_user_carwash` (`user_id`, `carwash_id`),
    KEY `idx_favorites_user_id` (`user_id`),
    KEY `idx_favorites_carwash_id` (`carwash_id`),
    CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_favorites_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. TIME_SLOTS TABLE
-- Available time slots for each carwash
-- ============================================================================

CREATE TABLE IF NOT EXISTS `time_slots` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `carwash_id` INT(11) NOT NULL,
    `day_of_week` TINYINT(1) NOT NULL COMMENT '0=Sunday, 6=Saturday',
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `capacity` INT(11) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_time_slots_carwash_id` (`carwash_id`),
    KEY `idx_time_slots_day_of_week` (`day_of_week`),
    CONSTRAINT `fk_time_slots_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. STAFF_MEMBERS TABLE
-- Staff members for each carwash
-- ============================================================================

CREATE TABLE IF NOT EXISTS `staff_members` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `carwash_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `position` VARCHAR(50) DEFAULT NULL,
    `hire_date` DATE DEFAULT NULL,
    `status` ENUM('active','inactive','on_leave') DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_members_user_id` (`user_id`),
    KEY `idx_staff_members_carwash_id` (`carwash_id`),
    CONSTRAINT `fk_staff_members_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. PROMOTIONS TABLE
-- Promotional codes and discounts
-- ============================================================================

CREATE TABLE IF NOT EXISTS `promotions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `carwash_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `discount_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    `discount_value` DECIMAL(10,2) NOT NULL,
    `code` VARCHAR(50) DEFAULT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `min_purchase` DECIMAL(10,2) DEFAULT NULL,
    `max_discount` DECIMAL(10,2) DEFAULT NULL,
    `usage_limit` INT(11) DEFAULT NULL,
    `usage_count` INT(11) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_promotions_carwash_id` (`carwash_id`),
    KEY `idx_promotions_code` (`code`),
    KEY `idx_promotions_start_date` (`start_date`),
    KEY `idx_promotions_is_active` (`is_active`),
    CONSTRAINT `fk_promotions_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 15. NOTIFICATIONS TABLE
-- User notifications
-- ============================================================================

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `reference_id` INT(11) DEFAULT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_id` (`user_id`),
    KEY `idx_notifications_is_read` (`is_read`),
    KEY `idx_notifications_reference` (`reference_id`, `reference_type`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 16. AUDIT_LOGS TABLE
-- System audit trail
-- ============================================================================

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `actor_id` INT(11) DEFAULT NULL,
    `actor_role` VARCHAR(50) DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` VARCHAR(50) NOT NULL,
    `old_values` LONGTEXT DEFAULT NULL,
    `new_values` LONGTEXT DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `request_id` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_actor_id` (`actor_id`),
    KEY `idx_audit_logs_action` (`action`),
    KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 17. SETTINGS TABLE
-- Application settings
-- ============================================================================

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string','number','boolean','json') DEFAULT 'string',
    `description` TEXT DEFAULT NULL,
    `is_public` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 18. SECURITY_SETTINGS TABLE
-- Security configuration
-- ============================================================================

CREATE TABLE IF NOT EXISTS `security_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_security_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 19. UI_LABELS TABLE
-- Internationalization labels
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ui_labels` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `label_key` VARCHAR(100) NOT NULL,
    `language_code` VARCHAR(10) NOT NULL DEFAULT 'en',
    `label_value` TEXT NOT NULL,
    `context` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ui_labels_key_lang` (`label_key`, `language_code`),
    KEY `idx_ui_labels_context` (`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 20. BOOKING_STATUS TABLE (Optional lookup)
-- Status lookup for bookings
-- ============================================================================

CREATE TABLE IF NOT EXISTS `booking_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `status_code` VARCHAR(50) NOT NULL,
    `status_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_booking_status_code` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICATION QUERIES
-- Run these after creating tables to verify structure
-- ============================================================================

SELECT '=== CANONICAL SCHEMA VERIFICATION ===' AS info;

SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    ROUND(DATA_LENGTH / 1024, 2) AS data_kb,
    ROUND(INDEX_LENGTH / 1024, 2) AS index_kb
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

SELECT 'Canonical schema creation completed successfully' AS result;

-- ============================================================================
-- End of create_canonical_schema.sql
-- ============================================================================
