<?php
// Manual reservation insert runner for debugging
require_once __DIR__ . '/../backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();

// Allow overriding via CLI args: php manual_reservation_insert.php user_id carwash_id service_id date time
$argv = $_SERVER['argv'] ?? [];
$user_id = isset($argv[1]) ? (int)$argv[1] : 14;
$carwash_id = isset($argv[2]) ? (int)$argv[2] : 7;
$service_id = isset($argv[3]) ? (int)$argv[3] : 19;
$date = isset($argv[4]) ? $argv[4] : '2025-11-28';
$time = isset($argv[5]) ? $argv[5] : '03:45';
$notes = isset($argv[6]) ? $argv[6] : '';
$price = 50.00;

$insert = [
    'user_id' => $user_id,
    'carwash_id' => $carwash_id,
    'service_id' => $service_id,
    'booking_date' => $date,
    'booking_time' => $time,
    'status' => 'pending',
    'total_price' => $price,
    'notes' => $notes,
    'created_at' => date('Y-m-d H:i:s')
];

$debugLogFile = __DIR__ . '/../backend/logs/db_insert_debug.log';
@file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - MANUAL RUN payload=" . json_encode($insert, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

try {
    $id = $db->insert('bookings', $insert);
    $result = ['success' => (bool)$id, 'insert_id' => $id, 'payload' => $insert];
    @file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - MANUAL RUN result=" . json_encode($result) . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Throwable $e) {
    $err = ['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'payload' => $insert];
    @file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - MANUAL RUN EXCEPTION=" . json_encode($err) . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode($err, JSON_PRETTY_PRINT) . PHP_EOL;
}

// Also show the latest booking for comparison
$last = $db->fetchOne('SELECT id,user_id,carwash_id,service_id,booking_date,booking_time,status,created_at FROM bookings ORDER BY id DESC LIMIT 1');
echo "\n-- latest booking row:\n" . json_encode($last, JSON_PRETTY_PRINT) . PHP_EOL;
