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

        // Store relative path in DB (consistent with vehicle images)
        $profilePath = 'uploads/profiles/' . $filename;

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

    // Persist fields into users and user_profiles tables
    try {
        // Fields for users table
        $userUpdate = [];
        if (!empty($name)) $userUpdate['full_name'] = $name;
        if (!empty($username)) $userUpdate['username'] = $username;
        if (!empty($email)) $userUpdate['email'] = $email;
        if (!empty($phone)) $userUpdate['phone'] = $phone;
        if ($profilePath) $userUpdate['profile_image'] = $profilePath;

        if (!empty($userUpdate)) {
            $db->update('users', $userUpdate, ['id' => $userId]);
        }

        // Fields for user_profiles table
        $profileUpdate = [];
        if (!empty($_POST['address'])) $profileUpdate['address'] = trim($_POST['address']);
        if (!empty($_POST['city'])) $profileUpdate['city'] = trim($_POST['city']);
        if (!empty($_POST['state'])) $profileUpdate['state'] = trim($_POST['state']);
        if (!empty($_POST['postal_code'])) $profileUpdate['postal_code'] = trim($_POST['postal_code']);
        if (!empty($_POST['country'])) $profileUpdate['country'] = trim($_POST['country']);
        if (!empty($_POST['birth_date'])) $profileUpdate['birth_date'] = trim($_POST['birth_date']);
        if (!empty($_POST['gender'])) $profileUpdate['gender'] = trim($_POST['gender']);
        if (!empty($_POST['notification_settings'])) $profileUpdate['notification_settings'] = json_encode($_POST['notification_settings']);
        if (!empty($_POST['preferences'])) $profileUpdate['preferences'] = json_encode($_POST['preferences']);
        if (!empty($phone)) $profileUpdate['phone'] = $phone;
        if (!empty($_POST['home_phone'])) $profileUpdate['home_phone'] = trim($_POST['home_phone']);
        if (!empty($nationalId)) $profileUpdate['national_id'] = $nationalId;
        if (!empty($_POST['driver_license'])) $profileUpdate['driver_license'] = trim($_POST['driver_license']);
        if ($profilePath) $profileUpdate['profile_image'] = $profilePath;

        // Check if user_profiles row exists
        $existingProfile = $db->fetchOne('SELECT id FROM user_profiles WHERE user_id = :user_id', ['user_id' => $userId]);
        if ($existingProfile) {
            if (!empty($profileUpdate)) {
                $db->update('user_profiles', $profileUpdate, ['user_id' => $userId]);
            }
        } else {
            // Insert new row
            $profileUpdate['user_id'] = $userId;
            $db->insert('user_profiles', $profileUpdate);
        }

        // Fetch authoritative merged data and refresh session
        $fresh = $db->fetchOne("
            SELECT 
                u.id, u.full_name, u.username, u.email, u.phone, u.profile_image, u.address,
                up.city, up.state, up.postal_code, up.country, up.birth_date, up.gender, 
                up.notification_settings, up.preferences, up.profile_image AS profile_img_extended,
                up.phone AS phone_extended, up.home_phone, up.national_id, up.driver_license,
                up.address AS profile_address
            FROM users u 
            LEFT JOIN user_profiles up ON u.id = up.user_id 
            WHERE u.id = :id
        ", ['id' => $userId]);

        if ($fresh) {
            $_SESSION['user'] = $fresh;
            $_SESSION['profile_image'] = $fresh['profile_img_extended'] ?? $fresh['profile_image'] ?? '';
            $_SESSION['profile_image_ts'] = time();
            $_SESSION['name'] = $fresh['full_name'] ?? '';
            $_SESSION['email'] = $fresh['email'] ?? '';
            $_SESSION['username'] = $fresh['username'] ?? '';
        }

        // Return merged profile in response
        $profile = [
            'id' => $fresh['id'],
            'full_name' => $fresh['full_name'],
            'username' => $fresh['username'] ?? '',
            'email' => $fresh['email'],
            'phone' => $fresh['phone_extended'] ?? $fresh['phone'],
            'home_phone' => $fresh['home_phone'],
            'national_id' => $fresh['national_id'],
            'driver_license' => $fresh['driver_license'],
            'profile_image' => $fresh['profile_img_extended'] ?? $fresh['profile_image'],
            'address' => $fresh['profile_address'] ?? $fresh['address'] ?? '',
            'city' => $fresh['city'],
            'state' => $fresh['state'],
            'postal_code' => $fresh['postal_code'],
            'country' => $fresh['country'],
            'birth_date' => $fresh['birth_date'],
            'gender' => $fresh['gender'],
            'notification_settings' => $fresh['notification_settings'] ? json_decode($fresh['notification_settings'], true) : null,
            'preferences' => $fresh['preferences'] ? json_decode($fresh['preferences'], true) : null,
        ];

        Response::success('Profile updated successfully', ['user' => $profile, 'profile_image' => ($_SESSION['profile_image'] ? ($_SESSION['profile_image'] . '?cb=' . $_SESSION['profile_image_ts']) : '')]);
    } catch (Exception $e) {
        error_log('Profile update DB error: ' . $e->getMessage());
        Response::error('Profil güncellenirken bir hata oluştu: ' . $e->getMessage(), 500);
    }

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    Response::error('Profil güncellenirken bir hata oluştu: ' . $e->getMessage(), 500);
}
