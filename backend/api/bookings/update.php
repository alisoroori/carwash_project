<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

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

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? null;
$sessionCsrf = $_SESSION['csrf_token'] ?? null;
if (empty($csrfToken) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'errors' => ['Invalid CSRF token']]);
    exit;
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

// Basic validation
$errors = [];
if (!$carwashId) $errors[] = 'carwash_id is required';
if (!$serviceId) $errors[] = 'service_id is required';
if (!$date) $errors[] = 'date is required';
if (!$time) $errors[] = 'time is required';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $model = new Booking_Model();
    $existing = $model->findById($bookingId);
    if (!$existing || (int)$existing['user_id'] !== (int)$userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'errors' => ['Booking not found or access denied']]);
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
    error_log('bookings/update.php error: ' . $e->getMessage());
    if (function_exists('send_structured_error_response')) {
        send_structured_error_response($e, 500);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Internal server error']]);
    exit;
}
