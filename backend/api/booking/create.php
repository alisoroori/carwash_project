<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Ensure user is authenticated
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'User not authenticated']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['service_id', 'date', 'time'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate and sanitize inputs
    $service_id = filter_var($data['service_id'], FILTER_VALIDATE_INT);
    $date = date('Y-m-d', strtotime($data['date']));
    $time = date('H:i:s', strtotime($data['time']));
    $user_id = $_SESSION['user_id'];

    if (!$service_id || !$date || !$time) {
        throw new Exception('Invalid input data');
    }

    // Start transaction
    $conn->begin_transaction();

    // Get service details and validate
    $stmt = $conn->prepare("
        SELECT s.*, c.id as carwash_id 
        FROM services s
        JOIN carwash c ON s.carwash_id = c.id
        WHERE s.id = ? AND s.status = 'active'
    ");

    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $service = $stmt->get_result()->fetch_assoc();

    if (!$service) {
        throw new Exception('Service not found or inactive');
    }

    // Check if timeslot is available
    $stmt = $conn->prepare("
        SELECT COUNT(*) as booking_count 
        FROM bookings 
        WHERE service_id = ? 
        AND booking_date = ? 
        AND booking_time = ?
        AND status != 'cancelled'
    ");

    $stmt->bind_param('iss', $service_id, $date, $time);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['booking_count'] > 0) {
        throw new Exception('Selected time slot is not available');
    }

    // Create booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (
            user_id, carwash_id, service_id, 
            booking_date, booking_time, 
            status, total_price, created_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");

    $stmt->bind_param(
        'iiissd',
        $user_id,
        $service['carwash_id'],
        $service_id,
        $date,
        $time,
        $service['price']
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create booking');
    }

    $booking_id = $conn->insert_id;

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'total_price' => $service['price']
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
