<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    $data = $_POST;
    $serviceId = $data['id'] ?? null;

    if ($serviceId) {
        // Update existing service
        $stmt = $conn->prepare("
            UPDATE services 
            SET name = ?, description = ?, price = ?, 
                duration = ?, category = ?, status = ?
            WHERE id = ? AND carwash_id = ?
        ");

        $stmt->bind_param(
            'ssdiisii',
            $data['name'],
            $data['description'],
            $data['price'],
            $data['duration'],
            $data['category'],
            $data['status'],
            $serviceId,
            $_SESSION['carwash_id']
        );
    } else {
        // Create new service
        $stmt = $conn->prepare("
            INSERT INTO services 
            (carwash_id, name, description, price, duration, category)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'issdis',
            $_SESSION['carwash_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['duration'],
            $data['category']
        );
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'id' => $serviceId ?? $conn->insert_id
        ]);
    } else {
        throw new Exception('Failed to save service');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
