<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Diagnostic: log that this endpoint was reached
error_log('carwash/services/get_by_carwash.php: entry (pre-bootstrap) from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Make errors less silent during debugging; production bootstrap may override these
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
@error_reporting(E_ALL);

// Include project bootstrap. Wrap in try/catch to capture exceptions thrown during initialization
try {
    // Resolve bootstrap path relative to this file. Services live under backend/carwash/services,
    // so backend/includes/bootstrap.php should be at ../../includes/bootstrap.php
    $bootstrapPath = __DIR__ . '/../../includes/bootstrap.php';
    if (!file_exists($bootstrapPath)) {
        error_log('carwash/services/get_by_carwash.php: bootstrap not found at expected path: ' . $bootstrapPath);
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'Server misconfiguration: bootstrap missing']);
        exit;
    }

    require_once $bootstrapPath;
    error_log('carwash/services/get_by_carwash.php: bootstrap included successfully from ' . $bootstrapPath);
} catch (Throwable $e) {
    // Log and return a JSON error for easier browser-side diagnosis while debugging
    error_log('carwash/services/get_by_carwash.php: bootstrap include ERROR: ' . $e->getMessage());
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['success' => false, 'message' => 'Bootstrap initialization failed', 'error' => $e->getMessage()]);
    exit;
}

use App\Classes\Database;
use App\Classes\Response;

try {
    $carwashId = isset($_GET['carwash_id']) && is_numeric($_GET['carwash_id']) ? (int)$_GET['carwash_id'] : ($_SESSION['carwash_id'] ?? null);
    error_log('carwash/services/get_by_carwash.php request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' - carwash_id=' . var_export($carwashId, true));

    if (empty($carwashId)) {
        Response::error('Missing carwash_id', 400);
        exit;
    }

    try {
        $db = Database::getInstance();
        $pdo = $db->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Throwable $e) {
        error_log('carwash/services/get_by_carwash.php: Database init failed: ' . $e->getMessage());
        if (class_exists('App\\Classes\\Logger')) { \App\Classes\Logger::exception($e); }
        if (class_exists('App\\Classes\\Response')) {
            App\Classes\Response::error('Database unavailable', 500);
        } else {
            header('Content-Type: application/json; charset=utf-8', true, 500);
            echo json_encode(['success' => false, 'message' => 'Database unavailable']);
        }
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, name, description, price, duration FROM services WHERE carwash_id = :cw AND status = "active" ORDER BY name ASC');
    $stmt->execute(['cw' => $carwashId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log('carwash/services/get_by_carwash.php: found ' . count($rows) . ' rows for carwash_id=' . $carwashId);

    $sanitized = [];
    foreach ($rows as $row) {
        $sanitized[] = [
            'id' => (int)($row['id'] ?? 0),
            'name' => htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            'price' => isset($row['price']) ? number_format((float)$row['price'], 2, '.', '') : '0.00',
            'duration' => isset($row['duration']) ? (int)$row['duration'] : 0
        ];
    }

    // Return standardized response with services under `data` key so frontend can parse reliably
    Response::success('OK', ['data' => $sanitized]);
    exit;

} catch (Throwable $e) {
    error_log('carwash/services/get_by_carwash.php ERROR: ' . $e->getMessage());
    if (class_exists('App\\Classes\\Logger')) { App\Classes\Logger::exception($e); }
    Response::error('Failed to fetch services', 500);
}
