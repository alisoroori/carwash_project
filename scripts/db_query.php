<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Classes\Database;

if ($argc < 2) {
    echo json_encode(null);
    exit;
}
$id = (int)$argv[1];
try {
    $db = Database::getInstance();
    $r = $db->fetchOne('SELECT id,status,carwash_id FROM bookings WHERE id = :id LIMIT 1', ['id' => $id]);
    echo json_encode($r ?: null);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
