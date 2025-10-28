<?php
require_once 'db.php';
class PaymentNotifier
{
    private $conn;
    private $mailer;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initNotificationTable();
    }

    private function initNotificationTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS payment_notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            payment_id VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            type ENUM('success', 'failed', 'pending', 'refund') NOT NULL,
            message TEXT,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX (payment_id)
        )";

        $this->conn->query($sql);
    }

    public function sendNotification($paymentData)
    {
        // Get user preferences
        $userPrefs = $this->getUserNotificationPreferences($paymentData['user_id']);

        // Send notifications based on preferences
        foreach ($userPrefs as $channel => $enabled) {
            if ($enabled) {
                $this->dispatchNotification($channel, $paymentData);
            }
        }
    }
}
