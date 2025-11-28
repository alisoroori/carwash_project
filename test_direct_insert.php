<?php
// Direct test with error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 14;

require_once 'backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();

echo "Direct Database Insert Test\n";
echo str_repeat("=", 60) . "\n\n";

$testData = [
    'user_id' => 14,
    'brand' => 'Test Brand',
    'model' => 'Test Model',
    'year' => 2020,
    'color' => 'Blue',
    'license_plate' => 'TEST' . rand(1000, 9999),
    'image_path' => null,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

echo "Inserting into user_vehicles:\n";
print_r($testData);

try {
    $vehicleId = $db->insert('user_vehicles', $testData);
    
    if ($vehicleId) {
        echo "\n✅ SUCCESS! Vehicle ID: $vehicleId\n";
        
        // Verify
        $check = $db->fetchOne("SELECT * FROM user_vehicles WHERE id = ?", [$vehicleId]);
        echo "\nVerified in DB:\n";
        print_r($check);
        
        // Cleanup
        $db->delete('user_vehicles', 'id = ?', [$vehicleId]);
        echo "\n✅ Cleaned up test data\n";
    } else {
        echo "\n❌ Insert returned false/null\n";
    }
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
