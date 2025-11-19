<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

// Prevent PHP notices/warnings from being emitted directly into JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// include bootstrap from backend/includes and composer autoload
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Classes\Database;
use App\Classes\Response;

error_log('bookings/approve.php: request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
        exit;
    }

    // CSRF check if helper exists
    if (file_exists(__DIR__ . '/../../includes/csrf_protect.php')) {
        require_once __DIR__ . '/../../includes/csrf_protect.php';
        require_valid_csrf();
    }

    $userId = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    if (!$userId || $role !== 'carwash') {
        Response::error('Unauthorized', 401);
        exit;
    }

    $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    if ($bookingId <= 0) {
        Response::error('Invalid booking id', 400);
        exit;
    }

    $action = ($_POST['action'] ?? 'approve');

    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Find carwash id for this user
    $stmt = $pdo->prepare('SELECT id FROM carwashes WHERE user_id = :uid LIMIT 1');
    $stmt->execute(['uid' => $userId]);
    $cw = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cw) {
        Response::error('Carwash not found for user', 404);
        exit;
    }
    $carwashId = (int)$cw['id'];

    // Verify booking belongs to this carwash
    $stmt = $pdo->prepare('SELECT id, status, carwash_id FROM bookings WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $bookingId]);
    $b = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$b) {
        Response::error('Booking not found', 404);
        exit;
    }
    if ((int)$b['carwash_id'] !== $carwashId) {
        Response::error('Access denied to this booking', 403);
        exit;
    }

    if ($action === 'reject') {
        $newStatus = 'cancelled';
    } else {
        $newStatus = 'confirmed';
    }

    $upd = $pdo->prepare('UPDATE bookings SET status = :st, updated_at = NOW() WHERE id = :id');
    $ok = $upd->execute(['st' => $newStatus, 'id' => $bookingId]);
    if (!$ok) throw new Exception('Failed to update booking status');

    // Optionally: notify customer (out of scope) or push websocket message
    Response::success('OK', ['booking_id' => $bookingId, 'status' => $newStatus]);

} catch (Throwable $e) {
    error_log('bookings/approve.php ERROR: ' . $e->getMessage());
    if (class_exists('\App\\Classes\\Logger')) {
        \App\Classes\Logger::exception($e);
    }
    Response::error('Failed to update booking', 500);
}
