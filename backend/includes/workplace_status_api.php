<?php
/**
 * Workplace Status API Endpoint
 * Handles GET and POST requests for carwash workplace status
 */

session_start();
require_once __DIR__ . '/bootstrap.php';

use App\Classes\Database;

header('Content-Type: application/json');

$uid = $_SESSION['user_id'] ?? null;

if (!$uid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// GET: Fetch current status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $db = Database::getInstance();
        $cw = $db->fetchOne('SELECT status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE user_id = :uid LIMIT 1', ['uid' => $uid]);
        $status = $cw['status'] ?? null;
        $isActive = isset($cw['is_active']) ? (int)$cw['is_active'] : 0;
        echo json_encode(['success' => true, 'status' => $status, 'is_active' => $isActive]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

// POST: Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isActiveVal = null;
    if (isset($_POST['ajax_is_active'])) {
        $isActiveVal = (int)$_POST['ajax_is_active'] ? 1 : 0;
    }

    if ($isActiveVal !== null) {
        $new = ($isActiveVal === 1) ? 'Açık' : 'Kapalı';
    } else {
        $incoming = trim((string)($_POST['ajax_workplace_status'] ?? ''));
        if ($incoming === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Empty status']);
            exit;
        }
        $incomingLower = strtolower($incoming);
        $openVariants = ['açık', 'acik', 'open', 'active'];
        $closedVariants = ['kapalı', 'kapali', 'closed', 'inactive'];
        if (in_array($incomingLower, $openVariants, true)) {
            $new = 'Açık';
            $isActiveVal = 1;
        } elseif (in_array($incomingLower, $closedVariants, true)) {
            $new = 'Kapalı';
            $isActiveVal = 0;
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status token', 'token' => $incoming]);
            exit;
        }
    }

    // Persist to session
    $_SESSION['workplace_status'] = $new;

    // Persist to DB
    try {
        $db = Database::getInstance();
        $pdo = $db->getPdo();
        $upd = $pdo->prepare('UPDATE carwashes SET status = :status, is_active = :is_active, updated_at = NOW() WHERE user_id = :uid');
        $upd->execute(['status' => $new, 'is_active' => $isActiveVal, 'uid' => $uid]);
        $rowCount = $upd->rowCount();
        error_log("[workplace_status_api] Toggle update: user_id={$uid}, status={$new}, is_active={$isActiveVal}, rows_affected={$rowCount}");
        
        echo json_encode(['success' => true, 'status' => $new, 'is_active' => (int)$isActiveVal]);
    } catch (Exception $e) {
        error_log("[workplace_status_api] Toggle update FAILED: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

// Other methods not allowed
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
