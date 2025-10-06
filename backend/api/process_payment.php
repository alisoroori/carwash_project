<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/payment_helper.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Lütfen önce giriş yapın');
    }

    // Get order and card data
    $order_data = json_decode($_POST['order_data'], true);
    $card_data = [
        'cardName' => $_POST['cardName'],
        'cardNumber' => $_POST['cardNumber'],
        'expMonth' => $_POST['expMonth'],
        'expYear' => $_POST['expYear'],
        'cvv' => $_POST['cvv']
    ];

    // Start transaction
    $conn->begin_transaction();

    // Create order in pending state
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id,
            items,
            subtotal,
            tax,
            discount,
            total,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $items_json = json_encode($order_data['items']);
    $stmt->bind_param(
        'isdddd',
        $_SESSION['user_id'],
        $items_json,
        $order_data['subtotal'],
        $order_data['tax'],
        $order_data['discount'],
        $order_data['total']
    );

    if (!$stmt->execute()) {
        throw new Exception('Sipariş oluşturulamadı');
    }

    $order_id = $conn->insert_id;
    $order_data['order_id'] = $order_id;

    // Get user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $order_data['user'] = $user;

    // Process payment through iyzico
    $paymentHelper = new PaymentHelper();
    $result = $paymentHelper->createPayment($order_data, $card_data);

    if ($result->getStatus() === 'success') {
        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'completed',
                payment_id = ?,
                payment_status = 'success'
            WHERE id = ?
        ");
        $payment_id = $result->getPaymentId();
        $stmt->bind_param('si', $payment_id, $order_id);
        $stmt->execute();

        // Clear cart
        unset($_SESSION['cart']);

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'order_id' => $order_id
        ]);
    } else {
        throw new Exception($result->getErrorMessage());
    }
} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
