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
    if ($conn instanceof PDO) {
        $conn->beginTransaction();
    } elseif ($conn instanceof mysqli) {
        $conn->autocommit(false);
    } else {
        throw new Exception('Database connection type not supported for transactions');
    }

    // Get booking details
    $stmt = $conn->prepare("
        SELECT b.*, s.price 
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.id = ? AND b.status = 'pending'
    ");

    $stmt->execute([$data['booking_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Invalid booking or already processed');
    }

    // Process payment (simulate payment gateway)
    $payment_success = true; // In real app, call payment gateway API

    if ($payment_success) {
        // Record payment
        $stmt = $conn->prepare("
            INSERT INTO payments (
                booking_id, amount, status,
                payment_method, created_at
            ) VALUES (?, ?, 'completed', ?, NOW())
        ");

        $stmt->execute([
            $booking['id'],
            $booking['price'],
            $data['payment_details']['method'] ?? 'card'
        ]);

        // Update booking status
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'confirmed'
            WHERE id = ?
        ");

        $stmt->execute([$booking['id']]);
        // Commit transaction
        if ($conn instanceof PDO) {
            $conn->commit();
        } elseif ($conn instanceof mysqli) {
            $conn->commit();
            $conn->autocommit(true);
        }

        // Respond with success
        $payment_id = null;
        if ($conn instanceof PDO) {
            $payment_id = $conn->lastInsertId();
        } elseif ($conn instanceof mysqli) {
            $payment_id = $conn->insert_id;
        }
        echo json_encode([
            'booking_id' => $booking['id'],
            'payment_id' => $payment_id
        ]);
    } else {
        throw new Exception('Payment processing failed');
    }
} catch (Exception $e) {
    if ($conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    } elseif ($conn instanceof mysqli) {
        $conn->rollback();
        $conn->autocommit(true);
    }

    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
