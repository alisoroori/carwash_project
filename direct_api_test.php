<?php
session_start();
$_SESSION['user_id'] = 14;
$_SESSION['role'] = 'customer';
$_SESSION['name'] = 'Test User';
$_SESSION['email'] = 'test@example.com';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

// Capture and display
ob_start();
require_once 'backend/api/get_reservations.php';
$output = ob_get_clean();

header('Content-Type: text/plain');
echo "Raw API Output:\n";
echo str_repeat("=", 70) . "\n";
echo $output . "\n";
echo str_repeat("=", 70) . "\n\n";

$json = json_decode($output, true);
if ($json) {
    echo "Decoded Structure:\n";
    echo "- success: " . ($json['success'] ? 'true' : 'false') . "\n";
    echo "- message: " . ($json['message'] ?? 'N/A') . "\n";
    echo "- count: " . ($json['count'] ?? 'N/A') . "\n";
    echo "- bookings: " . (isset($json['bookings']) ? 'EXISTS' : 'MISSING') . "\n";
    if (isset($json['bookings'])) {
        echo "- bookings length: " . count($json['bookings']) . "\n";
    }
}
