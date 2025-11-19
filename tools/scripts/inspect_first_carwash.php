<?php
require_once __DIR__ . '/../../backend/includes/bootstrap.php';
use App\Classes\Database;
try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $stmt = $pdo->query('SELECT * FROM carwashes LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['row' => $row], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
