﻿<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    $stmt = $conn->prepare("
        SELECT id, name, description, price, duration, image_url, category_id
        FROM services
        WHERE carwash_id = ?
        ORDER BY category_id, name
    ");

    $stmt->bind_param('i', $_SESSION['carwash_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }

    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
