-- Migration: Normalize user_vehicles.image_path to canonical web path
-- Goal: ensure image_path starts with '/carwash_project/backend/uploads/vehicles/<filename>'
-- IMPORTANT: Review and run on staging before production.

-- 1) Example SELECT to preview changes (run BEFORE applying updates):
-- SELECT id, image_path FROM user_vehicles WHERE image_path IS NOT NULL AND image_path <> '' LIMIT 100;

-- 2) If image_path looks like 'uploads/vehicles/...' (no leading slash), prefix with /carwash_project/backend/
UPDATE user_vehicles
SET image_path = CONCAT('/carwash_project/backend/', image_path)
WHERE image_path LIKE 'uploads/%'
  AND image_path NOT LIKE '/carwash_project/%';

-- 3) If image_path looks like 'backend/uploads/vehicles/...' (missing leading '/carwash_project/'), prefix with /carwash_project/
UPDATE user_vehicles
SET image_path = CONCAT('/carwash_project/', image_path)
WHERE image_path LIKE 'backend/%'
  AND image_path NOT LIKE '/carwash_project/%';

-- 4) If image_path is a filesystem path (contains BACKEND with backslashes) try to normalize by extracting filename
-- NOTE: this is a best-effort; inspect results after running.
UPDATE user_vehicles
SET image_path = CONCAT('/carwash_project/backend/uploads/vehicles/', REPLACE(SUBSTRING_INDEX(REPLACE(image_path, '\\', '/'), '/', -1), ' ', '_'))
WHERE (image_path LIKE '%\\%'
       OR image_path LIKE '%/backend/uploads/%')
  AND image_path NOT LIKE '/carwash_project/%';

-- 5) Optional: remove leading double-slashes
UPDATE user_vehicles
SET image_path = TRIM(LEADING '/' FROM image_path)
WHERE image_path LIKE '//%';

-- 6) Final sanity-check: show rows which still don't start with expected prefix
SELECT id, image_path FROM user_vehicles WHERE image_path IS NOT NULL AND image_path <> '' AND image_path NOT LIKE '/carwash_project/%' LIMIT 100;

-- If anything looks wrong, restore from backup. Always test on staging.
