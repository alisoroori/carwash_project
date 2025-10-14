<?php
header('Content-Type: application/json');
session_start();
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

class LocationSearchAPI {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function searchCarWash($params) {
        $query = "
            SELECT 
                c.*,
                cl.latitude,
                cl.longitude,
                cl.address,
                cl.district,
                cl.city
            FROM carwash c
            JOIN carwash_locations cl ON c.id = cl.carwash_id
            WHERE 1=1
        ";

        $conditions = [];
        $parameters = [];
        $types = "";

        if (!empty($params['district'])) {
            $conditions[] = "cl.district = ?";
            $parameters[] = $params['district'];
            $types .= "s";
        }

        if (!empty($params['city'])) {
            $conditions[] = "cl.city = ?";
            $parameters[] = $params['city'];
            $types .= "s";
        }

        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($query);
        if (!empty($parameters)) {
            $stmt->bind_param($types, ...$parameters);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchNearby($params) {
        $query = "
            SELECT 
                c.*,
                cl.latitude,
                cl.longitude,
                ( 6371 * acos( cos( radians(?) ) *
                cos( radians( latitude ) ) * 
                cos( radians( longitude ) - radians(?) ) +
                sin( radians(?) ) *
                sin( radians( latitude ) ) ) ) AS distance
            FROM carwash_locations cl
            JOIN carwash c ON cl.carwash_id = c.id
            WHERE 1=1
        ";

        $conditions = [];
        $parameters = [$params['lat'], $params['lng'], $params['lat']];
        $types = 'ddd';

        if (!empty($params['radius'])) {
            $conditions[] = "HAVING distance < ?";
            $parameters[] = $params['radius'];
            $types .= 'd';
        }

        if (!empty($conditions)) {
            $query .= ' ' . implode(' AND ', $conditions);
        }

        $query .= " ORDER BY distance";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$parameters);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}