<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Lütfen önce giriş yapın');
    }

    // Get cart data from POST request
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['items']) || empty($data['items'])) {
        throw new Exception('Geçersiz sepet verisi');
    }

    // Save cart to session
    $_SESSION['cart'] = $data;

    // Calculate totals
    $subtotal = array_sum(array_column($data['items'], 'price'));
    $tax = $subtotal * 0.18;
    $total = $subtotal + $tax;

    // Create cart in database
    $stmt = $conn->prepare("
        INSERT INTO carts (
            user_id,
            items,
            subtotal,
            tax,
            total,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $items_json = json_encode($data['items']);
    $stmt->bind_param(
        'isddd',
        $_SESSION['user_id'],
        $items_json,
        $subtotal,
        $tax,
        $total
    );

    if (!$stmt->execute()) {
        throw new Exception('Sepet kaydedilemedi');
    }

    $_SESSION['cart_id'] = $conn->insert_id;

    echo json_encode([
        'success' => true,
        'cart_id' => $conn->insert_id
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
