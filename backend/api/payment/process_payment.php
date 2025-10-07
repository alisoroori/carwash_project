<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';
require_once '../../includes/classes/PaymentGateway.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['booking_id']) || !isset($data['amount'])) {
        throw new Exception('Missing required parameters');
    }

    $payment = new PaymentGateway($conn);
    $result = $payment->processPayment(
        $data['booking_id'],
        $data['amount'],
        $data['payment_details'] ?? []
    );

    if ($result) {
        echo json_encode([
            'success' => true,
            'payment_id' => $result['payment_id'] ?? null,
            'status' => $payment->getStatus()
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
