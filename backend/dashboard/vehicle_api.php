<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Logger;

// Start session
if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() == PHP_SESSION_NONE) session_start();
}

// Require authenticated customer
Auth::requireRole('customer');

header('Content-Type: application/json');

$pdo = getDBConnection();
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Ensure table exists (safe noop if already present)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_vehicles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        brand VARCHAR(100) DEFAULT NULL,
        model VARCHAR(100) DEFAULT NULL,
        license_plate VARCHAR(50) DEFAULT NULL,
        year INT DEFAULT NULL,
        color VARCHAR(50) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    Logger::exception($e, ['user' => $user_id]);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT id, brand, model, license_plate, year, color, created_at FROM user_vehicles WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'vehicles' => $rows]);
        exit;
    }

    // Only allow POST for mutating actions
    if ($method === 'POST') {
        $action = $_POST['action'] ?? 'create';
        // CSRF
        $postedCsrf = $_POST['csrf_token'] ?? '';
        $sessionCsrf = $_SESSION['csrf_token'] ?? '';
        if (empty($postedCsrf) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $postedCsrf)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF']);
            exit;
        }

        if ($action === 'create') {
            $brand = trim($_POST['car_brand'] ?? '');
            $model = trim($_POST['car_model'] ?? '');
            $license = trim($_POST['license_plate'] ?? '');
            $year = $_POST['car_year'] ?? null;
            $color = trim($_POST['car_color'] ?? '');

            $stmt = $pdo->prepare('INSERT INTO user_vehicles (user_id, brand, model, license_plate, year, color, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $ok = $stmt->execute([$user_id, $brand, $model, $license, $year ?: null, $color]);
            if (!$ok) throw new Exception('Could not save vehicle');
            $id = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'vehicle_id' => $id]);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid vehicle id');
            $brand = trim($_POST['car_brand'] ?? '');
            $model = trim($_POST['car_model'] ?? '');
            $license = trim($_POST['license_plate'] ?? '');
            $year = $_POST['car_year'] ?? null;
            $color = trim($_POST['car_color'] ?? '');

            $stmt = $pdo->prepare('UPDATE user_vehicles SET brand = ?, model = ?, license_plate = ?, year = ?, color = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
            $ok = $stmt->execute([$brand, $model, $license, $year ?: null, $color, $id, $user_id]);
            if (!$ok) throw new Exception('Update failed');
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid vehicle id');
            $stmt = $pdo->prepare('DELETE FROM user_vehicles WHERE id = ? AND user_id = ?');
            $ok = $stmt->execute([$id, $user_id]);
            if (!$ok) throw new Exception('Delete failed');
            echo json_encode(['success' => true]);
            exit;
        }

        throw new Exception('Unknown action');
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    Logger::exception($e, ['user' => $user_id]);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
