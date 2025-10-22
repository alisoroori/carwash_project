<?php
declare(strict_types=1);

namespace App\Classes;

class RouteCalculator
{
    private $apiKey;
    private $baseUrl = 'https://maps.googleapis.com/maps/api/directions/json';

    public function __construct()
    {
        $this->apiKey = getenv('GOOGLE_MAPS_API_KEY');
    }

    public function calculateRoute($origin, $destination)
    {
        $params = http_build_query([
            'origin' => "{$origin['lat']},{$origin['lng']}",
            'destination' => "{$destination['lat']},{$destination['lng']}",
            'mode' => 'driving',
            'key' => $this->apiKey
        ]);

        $url = "{$this->baseUrl}?{$params}";
        $response = file_get_contents($url);

        return json_decode($response, true);
    }

    public function estimateArrivalTime($route, $departureTime = null)
    {
        if (!$departureTime) {
            $departureTime = time();
        }

        $params = http_build_query([
            'origin' => $route['origin'],
            'destination' => $route['destination'],
            'departure_time' => $departureTime,
            'traffic_model' => 'best_guess',
            'key' => $this->apiKey
        ]);

        $url = "{$this->baseUrl}?{$params}";
        $response = file_get_contents($url);

        return json_decode($response, true);
    }
}

