<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
// Remove or update the following line if 'Auth.php' does not exist or is named differently
require_once '../../includes/auth.php'; // Ensure this file defines the Auth class
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('Email and password are required');
    }

    $auth = new Auth($conn);
    $result = $auth->login($data['email'], $data['password']);

    echo json_encode([
        'success' => true,
        'user' => $result['user'],
        'token' => $result['token']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
