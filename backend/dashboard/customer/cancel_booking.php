<?php
session_start();
// CSRF helper
$csrf_helper = __DIR__ . '/../../includes/csrf_helper.php';
if (file_exists($csrf_helper)) require_once $csrf_helper;
require_once '../../includes/db.php';
// Request helpers
if (file_exists(__DIR__ . '/../../includes/request_helpers.php')) {
    require_once __DIR__ . '/../../includes/request_helpers.php';
}

header('Content-Type: application/json');
// API response helpers
if (file_exists(__DIR__ . '/../../includes/api_response.php')) {
    require_once __DIR__ . '/../../includes/api_response.php';
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    api_error('Not authenticated', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    api_error('Invalid request', 400);
}

// CSRF validation for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !function_exists('hash_equals') || !hash_equals((string)$_SESSION['csrf_token'], (string)$token)) {
        api_error('Invalid CSRF token', 403);
    }
}

try {
    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);

    // Verify booking belongs to user and is pending
    $stmt = $conn->prepare("
           SELECT status 
           FROM bookings 
           WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Randevu bulunamadı veya iptal edilemez.');
    }

    // Cancel booking
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'cancelled', 
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $booking_id);

    if (!$stmt->execute()) {
        throw new Exception('Randevu iptal edilemedi.');
    }

    api_success('Booking cancelled');
} catch (Throwable $e) {
    if (function_exists('send_structured_error_response')) {
        send_structured_error_response($e, 500);
    }
    api_error($e->getMessage(), 500);
}
