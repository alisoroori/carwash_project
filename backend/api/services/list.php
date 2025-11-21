<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Prevent PHP warnings from polluting JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Classes\Database;
use App\Classes\Response;

error_log('services/list.php: request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        Response::error('Authentication required', 401);
        exit;
    }

    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT id FROM carwashes WHERE user_id = :uid LIMIT 1');
    $stmt->execute(['uid' => $userId]);
    $cw = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cw) {
        Response::error('Carwash not found for current user', 404);
        exit;
    }
    $carwashId = (int)$cw['id'];

    // Temporary debug logging (human-readable) - remove after debugging
    $logFile = __DIR__ . '/../../../../logs/services_debug.log';
    try {
        @file_put_contents($logFile, sprintf("[%s] services/list.php - resolved carwash_id=%s (user_id=%s)\n", date('Y-m-d H:i:s'), $carwashId, $userId), FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {}

    $query = "SELECT s.id, s.carwash_id, s.name, s.description, COALESCE(s.price,0) AS price, COALESCE(s.duration,0) AS duration, COALESCE(s.status,'active') AS status, COALESCE(s.is_available,1) AS is_available, s.category, s.image FROM services s WHERE s.carwash_id = :cw ORDER BY s.name ASC";
    $stmt = $pdo->prepare($query);
    // Log the query for debugging
    try { @file_put_contents($logFile, sprintf("[%s] services/list.php - SQL: %s | PARAMS: cw=%s\n", date('Y-m-d H:i:s'), $query, $carwashId), FILE_APPEND | LOCK_EX); } catch (Throwable $e) {}
    $stmt->execute(['cw' => $carwashId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Log returned count
    try { @file_put_contents($logFile, sprintf("[%s] services/list.php - returned %d rows for carwash_id=%s\n", date('Y-m-d H:i:s'), count($rows), $carwashId), FILE_APPEND | LOCK_EX); } catch (Throwable $e) {}

    Response::success('OK', $rows);
} catch (Throwable $e) {
    error_log('services/list.php ERROR: ' . $e->getMessage());
    if (class_exists('\App\\Classes\\Logger')) {
        \App\Classes\Logger::exception($e);
    }
    Response::error('Failed to fetch services', 500);
}
