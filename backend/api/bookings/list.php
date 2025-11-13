<?php

declare(strict_types=1);

// API bootstrap (added by tools/add_api_bootstrap.php)
if (!defined('API_BOOTSTRAP_V1')) {
    define('API_BOOTSTRAP_V1', true);
    ob_start();
    register_shutdown_function(function() {
        try {
            $out = (string) @ob_get_clean();
            if ($out !== '') {
                if (class_exists('App\Classes\Logger')) {
                    try {
                        App\\Classes\\Logger::warn('API emitted HTML: ' . substr($out, 0, 200));
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
header('Content-Type: application/json; charset=utf-8');

// Start session if not already started (some flows rely on session auth)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Composer autoloader (optional)
$vendor = dirname(__DIR__, 3) . '/vendor/autoload.php';
if (file_exists($vendor)) {
    require_once $vendor;
}

use App\Classes\Database;
use App\Classes\Response;

try {
    $db = Database::getInstance();

    // If user is authenticated, return only their bookings; otherwise return recent bookings.
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        $rows = $db->fetchAll(
            "SELECT b.*, u.name AS user_name, s.name AS service_name
             FROM bookings b
             LEFT JOIN users u ON u.id = b.user_id
             LEFT JOIN services s ON s.id = b.service_id
             WHERE b.user_id = :uid
             ORDER BY b.id DESC",
            ['uid' => $userId]
        );
    } else {
        // Limit to latest 100 bookings to avoid heavy responses
        $rows = $db->fetchAll(
            "SELECT b.*, u.name AS user_name, s.name AS service_name
             FROM bookings b
             LEFT JOIN users u ON u.id = b.user_id
             LEFT JOIN services s ON s.id = b.service_id
             ORDER BY b.id DESC
             LIMIT 100"
        );
    }

    Response::success('OK', $rows);

} catch (Throwable $e) {
    // Log and return an error response
    if (function_exists('error_log')) {
        error_log('backend/api/bookings/list.php error: ' . $e->getMessage());
    }
    Response::error('Failed to fetch bookings', 500);
}
