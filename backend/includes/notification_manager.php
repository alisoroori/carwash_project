<?php
declare(strict_types=1);

namespace App\Classes;

use App\Classes\Logger;

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
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?, ?, ?, ?)
            ");

            if ($stmt === false) {
                Logger::error('createNotification prepare failed', ['errno' => $this->conn->errno, 'error' => $this->conn->error]);
                return false;
            }

            $stmt->bind_param('isss', $userId, $title, $message, $type);
            $res = $stmt->execute();
            $stmt->close();
            return (bool) $res;
        } catch (\Throwable $e) {
            Logger::exception($e, ['method' => 'createNotification', 'userId' => $userId]);
            return false;
        }
    }

    public function getUserNotifications($userId, $limit = 10, $offset = 0)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT *
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");

            if ($stmt === false) {
                Logger::error('getUserNotifications prepare failed', ['errno' => $this->conn->errno, 'error' => $this->conn->error]);
                return [];
            }

            $stmt->bind_param('iii', $userId, $limit, $offset);
            if (! $stmt->execute()) {
                Logger::error('getUserNotifications execute failed', ['errno' => $stmt->errno, 'error' => $stmt->error]);
                $stmt->close();
                return [];
            }

            $result = $stmt->get_result();
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        } catch (\Throwable $e) {
            Logger::exception($e, ['method' => 'getUserNotifications', 'userId' => $userId]);
            return [];
        }
    }

    public function markAsRead($notificationId, $userId)
    {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications
                SET status = 'read', read_at = NOW()
                WHERE id = ? AND user_id = ?
            ");

            if ($stmt === false) {
                Logger::error('markAsRead prepare failed', ['errno' => $this->conn->errno, 'error' => $this->conn->error]);
                return false;
            }

            $stmt->bind_param('ii', $notificationId, $userId);
            $res = $stmt->execute();
            $stmt->close();
            return (bool) $res;
        } catch (\Throwable $e) {
            Logger::exception($e, ['method' => 'markAsRead', 'notificationId' => $notificationId, 'userId' => $userId]);
            return false;
        }
    }

    public function getUnreadCount($userId)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM notifications
                WHERE user_id = ? AND status = 'unread'
            ");

            if ($stmt === false) {
                Logger::error('getUnreadCount prepare failed', ['errno' => $this->conn->errno, 'error' => $this->conn->error]);
                return 0;
            }

            $stmt->bind_param('i', $userId);
            if (! $stmt->execute()) {
                Logger::error('getUnreadCount execute failed', ['errno' => $stmt->errno, 'error' => $stmt->error]);
                $stmt->close();
                return 0;
            }

            $result = $stmt->get_result();
            $count = 0;
            if ($result) {
                $assoc = $result->fetch_assoc();
                $count = isset($assoc['count']) ? (int)$assoc['count'] : 0;
            }
            $stmt->close();
            return $count;
        } catch (\Throwable $e) {
            Logger::exception($e, ['method' => 'getUnreadCount', 'userId' => $userId]);
            return 0;
        }
    }
}
