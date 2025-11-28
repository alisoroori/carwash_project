<?php
// Quick test of auto-completion system
require_once __DIR__ . '/backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();

echo "Creating past booking...\n";

// Insert a booking from yesterday
$sql = "INSERT INTO bookings (user_id, service_id, carwash_id, vehicle_plate, booking_date, booking_time, total_price, status, payment_status, payment_method, created_at) VALUES (14, 1, 1, '34MNO987', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00', 50.00, 'confirmed', 'paid', 'card', NOW())";

$db->execute($sql);
$bookingId = $db->getLastInsertId();

echo "Created booking #$bookingId\n";
echo "Running cron...\n\n";

// Run cron
require __DIR__ . '/backend/cron/auto_complete_bookings.php';

echo "\nChecking result...\n";

$result = $db->fetchOne("SELECT id, status, completed_at FROM bookings WHERE id = ?", [$bookingId]);
echo "Status: {$result['status']}\n";
echo "Completed: " . ($result['completed_at'] ?: 'NULL') . "\n";

if ($result['status'] === 'completed') {
    echo "\n✅ SUCCESS! Auto-completion works!\n";
} else {
    echo "\n❌ FAILED - booking was not completed\n";
}
