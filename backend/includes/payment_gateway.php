<?php
declare(strict_types=1);

namespace App\Classes;

use Exception;

class PaymentGateway
{
    private $conn;
    private $apiKey;
    private $secretKey;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->apiKey = getenv('PAYMENT_API_KEY') ?: '';
        $this->secretKey = getenv('PAYMENT_SECRET_KEY') ?: '';
    }

    public function createPayment($bookingId)
    {
        $booking = $this->getBookingDetails($bookingId);

        // Create payment request
        $request = [
            'amount' => $booking['total_amount'] ?? $booking['total_price'] ?? 0,
            'currency' => 'TRY',
            'booking_id' => $bookingId,
            'customer_email' => $booking['customer_email']
        ];

        return $this->processPaymentRequest($request);
    }

    private function getBookingDetails($bookingId)
    {
        // Support both mysqli and PDO connections
        if (method_exists($this->conn, 'prepare') && ($this->conn instanceof \mysqli)) {
            $stmt = $this->conn->prepare("SELECT total_amount, customer_email FROM bookings WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $result = $stmt->get_result();
            $booking = $result->fetch_assoc();
            $stmt->close();
        } else {
            // Assume PDO
            $stmt = $this->conn->prepare("SELECT total_amount, customer_email FROM bookings WHERE id = :id");
            $stmt->execute(['id' => $bookingId]);
            $booking = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        if (!$booking) {
            throw new Exception("Booking not found.");
        }

        return $booking;
    }

    private function processPaymentRequest($request)
    {
        // Implementation for your payment provider
        // This is a placeholder for actual payment processing
        return [
            'status' => 'success',
            'payment_id' => uniqid('pay_'),
            'amount' => $request['amount']
        ];
    }
}
