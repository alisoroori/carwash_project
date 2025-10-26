<?php
declare(strict_types=1);

namespace App\Classes;

// Notification Manager Class
class NotificationManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function createNotification($userId, $title, $message, $type)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param('isss', $userId, $title, $message, $type);
        return $stmt->execute();
    }

    public function getUserNotifications($userId, $limit = 10, $offset = 0)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->bind_param('iii', $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function markAsRead($notificationId, $userId)
    {
        $stmt = $this->conn->prepare("
            UPDATE notifications
            SET status = 'read', read_at = NOW()
            WHERE id = ? AND user_id = ?
        ");

        $stmt->bind_param('ii', $notificationId, $userId);
        return $stmt->execute();
    }

    public function getUnreadCount($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM notifications
            WHERE user_id = ? AND status = 'unread'
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['count'];
    }
}

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Response;

// Enforce admin role for this admin API endpoint.
// If the user is not admin, Response::error(...) will send a 403 JSON and exit.
Auth::requireRole('admin');

// perform admin-only API actions, return JSON via Response::success / Response::error