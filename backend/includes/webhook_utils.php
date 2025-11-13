<?php
declare(strict_types=1);

/**
 * Webhook utilities
 */
if (!function_exists('verify_webhook_signature')) {
    /**
     * Verify a webhook signature using HMAC-SHA256
     * @param string $payload Raw request body
     * @param string $signature Signature provided by provider (hex string)
     * @param string $secret Shared secret
     * @return bool
     */
    function verify_webhook_signature(string $payload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') return false;
        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }
}
