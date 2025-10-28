<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    if (!isset($_GET['order_id'])) {
        throw new Exception('Order ID is required');
    }

    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ?
    ");

    $stmt->bind_param('ii', $_GET['order_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($payment = $result->fetch_assoc()) {
        $payment['items'] = json_decode($payment['items'], true);

        echo json_encode([
            'success' => true,
            'payment' => $payment
        ]);
    } else {
        throw new Exception('Payment not found');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
