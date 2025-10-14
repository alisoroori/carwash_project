<?php
require_once 'db.php';
class RefundProcessor {
    private $conn;
    private $paymentGateway;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->paymentGateway = new PaymentGateway($conn);
    }

    public function processRefund($transactionId, $amount, $reason) {
        try {
            $this->conn->begin_transaction();

            // Get transaction details
            $transaction = $this->getTransactionDetails($transactionId);
            
            // Process refund through payment gateway
            $refundResult = $this->paymentGateway->createRefund([
                'payment_id' => $transaction['payment_id'],
                'amount' => $amount,
                'reason' => $reason
            ]);

            if ($refundResult->isSuccessful()) {
                // Record refund
                $stmt = $this->conn->prepare("
                    INSERT INTO refunds (
                        transaction_id,
                        amount,
                        reason,
                        refund_id,
                        status
                    ) VALUES (?, ?, ?, ?, 'completed')
                ");

                $refundId = $refundResult->getId();
                $stmt->bind_param('idss', 
                    $transactionId, 
                    $amount, 
                    $reason, 
                    $refundId
                );
                
                $stmt->execute();
                $this->conn->commit();
                
                return [
                    'success' => true,
                    'refund_id' => $refundId
                ];
            }

            throw new Exception('Refund processing failed');

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}