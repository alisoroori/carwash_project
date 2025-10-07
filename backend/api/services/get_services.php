<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    // Get parameters
    $carwash_id = isset($_GET['carwash_id']) ? filter_var($_GET['carwash_id'], FILTER_VALIDATE_INT) : null;

    // Build query
    $sql = "SELECT s.id, s.name, s.description, s.price, s.duration, 
                   c.name as carwash_name, c.address as carwash_address
            FROM services s
            JOIN carwash c ON s.carwash_id = c.id
            WHERE s.status = 'active'";
    
    $params = [];
    $types = "";

    if ($carwash_id) {
        $sql .= " AND s.carwash_id = ?";
        $params[] = $carwash_id;
        $types .= "i";
    }

    $sql .= " ORDER BY s.price ASC";

    // Execute query
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Format results
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'duration' => (int)$row['duration'],
            'carwash' => [
                'name' => $row['carwash_name'],
                'address' => $row['carwash_address']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'services' => $services
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch services: ' . $e->getMessage()
    ]);
}