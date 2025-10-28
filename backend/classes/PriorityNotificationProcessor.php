<?php
declare(strict_types=1);

namespace App\Classes;

class PriorityNotificationProcessor {
    private $conn;
    private $priorityLevels = [
        'urgent' => 1,
        'high' => 2,
        'normal' => 3,
        'low' => 4
    ];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->initPriorityQueue();
    }

    private function initPriorityQueue() {
        $sql = "CREATE TABLE IF NOT EXISTS priority_notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            message_type VARCHAR(50) NOT NULL,
            content JSON NOT NULL,
            priority INT NOT NULL,
            status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
            retry_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (priority, status),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        $this->conn->query($sql);
    }

    public function addNotification($data) {
        $priority = $this->calculatePriority($data);
        
        $stmt = $this->conn->prepare("
            INSERT INTO priority_notifications 
            (user_id, message_type, content, priority)
            VALUES (?, ?, ?, ?)
        ");

        $content = json_encode($data['content']);
        $stmt->bind_param('issi', 
            $data['user_id'],
            $data['type'],
            $content,
            $priority
        );

        return $stmt->execute();
    }

    private function calculatePriority($data) {
        // Base priority from message type
        $basePriority = $this->priorityLevels[$data['priority']] ?? 3;
        
        // Adjust based on user preferences and context
        if ($data['type'] === 'service_update' && isset($data['content']['status'])) {
            $basePriority = min($basePriority, 2); // Higher priority for service updates
        }
        
        return $basePriority;
    }
}
