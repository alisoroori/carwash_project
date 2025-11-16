<?php
require_once __DIR__ . '/../includes/bootstrap.php';
use App\Classes\Auth;
use App\Classes\Database;

Auth::requireRole(['customer']);
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed'; exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    echo 'CSRF token invalid'; exit;
}

$reservation_id = $_POST['reservation_id'] ?? '';
if (!$reservation_id) { echo 'Reservation missing'; exit; }

// Verify reservation belongs to current user (or exists in session)
$reservation = null;
if (strpos($reservation_id, 's_') === 0) {
    if (!isset($_SESSION['reservations'][$reservation_id])) { echo 'Reservation not found'; exit; }
    $reservation = $_SESSION['reservations'][$reservation_id];
    $reservation['id'] = $reservation_id;
} else {
    $reservation = $db->fetchOne('SELECT * FROM reservations WHERE id = :id', ['id' => $reservation_id]);
    if (!$reservation) { echo 'Reservation not found'; exit; }
    if (($reservation['user_id'] ?? null) != ($_SESSION['user_id'] ?? null)) { echo 'Permission denied'; exit; }
}

// Mark reservation as processing (session or DB)
if (strpos($reservation_id, 's_') === 0) {
    $_SESSION['reservations'][$reservation_id]['status'] = 'processing';
} else {
    try { $db->update('reservations', ['status' => 'processing'], ['id' => $reservation_id]); } catch (\Throwable $e) {}
}

// Create a temporary payment session and redirect to simulated gateway
$paySession = 'p_' . bin2hex(random_bytes(8));
if (!isset($_SESSION['payment_sessions'])) $_SESSION['payment_sessions'] = [];
$_SESSION['payment_sessions'][$paySession] = [
    'reservation_id' => $reservation_id,
    'amount' => $reservation['price'] ?? 0,
    'created_at' => date('Y-m-d H:i:s')
];

// Redirect to a simulated bank gateway page
$gatewayUrl = '/carwash_project/backend/checkout/gateway.php?session=' . urlencode($paySession);
header('Location: ' . $gatewayUrl);
exit;
