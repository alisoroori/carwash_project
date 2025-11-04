-- Migration: set default vehicle image path for records with NULL or empty image_path
-- Backup first (SELECT) then UPDATE. Run in MySQL client or phpMyAdmin.

-- Preview affected rows:
SELECT id, user_id, image_path FROM user_vehicles WHERE image_path IS NULL OR TRIM(image_path) = '';

-- Update to default image (adjust path if your BASE_URL differs)
UPDATE user_vehicles
SET image_path = '/carwash_project/frontend/assets/images/default-car.png'
WHERE image_path IS NULL OR TRIM(image_path) = '';

-- Verify changes
SELECT COUNT(*) AS updated_count FROM user_vehicles WHERE image_path = '/carwash_project/frontend/assets/images/default-car.png';
