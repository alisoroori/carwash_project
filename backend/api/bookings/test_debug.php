<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json');

echo json_encode([
    'test' => 'working',
    'session_status' => session_status(),
    'file_exists_api_bootstrap' => file_exists(__DIR__ . '/../../includes/api_bootstrap.php'),
    'file_exists_autoload' => file_exists(__DIR__ . '/../../../vendor/autoload.php'),
    'php_version' => PHP_VERSION,
    'cwd' => getcwd(),
    'dir' => __DIR__
]);
