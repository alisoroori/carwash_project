<?php
/**
 * CSRF protection helper
 * - generate_csrf_token(): ensures a token exists in session and returns it
 * - verify_csrf_token($token): returns bool
 * - require_valid_csrf(): emits 403 JSON and exits if token missing/invalid
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        try {
            // 32 bytes -> 64 hex chars
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Fallback to openssl if random_bytes unavailable
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    if (empty($token)) return false;
    if (empty($_SESSION['csrf_token'])) return false;
    // Use hash_equals to mitigate timing attacks
    return hash_equals((string)$_SESSION['csrf_token'], (string)$token);
}

function require_valid_csrf(): void
{
    // Accept token from POST body or X-CSRF-Token header
    $token = $_POST['csrf_token'] ?? null;
    if (empty($token) && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    if (!verify_csrf_token($token)) {
        // Return JSON error and 403 status
        if (!headers_sent()) http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'errors' => ['Invalid CSRF token']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
