<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/api_bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// CSRF + auth helpers
if (file_exists(__DIR__ . '/../../includes/csrf_protect.php')) {
    require_once __DIR__ . '/../../includes/csrf_protect.php';
    generate_csrf_token();
    require_valid_csrf();
} else {
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
if ($bookingId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid booking id']);
    exit;
}

try {
    if (class_exists('\App\Classes\Database')) {
        $db = \App\Classes\Database::getInstance();
        $existing = $db->fetchOne('SELECT * FROM bookings WHERE id = :id', ['id' => $bookingId]);
        if (!$existing || (int)$existing['user_id'] !== (int)$userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Booking not found or access denied']);
            exit;
        }

        $ok = $db->update('bookings', ['status' => 'cancelled'], ['id' => $bookingId]);
        if ($ok === false) throw new Exception('Update failed');

        echo json_encode(['success' => true, 'booking_id' => $bookingId]);
        exit;
    }

    // Fallback: use PDO
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'carwash_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $bookingId]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['user_id'] !== (int)$userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Booking not found or access denied']);
        exit;
    }

    $upd = $pdo->prepare('UPDATE bookings SET status = :st WHERE id = :id');
    $upd->execute(['st' => 'cancelled', 'id' => $bookingId]);

    echo json_encode(['success' => true, 'booking_id' => $bookingId]);
    exit;

} catch (Throwable $e) {
    error_log('bookings/cancel.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
    exit;
}
