<?php
// Customer Dashboard Processing Script — improved security, CSRF, file upload handling

require_once __DIR__ . '/../includes/bootstrap.php'; // autoload, logger, handlers
require_once __DIR__ . '/../includes/db.php'; // legacy db helper (provides getDBConnection())

use App\Classes\Auth;
use App\Classes\Logger;
use App\Classes\Session;

// Ensure session started
if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() == PHP_SESSION_NONE) session_start();
}

// Require authenticated customer
Auth::requireRole('customer');

// Helper to send JSON response on AJAX
function sendJson($payload, $status = 200) {
    header('Content-Type: application/json', true, $status);
    echo json_encode($payload);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Customer_Dashboard.php');
    exit();
}

// CSRF validation
$postedCsrf = $_POST['csrf_token'] ?? '';
$sessionCsrf = $_SESSION['csrf_token'] ?? '';
if (empty($postedCsrf) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $postedCsrf)) {
    Logger::warning('CSRF validation failed', ['user' => $_SESSION['user_id'] ?? null]);
    // AJAX -> JSON 403
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (stripos($accept, 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        sendJson(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }
    $_SESSION['error_message'] = 'Geçersiz istek (CSRF).';
    header('Location: Customer_Dashboard.php');
    exit();
}

// Obtain DB connection (legacy function returns PDO)
$conn = getDBConnection();
$user_id = (int)($_SESSION['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_profile':
            // Collect and sanitize
            $name = trim($_POST['name'] ?? '');
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if ($name === '' || $email === '') {
                throw new Exception('Ad ve e-posta alanları zorunludur.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Geçerli bir e-posta adresi girin.');
            }

            // Check uniqueness
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception('Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.');
            }

            // Handle optional profile photo upload
            $profilePath = null;
            if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['profile_photo'];
                // Basic validations
                $allowed = ['image/jpeg','image/png','image/webp'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Dosya yükleme hatası.');
                }
                if ($file['size'] > 3 * 1024 * 1024) {
                    throw new Exception('Dosya boyutu 3MB\'ı aşamaz.');
                }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!in_array($mime, $allowed, true)) {
                    throw new Exception('Geçersiz dosya türü. (jpg, png, webp)');
                }

                // Save file to backend/auth/uploads/profiles/
                $uploadDir = __DIR__ . '/../auth/uploads/profiles/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safeName = 'profile_' . $user_id . '_' . time() . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
                $target = $uploadDir . $safeName;
                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    throw new Exception('Dosya kaydedilemedi.');
                }
                // store relative path
                $profilePath = 'backend/auth/uploads/profiles/' . $safeName;
            }

            // Update DB
            $updateSql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()";
            $params = [$name, $email, $phone, $address];
            if ($profilePath !== null) {
                $updateSql .= ", profile_photo = ?";
                $params[] = $profilePath;
            }
            $updateSql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $conn->prepare($updateSql);
            $ok = $stmt->execute($params);

            if (!$ok) {
                throw new Exception('Profil güncellenirken bir hata oluştu.');
            }

            // update session
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            if ($profilePath !== null) $_SESSION['profile_photo'] = $profilePath;

            // AJAX -> JSON success
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (stripos($accept, 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                sendJson(['success' => true, 'message' => 'Profil güncellendi']);
            }

            $_SESSION['success_message'] = 'Profil bilgileriniz başarıyla güncellendi.';
            header('Location: Customer_Dashboard.php#profile');
            exit();
            break;

        case 'update_vehicle':
            $car_brand = trim($_POST['car_brand'] ?? '');
            $car_model = trim($_POST['car_model'] ?? '');
            $license_plate = trim($_POST['license_plate'] ?? '');
            $car_year = $_POST['car_year'] ?? null;
            $car_color = trim($_POST['car_color'] ?? '');

            // For demo we store vehicles in a user_vehicles table or users table; here we assume user_vehicles table exists
            $stmt = $conn->prepare("
                INSERT INTO user_vehicles (user_id, brand, model, license_plate, year, color, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $res = $stmt->execute([$user_id, $car_brand, $car_model, $license_plate, $car_year ?: null, $car_color]);

            if (!$res) throw new Exception('Araç bilgileri kaydedilemedi.');

            if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                sendJson(['success' => true, 'message' => 'Araç eklendi']);
            }

            $_SESSION['success_message'] = 'Araç başarıyla eklendi.';
            header('Location: Customer_Dashboard.php#vehicles');
            exit();
            break;

        case 'create_reservation':
            $service_type = trim($_POST['service_type'] ?? '');
            $reservation_date = $_POST['reservation_date'] ?? '';
            $reservation_time = $_POST['reservation_time'] ?? '';
            $carwash_id = (int)($_POST['carwash_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');

            if ($service_type === '' || $reservation_date === '' || $reservation_time === '' || $carwash_id <= 0) {
                throw new Exception('Tüm zorunlu alanları doldurun.');
            }

            $reservation_datetime = $reservation_date . ' ' . $reservation_time;
            if (strtotime($reservation_datetime) <= time()) {
                throw new Exception('Rezervasyon tarihi geçmiş bir tarih olamaz.');
            }

            $stmt = $conn->prepare("
                INSERT INTO reservations (user_id, carwash_id, service_type, reservation_date, reservation_time, notes, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $res = $stmt->execute([$user_id, $carwash_id, $service_type, $reservation_date, $reservation_time, $notes]);

            if (!$res) throw new Exception('Rezervasyon oluşturulamadı.');

            if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                sendJson(['success' => true, 'message' => 'Rezervasyon oluşturuldu']);
            }

            $_SESSION['success_message'] = 'Rezervasyonunuz başarıyla oluşturuldu. Onay için bekleyiniz.';
            header('Location: Customer_Dashboard.php#reservations');
            exit();
            break;

        case 'cancel_reservation':
            $reservation_id = (int)($_POST['reservation_id'] ?? 0);
            if ($reservation_id <= 0) throw new Exception('Geçersiz rezervasyon ID.');

            $stmt = $conn->prepare("SELECT id, status FROM reservations WHERE id = ? AND user_id = ?");
            $stmt->execute([$reservation_id, $user_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation || !in_array($reservation['status'], ['pending','confirmed'], true)) {
                throw new Exception('Rezervasyon bulunamadı veya iptal edilemez.');
            }

            $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?");
            $res = $stmt->execute([$reservation_id, $user_id]);
            if (!$res) throw new Exception('Rezervasyon iptal edilirken hata oluştu.');

            if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                sendJson(['success' => true, 'message' => 'Rezervasyon iptal edildi']);
            }

            $_SESSION['success_message'] = 'Rezervasyonunuz başarıyla iptal edildi.';
            header('Location: Customer_Dashboard.php#reservations');
            exit();
            break;

        default:
            throw new Exception('Geçersiz işlem.');
    }
} catch (Exception $e) {
    Logger::exception($e, ['action' => $action, 'user' => $user_id]);
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (stripos($accept, 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 400);
    }
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: Customer_Dashboard.php');
    exit();
}
