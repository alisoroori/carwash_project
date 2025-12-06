-- ============================================================================
-- CarWash Project - Database Schema Creation Script
-- Version: 1.0.0
-- Date: 2025-12-05
-- Description: Creates all required tables with proper structure
-- IMPORTANT: Run on staging first, verify, then run on production
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. UI Labels / Translations Table (for separating UI text from schema)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `ui_labels` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `label_key` VARCHAR(100) NOT NULL COMMENT 'Technical identifier (snake_case)',
    `language_code` VARCHAR(5) NOT NULL DEFAULT 'tr' COMMENT 'ISO 639-1 code',
    `label_value` VARCHAR(500) NOT NULL COMMENT 'Translated text',
    `context` VARCHAR(100) NULL COMMENT 'Form/page context',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_label_lang` (`label_key`, `language_code`),
    INDEX `idx_context` (`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='UI labels and translations - keeps technical identifiers separate from display text';

-- ============================================================================
-- 2. Users Table (canonical definition)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'carwash', 'admin', 'staff') DEFAULT 'customer',
    `name` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `profile_image_path` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive', 'banned', 'pending') DEFAULT 'active',
    `email_verified_at` TIMESTAMP NULL,
    `login_attempts` INT(11) DEFAULT 0,
    `last_login_attempt` DATETIME NULL,
    `last_login_at` DATETIME NULL,
    `password_reset_token` VARCHAR(100) NULL,
    `password_reset_expires` DATETIME NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User accounts for customers, carwash owners, admins, and staff';

-- ============================================================================
-- 3. Carwashes Table (canonical - replaces carwash_profiles)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `carwashes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL COMMENT 'Owner user account',
    `name` VARCHAR(150) NOT NULL COMMENT 'Business name',
    `slug` VARCHAR(150) NULL COMMENT 'URL-friendly name',
    `description` TEXT NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `district` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,
    `latitude` DECIMAL(10,8) NULL,
    `longitude` DECIMAL(11,8) NULL,
    `phone` VARCHAR(20) NULL,
    `mobile_phone` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `website` VARCHAR(255) NULL,
    `owner_name` VARCHAR(100) NULL,
    `owner_phone` VARCHAR(20) NULL,
    `owner_birth_date` DATE NULL,
    `tax_number` VARCHAR(50) NULL,
    `license_number` VARCHAR(50) NULL,
    `logo_path` VARCHAR(255) NULL,
    `profile_image_path` VARCHAR(255) NULL,
    `cover_image_path` VARCHAR(255) NULL,
    `working_hours` JSON NULL COMMENT 'Structured working hours',
    `social_media` JSON NULL COMMENT 'Social media links',
    `amenities` JSON NULL COMMENT 'Available amenities',
    `status` ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending',
    `is_featured` TINYINT(1) DEFAULT 0,
    `rating_average` DECIMAL(3,2) DEFAULT 0.00,
    `rating_count` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_city_district` (`city`, `district`),
    INDEX `idx_status` (`status`),
    INDEX `idx_is_featured` (`is_featured`),
    CONSTRAINT `fk_carwashes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Car wash business profiles';

-- ============================================================================
-- 4. Services Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `carwash_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `category` VARCHAR(50) NULL COMMENT 'e.g., exterior, interior, full, premium',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `duration` INT(11) NULL COMMENT 'Duration in minutes',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_carwash_id` (`carwash_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_category` (`category`),
    CONSTRAINT `fk_services_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Services offered by each carwash';

-- ============================================================================
-- 5. User Vehicles Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `user_vehicles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `brand` VARCHAR(50) NULL,
    `model` VARCHAR(50) NULL,
    `year` INT(4) NULL,
    `color` VARCHAR(30) NULL,
    `license_plate` VARCHAR(20) NULL,
    `vehicle_type` ENUM('sedan', 'suv', 'hatchback', 'pickup', 'van', 'motorcycle', 'other') DEFAULT 'sedan',
    `image_path` VARCHAR(255) NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_license_plate` (`license_plate`),
    CONSTRAINT `fk_vehicles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer vehicles';

-- ============================================================================
-- 6. Bookings Table (Wash Reservations)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_number` VARCHAR(20) NULL COMMENT 'Human-readable booking reference',
    `user_id` INT(11) NULL COMMENT 'Customer user ID',
    `carwash_id` INT(11) NULL,
    `service_id` INT(11) NULL,
    `vehicle_id` INT(11) NULL,
    `time_slot_id` INT(11) NULL,
    `booking_date` DATE NULL,
    `booking_time` TIME NULL,
    `end_time` TIME NULL,
    `customer_name` VARCHAR(100) NULL COMMENT 'For guest bookings',
    `customer_phone` VARCHAR(20) NULL,
    `customer_email` VARCHAR(100) NULL,
    `vehicle_type` VARCHAR(50) NULL,
    `vehicle_plate` VARCHAR(20) NULL,
    `vehicle_model` VARCHAR(100) NULL,
    `vehicle_color` VARCHAR(30) NULL,
    `status` ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    `review_status` ENUM('pending', 'reviewed') DEFAULT 'pending',
    `total_price` DECIMAL(10,2) NULL,
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    `payment_status` ENUM('pending', 'paid', 'refunded', 'failed') DEFAULT 'pending',
    `payment_method` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `special_requests` TEXT NULL,
    `cancellation_reason` TEXT NULL,
    `cancelled_at` TIMESTAMP NULL,
    `confirmed_at` TIMESTAMP NULL,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_booking_number` (`booking_number`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_carwash_id` (`carwash_id`),
    INDEX `idx_service_id` (`service_id`),
    INDEX `idx_booking_date` (`booking_date`),
    INDEX `idx_status` (`status`),
    INDEX `idx_payment_status` (`payment_status`),
    CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `user_vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer booking/reservation records';

-- ============================================================================
-- 7. Booking Services (for multi-service bookings)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `booking_services` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `service_id` INT(11) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `quantity` INT(11) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_booking_id` (`booking_id`),
    INDEX `idx_service_id` (`service_id`),
    CONSTRAINT `fk_booking_services_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_booking_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Services included in each booking (for itemized billing)';

-- ============================================================================
-- 8. Reviews Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `carwash_id` INT(11) NOT NULL,
    `booking_id` INT(11) NULL,
    `rating` INT(11) NOT NULL COMMENT '1-5 stars',
    `title` VARCHAR(100) NULL,
    `comment` TEXT NULL,
    `response` TEXT NULL COMMENT 'Business owner response',
    `responded_at` TIMESTAMP NULL,
    `is_verified` TINYINT(1) DEFAULT 0,
    `is_visible` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_carwash_id` (`carwash_id`),
    INDEX `idx_booking_id` (`booking_id`),
    INDEX `idx_rating` (`rating`),
    UNIQUE KEY `uk_user_booking` (`user_id`, `booking_id`),
    CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer reviews and ratings';

-- ============================================================================
-- 9. Payments Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NULL,
    `user_id` INT(11) NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `total_amount` DECIMAL(10,2) NULL,
    `currency` VARCHAR(3) DEFAULT 'TRY',
    `payment_method` VARCHAR(50) NULL COMMENT 'card, cash, bank_transfer',
    `payment_gateway` VARCHAR(50) NULL,
    `transaction_id` VARCHAR(100) NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
    `paid_at` TIMESTAMP NULL,
    `refunded_at` TIMESTAMP NULL,
    `refund_amount` DECIMAL(10,2) NULL,
    `notes` TEXT NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_booking_id` (`booking_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_transaction_id` (`transaction_id`),
    CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Payment transactions';

-- ============================================================================
-- 10. Favorites Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `carwash_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_carwash` (`user_id`, `carwash_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_carwash_id` (`carwash_id`),
    CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_favorites_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer favorite carwashes';

-- ============================================================================
-- 11. Audit Logs Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `actor_id` INT(11) NULL,
    `actor_role` VARCHAR(50) NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` VARCHAR(50) NOT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `request_id` VARCHAR(50) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_actor_id` (`actor_id`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for security and compliance';

-- ============================================================================
-- 12. Booking Status Lookup Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `booking_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `label` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `color` VARCHAR(20) NULL COMMENT 'UI color class or hex',
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Booking status reference data';

-- ============================================================================
-- 13. Time Slots Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `time_slots` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `carwash_id` INT(11) NOT NULL,
    `day_of_week` TINYINT(1) NOT NULL COMMENT '0=Sunday, 6=Saturday',
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `max_bookings` INT(11) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_carwash_day` (`carwash_id`, `day_of_week`),
    CONSTRAINT `fk_timeslots_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Available time slots for each carwash';

-- ============================================================================
-- 14. Staff Members Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `staff_members` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL COMMENT 'Link to users table if staff has login',
    `carwash_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `position` VARCHAR(50) NULL,
    `hire_date` DATE NULL,
    `status` ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_carwash_id` (`carwash_id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `fk_staff_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Staff members per carwash location';

-- ============================================================================
-- 15. Security Settings Table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `security_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Application security configuration';

-- ============================================================================
-- Seed: Default Booking Statuses
-- ============================================================================
INSERT IGNORE INTO `booking_status` (`code`, `label`, `description`, `color`, `sort_order`) VALUES
('pending', 'Bekliyor', 'Booking awaiting confirmation', 'yellow', 1),
('confirmed', 'Onaylandı', 'Booking confirmed by carwash', 'blue', 2),
('in_progress', 'İşlemde', 'Service in progress', 'purple', 3),
('completed', 'Tamamlandı', 'Service completed', 'green', 4),
('cancelled', 'İptal', 'Booking cancelled', 'red', 5),
('no_show', 'Gelmedi', 'Customer did not show up', 'gray', 6);

-- ============================================================================
-- Seed: Default Security Settings
-- ============================================================================
INSERT IGNORE INTO `security_settings` (`setting_key`, `setting_value`, `description`) VALUES
('max_login_attempts', '5', 'Maximum failed login attempts before lockout'),
('login_timeout_minutes', '15', 'Lockout duration after max attempts'),
('password_min_length', '8', 'Minimum password length'),
('session_lifetime_minutes', '30', 'Session timeout in minutes'),
('require_2fa_for_admin', 'false', 'Require 2FA for admin accounts');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- End of create_tables.sql
-- ============================================================================
