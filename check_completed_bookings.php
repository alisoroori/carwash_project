<?php
require_once 'backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();

echo "Checking for completed bookings\n";
echo str_repeat("=", 60) . "\n\n";

// Check bookings for user 14
$bookings = $db->fetchAll("
    SELECT id, user_id, status, booking_date, booking_time, completed_at
    FROM bookings
    WHERE user_id = 14
    ORDER BY created_at DESC
", []);

echo "Total bookings for user 14: " . count($bookings) . "\n\n";

foreach ($bookings as $booking) {
    echo "ID: {$booking['id']}, Status: {$booking['status']}, Date: {$booking['booking_date']} {$booking['booking_time']}, Completed: " . ($booking['completed_at'] ?? 'NULL') . "\n";
}

// Count by status
$counts = $db->fetchAll("
    SELECT status, COUNT(*) as count
    FROM bookings
    WHERE user_id = 14
    GROUP BY status
", []);

echo "\nCounts by status:\n";
foreach ($counts as $count) {
    echo "{$count['status']}: {$count['count']}\n";
}
