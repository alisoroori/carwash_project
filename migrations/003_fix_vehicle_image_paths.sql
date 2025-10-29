-- Migration: Normalize vehicle image paths to canonical web paths
-- Run this after ensuring all uploads are under /carwash_project/backend/uploads/vehicles/

-- Update relative paths that start with 'uploads/vehicles/'
UPDATE user_vehicles
SET image_path = CONCAT('/carwash_project/backend/', image_path)
WHERE image_path LIKE 'uploads/vehicles/%' AND image_path NOT LIKE '/%';

-- Update paths that start with 'backend/uploads/vehicles/'
UPDATE user_vehicles
SET image_path = CONCAT('/carwash_project/', image_path)
WHERE image_path LIKE 'backend/uploads/vehicles/%' AND image_path NOT LIKE '/%';

-- Normalize any backslashes to forward slashes
UPDATE user_vehicles
SET image_path = REPLACE(image_path, '\\', '/')
WHERE image_path LIKE '%\\%';

-- Optional: Remove any duplicate leading slashes
UPDATE user_vehicles
SET image_path = CONCAT('/', TRIM(LEADING '/' FROM image_path))
WHERE image_path LIKE '//%';

-- Note: This assumes all images are stored under backend/uploads/vehicles/
-- If images are elsewhere, adjust accordingly.
-- After running, use tools/check_vehicle_image.php to verify.