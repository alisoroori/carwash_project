-- =====================================================
-- Rollback Phase 1: Schema Preparation
-- Migration Date: 2025-12-06
-- Purpose: Undo schema changes from Phase 1
-- =====================================================

USE `carwash`;

SELECT CONCAT('Rolling back Phase 1 at: ', NOW()) AS 'Rollback Status';

-- =====================================================
-- 1.1 Drop UI Labels Table
-- =====================================================

-- Only drop if empty or if you want to remove all translations
-- DROP TABLE IF EXISTS `ui_labels`;
SELECT 'UI Labels table preserved - drop manually if needed: DROP TABLE IF EXISTS ui_labels;' AS 'Note';

-- =====================================================
-- 1.2 Remove Added Columns from carwashes
-- (Only removes columns added by Phase 1, not original columns)
-- =====================================================

-- Uncomment these if you need to remove specific columns:
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_name`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_phone`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_birth_date`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `tax_number`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `license_number`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `profile_image_path`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `is_active`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `is_verified`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `verified_at`;

SELECT 'Column rollback commented out for safety. Edit script to enable.' AS 'Column Rollback';

-- =====================================================
-- 1.3 Remove Added Columns from other tables
-- =====================================================

-- ALTER TABLE `users` DROP COLUMN IF EXISTS `workplace_status`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `customer_name`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `customer_phone`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `notes`;

-- =====================================================
-- 1.4 Drop Indexes Added by Phase 1
-- =====================================================

-- DROP INDEX IF EXISTS `idx_booking_date_status` ON `bookings`;
-- DROP INDEX IF EXISTS `idx_carwash_date` ON `bookings`;
-- DROP INDEX IF EXISTS `idx_user_status` ON `bookings`;
-- DROP INDEX IF EXISTS `idx_carwash_active` ON `services`;
-- DROP INDEX IF EXISTS `idx_carwash_approved` ON `reviews`;
-- DROP INDEX IF EXISTS `idx_settlement` ON `payments`;

SELECT 'Index rollback commented out for safety. Edit script to enable.' AS 'Index Rollback';

-- =====================================================
-- 1.5 Drop Tables Created by Phase 1 (if empty)
-- =====================================================

-- Note: These are safe to drop only if they were empty before migration

-- Check counts before dropping
SELECT 'booking_status' AS 'Table', COUNT(*) AS 'Records' FROM booking_status
UNION ALL
SELECT 'audit_logs', COUNT(*) FROM audit_logs
UNION ALL
SELECT 'favorites', COUNT(*) FROM favorites
UNION ALL
SELECT 'time_slots', COUNT(*) FROM time_slots
UNION ALL
SELECT 'user_vehicles', COUNT(*) FROM user_vehicles;

-- Uncomment to drop (only if you're sure they weren't in use):
-- DROP TABLE IF EXISTS `booking_status`;
-- DROP TABLE IF EXISTS `ui_labels`;

SELECT 'Table drops commented out for safety. Review counts above before dropping.' AS 'Table Rollback';

-- =====================================================
-- 1.6 Drop Helper Procedures
-- =====================================================

DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;
DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;

SELECT 'Phase 1 rollback review complete.' AS 'Rollback Status';
SELECT 'Uncomment specific rollback commands as needed and re-run.' AS 'Next Steps';

-- =====================================================
-- End of Phase 1 Rollback
-- =====================================================
