<?php
// Minimal API to list reservations for the logged-in carwash owner
// Path: backend/carwash/reservations/list.php

require_once __DIR__ . '/../../includes/bootstrap.php';
use App\Classes\Database;
use App\Classes\Response;

// Ensure user is authenticated if Auth class exists
if (class_exists('App\\Classes\\Auth')) {
    try {
        App\Classes\Auth::requireAuth();
    } catch (Exception $e) {
        App\Classes\Response::error('Authentication required', 401);
        exit;
    }
}

// Session is started in bootstrap.php; avoid calling session_start() again
$carwashId = $_SESSION['carwash_id'] ?? null;
error_log("carwash/reservations/list.php - session carwash_id: " . var_export($carwashId, true));

if (empty($carwashId)) {
    Response::error('Carwash id missing from session', 401);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT b.*, u.fullname AS customer_name, s.name AS service_name
            FROM bookings b
            LEFT JOIN users u ON u.id = b.user_id
            LEFT JOIN services s ON s.id = b.service_id
            WHERE b.carwash_id = :carwash_id
            ORDER BY b.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['carwash_id' => $carwashId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = is_array($rows) ? count($rows) : 0;
    error_log("carwash/reservations/list.php - rows returned: " . $count);

    // Always return JSON array for data (empty array if none)
    Response::success('OK', $rows ?: []);
} catch (Exception $e) {
    error_log('carwash/reservations/list.php ERROR: ' . $e->getMessage());
    Response::error('Database error: ' . $e->getMessage(), 500);
}
