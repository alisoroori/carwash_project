<?php

require_once '../includes/api_bootstrap.php';


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

    // Temp debug logging
    $logFile = __DIR__ . '/../../../logs/services_debug.log';
    try {
        @file_put_contents($logFile, sprintf("[%s] api/services/get_services.php - incoming GET carwash_id=%s | SQL=%s | PARAMS=%s\n", date('Y-m-d H:i:s'), var_export($carwash_id, true), $sql, json_encode($params)), FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {}

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

    // Log returned count
    try { @file_put_contents($logFile, sprintf("[%s] api/services/get_services.php - returned %d services for carwash_id=%s\n", date('Y-m-d H:i:s'), count($services), var_export($carwash_id, true)), FILE_APPEND | LOCK_EX); } catch (Exception $e) {}

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
