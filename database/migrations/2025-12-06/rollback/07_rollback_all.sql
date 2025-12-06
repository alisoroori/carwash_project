-- ============================================================================
-- CarWash Project - Rollback Script
-- Version: 1.0.0
-- Date: 2025-12-05
-- Description: Reverses all migration changes (use with caution!)
-- WARNING: Some operations are DESTRUCTIVE - test on staging first
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- ROLLBACK PHASE 1: Revert data migrations
-- ============================================================================

-- Rollback: Clear booking numbers (non-destructive)
-- UPDATE bookings SET booking_number = NULL WHERE booking_number LIKE 'BK%';

-- Rollback: Clear ui_labels (non-destructive to application)
-- TRUNCATE TABLE ui_labels;

-- Rollback: Reset carwash ratings
-- UPDATE carwashes SET rating_average = 0.00, rating_count = 0;

-- ============================================================================
-- ROLLBACK PHASE 2: Revert ALTER TABLE changes
-- Only drop columns that were added by this migration
-- ============================================================================

-- Note: These are commented out for safety. Uncomment only if needed.

-- Rollback users columns
-- ALTER TABLE `users` DROP COLUMN IF EXISTS `profile_image_path`;
-- ALTER TABLE `users` DROP COLUMN IF EXISTS `email_verified_at`;
-- ALTER TABLE `users` DROP COLUMN IF EXISTS `last_login_at`;

-- Rollback carwashes columns
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_name`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_phone`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_birth_date`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `tax_number`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `license_number`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `district`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `profile_image_path`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `rating_average`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `rating_count`;

-- Rollback bookings columns
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `booking_number`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `customer_name`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `customer_phone`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `review_status`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `payment_status`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `payment_method`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `completed_at`;

-- Rollback services columns
-- ALTER TABLE `services` DROP COLUMN IF EXISTS `category`;
-- ALTER TABLE `services` DROP COLUMN IF EXISTS `sort_order`;

-- Rollback payments columns
-- ALTER TABLE `payments` DROP COLUMN IF EXISTS `booking_id`;
-- ALTER TABLE `payments` DROP COLUMN IF EXISTS `total_amount`;
-- ALTER TABLE `payments` DROP COLUMN IF EXISTS `status`;

-- Rollback user_vehicles columns
-- ALTER TABLE `user_vehicles` DROP COLUMN IF EXISTS `vehicle_type`;
-- ALTER TABLE `user_vehicles` DROP COLUMN IF EXISTS `is_default`;

-- ============================================================================
-- ROLLBACK PHASE 3: Delete migrated data (DESTRUCTIVE!)
-- Only use if you need to completely reverse the migration
-- ============================================================================

-- Delete carwashes that were migrated from carwash_profiles
-- WARNING: This will delete carwash records!
-- DELETE FROM carwashes 
-- WHERE id IN (
--     SELECT c.id FROM (SELECT * FROM carwashes) c
--     INNER JOIN carwash_profiles cp ON c.user_id = cp.user_id
--     WHERE c.created_at >= '2025-12-05'
-- );

-- ============================================================================
-- ROLLBACK PHASE 4: Drop new tables (DESTRUCTIVE!)
-- Only uncomment if you need to completely remove new tables
-- ============================================================================

-- DROP TABLE IF EXISTS `ui_labels`;
-- DROP TABLE IF EXISTS `migration_log`;

-- ============================================================================
-- VERIFICATION: Check rollback status
-- ============================================================================

SELECT '=== ROLLBACK VERIFICATION ===' AS info;

-- Check current table states
SELECT 
    'users' AS table_name,
    COUNT(*) AS record_count
FROM users
UNION ALL
SELECT 
    'carwashes' AS table_name,
    COUNT(*) AS record_count
FROM carwashes
UNION ALL
SELECT 
    'bookings' AS table_name,
    COUNT(*) AS record_count
FROM bookings
UNION ALL
SELECT 
    'ui_labels' AS table_name,
    COUNT(*) AS record_count
FROM ui_labels;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Rollback script completed. Review commented sections for destructive operations.' AS result;

-- ============================================================================
-- End of rollback.sql
-- ============================================================================
