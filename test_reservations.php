<?php
// Test script to check get_reservations API
session_start();
// Set up proper session data that Auth class expects
$_SESSION['user'] = [
    'id' => 1,
    'email' => 'test@example.com',
    'name' => 'Test User'
];
$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'test@example.com';
$_SESSION['name'] = 'Test User';

echo "Testing get_reservations.php API...\n";

try {
    require_once 'backend/api/get_reservations.php';
} catch (Exception $e) {
    echo 'Exception caught: ' . $e->getMessage() . PHP_EOL;
    echo 'Stack trace:' . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}
?>