<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

$db = Database::getInstance();

echo "=== All Carwashes Logo Status ===\n\n";

$carwashes = $db->fetchAll('SELECT id, name, logo_path FROM carwashes ORDER BY id');

foreach ($carwashes as $cw) {
    echo "ID: {$cw['id']} | Name: {$cw['name']}\n";
    echo "  Logo Path: " . ($cw['logo_path'] ?: 'NULL') . "\n";
    
    if (!empty($cw['logo_path'])) {
        $paths = [
            'backend/uploads/business_logo/' . basename($cw['logo_path']),
            'uploads/logos/' . basename($cw['logo_path']),
        ];
        
        foreach ($paths as $p) {
            if (file_exists($p)) {
                echo "  ✓ FOUND: $p\n";
            }
        }
    }
    echo "\n";
}

// Check what files exist in business_logo
echo "=== Files in backend/uploads/business_logo ===\n";
$logo_dir = __DIR__ . '/backend/uploads/business_logo';
if (is_dir($logo_dir)) {
    $files = scandir($logo_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }
} else {
    echo "Directory does not exist\n";
}
?>
