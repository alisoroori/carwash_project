-- Safely insert a sample booking for user 14 only if carwash_profiles and services have rows and this user has no bookings
INSERT INTO bookings (user_id, status, carwash_id, service_id, created_at)
SELECT 14, 'pending', cp.id, s.id, NOW()
FROM (SELECT id FROM carwash_profiles LIMIT 1) cp JOIN (SELECT id FROM services LIMIT 1) s
WHERE NOT EXISTS (SELECT 1 FROM bookings WHERE user_id = 14 LIMIT 1);

SELECT 'insert_sample_booking_safe.sql executed' AS _message;
