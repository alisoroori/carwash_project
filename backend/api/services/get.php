<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    $carwash_id = isset($_GET['carwash_id']) ?
        filter_var($_GET['carwash_id'], FILTER_VALIDATE_INT) : null;

    $sql = "SELECT s.*, c.name as carwash_name 
            FROM services s
            JOIN carwash c ON s.carwash_id = c.id
            WHERE s.status = 'active'";

    $params = [];
    if ($carwash_id) {
        $sql .= " AND s.carwash_id = ?";
        $params[] = $carwash_id;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'duration' => (int)$row['duration'],
            'carwash_id' => (int)$row['carwash_id'],
            'carwash_name' => $row['carwash_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
