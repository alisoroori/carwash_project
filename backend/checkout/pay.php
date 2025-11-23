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
    // Try reservations table first (older flow)
    try {
        $reservation = $db->fetchOne('SELECT * FROM reservations WHERE id = :id', ['id' => $reservation_id]);
    } catch (Exception $e) {
        // If the legacy `reservations` table does not exist or query fails,
        // log and continue to fall back to the canonical `bookings` table.
        if (class_exists('App\\Classes\\Logger')) {
            \App\Classes\Logger::error('pay.php: reservations lookup failed, falling back to bookings', ['exception' => $e->getMessage()]);
        } else {
            error_log('pay.php: reservations lookup failed: ' . $e->getMessage());
        }
        $reservation = null;
    }
    if (!$reservation) {
        // Fall back to bookings table (new canonical bookings)
        $b = $db->fetchOne('SELECT * FROM bookings WHERE id = :id', ['id' => $reservation_id]);
        if ($b) {
            // Normalize to expected reservation keys used in payment processing
            $reservation = [
                'id' => $b['id'],
                'user_id' => $b['user_id'],
                'price' => $b['total_price'] ?? 0,
                'status' => $b['status'] ?? 'pending'
            ];
        } else {
            echo 'Reservation not found'; exit;
        }
    }
    if (($reservation['user_id'] ?? null) != ($_SESSION['user_id'] ?? null)) { echo 'Permission denied'; exit; }
}

// Mark reservation as processing (session or DB)
if (strpos($reservation_id, 's_') === 0) {
    $_SESSION['reservations'][$reservation_id]['status'] = 'processing';
} else {
    // Try to update reservations table first
    try {
        $db->update('reservations', ['status' => 'in_progress'], ['id' => $reservation_id]);
    } catch (Exception $e) {
        // If reservations table doesn't exist, try bookings table
        if (class_exists('App\\Classes\\Logger')) {
            \App\Classes\Logger::error('pay.php: reservations update failed, trying bookings', ['exception' => $e->getMessage()]);
        } else {
            error_log('pay.php: reservations update failed: ' . $e->getMessage());
        }
        try {
            $db->update('bookings', ['status' => 'in_progress'], ['id' => $reservation_id]);
        } catch (Exception $e2) {
            // Log but don't fail - payment can still proceed
            if (class_exists('App\\Classes\\Logger')) {
                \App\Classes\Logger::error('pay.php: bookings update also failed', ['exception' => $e2->getMessage()]);
            } else {
                error_log('pay.php: bookings update failed: ' . $e2->getMessage());
            }
        }
    }
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
