<?php

class PaymentGateway {
    private $status;
    private $id;
    private $errorMessage;
    private $amount;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->status = 'pending';
        $this->errorMessage = '';
    }

    public function processPayment($amount, $cardDetails) {
        $this->amount = $amount;

        try {
            // Validate amount
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception('Invalid payment amount');
            }

            // Validate card details
            if (!$this->validateCardDetails($cardDetails)) {
                throw new Exception('Invalid card details');
            }

            // Create transaction record
            $transactionId = $this->createTransaction($amount);

            // Process payment logic here
            // In a real implementation, this would integrate with a payment provider
            $this->status = 'completed';
            $this->id = $transactionId;

            return [
                'success' => true,
                'transaction_id' => $this->id,
                'status' => $this->status,
                'amount' => $this->amount
            ];

        } catch (Exception $e) {
            $this->status = 'failed';
            $this->errorMessage = $e->getMessage();
            
            return [
                'success' => false,
                'error' => $this->errorMessage
            ];
        }
    }

    private function validateCardDetails($cardDetails) {
        // Basic validation for card details
        if (!isset($cardDetails['number']) || 
            !isset($cardDetails['expiry']) || 
            !isset($cardDetails['cvv'])) {
            return false;
        }

        // Remove spaces and dashes from card number
        $number = preg_replace('/[\s-]/', '', $cardDetails['number']);
        
        // Validate card number using Luhn algorithm
        if (!$this->validateLuhn($number)) {
            return false;
        }

        // Validate expiry date
        if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $cardDetails['expiry'])) {
            return false;
        }

        // Validate CVV (3 or 4 digits)
        if (!preg_match('/^[0-9]{3,4}$/', $cardDetails['cvv'])) {
            return false;
        }

        return true;
    }

    private function validateLuhn($number) {
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;
        
        for ($i = 0; $i < $numDigits; $i++) {
            $digit = intval($number[$i]);
            
            if ($i % 2 == $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return ($sum % 10) == 0;
    }

    private function createTransaction($amount) {
        $stmt = $this->conn->prepare("
            INSERT INTO transactions (amount, status, created_at) 
            VALUES (?, ?, NOW())
        ");

        if (!$stmt) {
            throw new Exception('Failed to prepare transaction statement');
        }

        $status = $this->status;
        $stmt->bind_param('ds', $amount, $status);

        if (!$stmt->execute()) {
            throw new Exception('Failed to create transaction record');
        }

        return $stmt->insert_id;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }
}