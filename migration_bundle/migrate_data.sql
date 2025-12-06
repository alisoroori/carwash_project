-- ============================================================================
-- CarWash Project - Data Migration Script
-- Version: 1.0.0
-- Date: 2025-12-06
-- Description: Migrates data from legacy tables to canonical schema
-- Danger Level: MEDIUM - Modifies data, but uses INSERT/UPDATE only
-- Expected Runtime: < 30 seconds for typical data volumes
-- IMPORTANT: Run DRY-RUN section first to preview changes
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- DRY-RUN SECTION
-- Run these SELECT statements FIRST to preview what will be migrated
-- ============================================================================

SELECT '========================================' AS divider;
SELECT 'DRY-RUN: Data Migration Preview' AS info;
SELECT '========================================' AS divider;

-- ============================================================================
-- DRY-RUN 1: Check carwash_profiles → carwashes migration candidates
-- ============================================================================

SELECT '--- carwash_profiles to migrate ---' AS step;

-- Count profiles that don't exist in carwashes
SELECT 
    (SELECT COUNT(*) FROM carwash_profiles) AS total_carwash_profiles,
    (SELECT COUNT(*) FROM carwashes) AS existing_carwashes,
    (SELECT COUNT(*) FROM carwash_profiles cp 
     WHERE NOT EXISTS (SELECT 1 FROM carwashes c WHERE c.user_id = cp.user_id)) AS profiles_to_migrate;

-- Preview profiles to migrate
SELECT 
    cp.id AS profile_id,
    cp.user_id,
    cp.business_name,
    cp.city,
    cp.district,
    cp.contact_phone,
    cp.contact_email,
    'TO_MIGRATE' AS action
FROM carwash_profiles cp
WHERE NOT EXISTS (
    SELECT 1 FROM carwashes c WHERE c.user_id = cp.user_id
)
LIMIT 20;

-- ============================================================================
-- DRY-RUN 2: Check vehicles → user_vehicles migration candidates
-- ============================================================================

SELECT '--- vehicles to migrate ---' AS step;

-- Count vehicles that might need migration
SELECT 
    (SELECT COUNT(*) FROM vehicles) AS total_vehicles,
    (SELECT COUNT(*) FROM user_vehicles) AS existing_user_vehicles,
    (SELECT COUNT(*) FROM vehicles v 
     WHERE NOT EXISTS (SELECT 1 FROM user_vehicles uv 
                       WHERE uv.user_id = v.user_id 
                       AND uv.license_plate = v.license_plate)) AS vehicles_to_migrate;

-- Preview vehicles to migrate
SELECT 
    v.id AS vehicle_id,
    v.user_id,
    v.brand,
    v.model,
    v.license_plate,
    'TO_MIGRATE' AS action
FROM vehicles v
WHERE NOT EXISTS (
    SELECT 1 FROM user_vehicles uv 
    WHERE uv.user_id = v.user_id 
    AND uv.license_plate = v.license_plate
)
LIMIT 20;

-- ============================================================================
-- DRY-RUN 3: Check customer_profiles → user_profiles migration candidates
-- ============================================================================

SELECT '--- customer_profiles to migrate ---' AS step;

SELECT 
    (SELECT COUNT(*) FROM customer_profiles) AS total_customer_profiles,
    (SELECT COUNT(*) FROM user_profiles) AS existing_user_profiles,
    (SELECT COUNT(*) FROM customer_profiles cp 
     WHERE NOT EXISTS (SELECT 1 FROM user_profiles up WHERE up.user_id = cp.user_id)) AS profiles_to_migrate;

-- Preview customer profiles to migrate
SELECT 
    cp.id AS customer_profile_id,
    cp.user_id,
    cp.city,
    cp.car_brand,
    cp.car_model,
    cp.license_plate,
    'TO_MIGRATE' AS action
FROM customer_profiles cp
WHERE NOT EXISTS (
    SELECT 1 FROM user_profiles up WHERE up.user_id = cp.user_id
)
LIMIT 20;

-- ============================================================================
-- DRY-RUN 4: Bookings without booking_number
-- ============================================================================

SELECT '--- bookings needing booking_number ---' AS step;

SELECT 
    COUNT(*) AS bookings_without_number
FROM bookings
WHERE booking_number IS NULL OR booking_number = '';

SELECT 
    id,
    user_id,
    carwash_id,
    booking_date,
    CONCAT('BK', YEAR(created_at), LPAD(id, 6, '0')) AS proposed_booking_number
FROM bookings
WHERE booking_number IS NULL OR booking_number = ''
LIMIT 10;

-- ============================================================================
-- DRY-RUN 5: Check duplicate user_ids between carwash_profiles and carwashes
-- ============================================================================

SELECT '--- Duplicate user_ids (already in carwashes) ---' AS step;

SELECT 
    cp.user_id,
    cp.business_name AS profile_name,
    c.name AS carwash_name,
    'SKIP_DUPLICATE' AS action
FROM carwash_profiles cp
INNER JOIN carwashes c ON cp.user_id = c.user_id
LIMIT 20;

SELECT '========================================' AS divider;
SELECT 'END DRY-RUN - Review above before proceeding' AS info;
SELECT '========================================' AS divider;

-- ============================================================================
-- MIGRATION SECTION
-- Execute only after reviewing dry-run results
-- Wrapped in transaction for safety
-- ============================================================================

START TRANSACTION;

-- ============================================================================
-- MIGRATION 1: carwash_profiles → carwashes
-- Only migrate records where user_id doesn't exist in carwashes
-- ============================================================================

SELECT '>>> MIGRATION 1: carwash_profiles → carwashes <<<' AS step;

INSERT INTO carwashes (
    user_id,
    name,
    description,
    address,
    city,
    district,
    state,
    country,
    postal_code,
    latitude,
    longitude,
    phone,
    email,
    website,
    opening_hours,
    social_media,
    image,
    rating,
    total_reviews,
    is_active,
    status,
    created_at,
    updated_at
)
SELECT 
    cp.user_id,
    COALESCE(cp.business_name, 'Unnamed Carwash') AS name,
    cp.description,
    cp.address,
    cp.city,
    cp.district,
    cp.state,
    cp.country,
    cp.postal_code,
    cp.latitude,
    cp.longitude,
    cp.contact_phone AS phone,
    cp.contact_email AS email,
    cp.website,
    cp.opening_hours,
    cp.social_media,
    cp.featured_image AS image,
    COALESCE(cp.average_rating, 0.00) AS rating,
    COALESCE(cp.total_reviews, 0) AS total_reviews,
    CASE WHEN cp.verified = 1 THEN 1 ELSE 0 END AS is_active,
    CASE 
        WHEN cp.verified = 1 THEN 'active'
        ELSE 'pending'
    END AS status,
    cp.created_at,
    COALESCE(cp.updated_at, cp.created_at) AS updated_at
FROM carwash_profiles cp
WHERE NOT EXISTS (
    SELECT 1 FROM carwashes c WHERE c.user_id = cp.user_id
);

SELECT ROW_COUNT() AS carwash_profiles_migrated;

-- ============================================================================
-- MIGRATION 2: vehicles → user_vehicles
-- Only migrate vehicles not already in user_vehicles
-- ============================================================================

SELECT '>>> MIGRATION 2: vehicles → user_vehicles <<<' AS step;

INSERT INTO user_vehicles (
    user_id,
    brand,
    model,
    year,
    color,
    license_plate,
    vehicle_type,
    notes,
    image_path,
    created_at,
    updated_at
)
SELECT 
    v.user_id,
    v.brand,
    v.model,
    v.year,
    v.color,
    v.license_plate,
    COALESCE(v.vehicle_type, 'sedan') AS vehicle_type,
    v.notes,
    v.image_path,
    v.created_at,
    v.updated_at
FROM vehicles v
WHERE NOT EXISTS (
    SELECT 1 FROM user_vehicles uv 
    WHERE uv.user_id = v.user_id 
    AND uv.license_plate = v.license_plate
);

SELECT ROW_COUNT() AS vehicles_migrated;

-- ============================================================================
-- MIGRATION 3: customer_profiles → user_profiles
-- Merge customer profile data into user_profiles
-- ============================================================================

SELECT '>>> MIGRATION 3: customer_profiles → user_profiles <<<' AS step;

INSERT INTO user_profiles (
    user_id,
    city,
    address,
    preferences,
    notification_settings,
    created_at,
    updated_at
)
SELECT 
    cp.user_id,
    cp.city,
    cp.address,
    cp.preferred_services AS preferences,
    cp.notifications AS notification_settings,
    cp.created_at,
    cp.updated_at
FROM customer_profiles cp
WHERE NOT EXISTS (
    SELECT 1 FROM user_profiles up WHERE up.user_id = cp.user_id
);

SELECT ROW_COUNT() AS customer_profiles_migrated;

-- Also migrate vehicle info from customer_profiles to user_vehicles if present
INSERT INTO user_vehicles (
    user_id,
    brand,
    model,
    year,
    color,
    license_plate,
    vehicle_type,
    created_at
)
SELECT 
    cp.user_id,
    cp.car_brand AS brand,
    cp.car_model AS model,
    cp.car_year AS year,
    cp.car_color AS color,
    cp.license_plate,
    'sedan' AS vehicle_type,
    cp.created_at
FROM customer_profiles cp
WHERE cp.license_plate IS NOT NULL
AND cp.license_plate != ''
AND NOT EXISTS (
    SELECT 1 FROM user_vehicles uv 
    WHERE uv.user_id = cp.user_id 
    AND uv.license_plate COLLATE utf8mb4_general_ci = cp.license_plate COLLATE utf8mb4_general_ci
);

SELECT ROW_COUNT() AS customer_vehicles_migrated;

-- ============================================================================
-- MIGRATION 4: Generate booking_number for bookings without one
-- Format: BK + Year + 6-digit padded ID
-- ============================================================================

SELECT '>>> MIGRATION 4: Generate booking_number <<<' AS step;

UPDATE bookings 
SET booking_number = CONCAT('BK', YEAR(created_at), LPAD(id, 6, '0'))
WHERE booking_number IS NULL OR booking_number = '';

SELECT ROW_COUNT() AS booking_numbers_generated;

-- ============================================================================
-- MIGRATION 5: Populate customer_name and customer_phone from users table
-- ============================================================================

SELECT '>>> MIGRATION 5: Populate customer info in bookings <<<' AS step;

UPDATE bookings b
INNER JOIN users u ON b.user_id = u.id
SET 
    b.customer_name = COALESCE(b.customer_name, u.full_name, u.name),
    b.customer_phone = COALESCE(b.customer_phone, u.phone)
WHERE b.customer_name IS NULL OR b.customer_phone IS NULL;

SELECT ROW_COUNT() AS bookings_customer_info_updated;

-- ============================================================================
-- MIGRATION 6: Update carwash rating statistics from reviews
-- ============================================================================

SELECT '>>> MIGRATION 6: Update carwash rating statistics <<<' AS step;

UPDATE carwashes c
SET 
    c.rating = (
        SELECT COALESCE(AVG(r.rating), 0.00) 
        FROM reviews r 
        WHERE r.carwash_id = c.id AND r.is_visible = 1
    ),
    c.rating_average = (
        SELECT COALESCE(AVG(r.rating), 0.00) 
        FROM reviews r 
        WHERE r.carwash_id = c.id AND r.is_visible = 1
    ),
    c.rating_count = (
        SELECT COUNT(*) 
        FROM reviews r 
        WHERE r.carwash_id = c.id AND r.is_visible = 1
    ),
    c.total_reviews = (
        SELECT COUNT(*) 
        FROM reviews r 
        WHERE r.carwash_id = c.id
    );

SELECT ROW_COUNT() AS carwashes_ratings_updated;

-- ============================================================================
-- MIGRATION 7: Populate ui_labels with standard labels (Turkish + English)
-- ============================================================================

SELECT '>>> MIGRATION 7: Populate ui_labels <<<' AS step;

INSERT IGNORE INTO ui_labels (label_key, language_code, label_value, context) VALUES
-- Customer Registration Form (Turkish)
('customer_name', 'tr', 'Ad Soyad', 'customer_registration'),
('customer_email', 'tr', 'E-posta', 'customer_registration'),
('customer_password', 'tr', 'Şifre', 'customer_registration'),
('customer_phone', 'tr', 'Telefon', 'customer_registration'),
('register_button', 'tr', 'Kayıt Ol', 'customer_registration'),

-- Car Wash Registration Form (Turkish)
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
('tc_kimlik', 'tr', 'TC Kimlik No', 'carwash_registration'),

-- Services (Turkish)
('exterior_wash', 'tr', 'Dış Yıkama', 'services'),
('interior_wash', 'tr', 'İç Yıkama', 'services'),
('full_wash', 'tr', 'Komple Yıkama', 'services'),
('detailing', 'tr', 'Detaylı Temizlik', 'services'),

-- Booking Form (Turkish)
('booking_location', 'tr', 'Konum', 'booking'),
('booking_service', 'tr', 'Hizmet', 'booking'),
('booking_vehicle', 'tr', 'Araç', 'booking'),
('booking_date', 'tr', 'Tarih', 'booking'),
('booking_time', 'tr', 'Saat', 'booking'),
('booking_notes', 'tr', 'Notlar', 'booking'),
('create_reservation', 'tr', 'Yeni Rezervasyon Oluştur', 'booking'),

-- Status Labels (Turkish)
('status_pending', 'tr', 'Bekliyor', 'status'),
('status_confirmed', 'tr', 'Onaylandı', 'status'),
('status_in_progress', 'tr', 'İşlemde', 'status'),
('status_completed', 'tr', 'Tamamlandı', 'status'),
('status_cancelled', 'tr', 'İptal', 'status'),
('status_no_show', 'tr', 'Gelmedi', 'status'),

-- Review Form (Turkish)
('review_rating', 'tr', 'Değerlendirme', 'review'),
('review_comment', 'tr', 'Yorum', 'review'),
('review_submit', 'tr', 'Gönder', 'review'),

-- Vehicle Form (Turkish)
('vehicle_brand', 'tr', 'Marka', 'vehicle'),
('vehicle_model', 'tr', 'Model', 'vehicle'),
('vehicle_plate', 'tr', 'Plaka', 'vehicle'),
('vehicle_year', 'tr', 'Yıl', 'vehicle'),
('vehicle_color', 'tr', 'Renk', 'vehicle'),

-- Payment (Turkish)
('payment_cash', 'tr', 'Nakit', 'payment'),
('payment_card', 'tr', 'Kart', 'payment'),
('payment_online', 'tr', 'Online', 'payment'),
('payment_total', 'tr', 'Toplam', 'payment'),

-- English translations
('customer_name', 'en', 'Full Name', 'customer_registration'),
('customer_email', 'en', 'Email', 'customer_registration'),
('customer_password', 'en', 'Password', 'customer_registration'),
('customer_phone', 'en', 'Phone', 'customer_registration'),
('register_button', 'en', 'Register', 'customer_registration'),
('business_name', 'en', 'Business Name', 'carwash_registration'),
('owner_name', 'en', 'Owner Name', 'carwash_registration'),
('owner_phone', 'en', 'Owner Phone', 'carwash_registration'),
('tax_number', 'en', 'Tax Number', 'carwash_registration'),
('license_number', 'en', 'License Number', 'carwash_registration'),
('birth_date', 'en', 'Birth Date', 'carwash_registration'),
('business_address', 'en', 'Address', 'carwash_registration'),
('city', 'en', 'City', 'carwash_registration'),
('district', 'en', 'District', 'carwash_registration'),
('booking_location', 'en', 'Location', 'booking'),
('booking_service', 'en', 'Service', 'booking'),
('booking_vehicle', 'en', 'Vehicle', 'booking'),
('booking_date', 'en', 'Date', 'booking'),
('booking_time', 'en', 'Time', 'booking'),
('booking_notes', 'en', 'Notes', 'booking'),
('create_reservation', 'en', 'Create New Reservation', 'booking'),
('status_pending', 'en', 'Pending', 'status'),
('status_confirmed', 'en', 'Confirmed', 'status'),
('status_in_progress', 'en', 'In Progress', 'status'),
('status_completed', 'en', 'Completed', 'status'),
('status_cancelled', 'en', 'Cancelled', 'status'),
('review_rating', 'en', 'Rating', 'review'),
('review_comment', 'en', 'Comment', 'review'),
('review_submit', 'en', 'Submit', 'review'),
('vehicle_brand', 'en', 'Brand', 'vehicle'),
('vehicle_model', 'en', 'Model', 'vehicle'),
('vehicle_plate', 'en', 'License Plate', 'vehicle'),
('vehicle_year', 'en', 'Year', 'vehicle'),
('vehicle_color', 'en', 'Color', 'vehicle'),
('payment_cash', 'en', 'Cash', 'payment'),
('payment_card', 'en', 'Card', 'payment'),
('payment_online', 'en', 'Online', 'payment'),
('payment_total', 'en', 'Total', 'payment');

SELECT ROW_COUNT() AS ui_labels_inserted;

-- ============================================================================
-- MIGRATION 8: Populate service_categories if empty
-- ============================================================================

SELECT '>>> MIGRATION 8: Populate service_categories <<<' AS step;

INSERT IGNORE INTO service_categories (name, description, display_order) VALUES
('basic', 'Basic wash services', 1),
('standard', 'Standard wash services', 2),
('premium', 'Premium wash services', 3),
('deluxe', 'Deluxe wash services', 4);

SELECT ROW_COUNT() AS service_categories_inserted;

-- Commit the transaction
COMMIT;

-- ============================================================================
-- VERIFICATION SECTION
-- Run after migration to verify data integrity
-- ============================================================================

SELECT '========================================' AS divider;
SELECT 'VERIFICATION: Post-Migration Counts' AS info;
SELECT '========================================' AS divider;

-- Table row counts
SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL SELECT 'carwashes', COUNT(*) FROM carwashes
UNION ALL SELECT 'carwash_profiles', COUNT(*) FROM carwash_profiles
UNION ALL SELECT 'bookings', COUNT(*) FROM bookings
UNION ALL SELECT 'services', COUNT(*) FROM services
UNION ALL SELECT 'payments', COUNT(*) FROM payments
UNION ALL SELECT 'reviews', COUNT(*) FROM reviews
UNION ALL SELECT 'user_vehicles', COUNT(*) FROM user_vehicles
UNION ALL SELECT 'vehicles', COUNT(*) FROM vehicles
UNION ALL SELECT 'user_profiles', COUNT(*) FROM user_profiles
UNION ALL SELECT 'customer_profiles', COUNT(*) FROM customer_profiles
UNION ALL SELECT 'ui_labels', COUNT(*) FROM ui_labels
UNION ALL SELECT 'service_categories', COUNT(*) FROM service_categories;

-- Verify booking numbers
SELECT 
    'Bookings with numbers' AS check_name,
    COUNT(*) AS total,
    SUM(CASE WHEN booking_number IS NOT NULL AND booking_number != '' THEN 1 ELSE 0 END) AS with_number,
    SUM(CASE WHEN booking_number IS NULL OR booking_number = '' THEN 1 ELSE 0 END) AS without_number
FROM bookings;

-- Verify carwash ratings
SELECT 
    c.id,
    c.name,
    c.rating,
    c.rating_average,
    c.rating_count,
    c.total_reviews,
    (SELECT COUNT(*) FROM reviews r WHERE r.carwash_id = c.id) AS actual_reviews
FROM carwashes c
LIMIT 10;

-- Verify ui_labels distribution
SELECT 
    language_code,
    COUNT(*) AS label_count,
    COUNT(DISTINCT context) AS contexts
FROM ui_labels
GROUP BY language_code;

-- Sample migrated data
SELECT '--- Sample carwashes ---' AS sample;
SELECT id, name, city, district, status, rating, created_at
FROM carwashes
ORDER BY id DESC
LIMIT 5;

SELECT '--- Sample bookings with numbers ---' AS sample;
SELECT id, booking_number, user_id, carwash_id, booking_date, status, total_price
FROM bookings
ORDER BY id DESC
LIMIT 5;

SELECT '--- Sample user_vehicles ---' AS sample;
SELECT id, user_id, brand, model, license_plate, vehicle_type
FROM user_vehicles
ORDER BY id DESC
LIMIT 5;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'migrate_data.sql completed successfully' AS result;

-- ============================================================================
-- End of migrate_data.sql
-- ============================================================================
