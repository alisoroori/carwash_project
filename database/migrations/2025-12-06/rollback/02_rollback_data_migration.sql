-- =====================================================
-- Rollback Phase 2: Data Migration
-- Migration Date: 2025-12-06
-- Purpose: Undo data migration from Phase 2
-- =====================================================

USE `carwash`;

SELECT CONCAT('Rolling back Phase 2 at: ', NOW()) AS 'Rollback Status';

-- =====================================================
-- 2.1 Remove Migrated Data from carwashes
-- =====================================================

-- WARNING: This will delete records that were migrated from legacy tables
-- Only run this if you need to completely undo the migration

-- First, identify which records were migrated (those that exist in both tables)
-- and verify counts before deletion

SELECT '=== PRE-ROLLBACK VERIFICATION ===' AS 'Section';

-- Check if legacy tables exist
SELECT COUNT(*) INTO @has_profiles
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwash_profiles';

SELECT COUNT(*) INTO @has_business
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'business_profiles';

-- If legacy tables exist, show overlap
DROP PROCEDURE IF EXISTS `show_migrated_records`;
DELIMITER //
CREATE PROCEDURE `show_migrated_records`()
BEGIN
    IF @has_profiles > 0 THEN
        SELECT 
            'Migrated from carwash_profiles' AS 'Source',
            COUNT(*) AS 'Records'
        FROM carwashes cw
        INNER JOIN carwash_profiles cp ON cw.user_id = cp.user_id;
    END IF;
    
    IF @has_business > 0 THEN
        SELECT 
            'Migrated from business_profiles' AS 'Source',
            COUNT(*) AS 'Records'
        FROM carwashes cw
        INNER JOIN business_profiles bp ON cw.user_id = bp.user_id;
    END IF;
END //
DELIMITER ;

CALL show_migrated_records();
DROP PROCEDURE IF EXISTS `show_migrated_records`;

-- =====================================================
-- 2.2 Delete Migrated Records (DANGEROUS - Commented Out)
-- =====================================================

-- Uncomment ONLY if you need to fully rollback and legacy tables still exist:

/*
-- Delete carwashes that were migrated from carwash_profiles
DELETE cw FROM carwashes cw
INNER JOIN carwash_profiles cp ON cw.user_id = cp.user_id
WHERE cw.name = cp.business_name;

-- Delete carwashes that were migrated from business_profiles  
DELETE cw FROM carwashes cw
INNER JOIN business_profiles bp ON cw.user_id = bp.user_id
WHERE cw.name = bp.business_name;
*/

SELECT 'Record deletion commented out for safety.' AS 'Rollback Status';

-- =====================================================
-- 2.3 Clear UI Labels (if needed)
-- =====================================================

-- Truncate ui_labels to remove migrated label data
-- TRUNCATE TABLE ui_labels;

SELECT 'UI Labels truncate commented out for safety.' AS 'Rollback Status';

-- =====================================================
-- 2.4 Restore Original Foreign Key References
-- =====================================================

-- If services.carwash_id was updated to point to new carwashes table,
-- you may need to restore original references

-- This requires the legacy table to still exist:
/*
UPDATE services s
INNER JOIN carwashes cw ON s.carwash_id = cw.id
INNER JOIN carwash_profiles cp ON cp.user_id = cw.user_id
SET s.carwash_id = cp.id;
*/

SELECT 'FK restoration commented out for safety.' AS 'Rollback Status';

-- =====================================================
-- 2.5 Reset Rating Calculations
-- =====================================================

-- Reset ratings to NULL if they were calculated during migration
-- UPDATE carwashes SET average_rating = NULL, total_reviews = 0;

SELECT 'Rating reset commented out for safety.' AS 'Rollback Status';

-- =====================================================
-- 2.6 Verification
-- =====================================================

SELECT '=== POST-ROLLBACK COUNTS ===' AS 'Section';

SELECT 
    'carwashes' AS 'Table',
    COUNT(*) AS 'Current Records'
FROM carwashes
UNION ALL
SELECT 'ui_labels', COUNT(*) FROM ui_labels;

SELECT 'Phase 2 rollback review complete.' AS 'Rollback Status';
SELECT 'Uncomment specific rollback commands as needed and re-run.' AS 'Next Steps';

-- =====================================================
-- End of Phase 2 Rollback
-- =====================================================
