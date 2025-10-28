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

    // Validate required fields
    $required = ['name', 'address', 'phone', 'email'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $stmt = $conn->prepare("
        INSERT INTO carwash (name, address, phone, email, latitude, longitude, status)
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");

    $stmt->bind_param(
        'ssssdd',
        $data['name'],
        $data['address'],
        $data['phone'],
        $data['email'],
        $data['latitude'] ?? null,
        $data['longitude'] ?? null
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create carwash');
    }

    echo json_encode([
        'success' => true,
        'carwash_id' => $conn->insert_id
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
