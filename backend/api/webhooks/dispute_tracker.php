<?php
session_start();
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

class DisputeWebhookHandler
{
    private $conn;
    private $webhookSecret;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->webhookSecret = getenv('WEBHOOK_SECRET');
    }

    public function handleWebhook()
    {
        $headers = getallheaders();
        $signature = $headers['X-Webhook-Signature'] ?? '';

        if (!$this->verifySignature($signature)) {
            http_response_code(401);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $this->processDisputeUpdate($payload);
    }

    private function processDisputeUpdate($payload)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO dispute_events 
            (dispute_id, event_type, event_data, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $eventData = json_encode($payload);
        $stmt->bind_param(
            'iss',
            $payload['dispute_id'],
            $payload['event_type'],
            $eventData
        );

        return $stmt->execute();
    }
}
