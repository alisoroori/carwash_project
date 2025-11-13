<?php

declare(strict_types=1);

require_once '../includes/api_bootstrap.php';


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
use App\Classes\Response;

// We'll use the centralized Response class for structured JSON responses

// Ensure user is logged in
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    Response::unauthorized();
}

// Accept POST form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method Not Allowed', 405);
}

// CSRF validation via helper
        if (file_exists(__DIR__ . '/../../includes/csrf_protect.php')) {
            require_once __DIR__ . '/../../includes/csrf_protect.php';
            // ensure token exists for session-based flows
            generate_csrf_token();
            // will emit 403 and exit on failure
            require_valid_csrf();
        } else {
            // Fallback: inline check (legacy)
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (empty($csrfToken) && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    $sessionCsrf = $_SESSION['csrf_token'] ?? null;
    if (empty($csrfToken) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrfToken)) {
        Response::error('Invalid CSRF token', 403);
    }
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
    Response::validationError($response['errors']);
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

        Response::success('Booking created', ['booking_id' => $bookingId]);
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

    Response::success('Booking created', ['booking_id' => (int)$pdo->lastInsertId()]);
    }
} catch (Throwable $e) {
    error_log('bookings/create.php error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}
