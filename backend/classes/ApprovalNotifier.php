<?php
declare(strict_types=1);

namespace App\Classes;

class ApprovalNotifier
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Notify about an approval event for a booking.
     *
     * @param int $bookingId
     * @return bool true on success, false on failure
     */
    public function notifyApproval(int $bookingId): bool
    {
        // TODO: implement actual notification logic (email/SMS/push)
        error_log("ApprovalNotifier: notifyApproval called for booking {$bookingId}");
        return true;
    }
}

