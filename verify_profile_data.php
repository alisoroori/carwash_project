<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

// Check user 14's profile data
$db = Database::getInstance();
$userId = 14;

echo "=== CHECKING USER {$userId} PROFILE DATA ===\n\n";

// Check users table
$user = $db->fetchOne("SELECT id, full_name, username, email, phone, address FROM users WHERE id = :id", ['id' => $userId]);
echo "Users table:\n";
print_r($user);
echo "\n";

// Check user_profiles table
$profile = $db->fetchOne("SELECT user_id, phone, home_phone, national_id, driver_license, city, address FROM user_profiles WHERE user_id = :id", ['id' => $userId]);
echo "User_profiles table:\n";
print_r($profile);
echo "\n";

// Check merged data (as API does)
$merged = $db->fetchOne("
    SELECT 
        u.id, u.full_name, u.email, u.phone, u.address, u.username,
        up.city, up.phone AS phone_extended, up.home_phone, up.national_id, up.driver_license,
        up.address AS profile_address
    FROM users u 
    LEFT JOIN user_profiles up ON u.id = up.user_id 
    WHERE u.id = :id
", ['id' => $userId]);

echo "Merged data (as API returns):\n";
print_r($merged);
echo "\n";

// Check what would be sent to frontend
$finalData = [
    'full_name' => $merged['full_name'],
    'username' => $merged['username'] ?? 'NOT SET',
    'email' => $merged['email'],
    'phone' => $merged['phone_extended'] ?? $merged['phone'],
    'home_phone' => $merged['home_phone'] ?? 'NOT SET',
    'national_id' => $merged['national_id'] ?? 'NOT SET',
    'driver_license' => $merged['driver_license'] ?? 'NOT SET',
    'address' => $merged['profile_address'] ?? $merged['address'] ?? 'NOT SET',
    'city' => $merged['city'] ?? 'NOT SET',
];

echo "Final data structure:\n";
print_r($finalData);
echo "\n";

echo "=== CHECK COMPLETE ===\n";
