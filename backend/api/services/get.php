<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Keep PHP warnings from polluting JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Classes\Database;

try {
    // Debug: log session state
    error_log('services/get.php: SESSION = ' . json_encode(['user_id' => $_SESSION['user_id'] ?? null, 'carwash_id' => $_SESSION['carwash_id'] ?? null, 'role' => $_SESSION['role'] ?? null]));

    // Require an authenticated session: either carwash_id directly or user_id to resolve
    if (empty($_SESSION['carwash_id']) && empty($_SESSION['user_id'])) {
        error_log('services/get.php: NOT_AUTHENTICATED - no user_id or carwash_id in session');
        echo json_encode(['success' => false, 'error' => 'NOT_AUTHENTICATED'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Determine carwash id: prefer explicit GET param if provided (frontend passes selected carwash_id), otherwise session value, otherwise resolve from user_id
    $carwashId = null;
    if (!empty($_GET['carwash_id']) && is_numeric($_GET['carwash_id'])) {
        $carwashId = (int)$_GET['carwash_id'];
    } elseif (!empty($_SESSION['carwash_id'])) {
        $carwashId = (int)$_SESSION['carwash_id'];
    } else {
        $stmt = $pdo->prepare('SELECT id FROM carwashes WHERE user_id = :uid LIMIT 1');
        $stmt->execute(['uid' => (int)$_SESSION['user_id']]);
        $cw = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cw) {
            echo json_encode(['success' => false, 'error' => 'NOT_AUTHENTICATED'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $carwashId = (int)$cw['id'];
    }

    // Fetch services for this carwash only
    // Temporary debug logging to services_debug.log
    $logFile = __DIR__ . '/../../../../logs/services_debug.log';
    try { @file_put_contents($logFile, sprintf("[%s] services/get.php - Fetching services for carwash_id=%s - session=%s\n", date('Y-m-d H:i:s'), $carwashId, json_encode(['user_id'=>$_SESSION['user_id'] ?? null, 'carwash_id'=>$_SESSION['carwash_id'] ?? null])), FILE_APPEND | LOCK_EX); } catch (Throwable $e) {}

    error_log('services/get.php: Fetching services for carwash_id=' . $carwashId);
    // Ensure deterministic ordering by name
    $stmt = $pdo->prepare('SELECT id, name, description, price, duration FROM services WHERE carwash_id = :cw ORDER BY name ASC');
    $stmt->execute(['cw' => $carwashId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('services/get.php: Found ' . count($rows) . ' services');
    try { @file_put_contents($logFile, sprintf("[%s] services/get.php - Found %d services for carwash_id=%s\n", date('Y-m-d H:i:s'), count($rows), $carwashId), FILE_APPEND | LOCK_EX); } catch (Throwable $e) {}

    // Sanitize output
    $sanitized = [];
    foreach ($rows as $row) {
        $sanitized[] = [
            'id' => (int)$row['id'],
            'name' => htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            'price' => number_format((float)($row['price'] ?? 0), 2, '.', ''),
            'duration' => (int)($row['duration'] ?? 0)
        ];
    }

    echo json_encode(['success' => true, 'data' => $sanitized], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    error_log('services/get.php ERROR: ' . $e->getMessage());
    $msg = $e->getMessage();
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

