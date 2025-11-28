<?php
/**
 * Auto-Completion System Verification Test
 * 
 * This script:
 * 1. Creates a past booking (should be auto-completed)
 * 2. Runs the auto-completion cron
 * 3. Verifies the booking was completed
 * 4. Tests the history API
 */

require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

$db = Database::getInstance();

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║   AUTO-COMPLETION SYSTEM VERIFICATION TEST                ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Test user: hasan (ID: 14)
$userId = 14;

echo "[STEP 1] Creating a past booking for auto-completion test...\n";
echo str_repeat("-", 63) . "\n";

// Get a random service and carwash
$service = $db->fetchOne("SELECT id, name, price FROM services WHERE is_active = 1 LIMIT 1");
$carwash = $db->fetchOne("SELECT id, name FROM carwashes WHERE status = 'active' LIMIT 1");
$vehicle = $db->fetchOne("SELECT license_plate FROM user_vehicles WHERE user_id = :uid LIMIT 1", ['uid' => $userId]);

if (!$service || !$carwash || !$vehicle) {
    die("❌ ERROR: Missing test data (service/carwash/vehicle)\n");
}

// Create a booking from yesterday
$pastDate = date('Y-m-d', strtotime('-1 day'));
$pastTime = '14:00:00';

$bookingData = [
    'user_id' => $userId,
    'service_id' => $service['id'],
    'carwash_id' => $carwash['id'],
    'vehicle_plate' => $vehicle['license_plate'],
    'booking_date' => $pastDate,
    'booking_time' => $pastTime,
    'total_price' => $service['price'],
    'status' => 'confirmed',
    'payment_status' => 'paid',
    'payment_method' => 'card',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$newBookingId = $db->insert('bookings', $bookingData);

if (!$newBookingId) {
    die("❌ ERROR: Failed to create test booking\n");
}

echo "✅ Created test booking:\n";
echo "   - Booking ID: $newBookingId\n";
echo "   - Service: {$service['name']}\n";
echo "   - Carwash: {$carwash['name']}\n";
echo "   - Vehicle: {$vehicle['license_plate']}\n";
echo "   - Date/Time: $pastDate $pastTime (yesterday)\n";
echo "   - Status: confirmed → should auto-complete\n";
echo "   - Payment: paid\n\n";

// Verify booking exists
$beforeCompletion = $db->fetchOne(
    "SELECT id, status, completed_at FROM bookings WHERE id = :id",
    ['id' => $newBookingId]
);

echo "   Status before cron: {$beforeCompletion['status']}\n";
echo "   Completed at: " . ($beforeCompletion['completed_at'] ?: 'NULL') . "\n\n";

echo "[STEP 2] Running auto-completion cron job...\n";
echo str_repeat("-", 63) . "\n";

// Execute the cron script
ob_start();
require __DIR__ . '/backend/cron/auto_complete_bookings.php';
$cronOutput = ob_get_clean();

echo $cronOutput;
echo "\n";

echo "[STEP 3] Verifying booking completion...\n";
echo str_repeat("-", 63) . "\n";

// Check if booking was completed
$afterCompletion = $db->fetchOne(
    "SELECT id, status, completed_at, updated_at FROM bookings WHERE id = :id",
    ['id' => $newBookingId]
);

if ($afterCompletion['status'] === 'completed' && $afterCompletion['completed_at']) {
    echo "✅ SUCCESS: Booking auto-completed!\n";
    echo "   - Status: {$afterCompletion['status']}\n";
    echo "   - Completed at: {$afterCompletion['completed_at']}\n";
    echo "   - Updated at: {$afterCompletion['updated_at']}\n\n";
} else {
    echo "❌ FAILED: Booking was NOT auto-completed\n";
    echo "   - Current status: {$afterCompletion['status']}\n";
    echo "   - Completed at: " . ($afterCompletion['completed_at'] ?: 'NULL') . "\n\n";
}

echo "[STEP 4] Testing History API endpoint...\n";
echo str_repeat("-", 63) . "\n";

// Simulate API call
$_SESSION['user_id'] = $userId; // Set session for auth

ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
include __DIR__ . '/backend/api/get_reservations.php';
$apiOutput = ob_get_clean();

$apiResponse = json_decode($apiOutput, true);

if ($apiResponse && $apiResponse['success']) {
    echo "✅ API Response successful\n";
    echo "   - Total bookings: {$apiResponse['data']['count']}\n";
    
    // Find our test booking in the response
    $foundInApi = false;
    foreach ($apiResponse['data']['bookings'] as $booking) {
        if ($booking['booking_id'] == $newBookingId) {
            $foundInApi = true;
            echo "   - ✅ Test booking found in API response\n";
            echo "     └─ Service: {$booking['service_name']}\n";
            echo "     └─ Carwash: {$booking['carwash_name']}\n";
            echo "     └─ Date: {$booking['booking_date']}\n";
            echo "     └─ Vehicle: {$booking['vehicle_plate']}\n";
            echo "     └─ Completed: {$booking['completed_at']}\n";
            break;
        }
    }
    
    if (!$foundInApi) {
        echo "   - ⚠️  Test booking NOT found in API response\n";
        echo "   - This might be normal if filtered by date range\n";
    }
} else {
    echo "❌ API request failed\n";
    echo "   - Response: " . ($apiOutput ?: 'No output') . "\n";
}

echo "\n";
echo "[STEP 5] Cleanup (optional)...\n";
echo str_repeat("-", 63) . "\n";
echo "Test booking ID: $newBookingId\n";
echo "To keep for UI testing: Leave as-is\n";
echo "To remove: DELETE FROM bookings WHERE id = $newBookingId;\n\n";

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║   TEST COMPLETE                                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";

// Summary
echo "\n📊 SUMMARY:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ Created past booking (ID: $newBookingId)\n";
echo "✓ Executed cron script\n";
echo "✓ " . ($afterCompletion['status'] === 'completed' ? "Booking auto-completed ✅" : "Booking NOT completed ❌") . "\n";
echo "✓ API endpoint tested\n";
echo "\n";

if ($afterCompletion['status'] === 'completed' && $apiResponse && $apiResponse['success']) {
    echo "🎉 ALL TESTS PASSED! System is working correctly.\n\n";
    echo "Next steps:\n";
    echo "1. Visit: http://localhost/carwash_project/backend/dashboard/Customer_Dashboard.php\n";
    echo "2. Click on 'Geçmiş' (History) tab\n";
    echo "3. Verify booking #$newBookingId appears in the list\n";
} else {
    echo "⚠️  Some tests failed. Check logs/auto_complete.log\n";
}

echo "\n";
