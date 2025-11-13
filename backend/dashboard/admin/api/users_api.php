<?php
// Simple users API stub — returns JSON list of users (protected)
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../Auth.php';
Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');
try {
    if (!class_exists('App\\Classes\\Database')) {
        throw new Exception('Database class not found');
    }
    $db = App\Classes\Database::getInstance();
    $users = $db->fetchAll('SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT 100');
    echo json_encode(['status' => 'success', 'data' => $users], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
