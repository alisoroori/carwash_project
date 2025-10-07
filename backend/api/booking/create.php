<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['service_id']) || !isset($data['date']) || !isset($data['time'])) {
        throw new Exception('Missing required fields');
    }

    // Start transaction
    $conn->begin_transaction();
    $transaction_started = true;

    // Validate service and get price
    $stmt = $conn->prepare("
        SELECT price, carwash_id 
        FROM services 
        WHERE id = ? AND status = 'active'
    ");

    $stmt->bind_param('i', $data['service_id']);
    $stmt->execute();
    $service = $stmt->get_result()->fetch_assoc();

    if (!$service) {
        throw new Exception('Invalid service selected');
    }

    // Create booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (
            user_id, carwash_id, service_id, booking_date, 
            booking_time, total_price, status
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception('User not authenticated');
    }

    $stmt->bind_param(
        'iiissd',
        $user_id,
        $service['carwash_id'],
        $data['service_id'],
        $data['date'],
        $data['time'],
        $service['price']
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create booking');
    }

    $booking_id = $stmt->insert_id;
    $conn->commit();
    $transaction_started = false;

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'total_price' => $service['price']
    ]);
} catch (Exception $e) {
    if (isset($transaction_started) && $transaction_started) {
        $conn->rollback();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
