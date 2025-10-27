<?php declare(strict_types=1);

namespace App\Classes;

class CalendarSync
{
    private \mysqli $conn;
    private $googleClient;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->initializeGoogleClient();
    }

    /**
     * Add a booking to the calendar.
     *
     * @param int $bookingId
     * @return bool true on success, false on failure
     */
    public function addToCalendar(int $bookingId): bool
    {
        $booking = $this->getBookingDetails($bookingId);
        if (empty($booking)) {
            error_log("CalendarSync: booking not found: {$bookingId}");
            return false;
        }

        $event = [
            'summary'     => 'Car Wash Appointment - ' . ($booking['carwash_name'] ?? 'Appointment'),
            'location'    => $booking['carwash_address'] ?? '',
            'description' => $this->formatServicesList($booking['services'] ?? []),
            'start'       => [
                'dateTime' => $booking['start_datetime'] ?? null,
                'timeZone' => $booking['timezone'] ?? 'UTC',
            ],
            'end' => [
                'dateTime' => $booking['end_datetime'] ?? null,
                'timeZone' => $booking['timezone'] ?? 'UTC',
            ],
        ];

        // If a Google client exists, attempt to insert the event.
        if ($this->googleClient && method_exists($this->googleClient, 'events')) {
            try {
                $service = new \Google_Service_Calendar($this->googleClient);
                $gEvent = new \Google_Service_Calendar_Event($event);
                $calendarId = 'primary';
                $service->events->insert($calendarId, $gEvent);
                return true;
            } catch (\Throwable $e) {
                error_log('CalendarSync: failed to add event - ' . $e->getMessage());
                return false;
            }
        }

        // No Google client available — log and return false.
        error_log('CalendarSync: Google client not initialized; event not created.');
        return false;
    }

    /**
     * Initialize Google client.
     * Stubbed so VS Code won't report missing methods; real init should load credentials.
     */
    private function initializeGoogleClient(): void
    {
        // Real implementation: load service account / OAuth2 credentials and configure scopes.
        // Keep stubbed to avoid runtime errors in environments without Google SDK.
        $this->googleClient = null;
        // Example (uncomment when Google SDK and credentials are available):
        // $client = new \Google_Client();
        // $client->setAuthConfig('/path/to/credentials.json');
        // $client->addScope(\Google_Service_Calendar::CALENDAR);
        // $this->googleClient = $client;
    }

    /**
     * Fetch booking details from the database.
     * Minimal safe implementation using prepared statements.
     *
     * @param int $bookingId
     * @return array
     */
    private function getBookingDetails(int $bookingId): array
    {
        $sql = "SELECT b.id, b.user_id, b.start_datetime, b.end_datetime, b.timezone,
                       cw.name AS carwash_name, cw.address AS carwash_address
                FROM bookings b
                LEFT JOIN carwashes cw ON cw.id = b.carwash_id
                WHERE b.id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log('CalendarSync::getBookingDetails prepare failed - ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $bookingId);
        if (!$stmt->execute()) {
            error_log('CalendarSync::getBookingDetails execute failed - ' . $stmt->error);
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

        // Attach services list if available (simple stub: replace with real query if needed)
        $booking['services'] = $this->fetchBookingServices($bookingId);

        return $booking;
    }

    /**
     * Fetch services for a booking. Stubbed implementation.
     *
     * @param int $bookingId
     * @return array
     */
    private function fetchBookingServices(int $bookingId): array
    {
        $sql = "SELECT s.name FROM booking_services bs
                JOIN services s ON s.id = bs.service_id
                WHERE bs.booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('i', $bookingId);
        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }
        $res = $stmt->get_result();
        $services = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $services[] = $row['name'] ?? '';
            }
        }
        $stmt->close();
        return $services;
    }

    /**
     * Format services list into a human-friendly description.
     *
     * @param array $services
     * @return string
     */
    private function formatServicesList(array $services): string
    {
        if (empty($services)) {
            return 'No services specified.';
        }
        return implode(', ', $services);
    }
}

