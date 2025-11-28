<?php
// Simple check without autoloader
$host = 'localhost';
$db   = 'carwash_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT id, user_id, brand, model, image_path FROM user_vehicles LIMIT 10");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Vehicle Image Paths in Database ===\n\n";
    echo "Total found: " . count($vehicles) . "\n\n";
    
    foreach ($vehicles as $v) {
        echo "ID: {$v['id']} | User: {$v['user_id']} | {$v['brand']} {$v['model']}\n";
        echo "Path: {$v['image_path']}\n";
        
        if (strpos($v['image_path'], 'carwash_project') !== false) echo "  ⚠️ Contains carwash_project\n";
        if (strpos($v['image_path'], 'backend') !== false) echo "  ⚠️ Contains backend\n";
        if (strpos($v['image_path'], 'http') !== false) echo "  ⚠️ Contains http\n";
        
        echo "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
