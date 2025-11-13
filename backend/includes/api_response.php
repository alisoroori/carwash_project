<?php
// Simple API response helpers for consistent JSON envelopes
if (!function_exists('api_send_header')) {
    function api_send_header()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
    }
}

if (!function_exists('api_success')) {
    /**
     * Send a success JSON response and exit
     * @param string $message
     * @param array $data
     * @param int $code
     */
    function api_success(string $message = 'OK', array $data = [], int $code = 200)
    {
        api_send_header();
        http_response_code($code);
        $payload = [
            'status' => 'success',
            'message' => $message,
            'data' => (object)$data,
            'timestamp' => time()
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('api_error')) {
    /**
     * Send an error JSON response and exit
     * @param string $message
     * @param int $code
     * @param string|null $errorCode
     */
    function api_error(string $message = 'Error', int $code = 400, $errorCode = null)
    {
        api_send_header();
        http_response_code($code);
        $payload = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => time()
        ];
        if ($errorCode !== null) {
            $payload['error_code'] = $errorCode;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
