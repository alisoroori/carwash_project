﻿<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized');
    }

    $stmt = $conn->prepare("
        SELECT id, title, slug, status, created_at, updated_at
        FROM pages
        ORDER BY created_at DESC
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $pages = [];
    while ($row = $result->fetch_assoc()) {
        $pages[] = $row;
    }

    echo json_encode([
        'success' => true,
        'pages' => $pages
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
