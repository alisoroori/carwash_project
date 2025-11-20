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

$carwashId = isset($_POST['carwash_id']) ? (int)$_POST['carwash_id'] : (isset($_SESSION['carwash_id']) ? (int)$_SESSION['carwash_id'] : null);
$serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : null;
$vehicleId = isset($_POST['vehicle_id']) && $_POST['vehicle_id'] !== '' ? (int)$_POST['vehicle_id'] : null;
$date = trim((string)($_POST['date'] ?? '')) ?: null;
$time = trim((string)($_POST['time'] ?? '')) ?: null;
$notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : null;
$customerName = isset($_POST['customer_name']) ? trim((string)$_POST['customer_name']) : (isset($_POST['name']) ? trim((string)$_POST['name']) : '');
$customerPhone = isset($_POST['customer_phone']) ? trim((string)$_POST['customer_phone']) : (isset($_POST['phone']) ? trim((string)$_POST['phone']) : '');

// Collect field-specific validation errors
$fieldErrors = [];

if (!$carwashId) {
    $fieldErrors['carwash_id'] = 'Carwash context missing';
}
if (!$serviceId) {
    $fieldErrors['service_id'] = 'Please select a service';
}
if (!$date) {
    $fieldErrors['date'] = 'Please select a date';
}
if (!$time) {
    $fieldErrors['time'] = 'Please select a time';
}

// Validate date format (expect YYYY-MM-DD) and not in the past
if ($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    $dateErrors = DateTime::getLastErrors();
    if (!$d || $dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0) {
        $fieldErrors['date'] = 'Invalid date format';
    } else {
        $today = (new DateTime('today'))->setTime(0,0,0);
        $d->setTime(0,0,0);
        if ($d < $today) {
            $fieldErrors['date'] = 'Date cannot be in the past';
        }
    }
}

// Validate time (HH:MM)
if ($time) {
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
        $fieldErrors['time'] = 'Invalid time format (HH:MM)';
    }
}

// Validate customer name and phone (optional but sanitize/length-check)
if ($customerName !== '' && strlen($customerName) < 2) {
    $fieldErrors['customer_name'] = 'Name is too short';
}
if ($customerPhone !== '' && strlen($customerPhone) < 5) {
    $fieldErrors['customer_phone'] = 'Phone number is too short';
}

// If we already have field errors, return them
if (!empty($fieldErrors)) {
    Response::validationError($fieldErrors);
}

try {
    // Use Database class if available
    if (class_exists('\App\Classes\Database')) {
        $db = Database::getInstance();
        // Fetch service and price (ensure service belongs to this carwash)
        $service = $db->fetchOne('SELECT id, price FROM services WHERE id = :id AND carwash_id = :cw LIMIT 1', ['id' => $serviceId, 'cw' => $carwashId]);
        if (empty($service)) {
            Response::validationError(['service_id' => 'Selected service not found for this carwash']);
        }
        $price = $service['price'] ?? 0.00;

        // If vehicle provided, verify ownership
        if ($vehicleId) {
            $vehicle = $db->fetchOne('SELECT id FROM vehicles WHERE id = :id AND user_id = :uid LIMIT 1', ['id' => $vehicleId, 'uid' => $userId]);
            if (empty($vehicle)) {
                Response::validationError(['vehicle_id' => 'Selected vehicle not found or not owned by you']);
            }
        }

        // Prepare insertion data (include optional fields)
        $insertData = [
            'user_id' => $userId,
            'carwash_id' => $carwashId,
            'service_id' => $serviceId,
            'vehicle_id' => $vehicleId ?? null,
            'customer_name' => $customerName !== '' ? $customerName : null,
            'customer_phone' => $customerPhone !== '' ? $customerPhone : null,
            'notes' => $notes !== '' ? $notes : null,
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

        // Fetch price and ensure service exists
        $stmt = $pdo->prepare('SELECT id, price FROM services WHERE id = :id AND carwash_id = :cw LIMIT 1');
        $stmt->execute(['id' => $serviceId, 'cw' => $carwashId]);
        $svc = $stmt->fetch();
        if (empty($svc)) {
            Response::validationError(['service_id' => 'Selected service not found for this carwash']);
        }
        $price = $svc['price'] ?? 0.00;

        // Vehicle ownership check
        if ($vehicleId) {
            $vstmt = $pdo->prepare('SELECT id FROM vehicles WHERE id = :id AND user_id = :uid LIMIT 1');
            $vstmt->execute(['id' => $vehicleId, 'uid' => $userId]);
            $vrow = $vstmt->fetch();
            if (empty($vrow)) {
                Response::validationError(['vehicle_id' => 'Selected vehicle not found or not owned by you']);
            }
        }

        // Insert including optional fields
        $ins = $pdo->prepare('INSERT INTO bookings (user_id, carwash_id, service_id, vehicle_id, customer_name, customer_phone, notes, booking_date, booking_time, status, total_price) VALUES (:user_id, :carwash_id, :service_id, :vehicle_id, :customer_name, :customer_phone, :notes, :date, :time, :status, :total_price)');
        $ins->execute([
            'user_id' => $userId,
            'carwash_id' => $carwashId,
            'service_id' => $serviceId,
            'vehicle_id' => $vehicleId,
            'customer_name' => $customerName !== '' ? $customerName : null,
            'customer_phone' => $customerPhone !== '' ? $customerPhone : null,
            'notes' => $notes !== '' ? $notes : null,
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
