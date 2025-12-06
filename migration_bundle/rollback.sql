-- ============================================================================
-- CarWash Project - Rollback Script
-- Version: 1.0.0
-- Date: 2025-12-06
-- Description: Reverses all migration changes
-- Danger Level: HIGH - Contains destructive operations (marked clearly)
-- IMPORTANT: Review each section before executing
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- SECTION 1: SAFE ROLLBACKS (Non-destructive)
-- These can be safely executed to revert data changes
-- ============================================================================

SELECT '========================================' AS separator;
SELECT 'SAFE ROLLBACKS (Non-Destructive)' AS info;
SELECT '========================================' AS separator;

-- ============================================================================
-- 1.1 Rollback booking_number generation
-- Sets booking_number back to NULL for auto-generated ones
-- ============================================================================

-- DRY-RUN: Preview affected rows
SELECT 'Bookings with auto-generated numbers (BK prefix):' AS preview;
SELECT COUNT(*) AS count FROM bookings WHERE booking_number LIKE 'BK%';

-- ROLLBACK (uncomment to execute):
-- UPDATE bookings SET booking_number = NULL WHERE booking_number LIKE 'BK%';
-- SELECT ROW_COUNT() AS booking_numbers_cleared;

-- ============================================================================
-- 1.2 Rollback ui_labels population
-- Removes labels added by migration (preserves pre-existing labels)
-- ============================================================================

-- DRY-RUN: Preview labels to remove
SELECT 'UI labels added by migration:' AS preview;
SELECT COUNT(*) AS count FROM ui_labels 
WHERE label_key IN (
    'customer_name', 'customer_email', 'customer_password', 'customer_phone',
    'register_button', 'business_name', 'owner_name', 'owner_phone',
    'tax_number', 'license_number', 'birth_date', 'business_address',
    'city', 'district', 'profile_image', 'logo_image', 'tc_kimlik',
    'exterior_wash', 'interior_wash', 'full_wash', 'detailing',
    'booking_location', 'booking_service', 'booking_vehicle', 'booking_date',
    'booking_time', 'booking_notes', 'create_reservation',
    'status_pending', 'status_confirmed', 'status_in_progress', 'status_completed',
    'status_cancelled', 'status_no_show',
    'review_rating', 'review_comment', 'review_submit',
    'vehicle_brand', 'vehicle_model', 'vehicle_plate', 'vehicle_year', 'vehicle_color',
    'payment_cash', 'payment_card', 'payment_online', 'payment_total'
);

-- ROLLBACK (uncomment to execute):
-- DELETE FROM ui_labels WHERE label_key IN (
--     'customer_name', 'customer_email', 'customer_password', 'customer_phone',
--     'register_button', 'business_name', 'owner_name', 'owner_phone',
--     'tax_number', 'license_number', 'birth_date', 'business_address',
--     'city', 'district', 'profile_image', 'logo_image', 'tc_kimlik',
--     'exterior_wash', 'interior_wash', 'full_wash', 'detailing',
--     'booking_location', 'booking_service', 'booking_vehicle', 'booking_date',
--     'booking_time', 'booking_notes', 'create_reservation',
--     'status_pending', 'status_confirmed', 'status_in_progress', 'status_completed',
--     'status_cancelled', 'status_no_show',
--     'review_rating', 'review_comment', 'review_submit',
--     'vehicle_brand', 'vehicle_model', 'vehicle_plate', 'vehicle_year', 'vehicle_color',
--     'payment_cash', 'payment_card', 'payment_online', 'payment_total'
-- );
-- SELECT ROW_COUNT() AS ui_labels_removed;

-- ============================================================================
-- 1.3 Rollback carwash rating statistics
-- Resets rating values to zero
-- ============================================================================

-- DRY-RUN: Preview affected carwashes
SELECT 'Carwashes with ratings to reset:' AS preview;
SELECT COUNT(*) AS count FROM carwashes WHERE rating > 0 OR rating_average > 0;

-- ROLLBACK (uncomment to execute):
-- UPDATE carwashes SET rating = 0.00, rating_average = 0.00, rating_count = 0, total_reviews = 0;
-- SELECT ROW_COUNT() AS carwash_ratings_reset;

-- ============================================================================
-- 1.4 Rollback customer info population in bookings
-- Note: This can't perfectly restore original NULL values
-- ============================================================================

-- DRY-RUN: Preview bookings with customer info
SELECT 'Bookings with customer info:' AS preview;
SELECT COUNT(*) AS count FROM bookings WHERE customer_name IS NOT NULL;

-- ROLLBACK (uncomment to execute):
-- UPDATE bookings SET customer_name = NULL, customer_phone = NULL;
-- SELECT ROW_COUNT() AS booking_customer_info_cleared;

-- ============================================================================
-- 1.5 Rollback service_categories population
-- ============================================================================

-- DRY-RUN: Preview service categories
SELECT 'Service categories to remove:' AS preview;
SELECT * FROM service_categories WHERE name IN ('basic', 'standard', 'premium', 'deluxe');

-- ROLLBACK (uncomment to execute):
-- DELETE FROM service_categories WHERE name IN ('basic', 'standard', 'premium', 'deluxe');
-- SELECT ROW_COUNT() AS service_categories_removed;

-- ============================================================================
-- SECTION 2: DATA MIGRATION ROLLBACKS
-- Removes data migrated from legacy tables
-- WARNING: Only use if you still have legacy tables intact
-- ============================================================================

SELECT '========================================' AS separator;
SELECT 'DATA MIGRATION ROLLBACKS' AS info;
SELECT '========================================' AS separator;

-- ============================================================================
-- 2.1 Rollback carwash_profiles → carwashes migration
-- Deletes carwashes that were migrated from carwash_profiles
-- ============================================================================

-- DRY-RUN: Preview carwashes to delete
SELECT 'Carwashes migrated from carwash_profiles:' AS preview;
SELECT c.id, c.name, c.user_id, 'TO_DELETE' AS action
FROM carwashes c
INNER JOIN carwash_profiles cp ON c.user_id = cp.user_id
WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);

-- ROLLBACK (uncomment to execute - DESTRUCTIVE):
-- DELETE c FROM carwashes c
-- INNER JOIN carwash_profiles cp ON c.user_id = cp.user_id
-- WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);
-- SELECT ROW_COUNT() AS carwashes_deleted;

-- ============================================================================
-- 2.2 Rollback vehicles → user_vehicles migration
-- Deletes user_vehicles that were migrated from vehicles
-- ============================================================================

-- DRY-RUN: Preview user_vehicles to delete
SELECT 'User vehicles migrated from vehicles table:' AS preview;
SELECT uv.id, uv.user_id, uv.brand, uv.model, 'TO_DELETE' AS action
FROM user_vehicles uv
INNER JOIN vehicles v ON uv.user_id = v.user_id AND uv.license_plate = v.license_plate;

-- ROLLBACK (uncomment to execute - DESTRUCTIVE):
-- DELETE uv FROM user_vehicles uv
-- INNER JOIN vehicles v ON uv.user_id = v.user_id AND uv.license_plate = v.license_plate;
-- SELECT ROW_COUNT() AS user_vehicles_deleted;

-- ============================================================================
-- 2.3 Rollback customer_profiles → user_profiles migration
-- Deletes user_profiles that were migrated from customer_profiles
-- ============================================================================

-- DRY-RUN: Preview user_profiles to delete
SELECT 'User profiles migrated from customer_profiles:' AS preview;
SELECT up.id, up.user_id, up.city, 'TO_DELETE' AS action
FROM user_profiles up
INNER JOIN customer_profiles cp ON up.user_id = cp.user_id;

-- ROLLBACK (uncomment to execute - DESTRUCTIVE):
-- DELETE up FROM user_profiles up
-- INNER JOIN customer_profiles cp ON up.user_id = cp.user_id;
-- SELECT ROW_COUNT() AS user_profiles_deleted;

-- ============================================================================
-- SECTION 3: COLUMN ROLLBACKS
-- Drops columns that were added by alter_tables_safe.sql
-- WARNING: This will DELETE DATA in those columns
-- ============================================================================

SELECT '========================================' AS separator;
SELECT 'COLUMN ROLLBACKS (DESTRUCTIVE!)' AS info;
SELECT '========================================' AS separator;

-- ============================================================================
-- 3.1 Rollback users columns
-- ============================================================================

-- ROLLBACK (uncomment to execute - DESTRUCTIVE):
-- ALTER TABLE `users` DROP COLUMN IF EXISTS `profile_image_path`;
-- ALTER TABLE `users` DROP COLUMN IF EXISTS `email_verified_at`;
-- ALTER TABLE `users` DROP COLUMN IF EXISTS `last_login_at`;
-- SELECT 'users columns dropped' AS result;

-- ============================================================================
-- 3.2 Rollback carwashes columns
-- ============================================================================

-- ROLLBACK (uncomment to execute - DESTRUCTIVE):
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_name`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_phone`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `owner_birth_date`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `tax_number`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `license_number`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `district`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `mobile_phone`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `tc_kimlik`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `logo_path`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `profile_image_path`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `certificate_path`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `working_hours`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `social_media`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `exterior_price`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `interior_price`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `detailing_price`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `rating_average`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `rating_count`;
-- ALTER TABLE `carwashes` DROP COLUMN IF EXISTS `status`;
-- SELECT 'carwashes columns dropped' AS result;

-- ============================================================================
-- 3.3 Rollback bookings columns
-- ============================================================================

-- ROLLBACK (uncomment to execute - DESTRUCTIVE):
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `booking_number`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `vehicle_id`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `customer_name`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `customer_phone`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `review_status`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `payment_status`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `payment_method`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `cancellation_reason`;
-- ALTER TABLE `bookings` DROP COLUMN IF EXISTS `completed_at`;
-- SELECT 'bookings columns dropped' AS result;

-- ============================================================================
-- 3.4 Rollback services columns
-- ============================================================================

-- ROLLBACK (uncomment to execute - DESTRUCTIVE):
-- ALTER TABLE `services` DROP COLUMN IF EXISTS `category_id`;
-- ALTER TABLE `services` DROP COLUMN IF EXISTS `category`;
-- ALTER TABLE `services` DROP COLUMN IF EXISTS `sort_order`;
-- ALTER TABLE `services` DROP COLUMN IF EXISTS `features`;
-- SELECT 'services columns dropped' AS result;

-- ============================================================================
-- 3.5 Rollback other table columns
-- ============================================================================

-- ROLLBACK (uncomment to execute - DESTRUCTIVE):
-- ALTER TABLE `payments` DROP COLUMN IF EXISTS `total_amount`;
-- ALTER TABLE `payments` DROP COLUMN IF EXISTS `receipt_url`;
-- ALTER TABLE `reviews` DROP COLUMN IF EXISTS `title`;
-- ALTER TABLE `reviews` DROP COLUMN IF EXISTS `response`;
-- ALTER TABLE `reviews` DROP COLUMN IF EXISTS `responded_at`;
-- ALTER TABLE `user_vehicles` DROP COLUMN IF EXISTS `vehicle_type`;
-- ALTER TABLE `user_vehicles` DROP COLUMN IF EXISTS `is_default`;
-- ALTER TABLE `user_vehicles` DROP COLUMN IF EXISTS `notes`;
-- ALTER TABLE `user_profiles` DROP COLUMN IF EXISTS `phone`;
-- ALTER TABLE `user_profiles` DROP COLUMN IF EXISTS `home_phone`;
-- ALTER TABLE `user_profiles` DROP COLUMN IF EXISTS `national_id`;
-- ALTER TABLE `user_profiles` DROP COLUMN IF EXISTS `driver_license`;
-- ALTER TABLE `user_profiles` DROP COLUMN IF EXISTS `profile_image`;
-- SELECT 'other columns dropped' AS result;

-- ============================================================================
-- SECTION 4: INDEX ROLLBACKS
-- Drops indexes that were added
-- ============================================================================

SELECT '========================================' AS separator;
SELECT 'INDEX ROLLBACKS' AS info;
SELECT '========================================' AS separator;

-- ROLLBACK (uncomment to execute):
-- DROP INDEX IF EXISTS `idx_bookings_booking_number` ON `bookings`;
-- DROP INDEX IF EXISTS `idx_carwashes_status` ON `carwashes`;
-- DROP INDEX IF EXISTS `idx_users_is_active` ON `users`;
-- SELECT 'indexes dropped' AS result;

-- ============================================================================
-- SECTION 5: TABLE CLEANUP ROLLBACKS
-- Restores legacy tables from backup
-- Only applicable if cleanup phase was executed
-- ============================================================================

SELECT '========================================' AS separator;
SELECT 'TABLE CLEANUP ROLLBACKS' AS info;
SELECT '========================================' AS separator;

-- If legacy tables were renamed to _backup_YYYYMMDD, restore them:
-- RENAME TABLE `carwash_profiles_backup_20251206` TO `carwash_profiles`;
-- RENAME TABLE `vehicles_backup_20251206` TO `vehicles`;
-- RENAME TABLE `customer_profiles_backup_20251206` TO `customer_profiles`;
-- SELECT 'legacy tables restored' AS result;

-- ============================================================================
-- SECTION 6: DROP NEW TABLES (EXTREME CAUTION!)
-- Only use if you need to completely remove new canonical tables
-- ============================================================================

SELECT '========================================' AS separator;
SELECT 'DROP NEW TABLES (EXTREME CAUTION!)' AS info;
SELECT '========================================' AS separator;

-- WARNING: This will permanently delete all data in these tables!
-- Only execute if you have a full backup and need to start over

-- DROP TABLE IF EXISTS `booking_status`;
-- DROP TABLE IF EXISTS `service_categories`;
-- DROP TABLE IF EXISTS `ui_labels`;
-- DROP TABLE IF EXISTS `booking_services`;
-- SELECT 'new tables dropped' AS result;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT '========================================' AS separator;
SELECT 'ROLLBACK VERIFICATION' AS info;
SELECT '========================================' AS separator;

SELECT 
    'Current table counts:' AS info;

SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL SELECT 'carwashes', COUNT(*) FROM carwashes
UNION ALL SELECT 'carwash_profiles', COUNT(*) FROM carwash_profiles
UNION ALL SELECT 'bookings', COUNT(*) FROM bookings
UNION ALL SELECT 'user_vehicles', COUNT(*) FROM user_vehicles
UNION ALL SELECT 'vehicles', COUNT(*) FROM vehicles
UNION ALL SELECT 'user_profiles', COUNT(*) FROM user_profiles
UNION ALL SELECT 'customer_profiles', COUNT(*) FROM customer_profiles
UNION ALL SELECT 'ui_labels', COUNT(*) FROM ui_labels;

SELECT 'Rollback script completed. Review commented sections for execution.' AS result;

-- ============================================================================
-- End of rollback.sql
-- ============================================================================
