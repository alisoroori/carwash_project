<?php
// Test get_reservations.php API
session_start();
$_SESSION['user_id'] = 14; // hasan user

require_once 'backend/includes/bootstrap.php';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

ob_start();
include 'backend/api/get_reservations.php';
$output = ob_get_clean();

echo "Testing Get Reservations API\n";
echo str_repeat("=", 60) . "\n\n";

echo "API Response:\n";
echo $output . "\n\n";

$response = json_decode($output, true);

if ($response && $response['success']) {
    echo "✅ SUCCESS\n";
    echo "Total bookings: " . ($response['count'] ?? 0) . "\n";
    
    if (isset($response['bookings']) && count($response['bookings']) > 0) {
        echo "\nFirst booking:\n";
        print_r($response['bookings'][0]);
    } else {
        echo "\n⚠️  No bookings returned\n";
    }
} else {
    echo "❌ FAILED\n";
    echo "Message: " . ($response['message'] ?? 'Unknown error') . "\n";
}
