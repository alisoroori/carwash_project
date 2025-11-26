<?php
// CLI test to simulate profile upload using existing image in uploads folder
require_once __DIR__ . '/../backend/includes/bootstrap.php';
require_once __DIR__ . '/../backend/includes/profile_upload_helper.php';

// Choose a sample file from uploads
$uploadsDir = __DIR__ . '/../backend/auth/uploads/profiles/';
$files = glob($uploadsDir . 'profile_*.*');
if (empty($files)) {
    echo "No existing profile images found in: $uploadsDir\n";
    exit(1);
}
$sample = $files[array_rand($files)];

// Use current CLI user id (adjust as needed). Try to infer from session or default to 14 if not set.
session_start();
$userId = $_SESSION['user_id'] ?? 14;

echo "Simulating profile upload for user_id={$userId} using source file: {$sample}\n";
$result = handleProfileUploadFromPath($userId, $sample);
if ($result['success']) {
    echo "SUCCESS: " . $result['message'] . "\n";
    echo "New profile image: " . $result['profile_image'] . "\n";
    exit(0);
} else {
    echo "FAILED: " . ($result['error'] ?? 'unknown') . "\n";
    exit(2);
}
