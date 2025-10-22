<?php
declare(strict_types=1);

namespace App\Classes;

class DisputeWorkflow
{
    private $conn;
    private $mailer;
    private $paymentGateway;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->mailer = new EmailHandler($conn);
        $this->paymentGateway = new PaymentGateway($conn);
    }

    public function processDispute($disputeId)
    {
        $dispute = $this->getDisputeDetails($disputeId);

        // Check if can be auto-resolved
        if ($this->canAutoResolve($dispute)) {
            return $this->autoResolveDispute($dispute);
        }

        // Start manual review process
        return $this->initiateManualReview($dispute);
    }

    private function canAutoResolve($dispute)
    {
        // Auto-resolution criteria
        $criteria = [
            'amount_threshold' => 100, // Auto-resolve disputes under ₺100
            'customer_history' => $this->getCustomerDisputeHistory($dispute['user_id']),
            'transaction_age' => $this->getTransactionAge($dispute['transaction_id'])
        ];

        return (
            $dispute['amount'] <= $criteria['amount_threshold'] &&
            $criteria['customer_history']['total_disputes'] < 3 &&
            $criteria['transaction_age'] < 48 // Hours
        );
    }

    private function autoResolveDispute($dispute)
    {
        try {
            $this->conn->begin_transaction();

            // Process refund if needed
            if ($dispute['resolution_type'] === 'refund') {
                $this->processRefund($dispute);
            }

            // Update dispute status
            $this->updateDisputeStatus($dispute['id'], 'resolved');

            // Send notifications
            $this->notifyResolution($dispute);

            $this->conn->commit();
            return ['success' => true, 'auto_resolved' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}

