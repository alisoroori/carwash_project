<?php
session_start();
$_SESSION['user_id'] = 14;
$_SESSION['role'] = 'customer';

require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    echo "Testing get_reservations.php API..." . PHP_EOL;
    echo "User ID: $userId" . PHP_EOL . PHP_EOL;

    // Test the exact query from get_reservations.php
    $bookings = $db->fetchAll("
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
        LEFT JOIN user_vehicles uv ON b.vehicle_plate = uv.license_plate AND uv.user_id = b.user_id
        WHERE b.user_id = :user_id
        AND b.status = 'completed'
        ORDER BY b.completed_at DESC, b.created_at DESC
    ", ['user_id' => $userId]);

    echo "Query executed successfully!" . PHP_EOL;
    echo "Found " . count($bookings) . " completed bookings" . PHP_EOL . PHP_EOL;
    
    if (count($bookings) > 0) {
        echo "Sample booking data:" . PHP_EOL;
        print_r($bookings[0]);
    } else {
        echo "No completed bookings found." . PHP_EOL;
        
        // Check for any bookings at all
        $allBookings = $db->fetchAll("
            SELECT id, user_id, status 
            FROM bookings 
            WHERE user_id = :user_id
            LIMIT 5
        ", ['user_id' => $userId]);
        
        echo PHP_EOL . "Sample of all bookings for user $userId:" . PHP_EOL;
        print_r($allBookings);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
