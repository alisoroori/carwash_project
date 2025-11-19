<?php
// Simple cache clear utility for development environments.
// Usage (PowerShell): php .\tools\clear_server_cache.php
// Or open in browser: http://localhost/carwash_project/tools/clear_server_cache.php

header('Content-Type: text/plain; charset=utf-8');
echo "clear_server_cache.php\n";

$results = [];

if (function_exists('opcache_reset')) {
    $ok = opcache_reset();
    $results[] = 'opcache_reset: ' . ($ok ? 'OK' : 'FAILED');
} else {
    $results[] = 'opcache_reset: not available';
}

if (function_exists('apcu_clear_cache')) {
    $ok = apcu_clear_cache();
    $results[] = 'apcu_clear_cache: ' . ($ok ? 'OK' : 'FAILED');
} else {
    $results[] = 'apcu_clear_cache: not available';
}

// Optionally clear session files (PHP default file session handler)
// Only attempt when session save path exists and is writable
$sessPath = ini_get('session.save_path');
if ($sessPath) {
    $results[] = 'session.save_path: ' . $sessPath;
    // Do not delete session files automatically; list them instead
    if (is_dir($sessPath)) {
        $files = glob(rtrim($sessPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_*');
        $results[] = 'session files found: ' . count($files);
    }
} else {
    $results[] = 'session.save_path: not set';
}

foreach ($results as $r) {
    echo $r . "\n";
}

exit(0);
