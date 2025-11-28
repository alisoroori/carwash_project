<?php
require_once 'backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();

echo "Sample Completed Booking Check\n";
echo str_repeat("=", 70) . "\n\n";

$booking = $db->fetchOne("
    SELECT 
        b.*,
        s.name as service_name,
        c.name as carwash_name,
        uv.brand, uv.model, uv.license_plate
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN carwashes c ON b.carwash_id = c.id
    LEFT JOIN user_vehicles uv ON b.vehicle_plate COLLATE utf8mb4_general_ci = uv.license_plate AND uv.user_id = b.user_id
    WHERE b.user_id = 14 AND b.status = 'completed'
    LIMIT 1
");

if ($booking) {
    echo "✅ Found completed booking:\n\n";
    echo "ID: {$booking['id']}\n";
    echo "Service: " . ($booking['service_name'] ?? 'NULL') . "\n";
    echo "Carwash: " . ($booking['carwash_name'] ?? 'NULL') . "\n";
    echo "Date: {$booking['booking_date']} {$booking['booking_time']}\n";
    echo "Vehicle Plate: {$booking['vehicle_plate']}\n";
    echo "Vehicle Brand: " . ($booking['brand'] ?? 'NULL') . "\n";
    echo "Vehicle Model: " . ($booking['model'] ?? 'NULL') . "\n";
    echo "Price: {$booking['total_price']}\n";
    echo "Status: {$booking['status']}\n";
    echo "Completed at: " . ($booking['completed_at'] ?? 'NULL') . "\n";
} else {
    echo "❌ No completed bookings found\n";
}
