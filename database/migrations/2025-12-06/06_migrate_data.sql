-- ============================================================================
-- CarWash Project - Data Migration Script
-- Version: 1.0.0
-- Date: 2025-12-05
-- Description: Migrates data from legacy tables to new schema
-- IMPORTANT: Run SELECT queries first (dry-run) to preview data
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- DRY-RUN SECTION: Preview data before migration
-- Run these SELECT statements first to verify data
-- ============================================================================

-- PREVIEW 1: Check carwash_profiles data to migrate
SELECT '=== DRY-RUN: carwash_profiles to migrate ===' AS info;
SELECT 
    cp.id AS source_id,
    cp.user_id,
    cp.business_name AS name,
    cp.address,
    cp.city,
    cp.district,
    cp.contact_phone AS phone,
    cp.contact_email AS email,
    cp.owner_name,
    cp.owner_phone,
    cp.tax_number,
    cp.license_number,
    cp.featured_image AS logo_path,
    cp.created_at,
    cp.updated_at
FROM carwash_profiles cp
WHERE NOT EXISTS (
    SELECT 1 FROM carwashes c WHERE c.user_id = cp.user_id
)
LIMIT 100;

-- PREVIEW 2: Count records to migrate
SELECT '=== DRY-RUN: Record counts ===' AS info;
SELECT 
    (SELECT COUNT(*) FROM carwash_profiles) AS total_carwash_profiles,
    (SELECT COUNT(*) FROM carwashes) AS existing_carwashes,
    (SELECT COUNT(*) FROM carwash_profiles cp WHERE NOT EXISTS (SELECT 1 FROM carwashes c WHERE c.user_id = cp.user_id)) AS profiles_to_migrate;

-- PREVIEW 3: Check for potential duplicates
SELECT '=== DRY-RUN: Potential duplicate user_ids ===' AS info;
SELECT 
    cp.user_id,
    cp.business_name AS profile_name,
    c.name AS carwash_name
FROM carwash_profiles cp
INNER JOIN carwashes c ON cp.user_id = c.user_id
LIMIT 50;

-- ============================================================================
-- MIGRATION SECTION: Execute only after reviewing dry-run results
-- Wrap in transaction for safety
-- ============================================================================

-- BEGIN TRANSACTION
START TRANSACTION;

-- MIGRATION 1: Migrate carwash_profiles to carwashes (where not already exists)
INSERT INTO carwashes (
    user_id,
    name,
    address,
    city,
    district,
    phone,
    mobile_phone,
    email,
    owner_name,
    owner_phone,
    owner_birth_date,
    tax_number,
    license_number,
    logo_path,
    working_hours,
    social_media,
    status,
    created_at,
    updated_at
)
SELECT 
    cp.user_id,
    COALESCE(cp.business_name, 'Unnamed Carwash') AS name,
    cp.address,
    cp.city,
    cp.district,
    cp.contact_phone AS phone,
    COALESCE(
        cp.mobile_phone,
        JSON_UNQUOTE(JSON_EXTRACT(cp.social_media, '$.mobile_phone')),
        JSON_UNQUOTE(JSON_EXTRACT(cp.social_media, '$.phone'))
    ) AS mobile_phone,
    cp.contact_email AS email,
    cp.owner_name,
    cp.owner_phone,
    cp.birth_date AS owner_birth_date,
    cp.tax_number,
    cp.license_number,
    COALESCE(cp.featured_image, cp.logo) AS logo_path,
    cp.opening_hours AS working_hours,
    cp.social_media,
    CASE 
        WHEN cp.status = 'active' THEN 'active'
        WHEN cp.status = 'pending' THEN 'pending'
        ELSE 'inactive'
    END AS status,
    cp.created_at,
    COALESCE(cp.updated_at, cp.created_at) AS updated_at
FROM carwash_profiles cp
WHERE NOT EXISTS (
    SELECT 1 FROM carwashes c WHERE c.user_id = cp.user_id
)
ON DUPLICATE KEY UPDATE
    name = COALESCE(VALUES(name), carwashes.name),
    address = COALESCE(VALUES(address), carwashes.address),
    phone = COALESCE(VALUES(phone), carwashes.phone),
    updated_at = CURRENT_TIMESTAMP;

-- MIGRATION 2: Populate ui_labels with discovered Turkish labels
INSERT IGNORE INTO ui_labels (label_key, language_code, label_value, context) VALUES
-- Customer Registration Form
('customer_name', 'tr', 'Ad Soyad', 'customer_registration'),
('customer_email', 'tr', 'E-posta', 'customer_registration'),
('customer_password', 'tr', 'Şifre', 'customer_registration'),
('customer_phone', 'tr', 'Telefon', 'customer_registration'),
('register_button', 'tr', 'Kayıt Ol', 'customer_registration'),

-- Car Wash Registration Form
('business_name', 'tr', 'İşletme Adı', 'carwash_registration'),
('owner_name', 'tr', 'Sahip Adı Soyadı', 'carwash_registration'),
('owner_phone', 'tr', 'Sahip Telefon', 'carwash_registration'),
('tax_number', 'tr', 'Vergi Numarası', 'carwash_registration'),
('license_number', 'tr', 'Ruhsat Numarası', 'carwash_registration'),
('birth_date', 'tr', 'Doğum Tarihi', 'carwash_registration'),
('business_address', 'tr', 'Adres', 'carwash_registration'),
('city', 'tr', 'Şehir', 'carwash_registration'),
('district', 'tr', 'İlçe', 'carwash_registration'),
('profile_image', 'tr', 'Profil Fotoğrafı', 'carwash_registration'),
('logo_image', 'tr', 'İşletme Logosu', 'carwash_registration'),
('exterior_wash', 'tr', 'Dış Yıkama', 'services'),
('interior_wash', 'tr', 'İç Yıkama', 'services'),

-- Booking Form
('booking_location', 'tr', 'Konum', 'booking'),
('booking_service', 'tr', 'Hizmet', 'booking'),
('booking_vehicle', 'tr', 'Araç', 'booking'),
('booking_date', 'tr', 'Tarih', 'booking'),
('booking_time', 'tr', 'Saat', 'booking'),
('booking_notes', 'tr', 'Notlar', 'booking'),
('create_reservation', 'tr', 'Yeni Rezervasyon Oluştur', 'booking'),

-- Status Labels
('status_pending', 'tr', 'Bekliyor', 'status'),
('status_confirmed', 'tr', 'Onaylandı', 'status'),
('status_in_progress', 'tr', 'İşlemde', 'status'),
('status_completed', 'tr', 'Tamamlandı', 'status'),
('status_cancelled', 'tr', 'İptal', 'status'),
('status_no_show', 'tr', 'Gelmedi', 'status'),

-- Review Form
('review_rating', 'tr', 'Değerlendirme', 'review'),
('review_comment', 'tr', 'Yorum', 'review'),
('review_submit', 'tr', 'Gönder', 'review'),

-- Vehicle Form
('vehicle_brand', 'tr', 'Marka', 'vehicle'),
('vehicle_model', 'tr', 'Model', 'vehicle'),
('vehicle_plate', 'tr', 'Plaka', 'vehicle'),
('vehicle_year', 'tr', 'Yıl', 'vehicle'),
('vehicle_color', 'tr', 'Renk', 'vehicle'),

-- English translations
('customer_name', 'en', 'Full Name', 'customer_registration'),
('customer_email', 'en', 'Email', 'customer_registration'),
('customer_password', 'en', 'Password', 'customer_registration'),
('customer_phone', 'en', 'Phone', 'customer_registration'),
('register_button', 'en', 'Register', 'customer_registration'),
('business_name', 'en', 'Business Name', 'carwash_registration'),
('owner_name', 'en', 'Owner Name', 'carwash_registration'),
('booking_location', 'en', 'Location', 'booking'),
('booking_service', 'en', 'Service', 'booking'),
('booking_date', 'en', 'Date', 'booking'),
('booking_time', 'en', 'Time', 'booking');

-- MIGRATION 3: Generate booking numbers for existing bookings
UPDATE bookings 
SET booking_number = CONCAT('BK', YEAR(created_at), LPAD(id, 6, '0'))
WHERE booking_number IS NULL;

-- MIGRATION 4: Update carwash rating statistics
UPDATE carwashes c
SET 
    rating_average = (
        SELECT COALESCE(AVG(r.rating), 0.00) 
        FROM reviews r 
        WHERE r.carwash_id = c.id AND r.is_visible = 1
    ),
    rating_count = (
        SELECT COUNT(*) 
        FROM reviews r 
        WHERE r.carwash_id = c.id AND r.is_visible = 1
    );

-- COMMIT TRANSACTION
COMMIT;

-- ============================================================================
-- VERIFICATION SECTION: Run after migration to verify data integrity
-- ============================================================================

SELECT '=== VERIFICATION: Post-migration counts ===' AS info;

-- Verify carwashes migration
SELECT 
    'carwashes' AS table_name,
    COUNT(*) AS record_count,
    COUNT(DISTINCT user_id) AS unique_users
FROM carwashes;

-- Verify ui_labels populated
SELECT 
    'ui_labels' AS table_name,
    COUNT(*) AS record_count,
    COUNT(DISTINCT label_key) AS unique_keys,
    COUNT(DISTINCT language_code) AS languages
FROM ui_labels;

-- Verify booking numbers generated
SELECT 
    'bookings' AS table_name,
    COUNT(*) AS total_bookings,
    COUNT(booking_number) AS with_booking_number,
    COUNT(*) - COUNT(booking_number) AS missing_booking_number
FROM bookings;

-- Sample verification queries
SELECT '=== VERIFICATION: Sample data ===' AS info;

-- Sample carwashes
SELECT id, name, city, district, status, rating_average, rating_count
FROM carwashes
ORDER BY id DESC
LIMIT 5;

-- Sample bookings with numbers
SELECT id, booking_number, status, booking_date, total_price
FROM bookings
ORDER BY id DESC
LIMIT 5;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- End of migrate_data.sql
-- ============================================================================
