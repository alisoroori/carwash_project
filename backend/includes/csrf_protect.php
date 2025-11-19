<?php
/**
 * CSRF protection helper
 * Provides session-based token generation, verification and utilities
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token(int $length = 32): string {
    if (empty($_SESSION['csrf_token'])) {
        try {
            if (function_exists('random_bytes')) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes($length));
            } else {
                $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes($length));
            }
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(substr(sha1(uniqid((string)mt_rand(), true)), 0, $length));
        }
        $_SESSION['csrf_token_time'] = time();
    }
    return (string)$_SESSION['csrf_token'];
}

function get_csrf_token(): string {
    return $_SESSION['csrf_token'] ?? generate_csrf_token();
}

function echo_csrf_input(): void {
    $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
    echo "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals((string)$_SESSION['csrf_token'], (string)$token);
}

function require_valid_csrf(): void {
    $token = $_POST['csrf_token'] ?? null;
    // Accept a variety of common header names for CSRF token to be tolerant
    $headerCandidates = [
        'HTTP_X_CSRF_TOKEN',
        'HTTP_X_CSRFTOKEN',
        'HTTP_X_XSRF_TOKEN',
        'HTTP_X_CSRF',
        'HTTP_X_XSRF'
    ];
    foreach ($headerCandidates as $h) {
        if (empty($token) && !empty($_SERVER[$h])) {
            $token = $_SERVER[$h];
            break;
        }
    }
    if (!verify_csrf_token($token)) {
        // Log blocked attempt for debugging (mask tokens)
        try {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $logFile = $logDir . '/csrf_blocked.log';
            $now = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $provided = is_string($token) ? $token : '';
            $sess = $_SESSION['csrf_token'] ?? '';
            $mask = function($s){ if (!$s) return ''; $s = (string)$s; $len = strlen($s); return substr($s,0,6) . '...' . $len; };
            $userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
            $line = "[{$now}] CSRF mismatch from {$ip} {$uri} user_id=" . ($userId ?? 'anon') . " provided=" . $mask($provided) . " session=" . $mask($sess) . PHP_EOL;
            @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // ignore logging failures
        }

        if (!headers_sent()) http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'errors' => ['Invalid CSRF token']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
