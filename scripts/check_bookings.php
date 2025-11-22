<?php
require_once __DIR__ . '/../backend/includes/bootstrap.php';
use App\Classes\Database;

try {
    $db = Database::getInstance();
    $rows = $db->fetchAll('SELECT * FROM bookings ORDER BY id DESC LIMIT 5');
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
