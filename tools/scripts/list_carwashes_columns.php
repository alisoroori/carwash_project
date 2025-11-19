<?php
require_once __DIR__ . '/../../backend/includes/bootstrap.php';
use App\Classes\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $stmt = $pdo->query('SHOW COLUMNS FROM carwashes');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
