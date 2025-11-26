<?php
// Helper for profile image uploads (reusable by web handlers and CLI tests)
require_once __DIR__ . '/bootstrap.php';
use App\Classes\Database;

function handleProfileUploadFromPath(int $userId, string $sourceFilePath) {
    $db = Database::getInstance();
    $result = ['success' => false, 'message' => '', 'profile_image' => null, 'error' => null];

    if (!file_exists($sourceFilePath) || !is_readable($sourceFilePath)) {
        $result['error'] = 'Source file not found or unreadable';
        return $result;
    }

    $uploadDir = __DIR__ . '/../auth/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            $result['error'] = 'Failed to create upload directory';
            return $result;
        }
    }

    $ext = strtolower(pathinfo($sourceFilePath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
        $result['error'] = 'Unsupported file extension';
        return $result;
    }

    $newName = 'profile_' . $userId . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $newName;

    if (!copy($sourceFilePath, $dest)) {
        $result['error'] = 'Failed to copy file to uploads';
        return $result;
    }

    // Build web-accessible path (consistent with header logic)
    $imagePath = '/carwash_project/backend/auth/uploads/profiles/' . $newName;

    try {
        $existing = $db->fetchOne('SELECT user_id FROM user_profiles WHERE user_id = :user_id', ['user_id' => $userId]);
        if (!empty($existing)) {
            $db->update('user_profiles', ['profile_image' => $imagePath], ['user_id' => $userId]);
        } else {
            $db->insert('user_profiles', ['user_id' => $userId, 'profile_image' => $imagePath]);
        }

        $verify = $db->fetchOne('SELECT profile_image FROM user_profiles WHERE user_id = :user_id', ['user_id' => $userId]);
        if (empty($verify) || empty($verify['profile_image'])) {
            $result['error'] = 'Database did not persist profile_image';
            return $result;
        }

        // Update PHP session if available
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['profile_image'] = $verify['profile_image'];
        $_SESSION['user']['profile_image'] = $verify['profile_image'];
        $_SESSION['profile_image_ts'] = time();

        $result['success'] = true;
        $result['message'] = 'Profile image uploaded and DB updated';
        $result['profile_image'] = $verify['profile_image'] . '?ts=' . intval($_SESSION['profile_image_ts']);
        return $result;
    } catch (Exception $e) {
        $result['error'] = 'DB error: ' . $e->getMessage();
        return $result;
    }
}
