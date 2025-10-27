﻿<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['name', 'address', 'opening_time', 'closing_time'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize inputs
    $name = trim(strip_tags($data['name']));
    $address = htmlspecialchars(trim($data['address']));
    $phone = filter_var($data['phone'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $latitude = filter_var($data['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $opening_time = $data['opening_time'];
    $closing_time = $data['closing_time'];

    $stmt = $conn->prepare("
        INSERT INTO carwashes (user_id, name, address, phone, latitude, longitude, opening_time, closing_time, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");

    $stmt->bind_param(
        'isssddss',
        $_SESSION['user_id'],
        $name,
        $address,
        $phone,
        $latitude,
        $longitude,
        $opening_time,
        $closing_time
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create carwash');
    }

    $carwash_id = $conn->insert_id;

    echo json_encode([
        'success' => true,
        'carwash_id' => $carwash_id,
        'message' => 'Carwash created successfully'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
