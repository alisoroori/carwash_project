<?php
declare(strict_types=1);
// Create booking endpoint
header('Content-Type: application/json; charset=utf-8');

// Start session to get authenticated user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Request helpers (merge JSON body into $_POST + structured errors)
if (file_exists(__DIR__ . '/../../includes/request_helpers.php')) {
    require_once __DIR__ . '/../../includes/request_helpers.php';
}

// Require autoload if available
if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
}

use App\Classes\Database;

$response = ['success' => false, 'errors' => []];

// Simple helper to send JSON and exit
function sendJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Ensure user is logged in
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    $response['errors'][] = 'Unauthorized: please log in';
    sendJson($response);
}

// Accept POST form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['errors'][] = 'Method Not Allowed';
    sendJson($response);
}

// CSRF validation: accept token in POST body (csrf_token) or X-CSRF-Token header
$csrfToken = $_POST['csrf_token'] ?? null;
if (empty($csrfToken) && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
}
$sessionCsrf = $_SESSION['csrf_token'] ?? null;
if (empty($csrfToken) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrfToken)) {
    http_response_code(403);
    $response['errors'][] = 'Invalid CSRF token';
    sendJson($response);
}

$carwashId = isset($_POST['carwash_id']) ? (int)$_POST['carwash_id'] : null;
$serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : null;
$date = $_POST['date'] ?? null;
$time = $_POST['time'] ?? null;
$notes = $_POST['notes'] ?? null;

// Basic validation
if (!$carwashId) $response['errors'][] = 'carwash_id is required';
if (!$serviceId) $response['errors'][] = 'service_id is required';
if (!$date) $response['errors'][] = 'date is required';
if (!$time) $response['errors'][] = 'time is required';

if (!empty($response['errors'])) {
    http_response_code(400);
    sendJson($response);
}

try {
    // Use Database class if available
    if (class_exists('\App\Classes\Database')) {
        $db = Database::getInstance();
        // Fetch service price
        $service = $db->fetchOne('SELECT id, price FROM services WHERE id = :id AND carwash_id = :cw LIMIT 1', ['id' => $serviceId, 'cw' => $carwashId]);
        $price = $service['price'] ?? null;

        // Insert booking
        $insertData = [
            'user_id' => $userId,
            'carwash_id' => $carwashId,
            'service_id' => $serviceId,
            'booking_date' => $date,
            'booking_time' => $time,
            'status' => 'pending',
            'total_price' => $price ?? 0.00
        ];

        $bookingId = $db->insert('bookings', $insertData);
        if ($bookingId === false) {
            throw new Exception('Failed to create booking');
        }

        $response['success'] = true;
        $response['booking_id'] = $bookingId;
        // Optionally store notes in a separate table or booking_notes column — not implemented here
        sendJson($response);
    } else {
        // PDO fallback
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'carwash_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

        // Fetch price
        $stmt = $pdo->prepare('SELECT price FROM services WHERE id = :id AND carwash_id = :cw LIMIT 1');
        $stmt->execute(['id' => $serviceId, 'cw' => $carwashId]);
        $svc = $stmt->fetch();
        $price = $svc['price'] ?? 0.00;

        // Insert
        $ins = $pdo->prepare('INSERT INTO bookings (user_id, carwash_id, service_id, booking_date, booking_time, status, total_price) VALUES (:user_id, :carwash_id, :service_id, :date, :time, :status, :total_price)');
        $ins->execute([
            'user_id' => $userId,
            'carwash_id' => $carwashId,
            'service_id' => $serviceId,
            'date' => $date,
            'time' => $time,
            'status' => 'pending',
            'total_price' => $price
        ]);

        $response['success'] = true;
        $response['booking_id'] = (int)$pdo->lastInsertId();
        sendJson($response);
    }
} catch (Throwable $e) {
    error_log('bookings/create.php error: ' . $e->getMessage());
    if (function_exists('send_structured_error_response')) {
        send_structured_error_response($e, 500);
    }
    http_response_code(500);
    $response['errors'][] = 'Internal server error';
    sendJson($response);
}
