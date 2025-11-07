<?php
// Test the vehicle API endpoint
session_start();

// Simulate user session (user_id 14 has vehicles)
$_SESSION['user_id'] = 14;

echo "Testing vehicle API endpoint...\n\n";

// Include the API file to simulate a GET request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'list';

// Capture output
ob_start();
require __DIR__ . '/backend/dashboard/vehicle_api.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";

// Try to decode JSON
$data = json_decode($output, true);
if ($data) {
    echo "\n✅ Valid JSON response\n";
    echo "Status: " . ($data['status'] ?? 'N/A') . "\n";
    echo "Message: " . ($data['message'] ?? 'N/A') . "\n";
    
    if (isset($data['vehicles'])) {
        echo "Vehicles count: " . count($data['vehicles']) . "\n";
    } elseif (isset($data['data']['vehicles'])) {
        echo "Vehicles count: " . count($data['data']['vehicles']) . "\n";
    }
} else {
    echo "\n❌ Invalid JSON response\n";
}
