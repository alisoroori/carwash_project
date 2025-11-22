<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;

// Customer must be authenticated
Auth::requireRole(['customer']);

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

// Diagnostic logging: record incoming reservation request (avoid logging sensitive tokens)
// Log incoming request keys for diagnostics (avoid printing CSRF token)
error_log('reservations/create.php: Request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' - POST keys: ' . json_encode(array_keys($_POST)));
// Also write a lightweight debug file so local tailing can read payloads reliably
$debugLogFile = __DIR__ . '/../../logs/reservations_debug.log';
$safePostKeys = array_keys($_POST);
@file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - Request from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " - POST keys: " . json_encode($safePostKeys) . "\n", FILE_APPEND | LOCK_EX);

// Log full POST body excluding sensitive fields (csrf_token)
$postForLog = $_POST;
if (isset($postForLog['csrf_token'])) unset($postForLog['csrf_token']);
error_log('reservations/create.php: POST body (sanitized): ' . json_encode($postForLog));
// Also append sanitized POST to debug file (exclude CSRF)
@file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - POST body (sanitized): " . json_encode($postForLog, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

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

// Required fields - accept both 'service' (legacy) and 'service_id' (new)
$service_id = trim($_POST['service_id'] ?? '');
$service = trim($_POST['service'] ?? '');
// If service_id is provided, use it; otherwise fallback to service field
if (empty($service_id) && !empty($service)) {
    $service_id = $service;
}
$vehicle = trim($_POST['vehicle'] ?? '');
$date = trim($_POST['reservationDate'] ?? '');
$time = trim($_POST['reservationTime'] ?? '');
$location = trim($_POST['location'] ?? '');
$location_id = trim($_POST['location_id'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (!$service_id || !$vehicle || !$date || !$time || !$location) {
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

// If service_id is numeric, try to fetch actual price from services table
if (is_numeric($service_id)) {
    try {
        $serviceData = $db->fetchOne(
            "SELECT price FROM services WHERE id = :service_id LIMIT 1",
            ['service_id' => (int)$service_id]
        );
        if ($serviceData && isset($serviceData['price'])) {
            $price = (float)$serviceData['price'];
        }
    } catch (\Throwable $e) {
        // Use default price if query fails
        error_log('Failed to fetch service price: ' . $e->getMessage());
    }
}

$reservation = [
    'user_id' => $user_id,
    'service_id' => $service_id,
    'service' => $service_id, // Keep for backward compatibility
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
        'service_id' => is_numeric($service_id) ? (int)$service_id : null,
        'booking_date' => $date,
        'booking_time' => $time,
        'status' => 'pending',
        'total_price' => $price,
        'notes' => $notes,
        'created_at' => date('Y-m-d H:i:s')
    ];
    // If Database class supports insert into bookings, use it
    error_log('reservations/create.php: Attempting DB insert into bookings - data keys: ' . json_encode(array_keys($insert)));
    // Log specific fields we care about for debugging
    error_log('reservations/create.php: carwash_id value: ' . var_export($insert['carwash_id'], true));
    error_log('reservations/create.php: service_id value: ' . var_export($insert['service_id'], true));
    error_log('reservations/create.php: insert payload: ' . json_encode($insert));
    $reservation_id = $db->insert('bookings', $insert);
    error_log('reservations/create.php: DB insert returned id: ' . var_export($reservation_id, true));
    // Also write DB insert result to the lightweight debug file for reliable local tailing
    @file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - DB insert returned id: " . var_export($reservation_id, true) . "\n", FILE_APPEND | LOCK_EX);
} catch (\Throwable $e) {
    // Log exception for diagnostics and fallback to session storage
    error_log('reservations/create.php INSERT ERROR: ' . $e->getMessage());
    error_log('reservations/create.php INSERT TRACE: ' . $e->getTraceAsString());
    error_log('reservations/create.php INSERT DATA: ' . json_encode($insert));
    // Also append exception details to the lightweight debug file
    @file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - INSERT ERROR: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    @file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - INSERT TRACE: " . $e->getTraceAsString() . "\n", FILE_APPEND | LOCK_EX);
    @file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - INSERT DATA: " . json_encode($insert) . "\n", FILE_APPEND | LOCK_EX);
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
