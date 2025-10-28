<?php
declare(strict_types=1);

namespace App\Classes;

class NotificationChannelManager
{
    private $conn;
    private $channels = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initChannels();
    }

    private function initChannels()
    {
        $this->channels = [
            'email' => new EmailChannel($this->conn),
            'sms' => new SMSChannel($this->conn),
            'push' => new PushNotificationChannel($this->conn),
            'whatsapp' => new WhatsAppChannel($this->conn)
        ];
    }

    public function send($userId, $message, $channels = [])
    {
        if (empty($channels)) {
            $channels = $this->getUserPreferredChannels($userId);
        }

        $results = [];
        foreach ($channels as $channel) {
            if (isset($this->channels[$channel])) {
                $results[$channel] = $this->channels[$channel]->send($userId, $message);
            }
        }

        $this->logNotification($userId, $message, $results);
        return $results;
    }

    private function getUserPreferredChannels($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT notification_preferences 
            FROM user_profiles 
            WHERE user_id = ?
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        return json_decode($result['notification_preferences'], true) ?? ['email'];
    }
}

