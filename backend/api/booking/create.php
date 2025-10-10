<?php
require_once '../../includes/config.php';
require_once '../../includes/security.php';
require_once '../../includes/middleware.php';

header('Content-Type: application/json');
session_start();

try {
    // Apply security middleware
    SecurityMiddleware::handle();

    // Initialize security
    $security = Security::getInstance();

    // Check rate limiting
    $security->checkRateLimit('booking_' . $_SESSION['user_id'], 5, 60);

    // Validate CSRF token
    $security->validateCSRFToken($_POST['csrf_token']);

    // Validate inputs
    $rules = [
        'service_id' => ['required' => true, 'regex' => '/^\d+$/'],
        'date' => ['required' => true, 'regex' => '/^\d{4}-\d{2}-\d{2}$/'],
        'time' => ['required' => true, 'regex' => '/^\d{2}:\d{2}$/']
    ];

    $errors = $security->validateInput($_POST, $rules);
    if (!empty($errors)) {
        throw new Exception(json_encode($errors));
    }

    // Sanitize inputs
    $data = $security->sanitize($_POST);

    // Process booking
    $conn = new PDO('mysql:host=localhost;dbname=carwash', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure $conn is a PDO instance for transaction support
    if (!($conn instanceof PDO)) {
        throw new Exception('Database connection must use PDO for transactions.');
    }

    try {
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
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
