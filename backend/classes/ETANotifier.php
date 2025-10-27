<?php
declare(strict_types=1);

namespace App\Classes;

class ETANotifier
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
        $sql = "CREATE TABLE IF NOT EXISTS eta_notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            booking_id INT NOT NULL,
            user_id INT NOT NULL,
            eta_minutes INT NOT NULL,
            notification_type ENUM('email', 'sms', 'push') NOT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        $this->conn->query($sql);
    }

    public function sendETAUpdate($bookingId, $etaMinutes)
    {
        $booking = $this->getBookingDetails($bookingId);

        // Create notification
        $stmt = $this->conn->prepare("
            INSERT INTO eta_notifications 
            (booking_id, user_id, eta_minutes, notification_type)
            VALUES (?, ?, ?, ?)
        ");

        $notificationType = $this->getPreferredNotificationType($booking['user_id']);
        $stmt->bind_param(
            'iiis',
            $bookingId,
            $booking['user_id'],
            $etaMinutes,
            $notificationType
        );

        if ($stmt->execute()) {
            return $this->dispatchNotification(
                $stmt->insert_id,
                $booking,
                $etaMinutes
            );
        }

        return false;
    }
}

