<?php
class NotificationQueue {
    private $conn;
    private $table = 'notification_queue';

    public function __construct($conn) {
        $this->conn = $conn;
        $this->initQueue();
    }

    private function initQueue() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            data JSON NOT NULL,
            priority INT DEFAULT 1,
            status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            scheduled_for DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX (status, priority, scheduled_for)
        )";
        
        $this->conn->query($sql);
    }

    public function addToQueue($notification) {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
            (user_id, type, data, priority, scheduled_for)
            VALUES (?, ?, ?, ?, ?)
        ");

        $data = json_encode($notification['data']);
        $stmt->bind_param('issis',
            $notification['user_id'],
            $notification['type'],
            $data,
            $notification['priority'] ?? 1,
            $notification['scheduled_for'] ?? null
        );

        return $stmt->execute();
    }

    public function processQueue() {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE status = 'pending'
            AND (scheduled_for IS NULL OR scheduled_for <= NOW())
            AND attempts < 3
            ORDER BY priority DESC, created_at ASC
            LIMIT 50
        ");

        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($notifications as $notification) {
            $this->dispatch($notification);
        }
    }
}