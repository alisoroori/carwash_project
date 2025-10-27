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

    $generator = new ReceiptGenerator($conn);

    if (!$generator->loadOrder($_GET['order_id'])) {
        throw new Exception('Order not found');
    }

    $pdf = $generator->generatePDF();

    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="receipt_' . $_GET['order_id'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdf;
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
