<?php
session_start();
require_once '../../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Validate carwash_id
if (!isset($_GET['carwash_id']) || !is_numeric($_GET['carwash_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid carwash ID']);
    exit();
}

$carwash_id = filter_var($_GET['carwash_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    // Get active services for the selected carwash
    $stmt = $conn->prepare("
        SELECT id, service_name, description, price, duration 
        FROM services 
        WHERE carwash_id = ? AND status = 'active' 
        ORDER BY price ASC
    ");

    $stmt->bind_param("i", $carwash_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'id' => $row['id'],
            'service_name' => htmlspecialchars($row['service_name']),
            'description' => htmlspecialchars($row['description']),
            'price' => number_format($row['price'], 2),
            'duration' => $row['duration']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($services);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
