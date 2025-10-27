<?php
require_once '../../includes/db.php';
require_once '../../includes/payment_gateway.php';

class PaymentWebhookHandler
{
    private $conn;
    private $secretKey;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->secretKey = getenv('IYZICO_SECRET_KEY');
    }

    public function handleWebhook()
    {
        // Verify webhook signature
        if (!$this->verifySignature($_SERVER['HTTP_X_IYZICO_SIGNATURE'])) {
            http_response_code(401);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true);

        // Update transaction status
        $stmt = $this->conn->prepare("
            UPDATE transactions 
            SET status = ?, 
                response_data = JSON_SET(response_data, '$.webhook_data', ?)
            WHERE payment_id = ?
        ");

        $webhookData = json_encode($payload);
        $stmt->bind_param(
            'sss',
            $this->mapPaymentStatus($payload['status']),
            $webhookData,
            $payload['paymentId']
        );

        if ($stmt->execute()) {
            $this->processStatusChange($payload);
        }
    }

    private function processStatusChange($payload)
    {
        switch ($payload['status']) {
            case 'SUCCESS':
                $this->sendConfirmationEmail($payload['paymentId']);
                break;
            case 'FAILURE':
                $this->notifyPaymentFailure($payload['paymentId']);
                break;
        }
    }
}
