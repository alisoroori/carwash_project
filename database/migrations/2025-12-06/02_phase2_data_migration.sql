-- =====================================================
-- Phase 2: Data Migration
-- Migration Date: 2025-12-06
-- Purpose: Migrate data from legacy tables to canonical tables
-- =====================================================

-- Ensure we're using the correct database
USE `carwash`;

SET @start_time = NOW();
SELECT CONCAT('Phase 2 Started: ', @start_time) AS 'Migration Status';

-- =====================================================
-- 2.1 Migrate carwash_profiles → carwashes
-- =====================================================

-- First, check if carwash_profiles table exists
SELECT COUNT(*) INTO @has_carwash_profiles
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'carwash_profiles';

-- Check if business_profiles table exists (another legacy table)
SELECT COUNT(*) INTO @has_business_profiles
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'business_profiles';

-- Count existing records before migration
SELECT COUNT(*) INTO @carwashes_before FROM carwashes;
SELECT CONCAT('Carwashes before migration: ', @carwashes_before) AS 'Record Count';

-- =====================================================
-- 2.1.1 Migrate from carwash_profiles (if exists)
-- =====================================================

DROP PROCEDURE IF EXISTS `migrate_carwash_profiles`;
DELIMITER //
CREATE PROCEDURE `migrate_carwash_profiles`()
BEGIN
    DECLARE profiles_count INT DEFAULT 0;
    DECLARE migrated_count INT DEFAULT 0;
    
    -- Check if source table exists
    SELECT COUNT(*) INTO @table_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'carwash_profiles';
    
    IF @table_exists = 0 THEN
        SELECT 'carwash_profiles table does not exist - skipping migration' AS 'Migration Status';
    ELSE
        -- Count source records
        SELECT COUNT(*) INTO profiles_count FROM carwash_profiles;
        SELECT CONCAT('Found ', profiles_count, ' records in carwash_profiles') AS 'Source Records';
        
        IF profiles_count > 0 THEN
            -- Insert records that don't already exist in carwashes (by user_id)
            INSERT INTO carwashes (
                user_id,
                name,
                description,
                address,
                city,
                postal_code,
                country,
                latitude,
                longitude,
                phone,
                email,
                website,
                logo_path,
                featured_image,
                gallery_images,
                working_hours,
                social_media,
                average_rating,
                total_reviews,
                is_verified,
                created_at,
                updated_at
            )
            SELECT
                cp.user_id,
                cp.business_name AS name,
                cp.description,
                cp.address,
                cp.city,
                cp.postal_code,
                COALESCE(cp.country, 'Turkey') AS country,
                cp.latitude,
                cp.longitude,
                cp.contact_phone AS phone,
                cp.contact_email AS email,
                cp.website,
                cp.featured_image AS logo_path,
                cp.featured_image,
                cp.gallery_images,
                cp.opening_hours AS working_hours,
                cp.social_media,
                cp.average_rating,
                cp.total_reviews,
                cp.verified AS is_verified,
                cp.created_at,
                cp.updated_at
            FROM carwash_profiles cp
            LEFT JOIN carwashes cw ON cw.user_id = cp.user_id
            WHERE cw.id IS NULL;
            
            SET migrated_count = ROW_COUNT();
            SELECT CONCAT('Migrated ', migrated_count, ' new records from carwash_profiles') AS 'Migration Result';
            
            -- Update existing carwashes with missing data from carwash_profiles
            UPDATE carwashes cw
            INNER JOIN carwash_profiles cp ON cw.user_id = cp.user_id
            SET
                cw.description = COALESCE(cw.description, cp.description),
                cw.city = COALESCE(cw.city, cp.city),
                cw.postal_code = COALESCE(cw.postal_code, cp.postal_code),
                cw.latitude = COALESCE(cw.latitude, cp.latitude),
                cw.longitude = COALESCE(cw.longitude, cp.longitude),
                cw.website = COALESCE(cw.website, cp.website),
                cw.average_rating = COALESCE(cw.average_rating, cp.average_rating),
                cw.total_reviews = GREATEST(COALESCE(cw.total_reviews, 0), COALESCE(cp.total_reviews, 0)),
                cw.is_verified = COALESCE(cw.is_verified, cp.verified),
                cw.updated_at = NOW()
            WHERE cw.description IS NULL 
               OR cw.city IS NULL 
               OR cw.latitude IS NULL;
            
            SELECT CONCAT('Updated ', ROW_COUNT(), ' existing carwashes with data from carwash_profiles') AS 'Update Result';
        ELSE
            SELECT 'No records in carwash_profiles to migrate' AS 'Migration Status';
        END IF;
    END IF;
END //
DELIMITER ;

CALL migrate_carwash_profiles();
DROP PROCEDURE IF EXISTS `migrate_carwash_profiles`;

-- =====================================================
-- 2.1.2 Migrate from business_profiles (if exists)
-- =====================================================

DROP PROCEDURE IF EXISTS `migrate_business_profiles`;
DELIMITER //
CREATE PROCEDURE `migrate_business_profiles`()
BEGIN
    DECLARE profiles_count INT DEFAULT 0;
    DECLARE migrated_count INT DEFAULT 0;
    
    -- Check if source table exists
    SELECT COUNT(*) INTO @table_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'business_profiles';
    
    IF @table_exists = 0 THEN
        SELECT 'business_profiles table does not exist - skipping migration' AS 'Migration Status';
    ELSE
        -- Count source records
        SELECT COUNT(*) INTO profiles_count FROM business_profiles;
        SELECT CONCAT('Found ', profiles_count, ' records in business_profiles') AS 'Source Records';
        
        IF profiles_count > 0 THEN
            -- Insert records that don't already exist in carwashes (by user_id)
            INSERT INTO carwashes (
                user_id,
                name,
                address,
                city,
                district,
                postal_code,
                phone,
                mobile_phone,
                email,
                logo_path,
                working_hours,
                created_at,
                updated_at
            )
            SELECT
                bp.user_id,
                bp.business_name AS name,
                bp.address,
                bp.city,
                bp.district,
                bp.postal_code,
                bp.phone,
                bp.mobile_phone,
                bp.email,
                bp.logo_path,
                bp.working_hours,
                bp.created_at,
                bp.updated_at
            FROM business_profiles bp
            LEFT JOIN carwashes cw ON cw.user_id = bp.user_id
            WHERE cw.id IS NULL;
            
            SET migrated_count = ROW_COUNT();
            SELECT CONCAT('Migrated ', migrated_count, ' new records from business_profiles') AS 'Migration Result';
        ELSE
            SELECT 'No records in business_profiles to migrate' AS 'Migration Status';
        END IF;
    END IF;
END //
DELIMITER ;

CALL migrate_business_profiles();
DROP PROCEDURE IF EXISTS `migrate_business_profiles`;

-- =====================================================
-- 2.2 Populate UI Labels with discovered form fields
-- =====================================================

SELECT 'Populating ui_labels with form field translations...' AS 'Migration Status';

-- Customer Registration Form fields
INSERT INTO ui_labels (table_name, column_name, form_name, field_name, label_tr, label_en, input_type, is_required, display_order) VALUES
('users', 'name', 'customer_registration', 'name', 'Ad Soyad', 'Full Name', 'text', 1, 1),
('users', 'email', 'customer_registration', 'email', 'E-posta', 'Email', 'email', 1, 2),
('users', 'password', 'customer_registration', 'password', 'Şifre', 'Password', 'password', 1, 3),
('users', 'phone', 'customer_registration', 'phone', 'Telefon', 'Phone', 'tel', 0, 4)
ON DUPLICATE KEY UPDATE 
    label_tr = VALUES(label_tr),
    label_en = VALUES(label_en),
    updated_at = NOW();

-- Car Wash Business Registration Form fields
INSERT INTO ui_labels (table_name, column_name, form_name, field_name, label_tr, label_en, input_type, is_required, display_order) VALUES
('carwashes', 'name', 'carwash_registration', 'business_name', 'İşletme Adı', 'Business Name', 'text', 1, 1),
('carwashes', 'owner_name', 'carwash_registration', 'owner_name', 'Sahip Adı Soyadı', 'Owner Name', 'text', 1, 2),
('carwashes', 'owner_phone', 'carwash_registration', 'owner_phone', 'Sahip Telefon', 'Owner Phone', 'tel', 1, 3),
('carwashes', 'tax_number', 'carwash_registration', 'tax_number', 'Vergi Numarası', 'Tax Number', 'text', 0, 4),
('carwashes', 'license_number', 'carwash_registration', 'license_number', 'Ruhsat Numarası', 'License Number', 'text', 0, 5),
('carwashes', 'owner_birth_date', 'carwash_registration', 'birth_date', 'Doğum Tarihi', 'Birth Date', 'date', 0, 6),
('carwashes', 'address', 'carwash_registration', 'address', 'Adres', 'Address', 'textarea', 1, 7),
('carwashes', 'city', 'carwash_registration', 'city', 'Şehir', 'City', 'select', 1, 8),
('carwashes', 'district', 'carwash_registration', 'district', 'İlçe', 'District', 'select', 1, 9),
('carwashes', 'email', 'carwash_registration', 'email', 'E-posta', 'Email', 'email', 1, 10),
('carwashes', 'phone', 'carwash_registration', 'phone', 'Telefon', 'Phone', 'tel', 1, 11),
('carwashes', 'profile_image_path', 'carwash_registration', 'profile_image', 'Profil Fotoğrafı', 'Profile Photo', 'file', 0, 12),
('carwashes', 'logo_path', 'carwash_registration', 'logo_image', 'İşletme Logosu', 'Business Logo', 'file', 0, 13)
ON DUPLICATE KEY UPDATE 
    label_tr = VALUES(label_tr),
    label_en = VALUES(label_en),
    updated_at = NOW();

-- Booking Form fields
INSERT INTO ui_labels (table_name, column_name, form_name, field_name, label_tr, label_en, input_type, is_required, display_order) VALUES
('bookings', 'carwash_id', 'booking', 'carwash_id', 'Konum', 'Location', 'select', 1, 1),
('bookings', 'service_id', 'booking', 'service_id', 'Hizmet', 'Service', 'select', 1, 2),
('bookings', 'vehicle_id', 'booking', 'vehicle_id', 'Araç', 'Vehicle', 'select', 0, 3),
('bookings', 'booking_date', 'booking', 'date', 'Tarih', 'Date', 'date', 1, 4),
('bookings', 'booking_time', 'booking', 'time', 'Saat', 'Time', 'time', 1, 5),
('bookings', 'customer_name', 'booking', 'customer_name', 'Müşteri Adı', 'Customer Name', 'text', 1, 6),
('bookings', 'customer_phone', 'booking', 'customer_phone', 'Müşteri Telefon', 'Customer Phone', 'tel', 1, 7),
('bookings', 'notes', 'booking', 'notes', 'Notlar', 'Notes', 'textarea', 0, 8)
ON DUPLICATE KEY UPDATE 
    label_tr = VALUES(label_tr),
    label_en = VALUES(label_en),
    updated_at = NOW();

-- Vehicle Management Form fields
INSERT INTO ui_labels (table_name, column_name, form_name, field_name, label_tr, label_en, input_type, is_required, display_order) VALUES
('user_vehicles', 'brand', 'vehicle_management', 'car_brand', 'Marka', 'Brand', 'text', 1, 1),
('user_vehicles', 'model', 'vehicle_management', 'car_model', 'Model', 'Model', 'text', 1, 2),
('user_vehicles', 'license_plate', 'vehicle_management', 'license_plate', 'Plaka', 'License Plate', 'text', 1, 3),
('user_vehicles', 'year', 'vehicle_management', 'car_year', 'Yıl', 'Year', 'number', 0, 4),
('user_vehicles', 'color', 'vehicle_management', 'car_color', 'Renk', 'Color', 'text', 0, 5),
('user_vehicles', 'image_path', 'vehicle_management', 'vehicle_image', 'Araç Fotoğrafı', 'Vehicle Photo', 'file', 0, 6)
ON DUPLICATE KEY UPDATE 
    label_tr = VALUES(label_tr),
    label_en = VALUES(label_en),
    updated_at = NOW();

-- Review Form fields
INSERT INTO ui_labels (table_name, column_name, form_name, field_name, label_tr, label_en, input_type, is_required, display_order) VALUES
('reviews', 'booking_id', 'review', 'reservation_id', 'Rezervasyon', 'Reservation', 'hidden', 1, 1),
('reviews', 'rating', 'review', 'rating', 'Değerlendirme', 'Rating', 'stars', 1, 2),
('reviews', 'comment', 'review', 'comment', 'Yorum', 'Comment', 'textarea', 0, 3)
ON DUPLICATE KEY UPDATE 
    label_tr = VALUES(label_tr),
    label_en = VALUES(label_en),
    updated_at = NOW();

-- Service fields for admin panel
INSERT INTO ui_labels (table_name, column_name, form_name, field_name, label_tr, label_en, input_type, is_required, display_order) VALUES
('services', 'name', 'service_management', 'service_name', 'Hizmet Adı', 'Service Name', 'text', 1, 1),
('services', 'category_id', 'service_management', 'category', 'Kategori', 'Category', 'select', 1, 2),
('services', 'description', 'service_management', 'description', 'Açıklama', 'Description', 'textarea', 0, 3),
('services', 'duration_minutes', 'service_management', 'duration', 'Süre (dakika)', 'Duration (min)', 'number', 1, 4),
('services', 'price', 'service_management', 'price', 'Fiyat', 'Price', 'number', 1, 5),
('services', 'is_active', 'service_management', 'is_active', 'Aktif', 'Active', 'checkbox', 0, 6)
ON DUPLICATE KEY UPDATE 
    label_tr = VALUES(label_tr),
    label_en = VALUES(label_en),
    updated_at = NOW();

SELECT CONCAT('Inserted/updated ', ROW_COUNT(), ' UI label records') AS 'UI Labels Status';

-- =====================================================
-- 2.3 Update foreign key references where needed
-- =====================================================

-- Update services.carwash_id to point to carwashes table
-- This handles cases where services were created pointing to carwash_profiles

DROP PROCEDURE IF EXISTS `fix_service_carwash_references`;
DELIMITER //
CREATE PROCEDURE `fix_service_carwash_references`()
BEGIN
    DECLARE fixed_count INT DEFAULT 0;
    
    -- Check if carwash_profiles exists
    SELECT COUNT(*) INTO @has_profiles
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'carwash_profiles';
    
    IF @has_profiles > 0 THEN
        -- Update services to use carwashes.id based on matching user_id
        UPDATE services s
        INNER JOIN carwash_profiles cp ON s.carwash_id = cp.id
        INNER JOIN carwashes cw ON cw.user_id = cp.user_id
        SET s.carwash_id = cw.id
        WHERE s.carwash_id != cw.id;
        
        SET fixed_count = ROW_COUNT();
        SELECT CONCAT('Updated ', fixed_count, ' service carwash_id references') AS 'FK Fix Result';
    ELSE
        SELECT 'No carwash_profiles table - skipping service FK fix' AS 'FK Fix Status';
    END IF;
END //
DELIMITER ;

CALL fix_service_carwash_references();
DROP PROCEDURE IF EXISTS `fix_service_carwash_references`;

-- =====================================================
-- 2.4 Update review carwash_id references
-- =====================================================

DROP PROCEDURE IF EXISTS `fix_review_carwash_references`;
DELIMITER //
CREATE PROCEDURE `fix_review_carwash_references`()
BEGIN
    -- Update reviews.carwash_id from booking's carwash_id where null
    UPDATE reviews r
    INNER JOIN bookings b ON r.booking_id = b.id
    SET r.carwash_id = b.carwash_id
    WHERE r.carwash_id IS NULL AND b.carwash_id IS NOT NULL;
    
    SELECT CONCAT('Updated ', ROW_COUNT(), ' review carwash_id references') AS 'Review FK Fix Result';
END //
DELIMITER ;

CALL fix_review_carwash_references();
DROP PROCEDURE IF EXISTS `fix_review_carwash_references`;

-- =====================================================
-- 2.5 Recalculate carwash ratings from reviews
-- =====================================================

UPDATE carwashes cw
SET 
    average_rating = (
        SELECT COALESCE(AVG(r.rating), 0)
        FROM reviews r
        WHERE r.carwash_id = cw.id AND r.is_approved = 1
    ),
    total_reviews = (
        SELECT COUNT(*)
        FROM reviews r
        WHERE r.carwash_id = cw.id AND r.is_approved = 1
    ),
    updated_at = NOW();

SELECT CONCAT('Recalculated ratings for ', ROW_COUNT(), ' carwashes') AS 'Rating Update Status';

-- =====================================================
-- 2.6 Count final records
-- =====================================================

SELECT COUNT(*) INTO @carwashes_after FROM carwashes;
SELECT CONCAT('Carwashes after migration: ', @carwashes_after, ' (Added: ', @carwashes_after - @carwashes_before, ')') AS 'Final Count';

SELECT COUNT(*) AS 'UI Labels Count' FROM ui_labels;

SELECT CONCAT('Phase 2 Completed: ', NOW(), ' (Duration: ', TIMEDIFF(NOW(), @start_time), ')') AS 'Migration Status';

-- =====================================================
-- End of Phase 2
-- =====================================================
