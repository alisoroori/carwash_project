<?php
/**
 * Profile Upload System Test
 * 
 * This script tests the entire profile upload workflow:
 * 1. Directory structure
 * 2. Database path format
 * 3. File accessibility
 * 4. URL generation
 */

require_once __DIR__ . '/backend/includes/bootstrap.php';
use App\Classes\Database;

echo "=== PROFILE UPLOAD SYSTEM TEST ===\n\n";

$db = Database::getInstance();
$errors = [];
$warnings = [];

// Test 1: Directory Structure
echo "Test 1: Directory Structure\n";
echo str_repeat('-', 50) . "\n";

$uploadDir = __DIR__ . '/uploads/profiles/';
if (!is_dir($uploadDir)) {
    $errors[] = "uploads/profiles/ directory does not exist";
    echo "❌ Directory missing: $uploadDir\n";
} else {
    echo "✓ Directory exists: $uploadDir\n";
    
    if (!is_writable($uploadDir)) {
        $warnings[] = "uploads/profiles/ is not writable";
        echo "⚠ Directory not writable\n";
    } else {
        echo "✓ Directory is writable\n";
    }
    
    $files = glob($uploadDir . 'profile_*.*');
    echo "✓ Found " . count($files) . " profile images\n";
    
    foreach ($files as $file) {
        if (!is_readable($file)) {
            $warnings[] = "File not readable: " . basename($file);
        }
    }
}
echo "\n";

// Test 2: Database Path Format
echo "Test 2: Database Path Format\n";
echo str_repeat('-', 50) . "\n";

// Check user_profiles table
$profiles = $db->fetchAll('SELECT user_id, profile_image FROM user_profiles WHERE profile_image IS NOT NULL AND profile_image != "" LIMIT 10');
echo "Checking user_profiles table (" . count($profiles) . " records):\n";

foreach ($profiles as $profile) {
    $path = $profile['profile_image'];
    $userId = $profile['user_id'];
    
    // Check if path follows correct format
    if (strpos($path, 'uploads/profiles/') === 0) {
        echo "  ✓ User $userId: $path (CORRECT)\n";
        
        // Verify file exists
        $filePath = __DIR__ . '/' . $path;
        if (!file_exists($filePath)) {
            $warnings[] = "User $userId: Database has path '$path' but file doesn't exist";
            echo "    ⚠ File not found on disk\n";
        }
    } else if (strpos($path, 'frontend/images/default-avatar') !== false) {
        echo "  ✓ User $userId: Using default avatar\n";
    } else {
        $errors[] = "User $userId: Incorrect path format '$path'";
        echo "  ❌ User $userId: $path (INCORRECT - should be uploads/profiles/filename)\n";
    }
}
echo "\n";

// Check users table
$users = $db->fetchAll('SELECT id, profile_image FROM users WHERE profile_image IS NOT NULL AND profile_image != "" LIMIT 10');
echo "Checking users table (" . count($users) . " records):\n";

foreach ($users as $user) {
    $path = $user['profile_image'];
    $userId = $user['id'];
    
    if (strpos($path, 'uploads/profiles/') === 0) {
        echo "  ✓ User $userId: $path (CORRECT)\n";
        
        $filePath = __DIR__ . '/' . $path;
        if (!file_exists($filePath)) {
            $warnings[] = "User $userId: Database has path '$path' but file doesn't exist";
            echo "    ⚠ File not found on disk\n";
        }
    } else if (strpos($path, 'frontend/images/default-avatar') !== false) {
        echo "  ✓ User $userId: Using default avatar\n";
    } else {
        $errors[] = "User $userId: Incorrect path format '$path'";
        echo "  ❌ User $userId: $path (INCORRECT)\n";
    }
}
echo "\n";

// Test 3: URL Generation
echo "Test 3: URL Generation\n";
echo str_repeat('-', 50) . "\n";

$baseUrl = BASE_URL ?? 'http://localhost/carwash_project';
echo "Base URL: $baseUrl\n";

$testPath = 'uploads/profiles/profile_14_1764379912.jpg';
$expectedUrl = $baseUrl . '/' . $testPath;
echo "Test path: $testPath\n";
echo "Generated URL: $expectedUrl\n";

$fullPath = __DIR__ . '/' . $testPath;
if (file_exists($fullPath)) {
    echo "✓ File exists at: $fullPath\n";
    echo "✓ URL should work: $expectedUrl\n";
} else {
    echo "⚠ Test file doesn't exist (this is OK for testing)\n";
}
echo "\n";

// Test 4: Configuration Constants
echo "Test 4: Configuration Constants\n";
echo str_repeat('-', 50) . "\n";

if (defined('PROFILE_UPLOAD_PATH')) {
    echo "PROFILE_UPLOAD_PATH: " . PROFILE_UPLOAD_PATH . "\n";
} else {
    echo "⚠ PROFILE_UPLOAD_PATH not defined\n";
}

if (defined('PROFILE_UPLOAD_URL')) {
    echo "PROFILE_UPLOAD_URL: " . PROFILE_UPLOAD_URL . "\n";
} else {
    echo "⚠ PROFILE_UPLOAD_URL not defined\n";
}

if (defined('BASE_URL')) {
    echo "BASE_URL: " . BASE_URL . "\n";
} else {
    echo "⚠ BASE_URL not defined\n";
}
echo "\n";

// Summary
echo "=== TEST SUMMARY ===\n";
echo str_repeat('-', 50) . "\n";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "\nProfile upload system is correctly configured:\n";
    echo "  • Directory: uploads/profiles/\n";
    echo "  • Database format: uploads/profiles/profile_USERID_TIMESTAMP.ext\n";
    echo "  • Web URL: $baseUrl/uploads/profiles/filename\n";
    echo "\nYou can now test uploading a profile image through:\n";
    echo "  • Customer Dashboard: $baseUrl/backend/dashboard/Customer_Dashboard.php\n";
} else {
    if (count($errors) > 0) {
        echo "❌ ERRORS FOUND (" . count($errors) . "):\n";
        foreach ($errors as $error) {
            echo "  • $error\n";
        }
        echo "\n";
    }
    
    if (count($warnings) > 0) {
        echo "⚠ WARNINGS (" . count($warnings) . "):\n";
        foreach ($warnings as $warning) {
            echo "  • $warning\n";
        }
        echo "\n";
    }
    
    echo "Please fix the issues above before testing uploads.\n";
}

echo "\n=== END OF TEST ===\n";
