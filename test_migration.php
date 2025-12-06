<?php
/**
 * Migration Test Script
 * Tests all core database operations after migration
 */

require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

echo "=== CarWash Migration Test ===\n\n";

$db = Database::getInstance();

// Test 1: Users
echo "1. Testing Users Table\n";
$users = $db->fetchAll("SELECT id, email, role, is_active FROM users");
echo "   Users count: " . count($users) . "\n";
foreach ($users as $u) {
    echo "   - {$u['email']} (role={$u['role']}, active={$u['is_active']})\n";
}

// Test 2: Carwashes
echo "\n2. Testing Carwashes Table\n";
$carwashes = $db->fetchAll("SELECT id, name, status, user_id, rating FROM carwashes");
echo "   Carwashes count: " . count($carwashes) . "\n";
foreach ($carwashes as $c) {
    echo "   - ID:{$c['id']} {$c['name']} (status={$c['status']}, rating={$c['rating']})\n";
}

// Test 3: Bookings
echo "\n3. Testing Bookings Table\n";
$bookings = $db->fetchAll("SELECT id, booking_number, status, user_id, carwash_id FROM bookings ORDER BY id DESC LIMIT 5");
echo "   Recent bookings:\n";
foreach ($bookings as $b) {
    echo "   - {$b['booking_number']} (status={$b['status']}, user={$b['user_id']}, carwash={$b['carwash_id']})\n";
}

// Test 4: Services
echo "\n4. Testing Services Table\n";
$services = $db->fetchAll("SELECT id, name, price, carwash_id FROM services");
echo "   Services count: " . count($services) . "\n";
foreach ($services as $s) {
    echo "   - {$s['name']} (price={$s['price']}, carwash_id={$s['carwash_id']})\n";
}

// Test 5: Reviews
echo "\n5. Testing Reviews Table\n";
$reviews = $db->fetchAll("SELECT id, rating, carwash_id, user_id FROM reviews");
echo "   Reviews count: " . count($reviews) . "\n";

// Test 6: User Vehicles
echo "\n6. Testing User Vehicles Table\n";
$vehicles = $db->fetchAll("SELECT id, brand, model, license_plate, user_id FROM user_vehicles ORDER BY id DESC LIMIT 5");
echo "   Recent vehicles:\n";
foreach ($vehicles as $v) {
    echo "   - {$v['brand']} {$v['model']} (plate={$v['license_plate']})\n";
}

// Test 7: UI Labels
echo "\n7. Testing UI Labels Table\n";
$labels = $db->fetchAll("SELECT language_code, COUNT(*) as cnt FROM ui_labels GROUP BY language_code");
echo "   Labels by language:\n";
foreach ($labels as $l) {
    echo "   - {$l['language_code']}: {$l['cnt']} labels\n";
}

// Test 8: Favorites
echo "\n8. Testing Favorites Table\n";
$favorites = $db->fetchAll("SELECT COUNT(*) as cnt FROM favorites");
echo "   Favorites count: {$favorites[0]['cnt']}\n";

// Test 9: Notifications
echo "\n9. Testing Notifications Table\n";
$notifications = $db->fetchAll("SELECT COUNT(*) as cnt FROM notifications");
echo "   Notifications count: {$notifications[0]['cnt']}\n";

// Test 10: Payments
echo "\n10. Testing Payments Table\n";
$payments = $db->fetchAll("SELECT COUNT(*) as cnt FROM payments");
echo "   Payments count: {$payments[0]['cnt']}\n";

// Test 11: Service Categories
echo "\n11. Testing Service Categories Table\n";
$categories = $db->fetchAll("SELECT id, name FROM service_categories");
echo "   Categories count: " . count($categories) . "\n";
foreach ($categories as $c) {
    echo "   - {$c['name']}\n";
}

// Test 12: Booking Status Table
echo "\n12. Testing Booking Status Table\n";
try {
    $statuses = $db->fetchAll("SELECT * FROM booking_status");
    echo "   Booking statuses count: " . count($statuses) . "\n";
} catch (Exception $e) {
    echo "   Table exists but is empty or has error\n";
}

// Summary
echo "\n=== Migration Test Summary ===\n";
$tables = $db->fetchAll("
    SELECT TABLE_NAME, TABLE_ROWS 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'carwash_db' 
    AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_ROWS DESC
");
echo "Total tables: " . count($tables) . "\n";
$total_rows = array_sum(array_column($tables, 'TABLE_ROWS'));
echo "Total rows: {$total_rows}\n";

echo "\n=== All Tests Completed ===\n";
