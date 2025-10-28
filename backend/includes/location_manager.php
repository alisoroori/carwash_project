<?php
require_once 'db.php';

class LocationManager {
    private $conn;
    private $googleMapsKey;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->googleMapsKey = getenv('GOOGLE_MAPS_API_KEY');
    }

    public function findNearbyCarWash($latitude, $longitude, $radius = 5) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.*,
                ( 6371 * acos( cos( radians(?) ) *
                cos( radians( latitude ) ) * 
                cos( radians( longitude ) - radians(?) ) +
                sin( radians(?) ) *
                sin( radians( latitude ) ) ) ) AS distance
            FROM carwash_locations c
            HAVING distance < ?
            ORDER BY distance
        ");

        $stmt->bind_param('dddd', $latitude, $longitude, $latitude, $radius);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
