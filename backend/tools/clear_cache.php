<?php
// Dev-only cache reset helper. Run only on local dev environments.
// Usage: open in browser from localhost or run via CLI: php clear_cache.php

// Restrict to localhost to avoid exposing on public servers
$allowed_hosts = ['127.0.0.1', '::1', 'localhost'];
$host = $_SERVER['REMOTE_ADDR'] ?? null;
if (php_sapi_name() !== 'cli' && $host && !in_array($host, $allowed_hosts, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$result = ['opcache_available' => function_exists('opcache_reset')];
if ($result['opcache_available']) {
    try {
        $ok = opcache_reset();
        $result['opcache_reset'] = $ok ? true : false;
    } catch (Throwable $e) {
        $result['opcache_reset'] = false;
        $result['error'] = $e->getMessage();
    }
} else {
    $result['message'] = 'OPcache not available';
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'result' => $result]);
