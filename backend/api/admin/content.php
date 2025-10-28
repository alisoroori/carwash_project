<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Verify admin authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Handle different content operations based on request method
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Fetch content
            $stmt = $pdo->prepare("SELECT * FROM content WHERE status = 'active'");
            $stmt->execute();
            $content = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'content' => $content
            ]);
            break;

        case 'POST':
            // Add new content
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['title']) || !isset($data['body'])) {
                throw new Exception('Missing required fields');
            }

            $stmt = $pdo->prepare("
                INSERT INTO content (title, body, created_by, status)
                VALUES (?, ?, ?, 'active')
            ");

            $stmt->execute([
                $data['title'],
                $data['body'],
                $_SESSION['user_id']
            ]);

            echo json_encode([
                'success' => true,
                'content_id' => $pdo->lastInsertId()
            ]);
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
