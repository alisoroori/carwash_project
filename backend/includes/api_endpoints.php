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
        'verify' => API_BASE_PATH . '/payment/verify.php',
        'refund' => API_BASE_PATH . '/payment/refund.php'
    ],
    
    // Service endpoints
    'service' => [
        'list' => API_BASE_PATH . '/service/list.php',
        'create' => API_BASE_PATH . '/service/create.php',
        'update' => API_BASE_PATH . '/service/update.php',
        'delete' => API_BASE_PATH . '/service/delete.php'
    ],
    
    // Review endpoints
    'review' => [
        'submit' => API_BASE_PATH . '/review/submit.php',
        'list' => API_BASE_PATH . '/review/list.php',
        'moderate' => API_BASE_PATH . '/review/moderate.php'
    ]
];
