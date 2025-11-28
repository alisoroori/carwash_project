<?php
/**
 * Web-Based Cron Trigger for Auto-Completing Bookings
 * 
 * This file provides a fallback mechanism when traditional cron jobs aren't available.
 * It can be called via HTTP requests or triggered internally by the application.
 * 
 * Security: Requires authentication or a secret token
 */

// Allow web-based cron execution
define('ALLOW_WEB_CRON', true);

// Check for authentication
session_start();

// Security check: Either authenticated admin/staff or valid cron token
$validCronToken = 'carwash_cron_secret_2025'; // Change this in production
$isAuthenticated = !empty($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'staff']);
$hasValidToken = isset($_GET['token']) && $_GET['token'] === $validCronToken;

if (!$isAuthenticated && !$hasValidToken) {
    http_response_code(403);
    die('Unauthorized access');
}

// Set execution time limit
set_time_limit(300); // 5 minutes max

// Log execution
$logFile = __DIR__ . '/../../logs/cron_execution.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

$executionLog = date('Y-m-d H:i:s') . " - Web cron triggered by " . 
                ($isAuthenticated ? "user #{$_SESSION['user_id']}" : "token") . "\n";
file_put_contents($logFile, $executionLog, FILE_APPEND);

// Include the actual cron script
require_once __DIR__ . '/auto_complete_bookings.php';
