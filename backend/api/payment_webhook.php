<?php
require_once '../includes/db.php';
require_once '../includes/payment_gateway.php';

// Verify iyzico request
$rawData = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_IYZI_TOKEN'] ?? '';

// Initialize payment gateway
$gateway = new PaymentGateway($conn);

try {
    // Verify signature
    if (!$gateway->verifyWebhookSignature($signature, $rawData)) {
        throw new Exception('Invalid signature');
    }

    $data = json_decode($rawData, true);

    // Update payment status
    $stmt = $conn->prepare("
        UPDATE payment_attempts 
        SET status = ?,
            response_data = ?,
            updated_at = NOW()
        WHERE payment_id = ?
    ");

    $status = $data['status'];
    $responseJson = json_encode($data);
    $paymentId = $data['paymentId'];

    $stmt->bind_param('sss', $status, $responseJson, $paymentId);
    $stmt->execute();

    // If payment is successful, update order status
    if ($status === 'SUCCESS') {
        $stmt = $conn->prepare("
            UPDATE orders o
            JOIN payment_attempts pa ON o.id = pa.order_id
            SET o.status = 'completed',
                o.payment_status = 'paid'
            WHERE pa.payment_id = ?
        ");

        $stmt->bind_param('s', $paymentId);
        $stmt->execute();

        // Send confirmation email/SMS
        // ... (implement notification logic)
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    http_response_code(400);
    error_log('Payment webhook error: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
