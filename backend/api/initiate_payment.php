<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/payment_gateway.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    // Get order data from request
    $orderData = json_decode(file_get_contents('php://input'), true);
    if (!$orderData) {
        throw new Exception('Invalid order data');
    }

    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $orderData['user'] = $user;

    // Initialize payment gateway
    $gateway = new PaymentGateway($conn);
    $result = $gateway->createPayment($orderData);

    if ($result->getStatus() === 'success') {
        // Save payment attempt
        $stmt = $conn->prepare("
            INSERT INTO payment_attempts (
                order_id,
                user_id,
                amount,
                payment_id,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, 'pending', NOW())
        ");

        $paymentId = $result->getPaymentId();
        $stmt->bind_param(
            'iids',
            $orderData['order_id'],
            $_SESSION['user_id'],
            $orderData['total'],
            $paymentId
        );
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'paymentUrl' => $result->getPaymentPageUrl(),
            'paymentId' => $paymentId
        ]);
    } else {
        throw new Exception($result->getErrorMessage());
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
