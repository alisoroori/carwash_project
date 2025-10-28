<?php
class NotificationHandler
{
    private $conn;
    private $websocket;
    private $cache;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initTables();
    }

    private function initTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            data JSON,
            read_status BOOLEAN DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(type),
            INDEX(read_status)
        )";

        $this->conn->query($sql);
    }

    public function createNotification($userId, $type, $message, $data = [])
    {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (user_id, type, message, data)
            VALUES (?, ?, ?, ?)
        ");

        $jsonData = json_encode($data);
        $stmt->bind_param('isss', $userId, $type, $message, $jsonData);

        if ($stmt->execute()) {
            $this->broadcastNotification($userId, $type, $message, $data);
            return true;
        }
        return false;
    }

    private function broadcastNotification($userId, $type, $message, $data)
    {
        global $analyticsServer;
        if ($analyticsServer) {
            $analyticsServer->broadcast([
                'type' => 'notification',
                'userId' => $userId,
                'message' => $message,
                'data' => $data
            ]);
        }
    }
}
