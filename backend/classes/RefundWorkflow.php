<?php
declare(strict_types=1);

namespace App\Classes;

class RefundWorkflow
{
    private $conn;
    private $mailer;
    private $refundProcessor;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->mailer = new PaymentEmailTemplates();
        $this->refundProcessor = new RefundProcessor($conn);
    }

    public function processRefundRequest($requestData)
    {
        // Check eligibility
        $eligibility = $this->checkRefundEligibility($requestData['booking_id']);
        if (!$eligibility['eligible']) {
            return [
                'success' => false,
                'message' => $eligibility['reason']
            ];
        }

        // Auto-approve if meets criteria
        if ($this->canAutoApprove($requestData)) {
            return $this->autoApproveRefund($requestData);
        }

        // Create manual review request
        return $this->createManualReview($requestData);
    }

    private function canAutoApprove($requestData)
    {
        // Auto-approve rules:
        // 1. Amount less than ₺500
        // 2. Within 24 hours of booking
        // 3. No previous refunds

        $booking = $this->getBookingDetails($requestData['booking_id']);

        return (
            $requestData['amount'] <= 500 &&
            (time() - strtotime($booking['created_at'])) < 86400 &&
            $this->getPreviousRefundCount($requestData['user_id']) === 0
        );
    }

    private function autoApproveRefund($requestData)
    {
        try {
            $this->conn->begin_transaction();

            // Process refund
            $refund = $this->refundProcessor->processRefund(
                $requestData['booking_id'],
                $requestData['amount'],
                'Auto-approved refund'
            );

            // Update booking status
            $this->updateBookingStatus($requestData['booking_id'], 'refunded');

            // Send notification
            $this->sendRefundNotification($requestData);

            $this->conn->commit();
            return ['success' => true, 'refund_id' => $refund['refund_id']];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}

