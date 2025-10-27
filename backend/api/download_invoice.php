<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/receipt_generator.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    if (!isset($_GET['order_id'])) {
        throw new Exception('Order ID is required');
    }

    $orderId = intval($_GET['order_id']);

    // Verify order belongs to user
    $stmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND user_id = ?
    ");

    $stmt->bind_param('ii', $orderId, $_SESSION['user_id']);
    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Order not found');
    }

    // Initialize receipt generator
    $receiptGenerator = new ReceiptGenerator($conn);

    // Load order data
    if (!$receiptGenerator->loadOrder($orderId)) {
        http_response_code(404);
        die(json_encode(['error' => 'Order not found']));
    }

    // Generate PDF
    $pdfContent = $receiptGenerator->generatePDF();

    if ($pdfContent === false) {
        http_response_code(500);
        die(json_encode(['error' => 'Failed to generate PDF']));
    }

    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="receipt_' . $orderId . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output PDF content
    echo $pdfContent;
    exit();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
