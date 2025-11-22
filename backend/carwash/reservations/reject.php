<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Classes\Database;
use App\Classes\Response;

error_log('carwash/reservations/reject.php: request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
        exit;
    }

    $carwashId = $_SESSION['carwash_id'] ?? null;
    if (empty($carwashId)) {
        Response::error('Unauthorized: missing carwash session', 401);
        exit;
    }

    $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    if ($bookingId <= 0) {
        Response::error('Invalid booking id', 400);
        exit;
    }

    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT id FROM bookings WHERE id = :id AND carwash_id = :cw LIMIT 1');
    $stmt->execute(['id' => $bookingId, 'cw' => $carwashId]);
    $b = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$b) {
        Response::error('Booking not found or access denied', 404);
        exit;
    }

    $newStatus = 'cancelled';
    $upd = $pdo->prepare('UPDATE bookings SET status = :st, updated_at = NOW() WHERE id = :id');
    $ok = $upd->execute(['st' => $newStatus, 'id' => $bookingId]);
    if ($ok === false) throw new Exception('Failed to update booking status');

    error_log('carwash/reservations/reject.php: booking ' . $bookingId . ' set to ' . $newStatus . ' by carwash ' . $carwashId);

    Response::success('OK', ['booking_id' => $bookingId, 'status' => $newStatus]);

} catch (Throwable $e) {
    error_log('carwash/reservations/reject.php ERROR: ' . $e->getMessage());
    if (class_exists('\App\\Classes\\Logger')) {
        \App\Classes\Logger::exception($e);
    }
    Response::error('Failed to update booking', 500);
}

