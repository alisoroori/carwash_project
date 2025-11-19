-- Migration: add commonly-missing columns detected in logs (2025-11-19)
-- Adds defensive columns to avoid "Unknown column" errors observed in logs.
-- NOTE: Requires MySQL 8.0+ for "IF NOT EXISTS" on ADD COLUMN. If using older MySQL,
-- run each ALTER TABLE only if the column is missing (manually check information_schema).

-- carwashes table: add postal_code, district, city, status, license_number, tax_number
ALTER TABLE `carwashes`
  ADD COLUMN IF NOT EXISTS `postal_code` VARCHAR(32) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `district` VARCHAR(128) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `city` VARCHAR(128) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS `license_number` VARCHAR(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `tax_number` VARCHAR(64) DEFAULT NULL;

-- users table: add workplace_status used by headers and dashboard
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `workplace_status` VARCHAR(16) DEFAULT 'open';

-- services table: ensure columns exist (price/status were referenced)
ALTER TABLE `services`
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS `price` DECIMAL(10,2) DEFAULT NULL;

-- business_profiles (legacy) ensure expected fields exist
ALTER TABLE `business_profiles`
  ADD COLUMN IF NOT EXISTS `postal_code` VARCHAR(32) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `district` VARCHAR(128) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `city` VARCHAR(128) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `logo_path` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `working_hours` TEXT DEFAULT NULL;

-- Optional: create user_vehicles table if missing (simple schema used by dashboard)
CREATE TABLE IF NOT EXISTS `user_vehicles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `make` VARCHAR(128) DEFAULT NULL,
  `model` VARCHAR(128) DEFAULT NULL,
  `year` SMALLINT UNSIGNED DEFAULT NULL,
  `plate` VARCHAR(32) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- End of migration
