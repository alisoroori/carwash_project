<?php
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Response;
use App\Classes\Logger;

if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Merge JSON body into POST
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        foreach ($parsed as $k => $v) if (!isset($_POST[$k])) $_POST[$k] = $v;
    }
}

// Simple auth check
if (class_exists(Auth::class) && method_exists(Auth::class, 'isAuthenticated')) {
    if (!Auth::isAuthenticated()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
} else {
    if (empty($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
}

try {
    // --- Begin: ensure DB connection available for vehicle/reservation handlers ---
    $dbConn = null;
    if (function_exists('getDBConnection')) {
        $dbConn = getDBConnection();
    } elseif (class_exists(\App\Classes\Database::class)) {
        try {
            $dbw = \App\Classes\Database::getInstance();
            if (method_exists($dbw, 'getPdo')) $dbConn = $dbw->getPdo();
            elseif ($dbw instanceof PDO) $dbConn = $dbw;
        } catch (Throwable $e) {
            $dbConn = null;
        }
    }
    // --- End: ensure DB connection ---

    $action = $_POST['action'] ?? '';

    switch ($action) {
        // --- Existing reservation handlers preserved ---
        case 'fetch_reservations':
            if (!$dbConn) throw new RuntimeException('DB helper missing');
            $pdo = $dbConn;
            $stmt = $pdo->prepare('SELECT * FROM bookings WHERE user_id = :uid ORDER BY id DESC');
            $stmt->execute([':uid' => $_SESSION['user_id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'reservations' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'cancel_reservation':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('Invalid id');
            if (!$dbConn) throw new RuntimeException('DB helper missing');
            $pdo = $dbConn;
            $stmt = $pdo->prepare('UPDATE bookings SET status = :st WHERE id = :id AND user_id = :uid');
            $ok = $stmt->execute([':st' => 'cancelled', ':id' => $id, ':uid' => $_SESSION['user_id']]);
            if (!$ok) throw new RuntimeException('Could not cancel');
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        // --- Begin: Vehicle handlers used by frontend (list/create/update/delete) ---
        case 'list_vehicles':
            if (!$dbConn) throw new RuntimeException('Database connection not available');
            $pdo = $dbConn;
            $stmt = $pdo->prepare('SELECT id, brand, model, license_plate, year, color, image_path, created_at FROM user_vehicles WHERE user_id = :uid ORDER BY created_at DESC');
            $stmt->execute([':uid' => $_SESSION['user_id']]);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (class_exists(\App\Classes\Response::class) && method_exists(\App\Classes\Response::class, 'success')) {
                \App\Classes\Response::success('Vehicles listed', ['vehicles' => $vehicles]);
            } else {
                echo json_encode(['success' => true, 'vehicles' => $vehicles], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;

        case 'create_vehicle':
            if (!$dbConn) throw new RuntimeException('Database connection not available');

            // CSRF enforcement (if token exists in session)
            $postedCsrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            $sessionCsrf = $_SESSION['csrf_token'] ?? '';
            if ($sessionCsrf !== '' || $postedCsrf !== '') {
                if (empty($postedCsrf) || !hash_equals((string)$sessionCsrf, (string)$postedCsrf)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF']);
                    exit;
                }
            }

            $brand = trim((string)($_POST['car_brand'] ?? ''));
            $model = trim((string)($_POST['car_model'] ?? ''));
            $license = trim((string)($_POST['license_plate'] ?? ''));
            $year = isset($_POST['car_year']) ? (int)$_POST['car_year'] : null;
            $color = trim((string)($_POST['car_color'] ?? ''));
            $image_path = null;

            // Handle optional uploaded file if present (FormData)
            if (!empty($_FILES['vehicle_image']['tmp_name']) && is_uploaded_file($_FILES['vehicle_image']['tmp_name'])) {
                $uploadDir = __DIR__ . '/../../uploads/vehicles';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['vehicle_image']['name'] ?? '', PATHINFO_EXTENSION);
                $safe = bin2hex(random_bytes(8)) . ($ext ? '.' . preg_replace('/[^a-z0-9]/i', '', $ext) : '');
                $target = $uploadDir . '/' . $safe;
                if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $target)) {
                    $image_path = 'uploads/vehicles/' . $safe;
                }
            }

            $pdo = $dbConn;
            $stmt = $pdo->prepare('INSERT INTO user_vehicles (user_id, brand, model, license_plate, year, color, image_path, created_at) VALUES (:uid, :brand, :model, :license, :year, :color, :img, NOW())');
            $ok = $stmt->execute([
                ':uid' => $_SESSION['user_id'],
                ':brand' => $brand,
                ':model' => $model,
                ':license' => $license,
                ':year' => $year,
                ':color' => $color,
                ':img' => $image_path
            ]);
            if (!$ok) throw new RuntimeException('Could not save vehicle');
            $vid = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'vehicle_id' => $vid], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'update_vehicle':
            if (!$dbConn) throw new RuntimeException('Database connection not available');
            $vid = (int)($_POST['vehicle_id'] ?? ($_POST['id'] ?? 0));
            if ($vid <= 0) throw new InvalidArgumentException('Invalid vehicle id');

            $postedCsrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            $sessionCsrf = $_SESSION['csrf_token'] ?? '';
            if ($sessionCsrf !== '' || $postedCsrf !== '') {
                if (empty($postedCsrf) || !hash_equals((string)$sessionCsrf, (string)$postedCsrf)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF']);
                    exit;
                }
            }

            $brand = trim((string)($_POST['car_brand'] ?? ''));
            $model = trim((string)($_POST['car_model'] ?? ''));
            $license = trim((string)($_POST['license_plate'] ?? ''));
            $year = isset($_POST['car_year']) ? (int)$_POST['car_year'] : null;
            $color = trim((string)($_POST['car_color'] ?? ''));
            $image_path = null;

            if (!empty($_FILES['vehicle_image']['tmp_name']) && is_uploaded_file($_FILES['vehicle_image']['tmp_name'])) {
                $uploadDir = __DIR__ . '/../../uploads/vehicles';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['vehicle_image']['name'] ?? '', PATHINFO_EXTENSION);
                $safe = bin2hex(random_bytes(8)) . ($ext ? '.' . preg_replace('/[^a-z0-9]/i', '', $ext) : '');
                $target = $uploadDir . '/' . $safe;
                if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $target)) {
                    $image_path = 'uploads/vehicles/' . $safe;
                }
            }

            $pdo = $dbConn;
            $sql = 'UPDATE user_vehicles SET brand = :brand, model = :model, license_plate = :license, year = :year, color = :color';
            if ($image_path !== null) $sql .= ', image_path = :img';
            $sql .= ' , updated_at = NOW() WHERE id = :id AND user_id = :uid';
            $stmt = $pdo->prepare($sql);
            $params = [':brand'=>$brand, ':model'=>$model, ':license'=>$license, ':year'=>$year, ':color'=>$color, ':id'=>$vid, ':uid'=>$_SESSION['user_id']];
            if ($image_path !== null) $params[':img'] = $image_path;
            $ok = $stmt->execute($params);
            if (!$ok) throw new RuntimeException('Update failed');
            echo json_encode(['success' => true, 'vehicle_id' => $vid], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'delete_vehicle':
            if (!$dbConn) throw new RuntimeException('Database connection not available');
            $vid = (int)($_POST['vehicle_id'] ?? ($_POST['id'] ?? 0));
            if ($vid <= 0) throw new InvalidArgumentException('Invalid vehicle id');

            $pdo = $dbConn;
            $stmt = $pdo->prepare('DELETE FROM user_vehicles WHERE id = :id AND user_id = :uid');
            $ok = $stmt->execute([':id' => $vid, ':uid' => $_SESSION['user_id']]);
            if (!$ok) throw new RuntimeException('Delete failed');
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
        // --- End: Vehicle handlers ---

        default:
            if (class_exists(\App\Classes\Logger::class) && method_exists(\App\Classes\Logger::class, 'warn')) {
                \App\Classes\Logger::warn('Unknown action in Customer_Dashboard_process: ' . $action);
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (Throwable $e) {
    if (class_exists(\App\Classes\Logger::class) && method_exists(\App\Classes\Logger::class, 'exception')) {
        \App\Classes\Logger::exception($e);
    } else {
        error_log('Customer_Dashboard_process error: ' . $e->getMessage());
    }
    $env = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production')));
    $payload = ['success' => false, 'error_type' => get_class($e), 'message' => $e->getMessage()];
    if (in_array($env, ['dev', 'development'], true)) $payload['trace'] = $e->getTraceAsString();
    http_response_code(500);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
