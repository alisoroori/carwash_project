<?php
class ZoneValidator {
    private $conn;
    private $errors = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function validateZone($zoneData) {
        // Validate zone name
        if (empty($zoneData['name']) || strlen($zoneData['name']) > 100) {
            $this->errors[] = 'Invalid zone name';
        }

        // Validate coordinates
        if (!$this->validateCoordinates($zoneData['coordinates'])) {
            $this->errors[] = 'Invalid zone coordinates';
        }

        // Validate pricing
        if (!$this->validatePricing($zoneData['multiplier'])) {
            $this->errors[] = 'Invalid price multiplier';
        }

        return empty($this->errors);
    }

    private function validateCoordinates($coordinates) {
        if (!is_array($coordinates) || count($coordinates) < 3) {
            return false;
        }

        foreach ($coordinates as $point) {
            if (!isset($point['lat']) || !isset($point['lng'])) {
                return false;
            }
        }

        return true;
    }

    public function getErrors() {
        return $this->errors;
    }
}