<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
use App\Classes\Database;

try {
    $db = Database::getInstance();
    
    echo "Testing bookings query...\n\n";
    
    $bookings = $db->fetchAll("
        SELECT 
            b.id as booking_id,
            b.booking_date,
            b.booking_time,
            b.status,
            b.total_price,
            b.vehicle_plate,
            s.name as service_name,
            c.name as carwash_name
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN carwashes c ON b.carwash_id = c.id
        WHERE b.user_id = :user_id
        AND b.status = 'completed'
        LIMIT 5
    ", ['user_id' => 1]);
    
    echo "Found " . count($bookings) . " completed bookings\n\n";
    
    if (count($bookings) > 0) {
        echo "Sample booking:\n";
        print_r($bookings[0]);
    } else {
        echo "No completed bookings found for user_id = 1\n";
        
        // Check if there are any bookings at all
        $allBookings = $db->fetchAll("SELECT id, user_id, status FROM bookings LIMIT 5");
        echo "\nSample of all bookings:\n";
        print_r($allBookings);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>