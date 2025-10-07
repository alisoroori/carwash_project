<?php
define('API_VERSION', 'v1');
define('API_BASE_PATH', '/carwash_project/backend/api/' . API_VERSION);

$API_ENDPOINTS = [
    // Authentication endpoints
    'auth' => [
        'login' => API_BASE_PATH . '/auth/login.php',
        'register' => API_BASE_PATH . '/auth/register.php',
        'logout' => API_BASE_PATH . '/auth/logout.php',
        'reset_password' => API_BASE_PATH . '/auth/reset_password.php'
    ],

    // Booking endpoints
    'booking' => [
        'create' => API_BASE_PATH . '/booking/create.php',
        'update' => API_BASE_PATH . '/booking/update.php',
        'cancel' => API_BASE_PATH . '/booking/cancel.php',
        'list' => API_BASE_PATH . '/booking/list.php',
        'details' => API_BASE_PATH . '/booking/details.php'
    ],

    // Payment endpoints
    'payment' => [
        'process' => API_BASE_PATH . '/payment/process.php',
    ]
]; // Close the array

// Optional: Make the endpoints array immutable
if (!defined('API_ENDPOINTS')) {
    define('API_ENDPOINTS', $API_ENDPOINTS);
}

// How to use these endpoints in your code
require_once 'api_endpoints.php';

$loginUrl = $API_ENDPOINTS['auth']['login'];
// Or if using the constant:
$loginUrl = API_ENDPOINTS['auth']['login'];

// Makes a request to: /carwash_project/backend/api/v1/auth/login.php
