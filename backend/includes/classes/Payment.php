<?php

class Payment {
    private $id;
    private $status;
    private $amount;
    private $customerId;
    private $bookingId;
    private $createdAt;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->status = $data['status'] ?? 'pending';
        $this->amount = $data['amount'] ?? 0;
    $this->customerId = $data['user_id'] ?? $data['customer_id'] ?? null;
        $this->bookingId = $data['booking_id'] ?? null;
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    public function getId() {
        return $this->id;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getCustomerId() {
        return $this->customerId;
    }

    public function getBookingId() {
        return $this->bookingId;
    }

    public function save() {
        global $conn;
        
        if ($this->id) {
            // Update existing payment
            $stmt = $conn->prepare("UPDATE payments SET 
                status = ?, 
                amount = ?, 
                user_id = ?, 
                booking_id = ? 
                WHERE id = ?");
                
            return $stmt->execute([
                $this->status,
                $this->amount,
                $this->customerId,
                $this->bookingId,
                $this->id
            ]);
        } else {
            // Insert new payment
            $stmt = $conn->prepare("INSERT INTO payments 
                (status, amount, user_id, booking_id, created_at) 
                VALUES (?, ?, ?, ?, ?)");
                
            return $stmt->execute([
                $this->status,
                $this->amount,
                $this->customerId,
                $this->bookingId,
                $this->createdAt
            ]);
        }
    }
}
