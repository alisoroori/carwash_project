<?php
class PaymentManager
{
    private $conn;
    private $apiKey; // Your payment provider API key

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->apiKey = getenv('PAYMENT_API_KEY');
    }

    public function createPaymentSession($bookingId, $amount)
    {
        $stmt = $this->conn->prepare("
            SELECT b.*, u.email, c.name as carwash_name 
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN carwash c ON b.carwash_id = c.id
            WHERE b.id = ?
        ");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();

        // Create payment session with provider (e.g., iyzico)
        $payment = [
            'amount' => $amount,
            'currency' => 'TRY',
            'payment_method_types' => ['card'],
            'customer_email' => $booking['email'],
            'success_url' => "http://localhost/carwash_project/frontend/payment/success.php?booking_id={$bookingId}",
            'cancel_url' => "http://localhost/carwash_project/frontend/payment/cancel.php?booking_id={$bookingId}"
        ];

        // Store payment intent
        $stmt = $this->conn->prepare("
            INSERT INTO payments (booking_id, amount, status)
            VALUES (?, ?, 'pending')
        ");
        $stmt->bind_param('id', $bookingId, $amount);
        $stmt->execute();

        return $payment;
    }
}
