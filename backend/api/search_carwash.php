<?php
require_once '../includes/db.php';
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    // Validate and sanitize search parameters
    $search = isset($_GET['query']) ? trim($_GET['query']) : '';
    $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

    // Build base query
    $sql = "SELECT id, name, address, phone, email, latitude, longitude 
            FROM carwash 
            WHERE status = 'active'";
    $params = [];
    $types = "";

    // Add search conditions if query provided
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR address LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    // Add distance calculation if coordinates provided
    if ($lat !== null && $lng !== null) {
        $sql .= " ORDER BY (
            6371 * acos(
                cos(radians(?)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(latitude))
            )
        )";
        $params[] = $lat;
        $params[] = $lng;
        $params[] = $lat;
        $types .= "ddd";
    } else {
        $sql .= " ORDER BY name";
    }

    // Add limit
    $sql .= " LIMIT 10";

    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Format results
    $carwashes = [];
    while ($row = $result->fetch_assoc()) {
        $carwashes[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'address' => $row['address'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'location' => [
                'lat' => $row['latitude'] ? (float)$row['latitude'] : null,
                'lng' => $row['longitude'] ? (float)$row['longitude'] : null
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'results' => $carwashes
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search failed: ' . $e->getMessage()
    ]);
}
