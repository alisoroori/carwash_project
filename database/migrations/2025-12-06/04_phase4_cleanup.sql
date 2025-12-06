-- =====================================================
-- Phase 4: Cleanup (Post-verification, Optional)
-- Migration Date: 2025-12-06
-- Purpose: Archive deprecated tables, remove redundant columns
-- =====================================================
-- WARNING: Only run this AFTER Phase 3 verification passes successfully!
-- This phase makes destructive changes that cannot be easily undone.
-- =====================================================

-- Ensure we're using the correct database
USE `carwash`;

SET @start_time = NOW();
SELECT CONCAT('Phase 4 Cleanup Started: ', @start_time) AS 'Cleanup Status';
SELECT '⚠️  WARNING: This phase makes destructive changes!' AS 'Warning';
SELECT 'Ensure you have verified Phase 3 results before proceeding.' AS 'Prerequisite';

-- =====================================================
-- 4.1 Pre-cleanup Safety Checks
-- =====================================================

SELECT '=== PRE-CLEANUP SAFETY CHECKS ===' AS 'Section';

-- Verify all data has been migrated before archiving
DROP PROCEDURE IF EXISTS `verify_migration_complete`;
DELIMITER //
CREATE PROCEDURE `verify_migration_complete`()
BEGIN
    DECLARE can_proceed TINYINT DEFAULT 1;
    DECLARE unmigrated INT DEFAULT 0;
    
    -- Check carwash_profiles
    SELECT COUNT(*) INTO @has_profiles
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwash_profiles';
    
    IF @has_profiles > 0 THEN
        SELECT COUNT(*) INTO unmigrated
        FROM carwash_profiles cp
        LEFT JOIN carwashes cw ON cw.user_id = cp.user_id
        WHERE cw.id IS NULL;
        
        IF unmigrated > 0 THEN
            SET can_proceed = 0;
            SELECT CONCAT('❌ BLOCKING: ', unmigrated, ' records in carwash_profiles not yet migrated') AS 'Safety Check';
        ELSE
            SELECT '✓ carwash_profiles: All records migrated' AS 'Safety Check';
        END IF;
    END IF;
    
    -- Check business_profiles
    SELECT COUNT(*) INTO @has_business
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'business_profiles';
    
    IF @has_business > 0 THEN
        SELECT COUNT(*) INTO unmigrated
        FROM business_profiles bp
        LEFT JOIN carwashes cw ON cw.user_id = bp.user_id
        WHERE cw.id IS NULL;
        
        IF unmigrated > 0 THEN
            SET can_proceed = 0;
            SELECT CONCAT('❌ BLOCKING: ', unmigrated, ' records in business_profiles not yet migrated') AS 'Safety Check';
        ELSE
            SELECT '✓ business_profiles: All records migrated' AS 'Safety Check';
        END IF;
    END IF;
    
    -- Final status
    IF can_proceed = 1 THEN
        SELECT '✓ All safety checks passed - proceeding with cleanup' AS 'Safety Status';
    ELSE
        SELECT '❌ Safety checks failed - cleanup ABORTED. Run Phase 2 migration first.' AS 'Safety Status';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Migration incomplete - cleanup aborted';
    END IF;
END //
DELIMITER ;

-- Run safety check (will abort if migration incomplete)
CALL verify_migration_complete();
DROP PROCEDURE IF EXISTS `verify_migration_complete`;

-- =====================================================
-- 4.2 Archive Legacy Tables (Rename, don't drop)
-- =====================================================

SELECT '=== ARCHIVING LEGACY TABLES ===' AS 'Section';

-- Archive carwash_profiles
DROP PROCEDURE IF EXISTS `archive_table`;
DELIMITER //
CREATE PROCEDURE `archive_table`(IN p_table VARCHAR(64))
BEGIN
    DECLARE table_exists INT DEFAULT 0;
    DECLARE archive_name VARCHAR(100);
    
    SET archive_name = CONCAT(p_table, '_archived_', DATE_FORMAT(NOW(), '%Y%m%d'));
    
    SELECT COUNT(*) INTO table_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table;
    
    IF table_exists > 0 THEN
        -- Check if archive already exists
        SELECT COUNT(*) INTO @archive_exists
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = archive_name;
        
        IF @archive_exists > 0 THEN
            SELECT CONCAT('⚠ Archive ', archive_name, ' already exists - skipping') AS 'Archive Status';
        ELSE
            SET @sql = CONCAT('RENAME TABLE `', p_table, '` TO `', archive_name, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            SELECT CONCAT('✓ Archived ', p_table, ' → ', archive_name) AS 'Archive Status';
        END IF;
    ELSE
        SELECT CONCAT('ℹ Table ', p_table, ' does not exist - nothing to archive') AS 'Archive Status';
    END IF;
END //
DELIMITER ;

-- Archive legacy tables
CALL archive_table('carwash_profiles');
CALL archive_table('business_profiles');

DROP PROCEDURE IF EXISTS `archive_table`;

-- =====================================================
-- 4.3 Clean Up Helper Procedures
-- =====================================================

SELECT '=== CLEANING UP HELPER PROCEDURES ===' AS 'Section';

DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;
DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;
DROP PROCEDURE IF EXISTS `migrate_carwash_profiles`;
DROP PROCEDURE IF EXISTS `migrate_business_profiles`;
DROP PROCEDURE IF EXISTS `fix_service_carwash_references`;
DROP PROCEDURE IF EXISTS `fix_review_carwash_references`;
DROP PROCEDURE IF EXISTS `check_unmigrated_data`;
DROP PROCEDURE IF EXISTS `verify_migration_complete`;
DROP PROCEDURE IF EXISTS `archive_table`;

SELECT '✓ Cleaned up temporary stored procedures' AS 'Cleanup Status';

-- =====================================================
-- 4.4 Optimize Tables
-- =====================================================

SELECT '=== OPTIMIZING TABLES ===' AS 'Section';

-- Optimize main tables after migration
OPTIMIZE TABLE carwashes;
OPTIMIZE TABLE services;
OPTIMIZE TABLE bookings;
OPTIMIZE TABLE reviews;
OPTIMIZE TABLE users;
OPTIMIZE TABLE ui_labels;

SELECT '✓ Tables optimized' AS 'Optimization Status';

-- =====================================================
-- 4.5 Update Statistics
-- =====================================================

SELECT '=== UPDATING STATISTICS ===' AS 'Section';

ANALYZE TABLE carwashes;
ANALYZE TABLE services;
ANALYZE TABLE bookings;
ANALYZE TABLE reviews;
ANALYZE TABLE users;

SELECT '✓ Table statistics updated' AS 'Statistics Status';

-- =====================================================
-- 4.6 Final Summary
-- =====================================================

SELECT '=== CLEANUP COMPLETE ===' AS 'Section';

-- List archived tables
SELECT 
    TABLE_NAME AS 'Archived Table',
    TABLE_ROWS AS 'Rows',
    ROUND(DATA_LENGTH / 1024 / 1024, 2) AS 'Size MB',
    CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE '%_archived_%'
ORDER BY TABLE_NAME;

-- Final table summary
SELECT 
    TABLE_NAME,
    TABLE_ROWS AS 'Rows',
    ROUND(DATA_LENGTH / 1024 / 1024, 2) AS 'Data MB',
    ROUND(INDEX_LENGTH / 1024 / 1024, 2) AS 'Index MB'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME NOT LIKE '%_archived_%'
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY DATA_LENGTH DESC
LIMIT 15;

SELECT CONCAT('Phase 4 Cleanup Completed: ', NOW(), ' (Duration: ', TIMEDIFF(NOW(), @start_time), ')') AS 'Cleanup Status';

-- =====================================================
-- 4.7 Post-Cleanup Notes
-- =====================================================

SELECT '=== POST-CLEANUP NOTES ===' AS 'Section';

SELECT 'Archived tables can be safely dropped after 30 days if no issues arise.' AS 'Note 1';
SELECT 'Run: DROP TABLE carwash_profiles_archived_YYYYMMDD;' AS 'Drop Command Example';
SELECT 'Update any remaining PHP code still referencing legacy tables.' AS 'Note 2';
SELECT 'Test all admin panel functionality thoroughly.' AS 'Note 3';

-- =====================================================
-- End of Phase 4
-- =====================================================
