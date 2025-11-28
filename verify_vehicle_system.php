<?php
/**
 * Final Vehicle Image System Verification
 * Checks database paths AND physical file existence
 */

$host = 'localhost';
$db   = 'carwash_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Final Vehicle Image System Verification ===\n\n";
    
    // Get all vehicles with image paths
    $stmt = $pdo->query("
        SELECT id, user_id, brand, model, image_path 
        FROM user_vehicles 
        WHERE image_path IS NOT NULL AND image_path != ''
        ORDER BY id
    ");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($vehicles) . " vehicles with images\n\n";
    
    $success = 0;
    $missing = 0;
    $bad_format = 0;
    
    foreach ($vehicles as $v) {
        echo "ID {$v['id']}: {$v['brand']} {$v['model']}\n";
        echo "  Path: {$v['image_path']}\n";
        
        // Check format
        if (strpos($v['image_path'], 'carwash_project') !== false || 
            strpos($v['image_path'], 'backend') !== false ||
            strpos($v['image_path'], 'http') !== false) {
            echo "  ❌ BAD FORMAT (contains project/backend/http)\n";
            $bad_format++;
            continue;
        }
        
        if (!str_starts_with($v['image_path'], 'uploads/vehicles/')) {
            echo "  ⚠️ WARNING: Unexpected path format\n";
            $bad_format++;
            continue;
        }
        
        // Check file existence
        $file_path = __DIR__ . '/' . $v['image_path'];
        if (file_exists($file_path)) {
            $size = filesize($file_path);
            $size_kb = round($size / 1024, 2);
            echo "  ✅ File exists ({$size_kb} KB)\n";
            $success++;
        } else {
            echo "  ❌ File NOT FOUND: {$file_path}\n";
            $missing++;
        }
        
        echo "\n";
    }
    
    echo str_repeat('=', 80) . "\n";
    echo "SUMMARY:\n";
    echo "✅ Valid (path + file): {$success}\n";
    echo "❌ Missing files: {$missing}\n";
    echo "❌ Bad format: {$bad_format}\n";
    
    if ($bad_format === 0 && $missing === 0) {
        echo "\n🎉 ALL SYSTEMS GREEN! Vehicle images are fully operational!\n";
    } else {
        echo "\n⚠️ Issues detected - see details above\n";
    }
    
    // Show expected URL format
    if ($success > 0) {
        $sample = $vehicles[0];
        echo "\n" . str_repeat('-', 80) . "\n";
        echo "SAMPLE URL CONSTRUCTION:\n";
        echo "Database Path: {$sample['image_path']}\n";
        echo "BASE_URL: http://localhost/carwash_project\n";
        echo "API Returns: http://localhost/carwash_project/{$sample['image_path']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
