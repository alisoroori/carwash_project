<?php
require 'backend/includes/config.php';

$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Profile Image Verification for User 14 ===" . PHP_EOL . PHP_EOL;

// Check database paths
$stmt = $pdo->prepare('
    SELECT 
        u.id,
        u.full_name,
        u.profile_image as users_path,
        up.profile_image as profiles_path
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = 14
');
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Database Paths:" . PHP_EOL;
echo "  users.profile_image: " . ($user['users_path'] ?? 'NULL') . PHP_EOL;
echo "  user_profiles.profile_image: " . ($user['profiles_path'] ?? 'NULL') . PHP_EOL;

// Check which path to use
$imagePath = $user['profiles_path'] ?? $user['users_path'];
echo PHP_EOL . "Active Path: {$imagePath}" . PHP_EOL;

// Verify file exists
$fullPath = __DIR__ . '/' . $imagePath;
$exists = file_exists($fullPath);
$readable = $exists && is_readable($fullPath);

echo "File Check:" . PHP_EOL;
echo "  Full path: {$fullPath}" . PHP_EOL;
echo "  Exists: " . ($exists ? 'YES ✓' : 'NO ✗') . PHP_EOL;
echo "  Readable: " . ($readable ? 'YES ✓' : 'NO ✗') . PHP_EOL;

if ($exists) {
    $size = filesize($fullPath);
    echo "  Size: " . number_format($size / 1024, 2) . " KB" . PHP_EOL;
}

// Generate expected URLs
echo PHP_EOL . "Expected URLs:" . PHP_EOL;
echo "  Relative: /{$imagePath}" . PHP_EOL;
echo "  Full: " . BASE_URL . "/{$imagePath}" . PHP_EOL;
echo "  With cache-buster: " . BASE_URL . "/{$imagePath}?cb=" . time() . PHP_EOL;

// Check if session would be correct
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 14) {
    echo PHP_EOL . "Session Check:" . PHP_EOL;
    echo "  session[profile_image]: " . ($_SESSION['profile_image'] ?? 'NOT SET') . PHP_EOL;
    echo "  session[user][profile_image]: " . ($_SESSION['user']['profile_image'] ?? 'NOT SET') . PHP_EOL;
    
    if (($_SESSION['profile_image'] ?? '') === $imagePath) {
        echo "  ✓ Session matches database" . PHP_EOL;
    } else {
        echo "  ⚠ Session needs update - log out and log back in" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Verification Complete ===" . PHP_EOL;

if ($exists && $readable && strpos($imagePath, 'uploads/profiles/') === 0) {
    echo "✅ Everything is correct! Image is accessible." . PHP_EOL;
    echo PHP_EOL . "Access it at: http://localhost/carwash_project/{$imagePath}" . PHP_EOL;
} else {
    echo "⚠️ Issues detected - review details above" . PHP_EOL;
}
