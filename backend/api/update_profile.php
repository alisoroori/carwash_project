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
        $allowedMime = ['image/jpeg','image/png','image/webp'];
            $fileType = mime_content_type($_FILES['profile_image']['tmp_name']);
            $maxSize = 3 * 1024 * 1024; // 3MB limit
            if (!in_array($fileType, $allowedMime)) {
                Response::error('Error: Geçersiz dosya türü. Sadece JPG, PNG veya WEBP yükleyin.', 400);
            }
            if ($_FILES['profile_image']['size'] > $maxSize) {
                Response::error('Error: Dosya çok büyük. Maksimum 3MB.', 400);
            }

        $uploadDir = PROFILE_UPLOAD_PATH;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
            $err = error_get_last();
            $detail = $err['message'] ?? 'move_uploaded_file failed';
            Response::error('Error: Profil resmi yüklenemedi. ' . $detail, 500);
        }

        // Store canonical path in DB (no cache-buster)
        $profilePath = PROFILE_UPLOAD_URL . '/' . $filename;

        // Remove old profile image if set and not default
        $old = $_SESSION['profile_image'] ?? null;
        if ($old && strpos($old, '/frontend/images/default-avatar.svg') === false) {
            $oldFull = str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'], $old);
            $oldFull = preg_replace('/\?ts=\d+$/', '', $oldFull); // Remove timestamp
            if (file_exists($oldFull)) @unlink($oldFull);
        }
    }

    // Validate national id (if provided)
    $nationalId = trim($_POST['national_id'] ?? '');
    if ($nationalId !== '' && !preg_match('/^[0-9]{11}$/', $nationalId)) {
        Response::error('Error: Geçersiz T.C. Kimlik No. 11 rakam olmalıdır.', 400);
    }

    // Update session values (we'll refresh from DB after persisting)
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    // Persist all fields into users table (canonical)
    try {
        $update = ['name' => $name];
        if (!empty($email)) $update['email'] = $email;
        if (!empty($phone)) $update['phone'] = $phone;
        if (!empty($username)) $update['username'] = $username;
        if (!empty($nationalId)) $update['national_id'] = $nationalId;
        if (!empty($_POST['driver_license'])) $update['driver_license'] = trim($_POST['driver_license']);
        if (!empty($_POST['home_phone'])) $update['home_phone'] = trim($_POST['home_phone']);
        if (!empty($_POST['city'])) $update['city'] = trim($_POST['city']);
        if (!empty($_POST['address'])) $update['address'] = trim($_POST['address']);
        if ($profilePath) $update['profile_image'] = $profilePath;

        $db->update('users', $update, ['id' => $userId]);

        // Fetch authoritative users row and refresh session
        $fresh = $db->fetchOne('SELECT * FROM users WHERE id = :id', ['id' => $userId]);
        if ($fresh) {
            $_SESSION['user'] = $fresh;
            $_SESSION['profile_image'] = $fresh['profile_image'] ?? '';
            $_SESSION['profile_image_ts'] = time();
            $_SESSION['name'] = $fresh['name'] ?? '';
            $_SESSION['email'] = $fresh['email'] ?? '';
            $_SESSION['username'] = $fresh['username'] ?? '';
        }

        // Return authoritative user in response
        Response::success('Profile updated successfully', ['user' => $fresh, 'profile_image' => ($_SESSION['profile_image'] ? ($_SESSION['profile_image'] . '?cb=' . $_SESSION['profile_image_ts']) : '')]);
    } catch (Exception $e) {
        error_log('Profile update DB error: ' . $e->getMessage());
        Response::error('Profil güncellenirken bir hata oluştu: ' . $e->getMessage(), 500);
    }

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    Response::error('Profil güncellenirken bir hata oluştu: ' . $e->getMessage(), 500);
}
