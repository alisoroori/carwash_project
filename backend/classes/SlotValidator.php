<?php
declare(strict_types=1);

namespace App\Classes;

class SlotValidator
{
    private $conn;
    private $redis;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initRedis();
    }

    public function validateSlot($carwashId, $serviceIds, $datetime)
    {
        $lockKey = "booking_lock:{$carwashId}:{$datetime}";

        try {
            // Acquire lock for validation
            if (!$this->redis->set($lockKey, 1, ['NX', 'EX' => 10])) {
                throw new Exception('Slot is being booked by another user');
            }

            // Validate business hours
            if (!$this->isWithinBusinessHours($carwashId, $datetime)) {
                throw new Exception('Selected time is outside business hours');
            }

            // Check staff availability
            if (!$this->hasAvailableStaff($carwashId, $datetime, $serviceIds)) {
                throw new Exception('No available staff for selected time');
            }

            // Verify slot hasn't been taken
            if ($this->isSlotTaken($carwashId, $datetime)) {
                throw new Exception('This slot has already been booked');
            }

            return [
                'valid' => true,
                'slot_token' => $this->generateSlotToken($carwashId, $datetime)
            ];
        } finally {
            // Release lock
            $this->redis->del($lockKey);
        }
    }

    private function generateSlotToken($carwashId, $datetime)
    {
        $token = bin2hex(random_bytes(16));
        $this->redis->setex(
            "slot_token:{$token}",
            300, // 5 minutes expiry
            json_encode([
                'carwash_id' => $carwashId,
                'datetime' => $datetime,
                'created_at' => time()
            ])
        );
        return $token;
    }
}

