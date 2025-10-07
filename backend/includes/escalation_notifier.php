<?php
class EscalationNotifier {
    private $conn;
    private $mailer;
    private $notificationChannels = ['email', 'sms', 'slack'];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->initNotificationTable();
    }

    private function initNotificationTable() {
        $sql = "CREATE TABLE IF NOT EXISTS escalation_notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ticket_id INT,
            escalation_level VARCHAR(50),
            notification_type VARCHAR(20),
            recipient VARCHAR(255),
            status ENUM('pending', 'sent', 'failed'),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
        )";
        
        $this->conn->query($sql);
    }

    public function notifyEscalation($ticketId, $level) {
        $ticket = $this->getTicketDetails($ticketId);
        $recipients = $this->getRecipientsByLevel($level);

        foreach ($recipients as $recipient) {
            foreach ($this->notificationChannels as $channel) {
                $this->sendNotification($channel, $recipient, $ticket);
            }
        }
    }
}