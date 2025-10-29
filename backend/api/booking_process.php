<?php
// Temporary replacement for booking_process - will move into place
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Session;
use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Logger;

if (class_exists(Session::class) && method_exists(Session::class, 'start')) Session::start(); else if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'POST';
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (in_array($method, ['POST','PUT','PATCH'], true) && stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) foreach ($parsed as $k => $v) if (!isset($_POST[$k])) $_POST[$k] = $v;
}

if (class_exists(Auth::class) && method_exists(Auth::class, 'isAuthenticated')) {
    if (!Auth::isAuthenticated()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
} else {
    if (empty($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Not authenticated']); exit; }
}

try {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    if ($user_id <= 0) throw new RuntimeException('Invalid session');

    $car_id = isset($_POST['car_id']) ? (int)$_POST['car_id'] : null;
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    $schedule = trim((string)($_POST['schedule'] ?? ''));

    if (!$car_id || !$service_id || $schedule === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    if (!function_exists('getDBConnection')) throw new RuntimeException('DB helper missing');
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('INSERT INTO bookings (user_id, car_id, service_id, scheduled_for, status, created_at) VALUES (:uid, :car, :svc, :sched, :st, NOW())');
    $ok = $stmt->execute([':uid' => $user_id, ':car' => $car_id, ':svc' => $service_id, ':sched' => $schedule, ':st' => 'pending']);
    if (!$ok) throw new RuntimeException('Failed to create booking');
    $id = (int)$pdo->lastInsertId();
    echo json_encode(['success' => true, 'booking_id' => $id]);
} catch (Throwable $e) {
    if (class_exists(Logger::class) && method_exists(Logger::class, 'exception')) Logger::exception($e, ['user' => $_SESSION['user_id'] ?? null]);
    $env = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production')));
    $payload = ['success' => false, 'error_type' => get_class($e), 'message' => $e->getMessage()];
    if (in_array($env, ['dev','development'], true)) $payload['trace'] = $e->getTraceAsString();
    http_response_code(500);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
