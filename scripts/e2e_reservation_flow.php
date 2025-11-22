<?php
// Coordinator: tail logs, run submit include, capture new log lines, verify DB and carwash list, output report JSON.
require_once __DIR__ . '/show_last_booking.php';
require_once __DIR__ . '/../backend/includes/bootstrap.php';
use App\Classes\Database;

// Config payload (override via env vars)
$payload = [
    'user_id' => (int)($_ENV['E2E_USER_ID'] ?? 14),
    'carwash_id' => (int)($_ENV['E2E_LOCATION_ID'] ?? 7),
    'service_id' => (int)($_ENV['E2E_SERVICE_ID'] ?? 19),
    'vehicle' => $_ENV['E2E_VEHICLE'] ?? 'E2E Test Car - 99 E2E 9',
    'reservationDate' => $_ENV['E2E_DATE'] ?? date('Y-m-d', strtotime('+7 days')),
    'reservationTime' => $_ENV['E2E_TIME'] ?? '10:30',
    'notes' => $_ENV['E2E_NOTES'] ?? 'Automated E2E test'
];

// Log files
$resDebug = __DIR__ . '/../backend/logs/reservations_debug.log';
$dbDebug = __DIR__ . '/../logs/db_insert_debug.log';

// Helper to read new lines added since a given file size
function readNewLines($path, $fromSize) {
    if (!file_exists($path)) return ['new' => [], 'size' => 0];
    $size = filesize($path);
    if ($size <= $fromSize) return ['new' => [], 'size' => $size];
    $fp = fopen($path, 'r');
    fseek($fp, $fromSize);
    $data = stream_get_contents($fp);
    fclose($fp);
    $lines = preg_split('/\r?\n/', trim($data));
    return ['new' => $lines ?: [], 'size' => $size];
}

// Record initial sizes
$resSize = file_exists($resDebug) ? filesize($resDebug) : 0;
$dbSize = file_exists($dbDebug) ? filesize($dbDebug) : 0;

// Run submit in separate process to avoid exit() stopping this script
$cmd = 'php "' . __DIR__ . '/e2e_submit_include.php"';
// Ensure environment used by submit script
putenv('E2E_USER_ID=' . $payload['user_id']);
putenv('E2E_LOCATION_ID=' . $payload['carwash_id']);
putenv('E2E_SERVICE_ID=' . $payload['service_id']);
putenv('E2E_VEHICLE=' . $payload['vehicle']);
putenv('E2E_DATE=' . $payload['reservationDate']);
putenv('E2E_TIME=' . $payload['reservationTime']);
putenv('E2E_NOTES=' . $payload['notes']);

// Timestamp
$startTs = date('Y-m-d H:i:s');

exec($cmd, $outputLines, $returnVar);
$responseRaw = implode("\n", $outputLines);
// Try parse JSON
$response = null;
@ $response = json_decode($responseRaw, true);

// Small wait for logs to flush
usleep(300000); // 300ms

// Read new log lines
$resNew = readNewLines($resDebug, $resSize);
$dbNew = readNewLines($dbDebug, $dbSize);

// Query DB for matching row
$db = Database::getInstance();
$row = $db->fetchOne('SELECT * FROM bookings WHERE user_id = :uid AND carwash_id = :cw AND service_id = :sid AND booking_date = :d AND booking_time LIKE :t ORDER BY id DESC LIMIT 1', [
    'uid' => $payload['user_id'],
    'cw' => $payload['carwash_id'],
    'sid' => $payload['service_id'],
    'd' => $payload['reservationDate'],
    't' => substr($payload['reservationTime'],0,5) . '%'
]);

// Call carwash list includer to check frontend visibility
$cmd2 = 'php "' . __DIR__ . '/e2e_carwash_list_include.php"';
putenv('E2E_LOCATION_ID=' . $payload['carwash_id']);
exec($cmd2, $listLines, $rv2);
$listRaw = implode("\n", $listLines);
$listJson = @json_decode($listRaw, true);

$frontendVisible = false;
if (is_array($listJson) && isset($listJson['status']) && $listJson['status'] === 'success') {
    $rows = $listJson['data'] ?? [];
    foreach ($rows as $r) {
        if ((int)($r['user_id'] ?? 0) === $payload['user_id'] && (int)($r['service_id'] ?? 0) === $payload['service_id'] && ($r['booking_date'] ?? '') === $payload['reservationDate']) {
            $frontendVisible = true;
            break;
        }
    }
}

$report = [
    'insert_success' => (bool)$row,
    'db_row' => $row ?: null,
    'endpoint_response_raw' => $responseRaw,
    'endpoint_response' => $response,
    'log_snippets' => [
        'reservations_debug' => $resNew['new'],
        'db_insert_debug' => $dbNew['new']
    ],
    'frontend_status' => $frontendVisible,
    'start_timestamp' => $startTs
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
