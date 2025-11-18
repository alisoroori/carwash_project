<?php
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;
use App\Classes\Validator;
use App\Classes\Session;

Session::start();

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) Response::unauthorized();

    $name = Validator::sanitizeString($_POST['name'] ?? '');
    if (empty($name) || strlen($name) < 2) {
        Response::error('Error: Geçerli bir isim girin (en az 2 karakter).', 400);
    }

    $email = Validator::sanitizeEmail($_POST['email'] ?? '');
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Response::error('Error: Geçerli bir e-posta adresi girin.', 400);
    }

    $phone = Validator::sanitizeString($_POST['phone'] ?? '');
    $username = Validator::sanitizeString($_POST['username'] ?? '');

    $profilePath = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowedMime = ['image/jpeg','image/png','image/webp','image/gif'];
        $fileType = mime_content_type($_FILES['profile_image']['tmp_name']);
        $maxSize = 5 * 1024 * 1024;
        if (!in_array($fileType, $allowedMime)) {
            Response::error('Error: Geçersiz dosya türü. Sadece JPG, PNG, WEBP veya GIF yükleyin.', 400);
        }
        if ($_FILES['profile_image']['size'] > $maxSize) {
            Response::error('Error: Dosya çok büyük. Maksimum 5MB.', 400);
        }

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/backend/uploads/profile_images/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
        $target = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
            $err = error_get_last();
            $detail = $err['message'] ?? 'move_uploaded_file failed';
            Response::error('Error: Profil resmi yüklenemedi. ' . $detail, 500);
        }

        $profilePath = '/carwash_project/backend/uploads/profile_images/' . $filename;

        // Remove old profile image if set and not default
        $old = $_SESSION['profile_image'] ?? null;
        if ($old && strpos($old, '/frontend/images/default-avatar.svg') === false) {
            $oldFull = $_SERVER['DOCUMENT_ROOT'] . $old;
            if (file_exists($oldFull)) @unlink($oldFull);
        }
    }

    // Force role to 'carwash' (İşletme) and update session
    $role = 'carwash';
    $_SESSION['role'] = $role;

    // Update session
    $_SESSION['name'] = $name;
    if (!empty($email)) $_SESSION['email'] = $email;
    if (!empty($phone)) $_SESSION['phone'] = $phone;
    if (!empty($username)) $_SESSION['username'] = $username;
    if ($profilePath) $_SESSION['profile_image'] = $profilePath;

    // Persist to users table if available (include role)
    try {
        $update = ['name' => $name];
        if (!empty($email)) $update['email'] = $email;
        if (!empty($phone)) $update['phone'] = $phone;
        if (!empty($username)) $update['username'] = $username;
        if ($profilePath) $update['profile_image'] = $profilePath;
        // ensure role persisted
        $update['role'] = $role;
        $db->update('users', $update, ['id' => $userId]);
    } catch (Exception $e) {
        // Log but continue - we still updated session
        error_log('Profile update DB warning: ' . $e->getMessage());
        // Return a partial-success but include DB warning in response
        Response::success('Profile updated successfully (but DB update warning).', ['name' => $name, 'email' => $email ?? ($_SESSION['email'] ?? null), 'phone' => $phone ?? ($_SESSION['phone'] ?? null), 'username' => $username ?? ($_SESSION['username'] ?? null), 'profile_image' => $profilePath ?? ($_SESSION['profile_image'] ?? null), 'db_warning' => $e->getMessage()]);
    }

    Response::success('Profile updated successfully', ['name' => $name, 'email' => $email ?? ($_SESSION['email'] ?? null), 'phone' => $phone ?? ($_SESSION['phone'] ?? null), 'username' => $username ?? ($_SESSION['username'] ?? null), 'profile_image' => $profilePath ?? ($_SESSION['profile_image'] ?? null), 'role' => $role]);

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    Response::error('Profil güncellenirken bir hata oluştu: ' . $e->getMessage(), 500);
}
