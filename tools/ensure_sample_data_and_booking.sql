-- Ensure at least one carwash_profiles row
INSERT INTO carwash_profiles (business_name, address)
SELECT 'Sample Carwash', 'Sample Address'
WHERE NOT EXISTS (SELECT 1 FROM carwash_profiles LIMIT 1);

-- Ensure at least one services row (status column exists)
INSERT INTO services (name, price, status)
SELECT 'Sample Wash', 10.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM services LIMIT 1);

-- Insert a booking for user 14 using the first carwash and service if user has no bookings
INSERT INTO bookings (user_id, status, carwash_id, service_id, created_at)
SELECT 14, 'pending', cp.id, s.id, NOW()
FROM (SELECT id FROM carwash_profiles LIMIT 1) cp JOIN (SELECT id FROM services LIMIT 1) s
WHERE NOT EXISTS (SELECT 1 FROM bookings WHERE user_id = 14 LIMIT 1);

SELECT 'ensure_sample_data_and_booking.sql executed' AS _message;
