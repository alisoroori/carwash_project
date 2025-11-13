<?php
declare(strict_types=1);

/**
 * Simple CSRF helper
 * - generateCsrfToken(): generate and store 32-byte token in session
 * - getCsrfTokenField(): return HTML hidden input with token
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Throwable $e) {
                // Fallback
                $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
            }
        }
        return (string)$_SESSION['csrf_token'];
    }
}

if (!function_exists('getCsrfTokenField')) {
    function getCsrfTokenField(): string
    {
        $token = generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" />';
    }
}
