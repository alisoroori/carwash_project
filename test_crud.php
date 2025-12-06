<?php
/**
 * CRUD Operations Test Script
 * Tests create, read, update, delete for all core tables
 */

require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

echo "=== CarWash CRUD Operations Test ===\n\n";

$db = Database::getInstance();
$errors = [];
$successes = [];

// Test 1: Customer Registration
echo "1. Testing Customer Registration...\n";
$testEmail = 'test_migration_' . time() . '@test.com';
$testPassword = password_hash('password123', PASSWORD_DEFAULT);

try {
    $userId = $db->insert('users', [
        'username' => 'testmigration' . time(),
        'email' => $testEmail,
        'password' => $testPassword,
        'full_name' => 'Test Migration User',
        'phone' => '5551234567',
        'role' => 'customer',
        'is_active' => 1
    ]);
    echo "   ✓ Customer created with ID: {$userId}\n";
    $successes[] = 'Customer Registration';
    
    // Clean up
    $db->delete('users', 'id = :id', ['id' => $userId]);
    echo "   ✓ Test customer cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = 'Customer Registration: ' . $e->getMessage();
}

// Test 2: Booking Creation
echo "\n2. Testing Booking Creation...\n";
try {
    $bookingId = $db->insert('bookings', [
        'booking_number' => 'BKTEST' . time(),
        'user_id' => 14,
        'carwash_id' => 7,
        'service_id' => 4,
        'booking_date' => date('Y-m-d', strtotime('+1 day')),
        'booking_time' => '10:00:00',
        'vehicle_type' => 'sedan',
        'status' => 'pending',
        'total_price' => 45.00
    ]);
    echo "   ✓ Booking created with ID: {$bookingId}\n";
    $successes[] = 'Booking Creation';
    
    // Clean up
    $db->delete('bookings', 'id = :id', ['id' => $bookingId]);
    echo "   ✓ Test booking cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = 'Booking Creation: ' . $e->getMessage();
}

// Test 3: Vehicle Creation
echo "\n3. Testing Vehicle Creation...\n";
try {
    $vehicleId = $db->insert('user_vehicles', [
        'user_id' => 14,
        'brand' => 'TestBrand',
        'model' => 'TestModel',
        'license_plate' => 'TEST' . time(),
        'vehicle_type' => 'sedan'
    ]);
    echo "   ✓ Vehicle created with ID: {$vehicleId}\n";
    $successes[] = 'Vehicle Creation';
    
    // Clean up
    $db->delete('user_vehicles', 'id = :id', ['id' => $vehicleId]);
    echo "   ✓ Test vehicle cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = 'Vehicle Creation: ' . $e->getMessage();
}

// Test 4: Review Creation
echo "\n4. Testing Review Creation...\n";
try {
    $reviewId = $db->insert('reviews', [
        'user_id' => 14,
        'carwash_id' => 7,
        'rating' => 5,
        'comment' => 'Test review from migration'
    ]);
    echo "   ✓ Review created with ID: {$reviewId}\n";
    $successes[] = 'Review Creation';
    
    // Clean up
    $db->delete('reviews', 'id = :id', ['id' => $reviewId]);
    echo "   ✓ Test review cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = 'Review Creation: ' . $e->getMessage();
}

// Test 5: Service Creation
echo "\n5. Testing Service Creation...\n";
try {
    $serviceId = $db->insert('services', [
        'carwash_id' => 7,
        'name' => 'Test Service',
        'description' => 'Test service from migration',
        'price' => 99.99,
        'duration' => 30,
        'category' => 'premium'
    ]);
    echo "   ✓ Service created with ID: {$serviceId}\n";
    $successes[] = 'Service Creation';
    
    // Clean up
    $db->delete('services', 'id = :id', ['id' => $serviceId]);
    echo "   ✓ Test service cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = 'Service Creation: ' . $e->getMessage();
}

// Test 6: Favorite Creation
echo "\n6. Testing Favorite Creation...\n";
try {
    $favoriteId = $db->insert('favorites', [
        'user_id' => 14,
        'carwash_id' => 7
    ]);
    echo "   ✓ Favorite created with ID: {$favoriteId}\n";
    $successes[] = 'Favorite Creation';
    
    // Clean up
    $db->delete('favorites', 'id = :id', ['id' => $favoriteId]);
    echo "   ✓ Test favorite cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = 'Favorite Creation: ' . $e->getMessage();
}

// Test 7: Notification Creation
echo "\n7. Testing Notification Creation...\n";
try {
    $notificationId = $db->insert('notifications', [
        'user_id' => 14,
        'title' => 'Test Notification',
        'message' => 'This is a test notification',
        'type' => 'info'
    ]);
    echo "   ✓ Notification created with ID: {$notificationId}\n";
    $successes[] = 'Notification Creation';
    
    // Clean up
    $db->delete('notifications', 'id = :id', ['id' => $notificationId]);
    echo "   ✓ Test notification cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = 'Notification Creation: ' . $e->getMessage();
}

// Test 8: Payment Creation
echo "\n8. Testing Payment Creation...\n";
try {
    // First create a booking for the payment
    $testBookingId = $db->insert('bookings', [
        'booking_number' => 'BKPAY' . time(),
        'user_id' => 14,
        'carwash_id' => 7,
        'service_id' => 4,
        'booking_date' => date('Y-m-d'),
        'booking_time' => '11:00:00',
        'vehicle_type' => 'sedan',
        'status' => 'pending',
        'total_price' => 100.00
    ]);
    
    $paymentId = $db->insert('payments', [
        'booking_id' => $testBookingId,
        'amount' => 100.00,
        'payment_method' => 'cash',
        'status' => 'completed'
    ]);
    echo "   ✓ Payment created with ID: {$paymentId}\n";
    $successes[] = 'Payment Creation';
    
    // Clean up
    $db->delete('payments', 'id = :id', ['id' => $paymentId]);
    $db->delete('bookings', 'id = :id', ['id' => $testBookingId]);
    echo "   ✓ Test payment cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = 'Payment Creation: ' . $e->getMessage();
}

// Summary
echo "\n=== CRUD Test Summary ===\n";
echo "Successful: " . count($successes) . "/" . (count($successes) + count($errors)) . "\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  ✗ {$error}\n";
    }
}

if (!empty($successes)) {
    echo "\nSuccesses:\n";
    foreach ($successes as $success) {
        echo "  ✓ {$success}\n";
    }
}

echo "\n=== CRUD Tests Completed ===\n";
