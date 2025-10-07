<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';
require_once '../../includes/classes/PaymentGateway.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['booking_id']) || !isset($data['payment_details'])) {
        throw new Exception('Missing required parameters');
    }

    // Start transaction
    $conn->begin_transaction();

    // Validate booking exists and is pending payment
    $stmt = $conn->prepare("
        SELECT b.*, s.price 
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.id = ? AND b.status = 'pending'
    ");
    
    $booking_id = filter_var($data['booking_id'], FILTER_VALIDATE_INT);
    if (!$booking_id) {
        throw new Exception('Invalid booking ID');
    }

    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        throw new Exception('Booking not found or already processed');
    }

    // Process payment
    $gateway = new PaymentGateway($conn);
    $payment_result = $gateway->processPayment($booking['price'], $data['payment_details']);

    if (!$payment_result['success']) {
        throw new Exception('Payment failed: ' . $payment_result['error']);
    }

    // Update booking status
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'confirmed', 
            payment_id = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param('si', $payment_result['transaction_id'], $booking_id);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'transaction_id' => $payment_result['transaction_id']
    ]);

} catch (Exception $e) {
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Frontend fetch example (to be removed in production)
// fetch('/carwash_project/backend/api/process.php', {
//     method: 'POST',
//     headers: { 'Content-Type': 'application/json' },
//     body: JSON.stringify({
//         booking_id: 123,
//         payment_details: {
//             card_number: '4242424242424242',
//             expiry: '12/25',
//             cvv: '123'
//         }
//     })
// })
// .then(response => response.json())
// .then(data => console.log(data))
// .catch(error => console.error('Error:', error));