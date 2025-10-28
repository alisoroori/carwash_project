<?php
declare(strict_types=1);

namespace App\Classes;

class AvailabilityMonitor
{
    private $conn;
    private $redis;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initRedisConnection();
    }

    private function initRedisConnection()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public function updateAvailability($carwashId, $timestamp)
    {
        $key = "availability:{$carwashId}:" . date('Y-m-d', strtotime($timestamp));

        $currentBookings = $this->getCurrentBookings($carwashId, $timestamp);
        $maxCapacity = $this->getMaxCapacity($carwashId, $timestamp);

        $availability = [
            'available_slots' => $maxCapacity - $currentBookings,
            'last_updated' => time(),
            'is_available' => ($maxCapacity - $currentBookings) > 0
        ];

        $this->redis->set($key, json_encode($availability));
        $this->broadcastUpdate($carwashId, $availability);

        return $availability;
    }

    private function broadcastUpdate($carwashId, $availability)
    {
        // WebSocket implementation for real-time updates
        $ws = new WebSocketServer();
        $ws->broadcast('availability_update', [
            'carwash_id' => $carwashId,
            'data' => $availability
        ]);
    }
}

