<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';
require_once '../../includes/payment_gateway.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['booking_id'])) {
        throw new Exception('Booking ID is required');
    }

    $gateway = new PaymentGateway($conn);
    $payment = $gateway->createPayment($_POST['booking_id']);

    if ($payment->getStatus() === 'success') {
        // Record transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions 
            (booking_id, payment_id, amount, status)
            VALUES (?, ?, ?, 'completed')
        ");

        $stmt->bind_param(
            'isd',
            $_POST['booking_id'],
            $payment->getId(),
            $payment->getAmount()
        );

        $stmt->execute();

        echo json_encode([
            'success' => true,
            'payment_id' => $payment->getId(),
            'redirect_url' => $payment->getRedirectUrl()
        ]);
    } else {
        throw new Exception($payment->getErrorMessage());
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
