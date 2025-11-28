<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

$db = Database::getInstance();

// Add missing fields to users table
try {
    $db->getConnection()->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL");
    echo "Added address to users\n";
} catch (Exception $e) {
    echo "Address already exists in users or error: " . $e->getMessage() . "\n";
}

try {
    $db->getConnection()->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) DEFAULT NULL");
    echo "Added username to users\n";
} catch (Exception $e) {
    echo "Username already exists in users or error: " . $e->getMessage() . "\n";
}

// Add missing fields to user_profiles table
$fields = [
    "profile_image VARCHAR(255) DEFAULT NULL",
    "phone VARCHAR(20) DEFAULT NULL",
    "home_phone VARCHAR(20) DEFAULT NULL",
    "national_id VARCHAR(20) DEFAULT NULL",
    "driver_license VARCHAR(50) DEFAULT NULL"
];

foreach ($fields as $field) {
    try {
        $db->getConnection()->exec("ALTER TABLE user_profiles ADD COLUMN $field");
        echo "Added $field to user_profiles\n";
    } catch (Exception $e) {
        echo "$field already exists in user_profiles or error: " . $e->getMessage() . "\n";
    }
}

echo "Migration completed.\n";
?>