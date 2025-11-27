<?php
/**
 * Profile Image Upload Handler
 * 
 * Secure file upload for user profile images
 */

// Load autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Response;
use App\Classes\Validator;


// Start session and check authentication
Session::start();
$auth = new Auth();
$auth->requireAuth();

// CSRF protection
if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    Response::error('توکن امنیتی نامعتبر است', 403);
}

// Use a storage directory under backend/auth/uploads/profiles
$storageBase = realpath(__DIR__) . '/uploads/profiles';
if (!is_dir($storageBase)) {
    @mkdir($storageBase, 0755, true);
}

// Configuration
$maxSize = 3 * 1024 * 1024; // 3MB
$allowedExt = ['jpg','jpeg','png','webp'];
$allowedMime = ['image/jpeg','image/png','image/webp'];

$userId = Session::get('user_id');

// Process upload
if (empty($_FILES['profile_image'])) {
    Response::error('فایلی برای آپلود انتخاب نشده است');
}

$file = $_FILES['profile_image'];
// Basic PHP upload error check
if ($file['error'] !== UPLOAD_ERR_OK) {
    Response::error('خطا در آپلود فایل (کد: ' . $file['error'] . ')');
}

// Enforce size
if ($file['size'] > $maxSize) {
    Response::error('حجم فایل بیش از حد مجاز است');
}

// Use finfo to detect MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowedMime, true)) {
    Response::error('نوع فایل پشتیبانی نمی‌شود');
}

// Determine extension
$origName = $file['name'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    Response::error('فرمت فایل مجاز نیست');
}

// Create user subdir
$userDir = $storageBase . '/user_' . intval($userId);
if (!is_dir($userDir)) {@mkdir($userDir, 0755, true);} 

// Randomized storage filename
try {
    $random = bin2hex(random_bytes(8));
} catch (Exception $e) {
    $random = time() . '_' . mt_rand(1000,9999);
}
$newFilename = $random . '.' . $ext;
$storagePath = $userDir . '/' . $newFilename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $storagePath)) {
    Response::error('ذخیره فایل با خطا مواجه شد');
}

// Optionally set restrictive permissions
@chmod($storagePath, 0644);

// Build relative/public paths for DB and session
// Store canonical path in DB (no timestamp)
$publicUrl = '/carwash_project/backend/auth/uploads/profiles/user_' . intval($userId) . '/' . $newFilename;

// Remove old profile image if set and not default
$old = Session::get('profile_image');
if ($old && strpos($old, '/frontend/images/default-avatar.svg') === false) {
    $oldFull = str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'] . '/carwash_project', $old);
    $oldFull = preg_replace('/\?ts=\d+$/', '', $oldFull); // Remove timestamp
    if (file_exists($oldFull)) @unlink($oldFull);
}

// Save to database (canonical users table)
$db = \App\Classes\Database::getInstance();
$updated = $db->update('users', ['profile_image' => $publicUrl], ['id' => $userId]);
if ($updated) {
    Session::set('profile_image', $publicUrl);
    Session::set('profile_image_ts', time());
    Response::success('تصویر پروفایل با موفقیت به‌روز شد', ['image_url' => $publicUrl . '?cb=' . $_SESSION['profile_image_ts']]);
} else {
    // Cleanup the uploaded file on DB failure
    @unlink($storagePath);
    Response::error('خطا در ذخیره مسیر فایل در پایگاه داده');
}
