<?php
// Simulate a Customer Dashboard POST by setting session and POST, then including the create endpoint.
require_once __DIR__ . '/../backend/includes/bootstrap.php';

// Start session and mark user as authenticated customer
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = $_ENV['E2E_USER_ID'] ?? ($_SESSION['user_id'] ?? 14);
$_SESSION['role'] = 'customer';
// ensure csrf token exists
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

// Build POST payload from env vars or defaults (match bookings/create.php expected keys)
$_POST = [];
$_POST['service_id'] = $_ENV['E2E_SERVICE_ID'] ?? '19';
$_POST['vehicle_id'] = $_ENV['E2E_VEHICLE_ID'] ?? '';
$_POST['date'] = $_ENV['E2E_DATE'] ?? date('Y-m-d', strtotime('+7 days'));
$_POST['time'] = $_ENV['E2E_TIME'] ?? '10:30';
$_POST['carwash_id'] = $_ENV['E2E_LOCATION_ID'] ?? '7';
$_POST['notes'] = $_ENV['E2E_NOTES'] ?? 'Automated E2E test';
$_POST['csrf_token'] = $_SESSION['csrf_token'];

// Provide minimal server values
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

// Capture output of create.php
$ob = fopen('php://output', 'w');
// Include the bookings create script which performs validation and insertion without requireRole
ob_start();
include __DIR__ . '/../backend/api/bookings/create.php';
$out = ob_get_clean();
echo $out;
$out = ob_get_clean();
echo $out;
