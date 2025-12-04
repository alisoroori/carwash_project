<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/api_bootstrap.php';


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

// Manual vehicle entry fields (if vehicle_id not provided)
$vehicleType = isset($_POST['vehicle_type']) ? trim((string)$_POST['vehicle_type']) : null;
$vehiclePlate = isset($_POST['vehicle_plate']) ? trim((string)$_POST['vehicle_plate']) : null;
$vehicleModel = isset($_POST['vehicle_model']) ? trim((string)$_POST['vehicle_model']) : null;
$vehicleColor = isset($_POST['vehicle_color']) ? trim((string)$_POST['vehicle_color']) : null;

// Customer info fields (manual entry - optional, for walk-ins)
$manualCustomerName = isset($_POST['customer_name']) ? trim((string)$_POST['customer_name']) : null;
$manualCustomerPhone = isset($_POST['customer_phone']) ? trim((string)$_POST['customer_phone']) : null;

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
    // Defensive: ensure getLastErrors() returned an array before indexing
    $warningCount = (is_array($dateErrors) && isset($dateErrors['warning_count'])) ? (int)$dateErrors['warning_count'] : 0;
    $errorCount = (is_array($dateErrors) && isset($dateErrors['error_count'])) ? (int)$dateErrors['error_count'] : 0;
    if (!$d || $warningCount > 0 || $errorCount > 0) {
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

// Validate vehicle data - either vehicle_id OR manual fields required
if (!$vehicleId && !$vehicleType && !$vehiclePlate) {
    $fieldErrors['vehicle'] = 'Please select a vehicle or enter vehicle details';
}

// If we already have field errors, return them
if (!empty($fieldErrors)) {
    Response::validationError($fieldErrors);
}

try {
    // Create logs directory if it doesn't exist
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/booking_create.log';
    
    // Log the incoming request
    $logEntry = sprintf(
        "[%s] POST Data: %s\n",
        date('Y-m-d H:i:s'),
        json_encode($_POST, JSON_UNESCAPED_UNICODE)
    );
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    // Also write to PHP error log for quick access in Apache/CLI logs
    error_log('BOOKING CREATE INPUT: ' . json_encode($_POST));
    
    // Use Database class if available
    if (class_exists('\App\Classes\Database')) {
        $db = Database::getInstance();
        
        // Fetch service and price (ensure service belongs to this carwash)
        $service = $db->fetchOne('SELECT id, name, price, is_available, status FROM services WHERE id = :id AND carwash_id = :cw LIMIT 1', ['id' => $serviceId, 'cw' => $carwashId]);
        error_log('SERVICE DATA: ' . json_encode($service));
        if (!$service || !is_array($service)) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Service not found - ID: $serviceId, Carwash: $carwashId\n", FILE_APPEND);
            Response::validationError(['service_id' => 'Selected service not found for this carwash']);
        }
        // Check service available flag (if present in schema)
        if ((isset($service['is_available']) && !$service['is_available']) || (isset($service['status']) && strtolower((string)$service['status']) !== 'active')) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Service inactive - ID: $serviceId\n", FILE_APPEND);
            Response::validationError(['service_id' => 'Selected service is not available']);
        }
        $price = isset($service['price']) ? (float)$service['price'] : 0.00;

        // Initialize vehicle data variables
        $finalVehicleType = null;
        $finalVehiclePlate = null;
        $finalVehicleModel = null;
        $finalVehicleColor = null;

        // If vehicle_id provided, fetch vehicle data from vehicles table
        if ($vehicleId) {
            $vehicle = $db->fetchOne(
                'SELECT id, type, license_plate, make, model, color FROM vehicles WHERE id = :id AND user_id = :uid LIMIT 1', 
                ['id' => $vehicleId, 'uid' => $userId]
            );
            error_log('VEHICLE DATA: ' . json_encode($vehicle));
            if (!$vehicle || !is_array($vehicle)) {
                @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Vehicle not found or not owned - ID: $vehicleId, User: $userId\n", FILE_APPEND);
                Response::validationError(['vehicle_id' => 'Selected vehicle not found or not owned by you']);
            }
            // Map vehicle data from vehicles table - with safe array access
            $finalVehicleType = isset($vehicle['type']) ? $vehicle['type'] : null;
            $finalVehiclePlate = isset($vehicle['license_plate']) ? $vehicle['license_plate'] : null;
            $finalVehicleModel = trim((isset($vehicle['make']) ? $vehicle['make'] : '') . ' ' . (isset($vehicle['model']) ? $vehicle['model'] : ''));
            $finalVehicleColor = isset($vehicle['color']) ? $vehicle['color'] : null;
        } else {
            // Use manual entry fields
            $finalVehicleType = $vehicleType;
            $finalVehiclePlate = $vehiclePlate;
            $finalVehicleModel = $vehicleModel;
            $finalVehicleColor = $vehicleColor;
        }

        // Fetch and validate user (customer) exists
        $userRow = $db->fetchOne('SELECT id, name, phone, email FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
        error_log('CUSTOMER DATA: ' . json_encode($userRow));
        if (!$userRow || !is_array($userRow)) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: User not found - ID: $userId\n", FILE_APPEND);
            Response::error('Authenticated user not found', 403);
        }

        // Validate time slot availability: simple non-overlap check for same date+time
        $existing = $db->fetchOne(
            'SELECT id FROM bookings WHERE carwash_id = :cw AND booking_date = :date AND booking_time = :time AND status <> :cancel LIMIT 1',
            ['cw' => $carwashId, 'date' => $date, 'time' => $time, 'cancel' => 'cancelled']
        );
        if ($existing && is_array($existing)) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] CONFLICT: Slot already taken - Carwash: $carwashId Date: $date Time: $time\n", FILE_APPEND);
            Response::validationError(['time' => 'Selected time slot is already taken']);
        }

        // Prepare insertion data with CORRECT column names
        $insertData = [
            'user_id' => $userId,
            'carwash_id' => $carwashId,
            'service_id' => $serviceId,
            'vehicle_type' => $finalVehicleType ?: 'sedan', // Default to 'sedan' if null
            'vehicle_plate' => $finalVehiclePlate,
            'vehicle_model' => $finalVehicleModel,
            'vehicle_color' => $finalVehicleColor,
            'notes' => $notes !== '' ? $notes : null,
            'booking_date' => $date,
            'booking_time' => $time,
            'status' => 'confirmed', // Auto-approve manual bookings
            'total_price' => (float)$price
        ];

        @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Insert Data: " . json_encode($insertData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

        $bookingId = $db->insert('bookings', $insertData);
        if ($bookingId === false) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Insert failed\n", FILE_APPEND);
            throw new Exception('Failed to create booking');
        }

        @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] SUCCESS: Booking ID: $bookingId\n", FILE_APPEND);
        Response::success('Booking created successfully', ['booking_id' => $bookingId]);
    } else {
        // PDO fallback with proper error handling
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'carwash_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        
        @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Using PDO fallback. DSN: $dsn\n", FILE_APPEND);
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Fetch price and ensure service exists
        $stmt = $pdo->prepare('SELECT id, name, price, is_available, status FROM services WHERE id = :id AND carwash_id = :cw LIMIT 1');
        $stmt->execute(['id' => $serviceId, 'cw' => $carwashId]);
        $svc = $stmt->fetch();
        error_log('SERVICE DATA (PDO): ' . json_encode($svc));
        if (!$svc || !is_array($svc)) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Service not found (PDO) - ID: $serviceId, Carwash: $carwashId\n", FILE_APPEND);
            Response::validationError(['service_id' => 'Selected service not found for this carwash']);
        }
        if ((isset($svc['is_available']) && !$svc['is_available']) || (isset($svc['status']) && strtolower((string)$svc['status']) !== 'active')) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Service inactive (PDO) - ID: $serviceId\n", FILE_APPEND);
            Response::validationError(['service_id' => 'Selected service is not available']);
        }
        $price = isset($svc['price']) ? (float)$svc['price'] : 0.00;

        // Initialize vehicle data
        $finalVehicleType = null;
        $finalVehiclePlate = null;
        $finalVehicleModel = null;
        $finalVehicleColor = null;

        // Vehicle data from vehicles table or manual entry
        if ($vehicleId) {
            $vstmt = $pdo->prepare('SELECT id, type, license_plate, make, model, color FROM vehicles WHERE id = :id AND user_id = :uid LIMIT 1');
            $vstmt->execute(['id' => $vehicleId, 'uid' => $userId]);
            $vrow = $vstmt->fetch();
            error_log('VEHICLE DATA (PDO): ' . json_encode($vrow));
            if (!$vrow || !is_array($vrow)) {
                @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Vehicle not found (PDO) - ID: $vehicleId, User: $userId\n", FILE_APPEND);
                Response::validationError(['vehicle_id' => 'Selected vehicle not found or not owned by you']);
            }
            $finalVehicleType = isset($vrow['type']) ? $vrow['type'] : null;
            $finalVehiclePlate = isset($vrow['license_plate']) ? $vrow['license_plate'] : null;
            $finalVehicleModel = trim((isset($vrow['make']) ? $vrow['make'] : '') . ' ' . (isset($vrow['model']) ? $vrow['model'] : ''));
            $finalVehicleColor = isset($vrow['color']) ? $vrow['color'] : null;
        } else {
            $finalVehicleType = $vehicleType;
            $finalVehiclePlate = $vehiclePlate;
            $finalVehicleModel = $vehicleModel;
            $finalVehicleColor = $vehicleColor;
        }

        // Validate user exists (PDO fallback)
        $ustmt = $pdo->prepare('SELECT id, name, phone, email FROM users WHERE id = :id LIMIT 1');
        $ustmt->execute(['id' => $userId]);
        $urow = $ustmt->fetch();
        error_log('CUSTOMER DATA (PDO): ' . json_encode($urow));
        if (!$urow || !is_array($urow)) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: User not found (PDO) - ID: $userId\n", FILE_APPEND);
            Response::error('Authenticated user not found', 403);
        }

        // Simple slot conflict check (PDO)
        $checkStmt = $pdo->prepare('SELECT id FROM bookings WHERE carwash_id = :cw AND booking_date = :date AND booking_time = :time AND status <> :cancel LIMIT 1');
        $checkStmt->execute(['cw' => $carwashId, 'date' => $date, 'time' => $time, 'cancel' => 'cancelled']);
        $conflictRow = $checkStmt->fetch();
        if ($conflictRow && is_array($conflictRow)) {
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] CONFLICT (PDO): Slot already taken - Carwash: $carwashId Date: $date Time: $time\n", FILE_APPEND);
            Response::validationError(['time' => 'Selected time slot is already taken']);
        }

        // Insert with CORRECT column names (no vehicle_id, no customer_name, no customer_phone)
        $ins = $pdo->prepare('
            INSERT INTO bookings 
            (user_id, carwash_id, service_id, vehicle_type, vehicle_plate, vehicle_model, vehicle_color, notes, booking_date, booking_time, status, total_price) 
            VALUES 
            (:user_id, :carwash_id, :service_id, :vehicle_type, :vehicle_plate, :vehicle_model, :vehicle_color, :notes, :date, :time, :status, :total_price)
        ');
        
        $insertParams = [
            'user_id' => $userId,
            'carwash_id' => $carwashId,
            'service_id' => $serviceId,
            'vehicle_type' => $finalVehicleType ?: 'sedan', // Default to 'sedan' if null
            'vehicle_plate' => $finalVehiclePlate,
            'vehicle_model' => $finalVehicleModel,
            'vehicle_color' => $finalVehicleColor,
            'notes' => $notes !== '' ? $notes : null,
            'date' => $date,
            'time' => $time,
            'status' => 'confirmed', // Auto-approve manual bookings
            'total_price' => $price
        ];
        
        @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] PDO Insert Params: " . json_encode($insertParams, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        $ins->execute($insertParams);
        $bookingId = (int)$pdo->lastInsertId();
        
        @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] SUCCESS (PDO): Booking ID: $bookingId\n", FILE_APPEND);
        Response::success('Booking created successfully', ['booking_id' => $bookingId]);
    }
} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    
    // Log detailed error
    $logDir = __DIR__ . '/../../logs';
    $logFile = $logDir . '/booking_create.log';
    $logEntry = sprintf(
        "[%s] EXCEPTION: %s in %s:%d\nTrace: %s\n",
        date('Y-m-d H:i:s'),
        $errorMsg,
        $errorFile,
        $errorLine,
        $e->getTraceAsString()
    );
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    error_log('bookings/create.php error: ' . $errorMsg . ' in ' . $errorFile . ':' . $errorLine);
    
    // Return detailed error in development (since display_errors is on)
    Response::error('Database error: ' . $errorMsg, 500);
}
