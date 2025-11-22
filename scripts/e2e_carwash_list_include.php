<?php
// Simulate Carwash Dashboard listing by setting session carwash_id and including the list endpoint.
require_once __DIR__ . '/../backend/includes/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) session_start();
// Carwash context
$_SESSION['carwash_id'] = $_ENV['E2E_LOCATION_ID'] ?? ($_SESSION['carwash_id'] ?? 7);
// Mark as authenticated (role may be carwash but listing only needs carwash_id)
$_SESSION['user_id'] = $_ENV['E2E_CW_USER_ID'] ?? ($_SESSION['user_id'] ?? 1);
$_SESSION['role'] = 'carwash';

// Ensure response builder available
ob_start();
include __DIR__ . '/../backend/carwash/reservations/list.php';
$out = ob_get_clean();
echo $out;
