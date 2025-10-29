<?php
session_start();
require_once '../../includes/db.php';
// Request helpers
if (file_exists(__DIR__ . '/../../includes/request_helpers.php')) {
    require_once __DIR__ . '/../../includes/request_helpers.php';
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
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

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if (function_exists('send_structured_error_response')) {
        send_structured_error_response($e, 500);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error_type' => get_class($e), 'message' => $e->getMessage()]);
    exit;
}
