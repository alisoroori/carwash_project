﻿<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    $location = $data['location'] ?? '';
    $serviceType = $data['serviceType'] ?? '';

    // Basic input validation
    if (empty($location)) {
        throw new Exception('Location is required');
    }

    // Prepare base query
    $query = "
        SELECT 
            c.id,
            c.name,
            c.address,
            c.image_url as image,
            c.price_range,
            COALESCE(AVG(r.rating), 0) as rating,
            COUNT(r.id) as reviews
        FROM carwash c
        LEFT JOIN reviews r ON c.id = r.carwash_id
        WHERE c.status = 'active'
    ";

    $params = [];
    $types = '';

    // Add service type filter if specified
    if (!empty($serviceType)) {
        $query .= " AND EXISTS (
            SELECT 1 FROM services s 
            WHERE s.carwash_id = c.id 
            AND s.type = ?
        )";
        $params[] = $serviceType;
        $types .= 's';
    }

    // Add location-based filter
    $query .= " AND ST_Distance_Sphere(
        point(c.longitude, c.latitude),
        point(?, ?)
    ) <= 10000"; // Within 10km

    // Get coordinates from location string
    $coords = getCoordinates($location);
    $params[] = $coords['lng'];
    $params[] = $coords['lat'];
    $types .= 'dd';

    $query .= " GROUP BY c.id ORDER BY rating DESC";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $carwashes = [];
    while ($row = $result->fetch_assoc()) {
        $carwashes[] = $row;
    }

    echo json_encode([
        'success' => true,
        'carwashes' => $carwashes
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getCoordinates($location)
{
    // Implementation of geocoding service
    // This could use Google Maps API or similar
    return [
        'lat' => 0, // Replace with actual geocoding
        'lng' => 0
    ];
}
