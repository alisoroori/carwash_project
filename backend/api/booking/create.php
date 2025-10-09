<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

// Ensure $conn is a PDO instance for transaction support
if (!($conn instanceof PDO)) {
    throw new Exception('Database connection must use PDO for transactions.');
}

header('Content-Type: application/json');

// Verify user authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Authentication required']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['service_id', 'date', 'time'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize inputs
    $service_id = filter_var($data['service_id'], FILTER_VALIDATE_INT);
    $date = date('Y-m-d', strtotime($data['date']));
    $time = date('H:i:s', strtotime($data['time']));

    // Start transaction
    $conn->beginTransaction();

    // Check service availability
    $stmt = $conn->prepare("
        SELECT s.*, c.id as carwash_id 
        FROM services s
        JOIN carwash c ON s.carwash_id = c.id
        WHERE s.id = ? AND s.status = 'active'
    ");

    $stmt->execute([$service_id]);
    $service = $stmt->fetch();

    if (!$service) {
        throw new Exception('Service not found or inactive');
    }

    // Create booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (
            user_id, service_id, carwash_id,
            booking_date, booking_time, status,
            total_price, created_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $service_id,
        $service['carwash_id'],
        $date,
        $time,
        $service['price']
    ]);

    $booking_id = $conn->lastInsertId();
    $conn->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'total_price' => $service['price']
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
