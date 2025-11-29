<?php
require 'backend/includes/config.php';

echo "=== PROFILE IMAGE DIAGNOSTIC ===" . PHP_EOL . PHP_EOL;

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Check database for all users with profile images
    echo "1. DATABASE CHECK:" . PHP_EOL;
    $stmt = $pdo->query("
        SELECT u.id, u.full_name, u.profile_image as user_img, up.profile_image as profile_img
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.role = 'customer' AND (u.profile_image IS NOT NULL OR up.profile_image IS NOT NULL)
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "   User {$user['id']} ({$user['full_name']}):" . PHP_EOL;
        echo "     users.profile_image: " . ($user['user_img'] ?? 'NULL') . PHP_EOL;
        echo "     user_profiles.profile_image: " . ($user['profile_img'] ?? 'NULL') . PHP_EOL;
        
        $imagePath = $user['profile_img'] ?? $user['user_img'];
        if ($imagePath) {
            // Check various possible locations
            $locations = [
                __DIR__ . '/' . $imagePath,
                __DIR__ . '/backend/uploads/profile/' . basename($imagePath),
                __DIR__ . '/uploads/profiles/' . basename($imagePath),
            ];
            
            $found = false;
            foreach ($locations as $loc) {
                if (file_exists($loc)) {
                    echo "     ✓ File found at: {$loc}" . PHP_EOL;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                echo "     ✗ File NOT found in any location" . PHP_EOL;
            }
        }
        echo PHP_EOL;
    }
    
    // 2. Check if backend/uploads/profile/ directory exists
    echo "2. DIRECTORY CHECK:" . PHP_EOL;
    $profileDir = __DIR__ . '/backend/uploads/profile';
    if (is_dir($profileDir)) {
        echo "   ✓ Directory exists: {$profileDir}" . PHP_EOL;
        echo "   Writable: " . (is_writable($profileDir) ? 'YES' : 'NO') . PHP_EOL;
        
        // List files
        $files = glob($profileDir . '/*');
        echo "   Files in directory: " . count($files) . PHP_EOL;
        foreach (array_slice($files, 0, 5) as $file) {
            echo "     - " . basename($file) . PHP_EOL;
        }
    } else {
        echo "   ✗ Directory does NOT exist: {$profileDir}" . PHP_EOL;
        
        // Check alternative location
        $altDir = __DIR__ . '/uploads/profiles';
        if (is_dir($altDir)) {
            echo "   ✓ Alternative directory found: {$altDir}" . PHP_EOL;
            $files = glob($altDir . '/*');
            echo "   Files in alternative: " . count($files) . PHP_EOL;
        }
    }
    
    // 3. Check constants
    echo PHP_EOL . "3. CONFIGURATION CHECK:" . PHP_EOL;
    echo "   BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . PHP_EOL;
    echo "   PROFILE_UPLOAD_PATH: " . (defined('PROFILE_UPLOAD_PATH') ? PROFILE_UPLOAD_PATH : 'NOT DEFINED') . PHP_EOL;
    echo "   PROFILE_UPLOAD_URL: " . (defined('PROFILE_UPLOAD_URL') ? PROFILE_UPLOAD_URL : 'NOT DEFINED') . PHP_EOL;
    
    echo PHP_EOL . "=== DIAGNOSTIC COMPLETE ===" . PHP_EOL;
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
