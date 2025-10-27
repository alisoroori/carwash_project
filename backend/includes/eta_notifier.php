<?php
declare(strict_types=1);

namespace App\Classes;

class ETANotifier
{
    private \mysqli $conn;
    private string $table = 'eta_notifications';

    /**
     * Note: This table uses foreign keys to bookings(id) and users(id).
     * Ensure the bookings and users tables exist before creating eta_notifications.
     */
    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->initNotificationTable();
    }

    /**
     * Create table if not exists. Foreign keys require bookings and users tables.
     */
    private function initNotificationTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            booking_id INT NOT NULL,
            user_id INT NOT NULL,
            eta_minutes INT NOT NULL,
            notification_type ENUM('email','sms','push') NOT NULL DEFAULT 'email',
            status ENUM('pending','sent','failed') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX (user_id),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        @$this->conn->query($sql); // ignore errors here; caller environment may handle migrations separately
    }

    /**
     * Insert ETA notification and dispatch it.
     *
     * @param int $bookingId
     * @param int $etaMinutes
     * @return bool true on success, false otherwise
     */
    public function sendETAUpdate(int $bookingId, int $etaMinutes): bool
    {
        // Retrieve booking details (stubbed / safe)
        $booking = $this->getBookingDetails($bookingId);
        if (empty($booking) || empty($booking['user_id'])) {
            error_log("ETANotifier: booking not found or missing user_id for booking {$bookingId}");
            return false;
        }

        $userId = (int)$booking['user_id'];
        $notificationType = $this->getPreferredNotificationType($userId);

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table} 
            (booking_id, user_id, eta_minutes, notification_type)
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt === false) {
            error_log('ETANotifier: prepare failed - ' . $this->conn->error);
            return false;
        }

        // Bind parameters and execute
        if (!$stmt->bind_param('iiis', $bookingId, $userId, $etaMinutes, $notificationType)) {
            error_log('ETANotifier: bind_param failed - ' . $stmt->error);
            $stmt->close();
            return false;
        }

        if (!$stmt->execute()) {
            error_log('ETANotifier: execute failed - ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $notificationId = $stmt->insert_id;
        $stmt->close();

        // Dispatch notification (stubbed implementation)
        $dispatched = $this->dispatchNotification((int)$notificationId, $booking, $etaMinutes);
        if ($dispatched) {
            // mark as sent
            $update = $this->conn->prepare("UPDATE {$this->table} SET status = 'sent' WHERE id = ?");
            if ($update) {
                $update->bind_param('i', $notificationId);
                $update->execute();
                $update->close();
            }
            return true;
        }

        // Optionally mark as failed
        $update = $this->conn->prepare("UPDATE {$this->table} SET status = 'failed' WHERE id = ?");
        if ($update) {
            $update->bind_param('i', $notificationId);
            $update->execute();
            $update->close();
        }

        return false;
    }

    /**
     * Fetch booking details for the given booking id.
     * Stubbed to avoid undefined method errors. Uses prepared statement.
     *
     * @param int $bookingId
     * @return array associative booking data or empty array if not found
     */
    private function getBookingDetails(int $bookingId): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, user_id, appointment_datetime, carwash_id
            FROM bookings
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmt === false) {
            error_log('ETANotifier::getBookingDetails prepare failed - ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $bookingId);
        if (!$stmt->execute()) {
            error_log('ETANotifier::getBookingDetails execute failed - ' . $stmt->error);
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        if (!$result) {
            $stmt->close();
            return [];
        }

        $booking = $result->fetch_assoc() ?: [];
        $stmt->close();

        return $booking;
    }

    /**
     * Determine the user's preferred notification channel.
     * Stubbed: attempts to read notification_preferences table, otherwise defaults to 'email'.
     *
     * @param int $userId
     * @return string one of 'email','sms','push'
     */
    private function getPreferredNotificationType(int $userId): string
    {
        $stmt = $this->conn->prepare("
            SELECT channel_email, channel_sms, channel_push
            FROM notification_preferences
            WHERE user_id = ?
            LIMIT 1
        ");

        if ($stmt === false) {
            // Default fallback
            return 'email';
        }

        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            return 'email';
        }

        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $stmt->close();
            if (!empty($row['channel_sms'])) return 'sms';
            if (!empty($row['channel_push'])) return 'push';
            return 'email';
        }

        $stmt->close();
        return 'email';
    }

    /**
     * Dispatch the notification to the preferred channel.
     * This is a minimal stub to avoid undefined method errors.
     *
     * @param int $notificationId
     * @param array $booking
     * @param int $etaMinutes
     * @return bool true if dispatch simulated as successful
     */
    private function dispatchNotification(int $notificationId, array $booking, int $etaMinutes): bool
    {
        // In real implementation:
        // - Load user's contact info
        // - Format message
        // - Send via email/SMS/push and record delivery result
        //
        // This stub logs and returns true to indicate success.
        $userId = (int)($booking['user_id'] ?? 0);
        error_log("ETANotifier::dispatchNotification - notificationId={$notificationId}, bookingId={$booking['id']}, userId={$userId}, etaMinutes={$etaMinutes}");
        return true;
    }
}
