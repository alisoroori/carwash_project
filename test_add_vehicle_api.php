<?php
// Test add vehicle API
session_start();
$_SESSION['user_id'] = 14; // hasan user

require_once 'backend/includes/bootstrap.php';
use App\Classes\Database;

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'brand' => 'Test Brand',
    'model' => 'Test Model',
    'year' => 2020,
    'color' => 'Blue',
    'license_plate' => 'TEST' . rand(1000, 9999)
];

ob_start();
include 'backend/api/add_vehicle.php';
$output = ob_get_clean();

echo "Testing Add Vehicle API\n";
echo str_repeat("=", 60) . "\n\n";

echo "Sending data:\n";
print_r($_POST);
echo "\n";

echo "API Response:\n";
echo $output . "\n\n";

$response = json_decode($output, true);

if ($response && $response['success']) {
    echo "✅ SUCCESS: Vehicle added!\n";
    echo "Vehicle ID: " . $response['data']['vehicle']['id'] . "\n";
    
    // Verify in database
    $vehicleId = $response['data']['vehicle']['id'];
    $check = $db->fetchOne("SELECT * FROM user_vehicles WHERE id = ?", [$vehicleId]);
    
    if ($check) {
        echo "\n✅ Verified in database:\n";
        echo "   Brand: {$check['brand']}\n";
        echo "   Model: {$check['model']}\n";
        echo "   License: {$check['license_plate']}\n";
        
        // Cleanup
        $db->delete('user_vehicles', 'id = ?', [$vehicleId]);
        echo "\n✅ Test vehicle cleaned up\n";
    }
} else {
    echo "❌ FAILED: " . ($response['message'] ?? 'Unknown error') . "\n";
    if (isset($response['errors'])) {
        print_r($response['errors']);
    }
}
