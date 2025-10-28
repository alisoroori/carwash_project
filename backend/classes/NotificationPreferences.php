<?php
declare(strict_types=1);

namespace App\Classes;

class NotificationPreferences
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initPreferencesTable();
    }

    private function initPreferencesTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS notification_preferences (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            channel_email BOOLEAN DEFAULT true,
            channel_sms BOOLEAN DEFAULT false,
            channel_push BOOLEAN DEFAULT false,
            eta_updates BOOLEAN DEFAULT true,
            status_changes BOOLEAN DEFAULT true,
            marketing_messages BOOLEAN DEFAULT false,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        $this->conn->query($sql);
    }

    public function updatePreferences($userId, $preferences)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO notification_preferences 
            (user_id, channel_email, channel_sms, channel_push, 
             eta_updates, status_changes, marketing_messages)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            channel_email = VALUES(channel_email),
            channel_sms = VALUES(channel_sms),
            channel_push = VALUES(channel_push),
            eta_updates = VALUES(eta_updates),
            status_changes = VALUES(status_changes),
            marketing_messages = VALUES(marketing_messages)
        ");

        $stmt->bind_param(
            'iiiiiii',
            $userId,
            $preferences['email'],
            $preferences['sms'],
            $preferences['push'],
            $preferences['eta_updates'],
            $preferences['status_changes'],
            $preferences['marketing']
        );

        return $stmt->execute();
    }
}

