<?php
class LocationFilter
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function filterLocations($filters)
    {
        $query = "
            SELECT 
                c.*,
                cl.latitude,
                cl.longitude,
                cl.address,
                AVG(r.rating) as avg_rating,
                COUNT(DISTINCT b.id) as booking_count
            FROM carwash_profiles c
            JOIN carwash_locations cl ON c.id = cl.carwash_id
            LEFT JOIN reviews r ON c.id = r.carwash_id
            LEFT JOIN bookings b ON c.id = b.carwash_id
            WHERE 1=1
        ";

        $conditions = [];
        $parameters = [];
        $types = '';

        if (!empty($filters['services'])) {
            $conditions[] = "c.id IN (
                SELECT carwash_id 
                FROM carwash_services 
                WHERE service_id IN (?)
            )";
            $parameters[] = implode(',', $filters['services']);
            $types .= 's';
        }

        if (!empty($filters['rating'])) {
            $conditions[] = "avg_rating >= ?";
            $parameters[] = $filters['rating'];
            $types .= 'd';
        }

        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        $query .= " GROUP BY c.id";

        $stmt = $this->conn->prepare($query);
        if (!empty($parameters)) {
            $stmt->bind_param($types, ...$parameters);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
