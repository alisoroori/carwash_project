<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

$db = Database::getInstance();

echo "=== Carwashes Table Sample Data ===\n\n";

// Get sample carwash
$carwash = $db->fetchOne('SELECT id, name, logo_path, address, city, district, phone, email FROM carwashes LIMIT 1');

if ($carwash) {
    echo "Sample Carwash Data:\n";
    echo json_encode($carwash, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check if logo file exists
    if (!empty($carwash['logo_path'])) {
        $logo_file = __DIR__ . '/backend/uploads/business_logo/' . basename($carwash['logo_path']);
        echo "Logo Path from DB: " . $carwash['logo_path'] . "\n";
        echo "Logo File Check: " . $logo_file . "\n";
        echo "File Exists: " . (file_exists($logo_file) ? 'YES' : 'NO') . "\n\n";
        
        // Check alternative paths
        $alt_paths = [
            __DIR__ . '/uploads/logos/' . basename($carwash['logo_path']),
            __DIR__ . '/backend/uploads/logos/' . basename($carwash['logo_path']),
            __DIR__ . '/uploads/business_logo/' . basename($carwash['logo_path']),
        ];
        
        echo "Checking alternative paths:\n";
        foreach ($alt_paths as $path) {
            echo "  - " . $path . ": " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND') . "\n";
        }
    } else {
        echo "Logo path is empty in database\n";
    }
} else {
    echo "No carwashes found in database\n";
}

// Check sample booking
echo "\n=== Sample Booking Data ===\n\n";
$booking = $db->fetchOne('SELECT b.id, b.carwash_id, c.name, c.logo_path, c.address, c.city, c.district FROM bookings b LEFT JOIN carwashes c ON b.carwash_id = c.id LIMIT 1');
if ($booking) {
    echo json_encode($booking, JSON_PRETTY_PRINT) . "\n";
}
?>
