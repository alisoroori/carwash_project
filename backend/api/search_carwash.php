<?php
require_once '../includes/db.php';
require_once '../includes/config.php';

// Ensure $conn is a PDO instance from db.php
if (!isset($conn) || !($conn instanceof PDO)) {
    throw new Exception('Database connection error.');
}

header('Content-Type: application/json');

try {
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

    $sql = "SELECT id, name, address, phone, latitude, longitude 
            FROM carwash 
            WHERE status = 'active'";
    $params = [];

    if ($query) {
        $sql .= " AND (name LIKE ? OR address LIKE ?)";
        $searchTerm = "%$query%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($lat && $lng) {
        $sql .= " ORDER BY (
            POW(69.1 * (latitude - ?), 2) +
            POW(69.1 * (? - longitude) * COS(latitude / 57.3), 2)
        ) ASC";
        $params[] = $lat;
        $params[] = $lng;
    } else {
        $sql .= " ORDER BY name ASC";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $carwashes = [];
    if ($stmt) {
        $carwashes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'carwashes' => $carwashes
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
