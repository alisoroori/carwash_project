<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;

// Customer must be authenticated
Auth::requireRole(['customer']);

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

// Simple JSON response helper
function jsonError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''; 
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    jsonError('CSRF token invalid', 403);
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) jsonError('User not authenticated', 401);

// Required fields
$service = trim($_POST['service'] ?? '');
$vehicle = trim($_POST['vehicle'] ?? '');
$date = trim($_POST['reservationDate'] ?? '');
$time = trim($_POST['reservationTime'] ?? '');
$location = trim($_POST['location'] ?? '');
$location_id = trim($_POST['location_id'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (!$service || !$vehicle || !$date || !$time || !$location) {
    jsonError('Missing required reservation fields', 422);
}

// Normalize time to HH:MM (24-hour) - reuse simple logic
function normalizeTimeTo24($s) {
    $s = trim($s);
    if ($s === '') return '';
    // Handle AM/PM
    if (preg_match('/^(\d{1,2}):(\d{2})\s*([AaPp][Mm])$/', $s, $m)) {
        $hh = intval($m[1]);
        $mm = $m[2];
        $ap = strtolower($m[3]);
        if ($ap === 'pm' && $hh < 12) $hh += 12;
        if ($ap === 'am' && $hh === 12) $hh = 0;
        return str_pad((string)$hh, 2, '0', STR_PAD_LEFT) . ':' . $mm;
    }
    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $s, $m)) {
        $hh = intval($m[1]);
        $mm = $m[2];
        if ($hh >= 0 && $hh <= 24) {
            return str_pad((string)$hh, 2, '0', STR_PAD_LEFT) . ':' . $mm;
        }
    }
    return $s;
}

$time = normalizeTimeTo24($time);
if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    jsonError('Invalid time format', 422);
}

// Basic price calculation (placeholder) - in real app you'd derive from service
$price = 50.00;

$reservation = [
    'user_id' => $user_id,
    'service' => $service,
    'vehicle' => $vehicle,
    'date' => $date,
    'time' => $time,
    'location' => $location,
    'location_id' => $location_id,
    'notes' => $notes,
    'status' => 'pending',
    'price' => $price,
    'created_at' => date('Y-m-d H:i:s')
];

// Try to insert into DB as a bookings row; if DB not available or insert fails, fallback to session
$reservation_id = null;
try {
    // Map to bookings table columns where possible
    $insert = [
        'user_id' => $user_id,
        'carwash_id' => is_numeric($location_id) ? (int)$location_id : null,
        'service_type' => $service,
        'booking_date' => $date,
        'booking_time' => $time,
        'status' => 'pending',
        'total_price' => $price,
        'notes' => $notes,
        'created_at' => date('Y-m-d H:i:s')
    ];
    // If Database class supports insert into bookings, use it
    $reservation_id = $db->insert('bookings', $insert);
} catch (\Throwable $e) {
    // fallback to session
}

if ($reservation_id) {
    // Redirect to invoice for bookings id
    $redirect = '/carwash_project/backend/checkout/invoice.php?id=' . urlencode($reservation_id);
    echo json_encode(['success' => true, 'reservation_id' => $reservation_id, 'redirect' => $redirect]);
    exit;
}

// Session fallback
if (!isset($_SESSION['reservations']) || !is_array($_SESSION['reservations'])) $_SESSION['reservations'] = [];
$sid = 's_' . bin2hex(random_bytes(8));
$_SESSION['reservations'][$sid] = $reservation;

$redirect = '/carwash_project/backend/checkout/invoice.php?id=' . urlencode($sid);
echo json_encode(['success' => true, 'reservation_id' => $sid, 'redirect' => $redirect]);
exit;
