<?php
/**
 * Profile Image Debug Endpoint
 * Checks all profile images for consistency between DB, session, and filesystem
 */

require_once __DIR__ . '/includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;
use App\Classes\Response;

Auth::requireAuth();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

$issues = [];
$fixes = [];

// Check DB
$row = $db->fetchOne('SELECT profile_image FROM users WHERE id = :user_id', ['user_id' => $userId]);
$dbImage = $row['profile_image'] ?? null;

// Check session
$sessionImage = $_SESSION['profile_image'] ?? null;

// Check filesystem
$files = [];
if ($dbImage) {
    $path = str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'] . '/carwash_project', $dbImage);
    $path = preg_replace('/\?ts=\d+$/', '', $path);
    $files['db'] = file_exists($path) ? 'exists' : 'missing';
}
if ($sessionImage && $sessionImage !== $dbImage) {
    $path = str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'] . '/carwash_project', $sessionImage);
    $path = preg_replace('/\?ts=\d+$/', '', $path);
    $files['session'] = file_exists($path) ? 'exists' : 'missing';
}

// Detect issues
if ($dbImage !== $sessionImage) {
    $issues[] = 'DB and session mismatch';
}
if (isset($files['db']) && $files['db'] === 'missing') {
    $issues[] = 'DB image file missing';
}
if (isset($files['session']) && $files['session'] === 'missing') {
    $issues[] = 'Session image file missing';
}

// Auto-fix if possible
if (empty($issues)) {
    $status = 'OK';
} else {
    // Run auto-fix
    include_once __DIR__ . '/includes/profile_auto_fix.php';
    $result = profile_auto_fix($userId);
    if ($result['success']) {
        $fixes = $result['fixes'];
        $status = 'Fixed';
    } else {
        $status = 'Fix failed: ' . $result['error'];
    }
}

Response::success('Profile image check', [
    'db_image' => $dbImage,
    'session_image' => $sessionImage,
    'files' => $files,
    'issues' => $issues,
    'status' => $status,
    'fixes' => $fixes
]);
?>