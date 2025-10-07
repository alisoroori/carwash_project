<?php
require_once 'vendor/autoload.php';

class InvoiceGenerator {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function generateInvoice($transactionId) {
        // Get transaction details with booking and services
        $stmt = $this->conn->prepare("
            SELECT 
                t.*,
                b.appointment_datetime,
                c.name as carwash_name,
                c.address as carwash_address,
                u.name as customer_name,
                u.email as customer_email
            FROM transactions t
            JOIN bookings b ON t.booking_id = b.id
            JOIN carwash c ON b.carwash_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE t.id = ?
        ");

        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        $transaction = $stmt->get_result()->fetch_assoc();

        // Generate PDF
        $pdf = new TCPDF();
        $pdf->SetCreator('CarWash System');
        $pdf->SetTitle('Invoice #' . $transactionId);
        $pdf->AddPage();

        // Add company logo
        $pdf->Image('path/to/logo.png', 10, 10, 30);

        // Add invoice content
        $html = $this->generateInvoiceHTML($transaction);
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'invoice_' . $transactionId . '.pdf';
        $pdf->Output($filename, 'F');

        return $filename;
    }

    private function generateInvoiceHTML($transaction) {
        return "
            <h1>INVOICE</h1>
            <div class='invoice-details'>
                <p><strong>Invoice #:</strong> {$transaction['id']}</p>
                <p><strong>Date:</strong> {$transaction['transaction_date']}</p>
                <p><strong>Payment Status:</strong> {$transaction['status']}</p>
            </div>
            <div class='customer-details'>
                <h2>Customer Details</h2>
                <p>{$transaction['customer_name']}</p>
                <p>{$transaction['customer_email']}</p>
            </div>
            <!-- More invoice content -->
        ";
    }
}
