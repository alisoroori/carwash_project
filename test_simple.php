<?php
/**
 * Simple CRUD Test
 */

require_once __DIR__ . '/backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();

echo "=== Simple CRUD Test ===\n";

// Test 1
echo "1. Customer: ";
try {
    $id = $db->insert('users', [
        'username' => 'test' . time(),
        'email' => 'test' . time() . '@test.com',
        'password' => password_hash('test', PASSWORD_DEFAULT),
        'full_name' => 'Test User',
        'role' => 'customer',
        'is_active' => 1
    ]);
    $db->delete('users', ['id' => $id]);
    echo "OK (ID: $id)\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// Test 2
echo "2. Booking: ";
try {
    $id = $db->insert('bookings', [
        'booking_number' => 'BK' . time(),
        'user_id' => 14,
        'carwash_id' => 7,
        'service_id' => 4,
        'booking_date' => date('Y-m-d'),
        'booking_time' => '10:00:00',
        'vehicle_type' => 'sedan',
        'status' => 'pending',
        'total_price' => 45.00
    ]);
    $db->delete('bookings', ['id' => $id]);
    echo "OK (ID: $id)\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// Test 3
echo "3. Vehicle: ";
try {
    $id = $db->insert('user_vehicles', [
        'user_id' => 14,
        'brand' => 'Test',
        'model' => 'Model',
        'license_plate' => 'T' . time(),
        'vehicle_type' => 'sedan'
    ]);
    $db->delete('user_vehicles', ['id' => $id]);
    echo "OK (ID: $id)\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// Test 4
echo "4. Review: ";
try {
    $id = $db->insert('reviews', [
        'user_id' => 14,
        'carwash_id' => 7,
        'rating' => 5,
        'comment' => 'Test'
    ]);
    $db->delete('reviews', ['id' => $id]);
    echo "OK (ID: $id)\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// Test 5
echo "5. Service: ";
try {
    $id = $db->insert('services', [
        'carwash_id' => 7,
        'name' => 'Test Service',
        'price' => 50.00,
        'duration' => 30
    ]);
    $db->delete('services', ['id' => $id]);
    echo "OK (ID: $id)\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// Test 6
echo "6. Favorite: ";
try {
    $id = $db->insert('favorites', [
        'user_id' => 14,
        'carwash_id' => 7
    ]);
    $db->delete('favorites', ['id' => $id]);
    echo "OK (ID: $id)\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

// Test 7
echo "7. Notification: ";
try {
    $id = $db->insert('notifications', [
        'user_id' => 14,
        'title' => 'Test',
        'message' => 'Test notification',
        'type' => 'info'
    ]);
    $db->delete('notifications', ['id' => $id]);
    echo "OK (ID: $id)\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";
