<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Classes\Database;
try {
    $db = Database::getInstance();
    $r = $db->fetchOne('SELECT id FROM users LIMIT 1');
    echo json_encode($r ?: null);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
