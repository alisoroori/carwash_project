<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Classes\Database;
use App\Classes\Response;

error_log('services/save_service.php: request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
        exit;
    }

    // CSRF helper if present
    if (file_exists(__DIR__ . '/../../includes/csrf_protect.php')) {
        require_once __DIR__ . '/../../includes/csrf_protect.php';
        require_valid_csrf();
    } else {
        $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (empty($_SESSION['csrf_token']) || empty($csrfToken) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrfToken)) {
            error_log('CSRF: missing or invalid token in services/save_service.php');
            echo json_encode(['success' => false, 'error' => 'invalid_csrf']);
            exit;
        }
    }

    $userId = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    if (!$userId || $role !== 'carwash') {
        echo json_encode(['success' => false, 'error' => 'unauthorized']);
        exit;
    }

    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // find carwash id
    $stmt = $pdo->prepare('SELECT id FROM carwashes WHERE user_id = :uid LIMIT 1');
    $stmt->execute(['uid' => $userId]);
    $cw = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cw) {
        echo json_encode(['success' => false, 'error' => 'carwash_not_found']);
        exit;
    }
    $carwashId = (int)$cw['id'];

    $data = $_POST;
    $serviceId = isset($data['id']) && $data['id'] !== '' ? (int)$data['id'] : null;

    // Per-field validation & sanitization
    $name = isset($data['name']) ? trim((string)$data['name']) : '';
    if ($name === '') {
        echo json_encode(['success' => false, 'error' => 'name']);
        exit;
    }

    $description = isset($data['description']) ? trim((string)$data['description']) : '';
    // sanitize description (allow some basic tags if desired, otherwise strip)
    $description = strip_tags($description);

    // price and duration are optional but must be numeric if provided
    $priceRaw = $data['price'] ?? '';
    if ($priceRaw === '') {
        $price = 0.0;
    } elseif (!is_numeric($priceRaw)) {
        echo json_encode(['success' => false, 'error' => 'price']);
        exit;
    } else {
        $price = (float)$priceRaw;
    }

    $durationRaw = $data['duration'] ?? '';
    if ($durationRaw === '') {
        $duration = 0;
    } elseif (!is_numeric($durationRaw)) {
        echo json_encode(['success' => false, 'error' => 'duration']);
        exit;
    } else {
        $duration = (int)$durationRaw;
    }

    $status = isset($data['status']) ? trim((string)$data['status']) : 'active';

    if ($serviceId) {
        $upd = $pdo->prepare('UPDATE services SET name = :name, description = :description, price = :price, duration = :duration, status = :status, updated_at = NOW() WHERE id = :id AND carwash_id = :cw');
        $ok = $upd->execute(['name' => $name, 'description' => $description, 'price' => $price, 'duration' => $duration, 'status' => $status, 'id' => $serviceId, 'cw' => $carwashId]);
        if (!$ok) throw new Exception('Failed to update service');
        echo json_encode(['success' => true, 'message' => 'saved', 'id' => $serviceId]);
        exit;
    } else {
        $ins = $pdo->prepare('INSERT INTO services (carwash_id, name, description, price, duration, status, created_at) VALUES (:cw, :name, :description, :price, :duration, :status, NOW())');
        $ok = $ins->execute(['cw' => $carwashId, 'name' => $name, 'description' => $description, 'price' => $price, 'duration' => $duration, 'status' => $status]);
        if (!$ok) throw new Exception('Failed to create service');
        $newId = (int)$pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'saved', 'id' => $newId]);
        exit;
    }

} catch (Throwable $e) {
    error_log('services/save_service.php ERROR: ' . $e->getMessage());
    if (class_exists('\App\\Classes\\Logger')) {
        \App\Classes\Logger::exception($e);
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
