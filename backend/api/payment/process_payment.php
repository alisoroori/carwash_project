<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';
require_once '../../includes/payment_gateway.php';
require_once '../../includes/classes/Payment.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['booking_id'])) {
        throw new Exception('Booking ID is required');
    }

    $gateway = new PaymentGateway($conn);
    
    $amount = //... get amount from your data source
    $customerId = //... get customer ID from your data source
    $bookingId = $_POST['booking_id'];

    // Create new payment
    $payment = new Payment([
        'amount' => $amount,
        'customer_id' => $customerId,
        'booking_id' => $bookingId
    ]);

    // Get payment details
    echo $payment->getStatus(); // 'pending'
    echo $payment->getAmount(); // 100.00

    // Update payment status
    $payment->setStatus('completed');
    $payment->save();

    $status = $payment->getStatus();
    $paymentId = $payment->getId();

    if ($status === 'success') {
        // Record transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions 
            (booking_id, payment_id, amount, status)
            VALUES (?, ?, ?, 'completed')
        ");

        $stmt->bind_param(
            'isd',
            $bookingId,
            $paymentId,
            $payment->getAmount()
        );

        $stmt->execute();

        echo json_encode([
            'success' => true,
            'payment_id' => $paymentId
        ]);
    } else {
        throw new Exception('Payment failed. Please try again.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
