<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    $carwash_id = isset($_GET['carwash_id']) ? filter_var($_GET['carwash_id'], FILTER_VALIDATE_INT) : null;
    
    $sql = "SELECT id, name, description, price, duration, status 
            FROM services 
            WHERE status = 'active'";
    
    if ($carwash_id) {
        $sql .= " AND carwash_id = ?";
    }

    $stmt = $conn->prepare($sql);
    
    if ($carwash_id) {
        $stmt->bind_param('i', $carwash_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'duration' => (int)$row['duration'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode(['success' => true, 'services' => $services]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch services: ' . $e->getMessage()
    ]);
}