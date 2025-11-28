<?php
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;

Auth::requireAuth();

error_log('REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'not set'));
error_log('HTTP_ACCEPT: ' . ($_SERVER['HTTP_ACCEPT'] ?? 'not set'));
error_log('REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'not set'));

Response::success('Test API called', [
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
    'http_accept' => $_SERVER['HTTP_ACCEPT'] ?? 'not set',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'not set'
]);
?>