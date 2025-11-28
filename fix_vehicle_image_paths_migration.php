<?php
/**
 * Fix Vehicle Image Paths in Database
 * 
 * Problem: Old paths stored as `/carwash_project/backend/uploads/vehicles/file.jpg`
 * Should be: `uploads/vehicles/file.jpg`
 * 
 * When API adds BASE_URL, old format creates: 
 * http://localhost/carwash_project//carwash_project/backend/uploads/... (WRONG)
 */

$host = 'localhost';
$db   = 'carwash_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Fixing Vehicle Image Paths ===\n\n";
    
    // Find all vehicles with bad paths
    $stmt = $pdo->query("
        SELECT id, image_path 
        FROM user_vehicles 
        WHERE image_path LIKE '%carwash_project%' 
           OR image_path LIKE '/backend/%'
    ");
    $bad_paths = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($bad_paths) . " vehicles with incorrect paths\n\n";
    
    if (empty($bad_paths)) {
        echo "✅ All paths are already correct!\n";
        exit(0);
    }
    
    // Show what will be fixed
    echo "Preview of changes:\n";
    echo str_repeat('-', 80) . "\n";
    
    $fixes = [];
    foreach ($bad_paths as $vehicle) {
        $old_path = $vehicle['image_path'];
        $new_path = $old_path;
        
        // Remove /carwash_project prefix
        $new_path = preg_replace('#^/carwash_project/#', '', $new_path);
        
        // Remove /backend/ prefix
        $new_path = preg_replace('#^/backend/#', '', $new_path);
        
        // Remove backend/ prefix (no leading slash)
        $new_path = preg_replace('#^backend/#', '', $new_path);
        
        // Ensure it starts with uploads/vehicles/
        if (!str_starts_with($new_path, 'uploads/')) {
            if (str_contains($new_path, 'uploads/vehicles/')) {
                // Extract just the uploads/vehicles/... part
                $new_path = substr($new_path, strpos($new_path, 'uploads/vehicles/'));
            }
        }
        
        $fixes[] = [
            'id' => $vehicle['id'],
            'old' => $old_path,
            'new' => $new_path
        ];
        
        echo "ID {$vehicle['id']}:\n";
        echo "  OLD: {$old_path}\n";
        echo "  NEW: {$new_path}\n\n";
    }
    
    // Ask for confirmation
    echo str_repeat('-', 80) . "\n";
    echo "Ready to update " . count($fixes) . " records.\n";
    echo "Type 'yes' to proceed: ";
    
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    
    if (strtolower($line) !== 'yes') {
        echo "\nAborted. No changes made.\n";
        exit(0);
    }
    
    // Apply fixes
    echo "\nApplying fixes...\n";
    $update_stmt = $pdo->prepare("UPDATE user_vehicles SET image_path = :new_path WHERE id = :id");
    
    $success = 0;
    $errors = 0;
    
    foreach ($fixes as $fix) {
        try {
            $update_stmt->execute([
                'id' => $fix['id'],
                'new_path' => $fix['new']
            ]);
            $success++;
            echo "✅ Updated ID {$fix['id']}\n";
        } catch (Exception $e) {
            $errors++;
            echo "❌ Error updating ID {$fix['id']}: {$e->getMessage()}\n";
        }
    }
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Complete!\n";
    echo "✅ Successfully updated: {$success}\n";
    if ($errors > 0) {
        echo "❌ Errors: {$errors}\n";
    }
    
    // Verify
    echo "\nVerifying fixes...\n";
    $stmt = $pdo->query("
        SELECT id, image_path 
        FROM user_vehicles 
        WHERE image_path LIKE '%carwash_project%' 
           OR image_path LIKE '/backend/%'
    ");
    $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($remaining)) {
        echo "✅ All paths are now correct!\n";
    } else {
        echo "⚠️ Warning: " . count($remaining) . " paths still need attention:\n";
        foreach ($remaining as $v) {
            echo "  ID {$v['id']}: {$v['image_path']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
