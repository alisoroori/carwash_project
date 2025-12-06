-- =====================================================
-- Phase 3: Verification
-- Migration Date: 2025-12-06
-- Purpose: Verify data integrity and foreign key relationships
-- =====================================================

-- Ensure we're using the correct database
USE `carwash`;

SET @start_time = NOW();
SELECT CONCAT('Phase 3 Verification Started: ', @start_time) AS 'Verification Status';

SET @errors_found = 0;
SET @warnings_found = 0;

-- =====================================================
-- 3.1 Table Existence Checks
-- =====================================================

SELECT '=== TABLE EXISTENCE CHECKS ===' AS 'Section';

-- Required tables that must exist
SELECT 
    TABLE_NAME,
    TABLE_ROWS AS 'Approx Rows',
    ROUND(DATA_LENGTH / 1024 / 1024, 2) AS 'Data MB',
    CREATE_TIME,
    UPDATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
      'users', 'carwashes', 'services', 'bookings', 
      'reviews', 'user_vehicles', 'payments', 
      'audit_logs', 'favorites', 'time_slots',
      'booking_status', 'ui_labels'
  )
ORDER BY TABLE_NAME;

-- Check for missing required tables
SELECT 'users' AS 'Table', 
       CASE WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users') 
            THEN '✓ EXISTS' ELSE '✗ MISSING' END AS 'Status'
UNION ALL
SELECT 'carwashes', 
       CASE WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwashes') 
            THEN '✓ EXISTS' ELSE '✗ MISSING' END
UNION ALL
SELECT 'services', 
       CASE WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services') 
            THEN '✓ EXISTS' ELSE '✗ MISSING' END
UNION ALL
SELECT 'bookings', 
       CASE WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings') 
            THEN '✓ EXISTS' ELSE '✗ MISSING' END
UNION ALL
SELECT 'reviews', 
       CASE WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews') 
            THEN '✓ EXISTS' ELSE '✗ MISSING' END
UNION ALL
SELECT 'user_vehicles', 
       CASE WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_vehicles') 
            THEN '✓ EXISTS' ELSE '✗ MISSING' END
UNION ALL
SELECT 'payments', 
       CASE WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments') 
            THEN '✓ EXISTS' ELSE '✗ MISSING' END
UNION ALL
SELECT 'ui_labels', 
       CASE WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ui_labels') 
            THEN '✓ EXISTS' ELSE '✗ MISSING' END;

-- =====================================================
-- 3.2 Column Existence Verification
-- =====================================================

SELECT '=== COLUMN EXISTENCE CHECKS ===' AS 'Section';

-- Check carwashes has all required columns
SELECT 
    'carwashes' AS 'Table',
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'carwashes'
  AND COLUMN_NAME IN (
      'id', 'user_id', 'name', 'owner_name', 'owner_phone', 
      'address', 'city', 'district', 'phone', 'email',
      'tax_number', 'license_number', 'logo_path', 'status', 
      'is_active', 'is_verified', 'average_rating', 'total_reviews'
  )
ORDER BY ORDINAL_POSITION;

-- =====================================================
-- 3.3 Foreign Key Integrity Checks
-- =====================================================

SELECT '=== FOREIGN KEY INTEGRITY CHECKS ===' AS 'Section';

-- Check for orphaned services (carwash_id not in carwashes)
SELECT 
    'services -> carwashes' AS 'Relationship',
    COUNT(*) AS 'Orphaned Records',
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ORPHANS FOUND' END AS 'Status'
FROM services s
LEFT JOIN carwashes cw ON s.carwash_id = cw.id
WHERE cw.id IS NULL AND s.carwash_id IS NOT NULL;

-- Check for orphaned bookings (carwash_id not in carwashes)
SELECT 
    'bookings -> carwashes' AS 'Relationship',
    COUNT(*) AS 'Orphaned Records',
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ORPHANS FOUND' END AS 'Status'
FROM bookings b
LEFT JOIN carwashes cw ON b.carwash_id = cw.id
WHERE cw.id IS NULL AND b.carwash_id IS NOT NULL;

-- Check for orphaned bookings (user_id not in users)
SELECT 
    'bookings -> users' AS 'Relationship',
    COUNT(*) AS 'Orphaned Records',
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ORPHANS FOUND' END AS 'Status'
FROM bookings b
LEFT JOIN users u ON b.user_id = u.id
WHERE u.id IS NULL AND b.user_id IS NOT NULL;

-- Check for orphaned reviews (booking_id not in bookings)
SELECT 
    'reviews -> bookings' AS 'Relationship',
    COUNT(*) AS 'Orphaned Records',
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ORPHANS FOUND' END AS 'Status'
FROM reviews r
LEFT JOIN bookings b ON r.booking_id = b.id
WHERE b.id IS NULL;

-- Check for orphaned favorites (user_id not in users)
SELECT 
    'favorites -> users' AS 'Relationship',
    COUNT(*) AS 'Orphaned Records',
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ORPHANS FOUND' END AS 'Status'
FROM favorites f
LEFT JOIN users u ON f.user_id = u.id
WHERE u.id IS NULL;

-- Check for orphaned favorites (carwash_id not in carwashes)
SELECT 
    'favorites -> carwashes' AS 'Relationship',
    COUNT(*) AS 'Orphaned Records',
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ORPHANS FOUND' END AS 'Status'
FROM favorites f
LEFT JOIN carwashes cw ON f.carwash_id = cw.id
WHERE cw.id IS NULL;

-- Check for orphaned user_vehicles (user_id not in users)
SELECT 
    'user_vehicles -> users' AS 'Relationship',
    COUNT(*) AS 'Orphaned Records',
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ORPHANS FOUND' END AS 'Status'
FROM user_vehicles uv
LEFT JOIN users u ON uv.user_id = u.id
WHERE u.id IS NULL;

-- =====================================================
-- 3.4 Data Consistency Checks
-- =====================================================

SELECT '=== DATA CONSISTENCY CHECKS ===' AS 'Section';

-- Check carwashes have valid status values
SELECT 
    'carwashes.status' AS 'Field',
    status AS 'Value',
    COUNT(*) AS 'Count'
FROM carwashes
GROUP BY status;

-- Check users have valid role values
SELECT 
    'users.role' AS 'Field',
    role AS 'Value',
    COUNT(*) AS 'Count'
FROM users
GROUP BY role;

-- Check bookings have valid status values
SELECT 
    'bookings.status' AS 'Field',
    status AS 'Value',
    COUNT(*) AS 'Count'
FROM bookings
GROUP BY status;

-- Check for duplicate user emails
SELECT 
    'Duplicate Emails' AS 'Check',
    CASE WHEN COUNT(*) = 0 THEN '✓ No Duplicates' 
         ELSE CONCAT('✗ ', COUNT(*), ' duplicates found') END AS 'Status'
FROM (
    SELECT email, COUNT(*) as cnt
    FROM users
    WHERE email IS NOT NULL AND email != ''
    GROUP BY email
    HAVING cnt > 1
) dups;

-- Check for carwashes without user association
SELECT 
    'Carwashes without user' AS 'Check',
    COUNT(*) AS 'Count',
    CASE WHEN COUNT(*) = 0 THEN '✓ All have users' 
         ELSE '⚠ Some orphaned' END AS 'Status'
FROM carwashes
WHERE user_id IS NULL;

-- =====================================================
-- 3.5 Rating Consistency Check
-- =====================================================

SELECT '=== RATING CONSISTENCY CHECK ===' AS 'Section';

-- Compare stored ratings with calculated ratings
SELECT 
    cw.id,
    cw.name,
    cw.average_rating AS 'Stored Rating',
    cw.total_reviews AS 'Stored Count',
    COALESCE(ROUND(AVG(r.rating), 2), 0) AS 'Calculated Rating',
    COUNT(r.id) AS 'Calculated Count',
    CASE 
        WHEN ABS(COALESCE(cw.average_rating, 0) - COALESCE(AVG(r.rating), 0)) < 0.1 
             AND cw.total_reviews = COUNT(r.id)
        THEN '✓ Match'
        ELSE '⚠ Mismatch'
    END AS 'Status'
FROM carwashes cw
LEFT JOIN reviews r ON r.carwash_id = cw.id AND r.is_approved = 1
GROUP BY cw.id, cw.name, cw.average_rating, cw.total_reviews
HAVING 'Status' = '⚠ Mismatch'
LIMIT 10;

-- =====================================================
-- 3.6 UI Labels Verification
-- =====================================================

SELECT '=== UI LABELS VERIFICATION ===' AS 'Section';

-- Check UI labels coverage by form
SELECT 
    form_name AS 'Form',
    COUNT(*) AS 'Label Count',
    SUM(CASE WHEN is_required = 1 THEN 1 ELSE 0 END) AS 'Required Fields',
    SUM(CASE WHEN label_en IS NOT NULL THEN 1 ELSE 0 END) AS 'Has English',
    CASE WHEN COUNT(*) > 0 THEN '✓ Has Labels' ELSE '⚠ No Labels' END AS 'Status'
FROM ui_labels
GROUP BY form_name
ORDER BY form_name;

-- =====================================================
-- 3.7 Index Verification
-- =====================================================

SELECT '=== INDEX VERIFICATION ===' AS 'Section';

SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS 'Columns',
    INDEX_TYPE,
    CASE WHEN NON_UNIQUE = 0 THEN 'UNIQUE' ELSE 'NON-UNIQUE' END AS 'Uniqueness'
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('carwashes', 'bookings', 'services', 'reviews', 'users')
GROUP BY TABLE_NAME, INDEX_NAME, INDEX_TYPE, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;

-- =====================================================
-- 3.8 Migration Completeness Check
-- =====================================================

SELECT '=== MIGRATION COMPLETENESS ===' AS 'Section';

-- Check if legacy tables still have unmigrated data
DROP PROCEDURE IF EXISTS `check_unmigrated_data`;
DELIMITER //
CREATE PROCEDURE `check_unmigrated_data`()
BEGIN
    DECLARE unmigrated_profiles INT DEFAULT 0;
    DECLARE unmigrated_business INT DEFAULT 0;
    
    -- Check carwash_profiles
    SELECT COUNT(*) INTO @has_profiles
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carwash_profiles';
    
    IF @has_profiles > 0 THEN
        SELECT COUNT(*) INTO unmigrated_profiles
        FROM carwash_profiles cp
        LEFT JOIN carwashes cw ON cw.user_id = cp.user_id
        WHERE cw.id IS NULL;
        
        SELECT 
            'carwash_profiles' AS 'Legacy Table',
            unmigrated_profiles AS 'Unmigrated Records',
            CASE WHEN unmigrated_profiles = 0 THEN '✓ Fully Migrated' 
                 ELSE '⚠ Has Unmigrated Data' END AS 'Status';
    END IF;
    
    -- Check business_profiles
    SELECT COUNT(*) INTO @has_business
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'business_profiles';
    
    IF @has_business > 0 THEN
        SELECT COUNT(*) INTO unmigrated_business
        FROM business_profiles bp
        LEFT JOIN carwashes cw ON cw.user_id = bp.user_id
        WHERE cw.id IS NULL;
        
        SELECT 
            'business_profiles' AS 'Legacy Table',
            unmigrated_business AS 'Unmigrated Records',
            CASE WHEN unmigrated_business = 0 THEN '✓ Fully Migrated' 
                 ELSE '⚠ Has Unmigrated Data' END AS 'Status';
    END IF;
END //
DELIMITER ;

CALL check_unmigrated_data();
DROP PROCEDURE IF EXISTS `check_unmigrated_data`;

-- =====================================================
-- 3.9 Summary Statistics
-- =====================================================

SELECT '=== SUMMARY STATISTICS ===' AS 'Section';

SELECT 
    (SELECT COUNT(*) FROM users) AS 'Total Users',
    (SELECT COUNT(*) FROM users WHERE role = 'admin') AS 'Admins',
    (SELECT COUNT(*) FROM users WHERE role = 'carwash') AS 'CarWash Owners',
    (SELECT COUNT(*) FROM users WHERE role = 'customer') AS 'Customers',
    (SELECT COUNT(*) FROM carwashes) AS 'Total CarWashes',
    (SELECT COUNT(*) FROM carwashes WHERE status = 'active') AS 'Active CarWashes',
    (SELECT COUNT(*) FROM services) AS 'Total Services',
    (SELECT COUNT(*) FROM bookings) AS 'Total Bookings',
    (SELECT COUNT(*) FROM reviews) AS 'Total Reviews',
    (SELECT COUNT(*) FROM user_vehicles) AS 'Total Vehicles',
    (SELECT COUNT(*) FROM ui_labels) AS 'UI Labels';

SELECT CONCAT('Phase 3 Verification Completed: ', NOW(), ' (Duration: ', TIMEDIFF(NOW(), @start_time), ')') AS 'Verification Status';

-- =====================================================
-- 3.10 Final Verification Result
-- =====================================================

SELECT '=== VERIFICATION COMPLETE ===' AS 'Section';
SELECT 'Review all checks above. Address any ✗ or ⚠ issues before proceeding to Phase 4 (Cleanup).' AS 'Next Steps';

-- =====================================================
-- End of Phase 3
-- =====================================================
