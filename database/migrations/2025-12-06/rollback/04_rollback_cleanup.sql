-- =====================================================
-- Rollback Phase 4: Cleanup
-- Migration Date: 2025-12-06
-- Purpose: Restore archived tables from Phase 4
-- =====================================================

USE `carwash`;

SELECT CONCAT('Rolling back Phase 4 at: ', NOW()) AS 'Rollback Status';

-- =====================================================
-- 4.1 Find and Restore Archived Tables
-- =====================================================

SELECT '=== FINDING ARCHIVED TABLES ===' AS 'Section';

-- List all archived tables
SELECT 
    TABLE_NAME AS 'Archived Table',
    TABLE_ROWS AS 'Rows',
    CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE '%_archived_%'
ORDER BY CREATE_TIME DESC;

-- =====================================================
-- 4.2 Restore carwash_profiles from archive
-- =====================================================

DROP PROCEDURE IF EXISTS `restore_archived_table`;
DELIMITER //
CREATE PROCEDURE `restore_archived_table`(IN p_original_name VARCHAR(64))
BEGIN
    DECLARE archive_name VARCHAR(100);
    DECLARE found_archive VARCHAR(100) DEFAULT NULL;
    
    -- Find the most recent archive
    SELECT TABLE_NAME INTO found_archive
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME LIKE CONCAT(p_original_name, '_archived_%')
    ORDER BY CREATE_TIME DESC
    LIMIT 1;
    
    IF found_archive IS NOT NULL THEN
        -- Check if original table now exists (would block restore)
        SELECT COUNT(*) INTO @original_exists
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_original_name;
        
        IF @original_exists > 0 THEN
            SELECT CONCAT('⚠ Cannot restore: ', p_original_name, ' already exists. Drop it first.') AS 'Restore Status';
        ELSE
            SET @sql = CONCAT('RENAME TABLE `', found_archive, '` TO `', p_original_name, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            SELECT CONCAT('✓ Restored ', found_archive, ' → ', p_original_name) AS 'Restore Status';
        END IF;
    ELSE
        SELECT CONCAT('ℹ No archive found for ', p_original_name) AS 'Restore Status';
    END IF;
END //
DELIMITER ;

-- Restore legacy tables
CALL restore_archived_table('carwash_profiles');
CALL restore_archived_table('business_profiles');

DROP PROCEDURE IF EXISTS `restore_archived_table`;

-- =====================================================
-- 4.3 Verify Restoration
-- =====================================================

SELECT '=== VERIFICATION ===' AS 'Section';

SELECT 
    TABLE_NAME,
    TABLE_ROWS AS 'Rows',
    CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('carwash_profiles', 'business_profiles')
ORDER BY TABLE_NAME;

SELECT 'Phase 4 rollback complete.' AS 'Rollback Status';
SELECT 'Legacy tables restored from archive (if archives existed).' AS 'Result';

-- =====================================================
-- End of Phase 4 Rollback
-- =====================================================
