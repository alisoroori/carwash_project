<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/invoice_generator.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    if (!isset($_GET['order_id'])) {
        throw new Exception('Order ID is required');
    }

    // Verify order belongs to user
    $stmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND user_id = ?
    ");

    $stmt->bind_param('ii', $_GET['order_id'], $_SESSION['user_id']);
    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Order not found');
    }

    // Generate invoice
    $generator = new InvoiceGenerator($conn);
    $pdf = $generator->generateInvoice($_GET['order_id']);

    if (!$pdf) {
        throw new Exception('Failed to generate invoice');
    }

    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice_' . $_GET['order_id'] . '.pdf"');
    echo $pdf->Output('invoice_' . $_GET['order_id'] . '.pdf', 'S');
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
