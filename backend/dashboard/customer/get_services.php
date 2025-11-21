<?php
session_start();
require_once '../../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Validate carwash_id
if (!isset($_GET['carwash_id']) || !is_numeric($_GET['carwash_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid carwash ID']);
    exit();
}

$carwash_id = filter_var($_GET['carwash_id'], FILTER_SANITIZE_NUMBER_INT);

// Temporary debug logging (remove after debugging)
$logFile = __DIR__ . '/../../../logs/services_debug.log';
try {
    $rawGet = json_encode($_GET, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $rawPost = json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $entry = sprintf("[%s] get_services.php - incoming carwash_id=%s | GET=%s | POST=%s\n",
        date('Y-m-d H:i:s'), $carwash_id, $rawGet, $rawPost
    );
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    // best-effort logging; do not break response
}

try {
    // Get active services for the selected carwash
        $stmt = $conn->prepare("
            SELECT id, name AS service_name, description, price, duration 
        WHERE carwash_id = ? AND status = 'active' 
        ORDER BY price ASC
    ");

    $stmt->bind_param("i", $carwash_id);
    // Log the prepared SQL for debugging (human-readable)
    try {
        $sqlForLog = "SELECT id, name AS service_name, description, price, duration FROM services WHERE carwash_id = ? AND status = 'active' ORDER BY price ASC";
        @file_put_contents($logFile, sprintf("[%s] get_services.php - SQL: %s | PARAMS: carwash_id=%s\n", date('Y-m-d H:i:s'), $sqlForLog, $carwash_id), FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {}

    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = [
                'id' => (int)$row['id'],
                'service_name' => htmlspecialchars($row['service_name'] ?? $row['name'] ?? ''),
                'description' => htmlspecialchars($row['description'] ?? ''),
                'price' => is_null($row['price']) ? '0.00' : number_format((float)$row['price'], 2),
                'duration' => isset($row['duration']) ? (int)$row['duration'] : 0
            ];
    }

    // Log returned count
    try {
        @file_put_contents($logFile, sprintf("[%s] get_services.php - returned %d services for carwash_id=%s\n", date('Y-m-d H:i:s'), count($services), $carwash_id), FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {}

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $services]);
} catch (Exception $e) {
    // Log exception to debug file as well as app log
    $errMsg = sprintf("[%s] get_services.php - Exception: %s | carwash_id=%s | GET=%s | POST=%s\n", date('Y-m-d H:i:s'), $e->getMessage(), $carwash_id, json_encode($_GET), json_encode($_POST));
    @file_put_contents($logFile, $errMsg, FILE_APPEND | LOCK_EX);
    error_log($errMsg);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
