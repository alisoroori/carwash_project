<?php
// Central API bootstrap to start output buffering and detect accidental HTML
// This file is intentionally minimal and safe to include at the top of API files.
if (!defined('API_BOOTSTRAP_V1')) {
    define('API_BOOTSTRAP_V1', true);
    ob_start();
    register_shutdown_function(function() {
        try {
            $out = (string) @ob_get_clean();
            if ($out !== '') {
                if (class_exists('App\\Classes\\Logger')) {
                    try {
                        // Use fully-qualified name to avoid namespace issues
                        \App\Classes\Logger::warn('API emitted HTML: ' . substr($out, 0, 200));
                    } catch (Throwable $e) {
                        error_log('Logger::warn failed: ' . $e->getMessage());
                    }
                } else {
                    error_log('API emitted HTML: ' . substr(strip_tags($out), 0, 200));
                }
            }
        } catch (Throwable $e) {
            error_log('API bootstrap shutdown handler error: ' . $e->getMessage());
        }
    });
}
