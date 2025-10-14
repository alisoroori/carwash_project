<?php
class TransactionManager
{
    private $conn;
    private $paymentGateway;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->paymentGateway = new PaymentGateway($conn);
    }

    public function createTransaction($bookingId)
    {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Create payment in payment gateway
            $payment = $this->paymentGateway->createPayment($bookingId);

            // Store transaction record
            $stmt = $this->conn->prepare("
                INSERT INTO transactions 
                (booking_id, payment_id, amount, payment_method, response_data)
                VALUES (?, ?, ?, ?, ?)
            ");

            $paymentData = json_encode($payment->getRawResult());
            $stmt->bind_param(
                'isdss',
                $bookingId,
                $payment->getPaymentId(),
                $payment->getPaidPrice(),
                'iyzico',
                $paymentData
            );

            $stmt->execute();
            $this->conn->commit();

            return [
                'success' => true,
                'payment_id' => $payment->getPaymentId(),
                'checkout_form' => $payment->getCheckoutFormContent()
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
