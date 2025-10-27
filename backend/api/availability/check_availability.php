<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

class AvailabilityAPI
{
    private $conn;
    private $redis;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initRedis();
    }

    private function initRedis()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public function checkAvailability($carwashId, $serviceIds, $date)
    {
        // Try to get from cache first
        $cacheKey = "availability:{$carwashId}:{$date}";
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        // Calculate available slots
        $slots = $this->calculateAvailableSlots($carwashId, $serviceIds, $date);

        // Cache for 5 minutes
        $this->redis->setex($cacheKey, 300, json_encode($slots));

        return $slots;
    }

    private function calculateAvailableSlots($carwashId, $serviceIds, $date)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                wh.opening_time,
                wh.closing_time,
                b.appointment_datetime
            FROM working_hours wh
            LEFT JOIN bookings b ON b.carwash_id = wh.carwash_id 
                AND DATE(b.appointment_datetime) = ?
            WHERE wh.carwash_id = ?
            AND wh.day_of_week = WEEKDAY(?)
        ");

        $stmt->bind_param('sis', $date, $carwashId, $date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $this->processAvailableSlots($result);
    }
}

// Handle API request
try {
    $api = new AvailabilityAPI($conn);
    $data = json_decode(file_get_contents('php://input'), true);

    $availability = $api->checkAvailability(
        $data['carwash_id'],
        $data['service_ids'],
        $data['date']
    );

    echo json_encode([
        'success' => true,
        'availability' => $availability
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
