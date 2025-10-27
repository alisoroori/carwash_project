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

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // List notifications
            $status = $_GET['status'] ?? 'active';
            $type = $_GET['type'] ?? null;

            $query = "SELECT n.*, 
                     COUNT(nr.user_id) as read_count,
                     u.name as created_by_name
                     FROM notifications n
                     LEFT JOIN notification_reads nr ON n.id = nr.notification_id
                     LEFT JOIN users u ON n.created_by = u.id
                     WHERE n.status = ?";

            $params = [$status];

            if ($type) {
                $query .= " AND n.type = ?";
                $params[] = $type;
            }

            $query .= " GROUP BY n.id ORDER BY n.created_at DESC";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll();

            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;

        case 'POST':
            // Create new notification
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare("
                INSERT INTO notifications 
                (title, message, type, target_role, scheduled_at, expires_at, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['title'],
                $data['message'],
                $data['type'],
                $data['target_role'],
                $data['scheduled_at'] ?? null,
                $data['expires_at'] ?? null,
                $_SESSION['user_id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Notification created successfully',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            // Update notification
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET title = ?, message = ?, status = ?, 
                    scheduled_at = ?, expires_at = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['title'],
                $data['message'],
                $data['status'],
                $data['scheduled_at'] ?? null,
                $data['expires_at'] ?? null,
                $data['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Notification updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete notification
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Notification ID required');
            }

            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
