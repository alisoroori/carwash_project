<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/api_bootstrap.php';


if (session_status() === PHP_SESSION_NONE) session_start();

// Request helpers: JSON body merge + structured errors
if (file_exists(__DIR__ . '/../../includes/request_helpers.php')) {
    require_once __DIR__ . '/../../includes/request_helpers.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed']]);
    exit;
}

// CSRF validation using helper (preferred) with fallback to inline check
if (file_exists(__DIR__ . '/../../includes/csrf_protect.php')) {
    require_once __DIR__ . '/../../includes/csrf_protect.php';
    // Ensure token exists for the session
    generate_csrf_token();
    // Will emit 403 JSON and exit on failure
    require_valid_csrf();
} else {
    // Legacy inline check
    $csrfToken = $_POST['csrf_token'] ?? null;
    $sessionCsrf = $_SESSION['csrf_token'] ?? null;
    if (empty($csrfToken) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'errors' => ['Invalid CSRF token']]);
        exit;
    }
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

require_once __DIR__ . '/../../../vendor/autoload.php';
use App\Models\Booking_Model;

$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
if ($bookingId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Invalid booking id']]);
    exit;
}

$carwashId = isset($_POST['carwash_id']) ? (int)$_POST['carwash_id'] : null;
$serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : null;
$date = $_POST['date'] ?? null;
$time = $_POST['time'] ?? null;
$notes = $_POST['notes'] ?? null;

// We will validate after loading existing booking so we can fallback to existing carwash_id/service_id
$errors = [];

try {
    $model = new Booking_Model();
    $existing = $model->findById($bookingId);
    if (!$existing || (int)$existing['user_id'] !== (int)$userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'errors' => ['Booking not found or access denied']]);
        exit;
    }
    // Fallback to existing values if not provided
    if (empty($carwashId) && isset($existing['carwash_id'])) $carwashId = (int)$existing['carwash_id'];
    if (empty($serviceId) && isset($existing['service_id'])) $serviceId = (int)$existing['service_id'];

    // Basic validation (after fallback)
    if (!$carwashId) $errors[] = 'carwash_id is required';
    if (!$serviceId) $errors[] = 'service_id is required';
    if (!$date) $errors[] = 'date is required';
    if (!$time) $errors[] = 'time is required';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $updateData = [
        'carwash_id' => $carwashId,
        'service_id' => $serviceId,
        'booking_date' => $date,
        'booking_time' => $time,
    ];
    // optionally store notes if booking table has notes column
    if (isset($existing['notes'])) {
        $updateData['notes'] = $notes;
    }

    $ok = $model->update($bookingId, $updateData);
    if (!$ok) throw new Exception('Update failed');

    echo json_encode(['success' => true, 'booking_id' => $bookingId]);
    exit;
} catch (Throwable $e) {
    // Write full exception to application log (prefer Logger::exception)
    if (class_exists('\App\\Classes\\Logger')) {
        try {
            \App\Classes\Logger::exception($e);
        } catch (Throwable $logEx) {
            error_log('Logger::exception failed: ' . $logEx->getMessage());
            error_log('Original bookings/update.php error: ' . $e->getMessage());
        }
    } else {
        error_log('bookings/update.php error: ' . $e->getMessage());
    }

    // If a structured responder exists, prefer it (it may include stack/trace in dev)
    if (function_exists('send_structured_error_response')) {
        send_structured_error_response($e, 500);
        exit;
    }

    // Ensure client receives JSON
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode(['success' => false, 'errors' => ['Internal server error']]);
    exit;
}
