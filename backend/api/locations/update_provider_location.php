﻿<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

class ServiceLocationUpdater
{
    private $conn;
    private $provider_id;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->provider_id = $_SESSION['provider_id'] ?? null;
    }

    public function updateLocation($latitude, $longitude, $booking_id)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO provider_locations 
            (provider_id, booking_id, latitude, longitude, timestamp)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            'iidd',
            $this->provider_id,
            $booking_id,
            $latitude,
            $longitude
        );

        return $stmt->execute();
    }
}
