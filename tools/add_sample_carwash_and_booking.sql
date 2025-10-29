-- Create a minimal carwash_profile for user 14 if the user exists and no profile exists
INSERT INTO carwash_profiles (user_id, business_name, address, city, country)
SELECT 14, 'Sample Carwash for hasan', 'Sample Address', 'Sample City', 'Turkey'
WHERE EXISTS (SELECT 1 FROM users WHERE id = 14)
  AND NOT EXISTS (SELECT 1 FROM carwash_profiles WHERE user_id = 14);

-- Ensure at least one services row exists
INSERT INTO services (name, price, status)
SELECT 'Sample Wash', 10.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM services LIMIT 1);

-- Insert booking for user 14 now that a carwash and service should exist
INSERT INTO bookings (user_id, status, carwash_id, service_id, created_at)
SELECT 14, 'pending', cp.id, s.id, NOW()
FROM (SELECT id FROM carwash_profiles WHERE user_id = 14 LIMIT 1) cp
JOIN (SELECT id FROM services LIMIT 1) s
WHERE NOT EXISTS (SELECT 1 FROM bookings WHERE user_id = 14 LIMIT 1);

SELECT 'add_sample_carwash_and_booking.sql executed' AS _message;
