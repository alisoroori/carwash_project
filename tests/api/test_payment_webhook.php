<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PaymentWebhookTest extends TestCase
{
    public function testVerifyWebhookSignature(): void
    {
        require_once __DIR__ . '/../../backend/includes/webhook_utils.php';

        $secret = 'test_secret_' . uniqid();
        $payload = json_encode(['paymentId' => 'pay_123', 'status' => 'SUCCESS']);
        $signature = hash_hmac('sha256', $payload, $secret);

        $this->assertTrue(verify_webhook_signature($payload, $signature, $secret));
        $this->assertFalse(verify_webhook_signature($payload, $signature . 'x', $secret));
    }
}
