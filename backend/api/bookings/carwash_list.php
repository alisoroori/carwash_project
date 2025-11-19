<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

// Don't output PHP warnings/notices into JSON responses
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// include bootstrap from backend/includes
require_once __DIR__ . '/../../includes/bootstrap.php';
// composer autoload (project root)
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Classes\Database;
use App\Classes\Response;

error_log('bookings/carwash_list.php: request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        Response::error('Authentication required', 401);
        exit;
    }

    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Find carwash owned by this user
    $stmt = $pdo->prepare('SELECT id FROM carwashes WHERE user_id = :uid LIMIT 1');
    $stmt->execute(['uid' => $userId]);
    $cw = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cw) {
        Response::error('Carwash not found for current user', 404);
        exit;
    }
    $carwashId = (int)$cw['id'];

    $query = "SELECT b.id, b.user_id, b.carwash_id, b.service_id, b.booking_date, b.booking_time, b.status, 
                b.total_price, b.payment_status, b.notes, b.vehicle_type, b.vehicle_plate, b.vehicle_model,
                u.name as user_name, u.phone as user_phone, u.email as user_email, 
                s.name as service_name, s.description as service_description
              FROM bookings b
              LEFT JOIN users u ON u.id = b.user_id
              LEFT JOIN services s ON s.id = b.service_id
              WHERE b.carwash_id = :cw
              ORDER BY b.booking_date DESC, b.booking_time DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['cw' => $carwashId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success('OK', ['data' => $rows]);
} catch (Throwable $e) {
    error_log('bookings/carwash_list.php ERROR: ' . $e->getMessage());
    if (class_exists('\App\\Classes\\Logger')) {
        \App\Classes\Logger::exception($e);
    }
    Response::error('Failed to fetch reservations', 500);
}

