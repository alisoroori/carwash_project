<?php
// Simple test helper to create a carwash session for E2E tests
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Session;

Session::start();

$carwash_id = isset($_POST['carwash_id']) ? (int)$_POST['carwash_id'] : (isset($_GET['carwash_id']) ? (int)$_GET['carwash_id'] : null);
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : null);

if (empty($carwash_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'carwash_id required']);
    exit;
}

// Set test session values
$_SESSION['user_id'] = $user_id ?? 0;
$_SESSION['role'] = 'carwash';
$_SESSION['carwash_id'] = $carwash_id;

// Ensure csrf token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'session set', 'carwash_id' => $carwash_id, 'session_id' => session_id(), 'csrf_token' => $_SESSION['csrf_token']]);
