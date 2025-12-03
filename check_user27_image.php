<?php
require __DIR__ . '/backend/includes/bootstrap.php';

$db = App\Classes\Database::getInstance();
$user = $db->fetchOne('SELECT id, profile_image FROM users WHERE id = 27');
$userProfile = $db->fetchOne('SELECT user_id, profile_image FROM user_profiles WHERE user_id = 27');

echo "=== User 27 Profile Image Check ===\n";
echo "users.profile_image: " . ($user['profile_image'] ?? 'NULL') . "\n";
echo "user_profiles.profile_image: " . ($userProfile['profile_image'] ?? 'NULL') . "\n";
echo "\n";

// Check if files exist
$paths = [
    $user['profile_image'] ?? null,
    $userProfile['profile_image'] ?? null,
];

foreach ($paths as $path) {
    if (empty($path)) continue;
    
    $fullPath = __DIR__ . '/' . ltrim($path, '/');
    echo "Checking: $path\n";
    echo "  Full path: $fullPath\n";
    echo "  Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n\n";
}
