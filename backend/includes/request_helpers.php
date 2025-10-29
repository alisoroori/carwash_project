<?php
/**
 * Request helpers: merge JSON body into $_POST and provide structured error responses
 */

// Parse JSON request body and merge into $_POST without overwriting existing keys
function merge_json_request_body_into_post(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'POST' && $method !== 'PUT' && $method !== 'PATCH') return;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) return;

    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') return;
    $data = json_decode($raw, true);
    if (!is_array($data)) return;
    foreach ($data as $k => $v) {
        if (!isset($_POST[$k])) $_POST[$k] = $v;
    }
}

// Structured error response helper
function send_structured_error_response(Throwable $e, int $status = 500): void
{
    // Try to log
    if (class_exists('\App\Classes\Logger') && method_exists('\App\Classes\Logger', 'exception')) {
        \App\Classes\Logger::exception($e, ['source' => __FILE__]);
    } else {
        error_log('Unhandled exception: ' . $e->getMessage());
    }

    $env = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production')));
    $payload = [
        'success' => false,
        'error_type' => get_class($e),
        'message' => $e->getMessage(),
    ];
    if (in_array($env, ['dev', 'development'], true)) {
        $payload['trace'] = $e->getTraceAsString();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Register exception handler to ensure structured JSON is returned on uncaught exceptions
set_exception_handler(function ($e) {
    if ($e instanceof Throwable) {
        send_structured_error_response($e, 500);
    } else {
        // convert legacy
        $ex = new ErrorException((string)$e);
        send_structured_error_response($ex, 500);
    }
});

// Lightweight unknown-action logger for endpoints
function log_unknown_action(string $action, string $context = ''): void
{
    $msg = 'Unknown action: ' . $action . ($context ? ' in ' . $context : '');
    if (class_exists('\App\Classes\Logger') && method_exists('\App\Classes\Logger', 'warn')) {
        \App\Classes\Logger::warn($msg);
    } else {
        error_log($msg);
    }
}

// Perform merging immediately when included
try {
    merge_json_request_body_into_post();
} catch (Throwable $e) {
    // Non-fatal: don't stop execution; exception handler will handle it if uncaught
}
