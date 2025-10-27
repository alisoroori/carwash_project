﻿<?php
require_once '../../includes/db.php';

class PaymentWebhookHandler
{
    private $conn;
    private $webhookSecret;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->webhookSecret = getenv('PAYMENT_WEBHOOK_SECRET');
    }

    public function handleWebhook()
    {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

        if (!$this->verifySignature($payload, $signature)) {
            http_response_code(401);
            return ['error' => 'Invalid signature'];
        }

        $data = json_decode($payload, true);
        return $this->processPaymentEvent($data);
    }

    private function processPaymentEvent($data)
    {
        $stmt = $this->conn->prepare("
            UPDATE transactions 
            SET status = ?,
                updated_at = NOW(),
                response_data = JSON_SET(response_data, '$.webhook_data', ?)
            WHERE payment_id = ?
        ");

        $webhookData = json_encode($data);
        $stmt->bind_param(
            'sss',
            $data['status'],
            $webhookData,
            $data['payment_id']
        );

        return $stmt->execute();
    }
}
