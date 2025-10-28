<?php
class ZoneConflictDetector {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function checkConflicts($zoneData, $carwashId) {
        // Convert coordinates to polygon format
        $polygon = $this->formatPolygon($zoneData['coordinates']);
        
        $stmt = $this->conn->prepare("
            SELECT id, name 
            FROM service_zones 
            WHERE carwash_id = ? 
            AND ST_Intersects(
                ST_GeomFromText(?),
                zone_polygon
            )
        ");

        $stmt->bind_param('is', $carwashId, $polygon);
        $stmt->execute();
        $conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'hasConflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts,
            'overlappingArea' => $this->calculateOverlap($polygon, $conflicts)
        ];
    }

    private function calculateOverlap($newPolygon, $existingZones) {
        $overlapAreas = [];
        foreach ($existingZones as $zone) {
            $stmt = $this->conn->prepare("
                SELECT ST_Area(
                    ST_Intersection(
                        ST_GeomFromText(?),
                        zone_polygon
                    )
                ) as overlap_area
                FROM service_zones
                WHERE id = ?
            ");

            $stmt->bind_param('si', $newPolygon, $zone['id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            $overlapAreas[$zone['id']] = $result['overlap_area'];
        }
        return $overlapAreas;
    }
}
