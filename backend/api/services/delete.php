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

error_log('services/delete.php: request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
        exit;
    }

    // CSRF
    if (file_exists(__DIR__ . '/../../includes/csrf_protect.php')) {
        require_once __DIR__ . '/../../includes/csrf_protect.php';
        require_valid_csrf();
    } else {
        $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (empty($_SESSION['csrf_token']) || empty($csrfToken) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrfToken)) {
            error_log('CSRF: missing or invalid token in services/delete.php');
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

    $serviceId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($serviceId <= 0) { echo json_encode(['success' => false, 'error' => 'invalid_service_id']); exit; }

    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // find carwash id
    $stmt = $pdo->prepare('SELECT id FROM carwashes WHERE user_id = :uid LIMIT 1');
    $stmt->execute(['uid' => $userId]);
    $cw = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cw || !is_array($cw)) {
        echo json_encode(['success' => false, 'error' => 'carwash_not_found']);
        exit;
    }
    $carwashId = (int)$cw['id'];

    // Verify ownership
    $chk = $pdo->prepare('SELECT id FROM services WHERE id = :id AND carwash_id = :cw LIMIT 1');
    $chk->execute(['id' => $serviceId, 'cw' => $carwashId]);
    $found = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$found || !is_array($found)) { echo json_encode(['success' => false, 'error' => 'not_found_or_denied']); exit; }

    $del = $pdo->prepare('DELETE FROM services WHERE id = :id AND carwash_id = :cw');
    $ok = $del->execute(['id' => $serviceId, 'cw' => $carwashId]);
    if (!$ok) throw new Exception('Failed to delete service');

    echo json_encode(['success' => true, 'message' => 'deleted', 'id' => $serviceId]);
    exit;

} catch (Throwable $e) {
    error_log('services/delete.php ERROR: ' . $e->getMessage());
    if (class_exists('\App\\Classes\\Logger')) {
        \App\Classes\Logger::exception($e);
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
