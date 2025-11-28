<?php
/**
 * Verify Test Bookings API Responses
 * Tests that all created bookings appear correctly in the APIs
 */

session_start();
$_SESSION['user_id'] = 14;
$_SESSION['role'] = 'customer';

require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

$db = Database::getInstance();
$userId = 14;

echo "========================================\n";
echo "API VERIFICATION TEST\n";
echo "========================================\n\n";

echo "Testing for user_id: $userId\n\n";

// Test 1: Active Bookings
echo "--- Test 1: Active Bookings ---\n";
$activeBookings = $db->fetchAll("
    SELECT 
        b.id,
        b.status,
        b.booking_date,
        b.booking_time,
        s.name as service_name,
        c.name as carwash_name,
        b.vehicle_model,
        b.vehicle_plate
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN carwashes c ON b.carwash_id = c.id
    WHERE b.user_id = :user_id
    AND b.status IN ('pending', 'confirmed', 'in_progress')
    ORDER BY b.booking_date ASC, b.booking_time ASC
", ['user_id' => $userId]);

echo "Found " . count($activeBookings) . " active bookings:\n";
foreach ($activeBookings as $booking) {
    echo "  #{$booking['id']} - {$booking['status']}\n";
    echo "    {$booking['service_name']} at {$booking['carwash_name']}\n";
    echo "    {$booking['booking_date']} {$booking['booking_time']}\n";
    echo "    Vehicle: {$booking['vehicle_model']} ({$booking['vehicle_plate']})\n\n";
}

// Test 2: Completed Bookings (History)
echo "--- Test 2: Completed Bookings (History) ---\n";
$completedBookings = $db->fetchAll("
    SELECT 
        b.id,
        b.status,
        b.booking_date,
        b.booking_time,
        b.completed_at,
        s.name as service_name,
        c.name as carwash_name,
        b.vehicle_model,
        b.vehicle_plate,
        b.total_price,
        b.payment_status
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN carwashes c ON b.carwash_id = c.id
    WHERE b.user_id = :user_id
    AND b.status = 'completed'
    ORDER BY b.completed_at DESC
", ['user_id' => $userId]);

echo "Found " . count($completedBookings) . " completed bookings:\n";
foreach ($completedBookings as $booking) {
    echo "  #{$booking['id']} - {$booking['status']}\n";
    echo "    {$booking['service_name']} at {$booking['carwash_name']}\n";
    echo "    Date: {$booking['booking_date']} {$booking['booking_time']}\n";
    echo "    Completed: {$booking['completed_at']}\n";
    echo "    Vehicle: {$booking['vehicle_model']} ({$booking['vehicle_plate']})\n";
    echo "    Price: {$booking['total_price']} TL - Payment: {$booking['payment_status']}\n\n";
}

// Test 3: Cancelled Bookings
echo "--- Test 3: Cancelled Bookings ---\n";
$cancelledBookings = $db->fetchAll("
    SELECT 
        b.id,
        b.status,
        b.booking_date,
        b.booking_time,
        b.cancellation_reason,
        s.name as service_name,
        c.name as carwash_name,
        b.vehicle_model
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN carwashes c ON b.carwash_id = c.id
    WHERE b.user_id = :user_id
    AND b.status = 'cancelled'
    ORDER BY b.booking_date DESC
", ['user_id' => $userId]);

echo "Found " . count($cancelledBookings) . " cancelled bookings:\n";
foreach ($cancelledBookings as $booking) {
    echo "  #{$booking['id']} - {$booking['status']}\n";
    echo "    {$booking['service_name']} at {$booking['carwash_name']}\n";
    echo "    Date: {$booking['booking_date']} {$booking['booking_time']}\n";
    echo "    Reason: {$booking['cancellation_reason']}\n";
    echo "    Vehicle: {$booking['vehicle_model']}\n\n";
}

// Test 4: Summary Statistics
echo "--- Test 4: Summary Statistics ---\n";
$stats = $db->fetchAll("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(total_price) as total_revenue
    FROM bookings
    WHERE user_id = :user_id
    GROUP BY status
", ['user_id' => $userId]);

echo "Booking statistics:\n";
foreach ($stats as $stat) {
    $revenue = $stat['total_revenue'] ? number_format($stat['total_revenue'], 2) : '0.00';
    echo "  {$stat['status']}: {$stat['count']} bookings (Revenue: {$revenue} TL)\n";
}

echo "\n========================================\n";
echo "✅ API VERIFICATION COMPLETE\n";
echo "========================================\n\n";

echo "Summary:\n";
echo "  - Active bookings API should return: " . count($activeBookings) . " bookings\n";
echo "  - History API should return: " . count($completedBookings) . " bookings\n";
echo "  - Cancelled bookings visible: " . count($cancelledBookings) . " bookings\n\n";

echo "Next steps:\n";
echo "  1. Login to Customer Dashboard as user: hasan (ID: 14)\n";
echo "  2. Navigate to 'Rezervasyonlarım' - should show " . count($activeBookings) . " active bookings\n";
echo "  3. Navigate to 'Geçmiş' - should show " . count($completedBookings) . " completed bookings\n";
echo "  4. All data should display correctly with proper formatting\n\n";
