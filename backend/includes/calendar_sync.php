<?php
class CalendarSync
{
    private $conn;
    private $googleClient;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initializeGoogleClient();
    }

    public function addToCalendar($bookingId)
    {
        $booking = $this->getBookingDetails($bookingId);

        $event = [
            'summary' => 'Car Wash Appointment - ' . $booking['carwash_name'],
            'location' => $booking['carwash_address'],
            'description' => $this->formatServicesList($booking['services']),
            'start' => [
                'dateTime' => $booking['start_time'],
                'timeZone' => 'Europe/Istanbul',
            ],
            'end' => [
                'dateTime' => $booking['end_time'],
                'timeZone' => 'Europe/Istanbul',
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 60],
                ],
            ],
        ];

        try {
            $createdEvent = $this->googleClient->events->insert(
                $booking['calendar_id'],
                $event
            );

            // Store the event ID
            $stmt = $this->conn->prepare("
                UPDATE bookings 
                SET calendar_event_id = ?
                WHERE id = ?
            ");
            $stmt->bind_param('si', $createdEvent->id, $bookingId);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            error_log("Calendar sync failed: " . $e->getMessage());
            return false;
        }
    }
}
