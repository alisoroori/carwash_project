<?php

declare(strict_types=1);

// Ensure API returns JSON
header('Content-Type: application/json; charset=utf-8');

// Start session (some flows rely on session auth)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Composer autoloader
$vendor = dirname(__DIR__, 3) . '/vendor/autoload.php';
if (file_exists($vendor)) {
    require_once $vendor;
}

// Project bootstrap (optional)
$projectBootstrap = dirname(__DIR__, 3) . '/backend/includes/bootstrap.php';
if (file_exists($projectBootstrap)) {
    require_once $projectBootstrap;
}

use App\Classes\Database;
use App\Classes\Response;

error_log('bookings/list.php: Request started from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    $userId = $_SESSION['user_id'] ?? null;
    error_log('bookings/list.php: User ID = ' . ($userId ?? 'null'));

    if (!$userId) {
        error_log('bookings/list.php: No user authentication found');
        Response::error('Authentication required', 401);
        exit;
    }

    $db = Database::getInstance();
    error_log('bookings/list.php: Database connected');

    // Use the underlying PDO connection directly so we can surface real PDO errors
    $pdo = $db->getPdo();
    // Ensure exceptions are thrown on PDO errors (Database already sets this, but enforce here)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prefer joining against `carwashes` (canonical) if present, otherwise fall back to `business_profiles` if present
    try {
        $tblCheck = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tbl");
        $tblCheck->execute(['tbl' => 'carwashes']);
        $hasCarwashes = (int)$tblCheck->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
    } catch (Exception $e) {
        // On any error, assume table absent and fall back
        $hasCarwashes = false;
    }

    if ($hasCarwashes) {
        $cwSelect = 'cw.name AS carwash_name';
        $cwJoin = 'LEFT JOIN carwashes cw ON cw.id = b.carwash_id';
    } else {
        // Try business_profiles as a secondary source; if not present, return null carwash name
        try {
            $tblCheck->execute(['tbl' => 'business_profiles']);
            $hasBusinessProfiles = (int)$tblCheck->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        } catch (Exception $e) {
            $hasBusinessProfiles = false;
        }

        if (!empty($hasBusinessProfiles)) {
            $cwSelect = 'cw.business_name AS carwash_name';
            $cwJoin = 'LEFT JOIN business_profiles cw ON cw.id = b.carwash_id';
        } else {
            $cwSelect = 'NULL AS carwash_name';
            $cwJoin = '';
        }
    }

    $query = "SELECT b.*, u.name AS user_name, s.name AS service_name, {$cwSelect} FROM bookings b
            LEFT JOIN users u ON u.id = b.user_id
            LEFT JOIN services s ON s.id = b.service_id
            {$cwJoin}
            WHERE b.user_id = :uid
            ORDER BY b.id DESC";

    try {
        error_log('bookings/list.php: Preparing bookings query');
        $stmt = $pdo->prepare($query);
        $params = ['uid' => $userId];
        error_log('bookings/list.php: Executing bookings query - SQL: ' . $query . ' - Params: ' . json_encode($params));
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log('bookings/list.php: Found ' . count($rows) . ' bookings');

        Response::success('OK', ['data' => $rows]);
    } catch (\PDOException $pdoEx) {
        // Log the full PDO exception for debugging but don't expose sensitive details to the client
        error_log('bookings/list.php PDO ERROR: ' . $pdoEx->getMessage());
        error_log('bookings/list.php PDO TRACE: ' . $pdoEx->getTraceAsString());
        if (class_exists('\App\\Classes\\Logger')) {
            \App\Classes\Logger::exception($pdoEx);
        }
        if (class_exists('\App\\Classes\\Response')) {
            \App\Classes\Response::error('Failed to fetch bookings: ' . $pdoEx->getMessage(), 500);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch bookings', 'error' => $pdoEx->getMessage()]);
        }
        exit;
    }

} catch (Throwable $e) {
    error_log('bookings/list.php ERROR: ' . $e->getMessage());
    error_log('bookings/list.php TRACE: ' . $e->getTraceAsString());
    if (class_exists('\App\\Classes\\Response')) {
        \App\Classes\Response::error('Failed to fetch bookings: ' . $e->getMessage(), 500);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch bookings', 'error' => $e->getMessage()]);
        exit;
    }
}
