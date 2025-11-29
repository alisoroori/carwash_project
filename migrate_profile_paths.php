<?php
/**
 * Migration Script: Fix Profile Image Paths in Database
 * 
 * This script updates all profile image paths to use the standard format:
 * uploads/profiles/profile_X_TIMESTAMP.ext
 * 
 * Run this once to fix existing data.
 */

require_once __DIR__ . '/backend/includes/bootstrap.php';
use App\Classes\Database;

echo "=== PROFILE PATH MIGRATION ===\n\n";
echo "This will update all profile image paths to: uploads/profiles/filename\n\n";

$db = Database::getInstance();

// Function to normalize path
function normalizeProfilePath($path) {
    if (empty($path)) return null;
    
    // Remove query parameters
    $path = preg_replace('/\?.*$/', '', $path);
    
    // Remove leading slash
    $path = ltrim($path, '/');
    
    // Extract just the filename
    $filename = basename($path);
    
    // If it's a default avatar, keep it as is
    if (strpos($path, 'default-avatar') !== false || strpos($path, 'frontend/images') !== false) {
        return $path;
    }
    
    // Return normalized path
    return 'uploads/profiles/' . $filename;
}

echo "Step 1: Migrating user_profiles table...\n";
$profiles = $db->fetchAll('SELECT user_id, profile_image FROM user_profiles WHERE profile_image IS NOT NULL AND profile_image != ""');

$updatedProfiles = 0;
foreach ($profiles as $profile) {
    $oldPath = $profile['profile_image'];
    $newPath = normalizeProfilePath($oldPath);
    
    if ($oldPath !== $newPath) {
        $db->update('user_profiles', ['profile_image' => $newPath], ['user_id' => $profile['user_id']]);
        echo "  User {$profile['user_id']}: '{$oldPath}' → '{$newPath}'\n";
        $updatedProfiles++;
    }
}
echo "Updated $updatedProfiles records in user_profiles\n\n";

echo "Step 2: Migrating users table...\n";
try {
    $users = $db->fetchAll('SELECT id, profile_image FROM users WHERE profile_image IS NOT NULL AND profile_image != ""');
    
    $updatedUsers = 0;
    foreach ($users as $user) {
        $oldPath = $user['profile_image'];
        $newPath = normalizeProfilePath($oldPath);
        
        if ($oldPath !== $newPath) {
            $db->update('users', ['profile_image' => $newPath], ['id' => $user['id']]);
            echo "  User {$user['id']}: '{$oldPath}' → '{$newPath}'\n";
            $updatedUsers++;
        }
    }
    echo "Updated $updatedUsers records in users\n\n";
} catch (Exception $e) {
    echo "Note: users table may not have profile_image column (this is OK)\n\n";
}

echo "Step 3: Verifying uploads/profiles/ directory...\n";
$uploadDir = __DIR__ . '/uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "Created directory: $uploadDir\n";
} else {
    echo "Directory exists: $uploadDir\n";
    $files = glob($uploadDir . 'profile_*.*');
    echo "Found " . count($files) . " profile images\n";
}

echo "\n=== MIGRATION COMPLETE ===\n";
echo "Total updated: " . ($updatedProfiles + ($updatedUsers ?? 0)) . " records\n";
echo "\nAll profile images should now use the path format:\n";
echo "uploads/profiles/profile_USERID_TIMESTAMP.ext\n\n";
echo "You can now test profile uploads through the dashboard.\n";
