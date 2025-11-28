<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Database;

try {
    $db = Database::getInstance();
    
    echo "=== Checking Vehicle Image Paths ===\n\n";
    
    // Get some sample vehicles
    $vehicles = $db->fetchAll("SELECT id, user_id, brand, model, image_path FROM user_vehicles LIMIT 10");
    
    if (empty($vehicles)) {
        echo "No vehicles found in user_vehicles table.\n";
    } else {
        echo "Found " . count($vehicles) . " vehicles:\n\n";
        
        foreach ($vehicles as $vehicle) {
            echo "ID: {$vehicle['id']}\n";
            echo "User: {$vehicle['user_id']}\n";
            echo "Vehicle: {$vehicle['brand']} {$vehicle['model']}\n";
            echo "Stored Path: {$vehicle['image_path']}\n";
            echo "Expected: uploads/vehicles/filename.jpg\n";
            
            // Check if path contains any suspicious patterns
            if (strpos($vehicle['image_path'], 'carwash_project') !== false) {
                echo "⚠️ WARNING: Path contains 'carwash_project'\n";
            }
            if (strpos($vehicle['image_path'], 'backend') !== false) {
                echo "⚠️ WARNING: Path contains 'backend'\n";
            }
            if (strpos($vehicle['image_path'], 'http') !== false) {
                echo "⚠️ WARNING: Path contains full URL\n";
            }
            
            echo str_repeat('-', 50) . "\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
