<?php
require_once __DIR__ . '/backend/includes/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing FIXED query with COLLATE..." . PHP_EOL . PHP_EOL;
    
    // Test the fixed query
    $query = "
        SELECT 
            b.id as booking_id,
            b.booking_date,
            b.booking_time,
            b.status,
            b.total_price,
            b.payment_status,
            b.payment_method,
            b.vehicle_plate,
            b.vehicle_model,
            b.vehicle_color,
            b.vehicle_type,
            b.notes,
            b.completed_at,
            b.created_at,
            s.name as service_name,
            s.description as service_description,
            s.price as service_price,
            s.category as service_category,
            s.duration as service_duration,
            c.name as carwash_name,
            c.address as carwash_address,
            c.city as carwash_city,
            c.phone as carwash_phone,
            uv.brand as vehicle_brand,
            uv.model as vehicle_model_full,
            uv.year as vehicle_year
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN carwashes c ON b.carwash_id = c.id
        LEFT JOIN user_vehicles uv ON b.vehicle_plate COLLATE utf8mb4_general_ci = uv.license_plate AND uv.user_id = b.user_id
        WHERE b.user_id = :user_id
        AND b.status = 'completed'
        ORDER BY b.completed_at DESC, b.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => 14]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✓ Query executed successfully!" . PHP_EOL;
    echo "✓ Found " . count($results) . " completed bookings for user_id=14" . PHP_EOL . PHP_EOL;
    
    if (count($results) > 0) {
        echo "First booking data:" . PHP_EOL;
        print_r($results[0]);
    } else {
        echo "No completed bookings found." . PHP_EOL;
        echo "Checking for ANY bookings..." . PHP_EOL . PHP_EOL;
        
        $stmt = $pdo->query("SELECT id, user_id, status, booking_date FROM bookings WHERE user_id = 14 LIMIT 5");
        $allBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "All bookings for user_id=14:" . PHP_EOL;
        print_r($allBookings);
    }
    
} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . PHP_EOL;
}
