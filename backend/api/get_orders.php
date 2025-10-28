<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $stmt = $conn->prepare("
        SELECT 
            id,
            items,
            subtotal,
            tax,
            discount,
            total,
            status,
            created_at
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $row['items'] = json_decode($row['items'], true);
        $orders[] = $row;
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
