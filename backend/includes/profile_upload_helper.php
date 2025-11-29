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

    $uploadDir = __DIR__ . '/../../uploads/profiles/';
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

    // Resolve real paths for decision making
    $sourceReal = realpath($sourceFilePath);
    $uploadReal = realpath($uploadDir);

    // If the source is already inside the uploads directory, prefer to use its basename
    if ($sourceReal !== false && $uploadReal !== false && strpos($sourceReal, $uploadReal) === 0) {
        $finalName = basename($sourceReal);
        $dest = $uploadDir . $finalName;
        // If dest differs from source path, attempt to rename to canonical location
        if ($sourceReal !== realpath($dest)) {
            if (!@rename($sourceReal, $dest)) {
                // fallback to copy
                if (!@copy($sourceReal, $dest)) {
                    $result['error'] = 'Failed to copy/rename file to uploads';
                    return $result;
                }
            }
        }
    } else {
        // Create a canonical name and copy/rename the source into uploads
        $finalName = 'profile_' . $userId . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $finalName;
        if (!@copy($sourceFilePath, $dest)) {
            if (!@rename($sourceFilePath, $dest)) {
                $result['error'] = 'Failed to copy file to uploads';
                return $result;
            }
        }
    }

    // Ensure reasonable permissions on the stored file
    @chmod($dest, 0644);

    // Build web-accessible path (consistent with header logic)
    $imagePath = 'uploads/profiles/' . $finalName;

    try {
        // Persist profile_image to canonical `user_profiles` table
        $existingUser = $db->fetchOne('SELECT id FROM users WHERE id = :id', ['id' => $userId]);
        if (empty($existingUser)) {
            $result['error'] = 'User not found';
            return $result;
        }

        // Upsert into user_profiles
        $existingProfile = $db->fetchOne('SELECT user_id FROM user_profiles WHERE user_id = :user_id', ['user_id' => $userId]);
        if ($existingProfile) {
            $db->update('user_profiles', ['profile_image' => $imagePath], ['user_id' => $userId]);
        } else {
            $db->insert('user_profiles', ['user_id' => $userId, 'profile_image' => $imagePath]);
        }

        // Verify written to user_profiles table
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
        // Return a cache-busted URL using `cb` to match client behavior
        $result['profile_image'] = $verify['profile_image'] . (strpos($verify['profile_image'], '?') === false ? '?cb=' . intval($_SESSION['profile_image_ts']) : '&cb=' . intval($_SESSION['profile_image_ts']));
        return $result;
    } catch (Exception $e) {
        $result['error'] = 'DB error: ' . $e->getMessage();
        return $result;
    }
}

/**
 * Handle an uploaded file array (from $_FILES) and persist it as profile image.
 * Accepts the $_FILES['profile_image'] entry or similar.
 * Returns same result shape as handleProfileUploadFromPath().
 */
function handleProfileUpload(int $userId, array $fileArray) {
    $db = Database::getInstance();
    $result = ['success' => false, 'message' => '', 'profile_image' => null, 'error' => null];

    if (empty($fileArray) || empty($fileArray['tmp_name']) || !is_uploaded_file($fileArray['tmp_name'])) {
        $result['error'] = 'No uploaded file provided';
        return $result;
    }

    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileArray['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedTypes[$mime])) {
        $result['error'] = 'Invalid file type. Only JPG, PNG and WEBP allowed.';
        return $result;
    }

    $maxSize = 3 * 1024 * 1024; // 3MB
    if ($fileArray['size'] > $maxSize) {
        $result['error'] = 'File too large. Maximum size is 3MB.';
        return $result;
    }

    $uploadDir = __DIR__ . '/../../uploads/profiles/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            $result['error'] = 'Failed to create upload directory';
            return $result;
        }
    }

    $ext = $allowedTypes[$mime];
    $newName = 'profile_' . $userId . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $newName;

    if (!move_uploaded_file($fileArray['tmp_name'], $dest)) {
        $result['error'] = 'Failed to move uploaded file';
        return $result;
    }

    // Delegate DB persistence to the existing path-based function for consistency
    return handleProfileUploadFromPath($userId, $dest);
}
