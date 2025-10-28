<?php
class MapsConfig
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = getenv('GOOGLE_MAPS_API_KEY');
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getMapSettings()
    {
        return [
            'center' => [
                'lat' => 41.0082, // Istanbul default center
                'lng' => 28.9784
            ],
            'zoom' => 12,
            'mapTypeId' => 'roadmap'
        ];
    }
}
