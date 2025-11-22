<?php
// Central API bootstrap to start output buffering and detect accidental HTML
// This file is intentionally minimal and safe to include at the top of API files.
if (!defined('API_BOOTSTRAP_V1')) {
    define('API_BOOTSTRAP_V1', true);
    // Attempt to initialize Composer autoload and Logger early so shutdown
    // handler can write to the application's log file. This is best-effort
    // and wrapped in try/catch to avoid breaking APIs that intentionally
    // include this file before the full bootstrap.
    try {
        $vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
            if (class_exists('App\\Classes\\Logger')) {
                try {
                    \App\Classes\Logger::init();
                } catch (Throwable $e) {
                    // Non-fatal: leave error_log as-is
                    error_log('api_bootstrap: Logger::init() failed: ' . $e->getMessage());
                }
            }
        }
    } catch (Throwable $e) {
        // If autoloading or logger init fails, don't break API; just continue.
        error_log('api_bootstrap init error: ' . $e->getMessage());
    }
    // Install a lightweight error handler for API contexts so warnings/notices
    // are converted to ErrorException and logged. This ensures update endpoints
    // capture warnings/notices in `logs/app.log` via Logger::exception().
    if (!defined('API_BOOTSTRAP_ERROR_HANDLER')) {
        define('API_BOOTSTRAP_ERROR_HANDLER', true);
        set_error_handler(function ($severity, $message, $file, $line) {
            // Respect @ operator
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $msg = sprintf("PHP %s: %s in %s:%d", (($severity & E_RECOVERABLE_ERROR) ? 'Recoverable Error' : 'Warning/Notice'), $message, $file, $line);
            if (class_exists('App\\Classes\\Logger')) {
                try {
                    \App\Classes\Logger::warning($msg);
                } catch (Throwable $e) {
                    error_log('api_bootstrap: Logger::warning failed: ' . $e->getMessage());
                }
            } else {
                error_log($msg);
            }

            // Convert to ErrorException so existing try/catch in endpoints can catch it
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }
    ob_start();
    register_shutdown_function(function() {
        try {
            $out = (string) @ob_get_clean();
            if ($out !== '') {
                // If the output is already valid JSON, assume the API
                // intentionally emitted JSON and do not treat it as accidental output.
                $trim = trim($out);
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Re-emit the original JSON and skip the accidental-output handling
                    if (!headers_sent()) {
                        header('Content-Type: application/json; charset=utf-8');
                    }
                    echo $out;
                    return;
                }
                // Log the accidental output for operators
                $snippet = substr($out, 0, 200);
                if (class_exists('App\\Classes\\Logger')) {
                    try {
                        \App\Classes\Logger::warning('API emitted HTML/output: ' . $snippet);
                    } catch (Throwable $e) {
                        error_log('Logger::warning failed: ' . $e->getMessage());
                    }
                } else {
                    error_log('API emitted HTML/output: ' . substr(strip_tags($out), 0, 200));
                }

                // Convert accidental HTML/text output into a structured JSON error response
                // to avoid client-side JSON.parse failures when APIs are expected to return JSON.
                // Only modify the response if headers have not already been sent.
                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');

                    $env = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production')));
                    $payload = [
                        'success' => false,
                        'message' => 'Server emitted unexpected output before JSON response',
                    ];
                    if (in_array($env, ['dev', 'development'], true)) {
                        $payload['raw'] = $out;
                    } else {
                        $payload['hint'] = 'Enable dev logging to see raw output';
                    }

                    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    // ensure no further output
                    flush();
                }
            }
        } catch (Throwable $e) {
            error_log('API bootstrap shutdown handler error: ' . $e->getMessage());
        }
    });
}
