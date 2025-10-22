<?php
declare(strict_types=1);

namespace App\Classes;

class ETACalculator
{
    private $conn;
    private $googleMapsKey;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->googleMapsKey = getenv('GOOGLE_MAPS_API_KEY');
    }

    public function calculateETA($serviceId)
    {
        // Get current service location
        $currentLocation = $this->getCurrentLocation($serviceId);

        // Get destination
        $destination = $this->getServiceDestination($serviceId);

        // Calculate using Google Maps Distance Matrix API
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json";
        $params = http_build_query([
            'origins' => "{$currentLocation['lat']},{$currentLocation['lng']}",
            'destinations' => "{$destination['lat']},{$destination['lng']}",
            'mode' => 'driving',
            'departure_time' => 'now',
            'key' => $this->googleMapsKey
        ]);

        $response = file_get_contents("$url?$params");
        $data = json_decode($response, true);

        if ($data['status'] === 'OK') {
            return [
                'duration' => $data['rows'][0]['elements'][0]['duration']['value'],
                'distance' => $data['rows'][0]['elements'][0]['distance']['value'],
                'arrival_time' => time() + $data['rows'][0]['elements'][0]['duration']['value']
            ];
        }

        throw new Exception('Failed to calculate ETA');
    }
}

