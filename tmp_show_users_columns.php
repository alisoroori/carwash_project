<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
$db = App\Classes\Database::getInstance();
try {
    $cols = $db->getPdo()->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
}
