<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    // Get search parameters
    $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
    $service_type = isset($_GET['service_type']) ? filter_var($_GET['service_type'], FILTER_SANITIZE_STRING) : null;
    $price_range = isset($_GET['price_range']) ? filter_var($_GET['price_range'], FILTER_SANITIZE_STRING) : null;
    $sort_by = isset($_GET['sort_by']) ? filter_var($_GET['sort_by'], FILTER_SANITIZE_STRING) : 'rating';
    $location = isset($_GET['location']) ? filter_var($_GET['location'], FILTER_SANITIZE_STRING) : null;

    // Build base query
    $query = "
        SELECT 
            c.id,
            c.business_name,
            c.address,
            c.lat,
            c.lng,
            c.image_url,
            c.price_range,
            COALESCE(AVG(r.rating), 0) as rating,
            COUNT(r.id) as review_count,
            c.working_hours
        FROM carwashes c
        LEFT JOIN reviews r ON c.id = r.carwash_id
        WHERE c.status = 'active'
    ";

    $params = [];
    $types = "";

    // Add filters
    if ($location) {
        $query .= " AND (c.address LIKE ? OR c.city LIKE ?)";
        $location = "%$location%";
        $params[] = $location;
        $params[] = $location;
        $types .= "ss";
    }

    if ($service_type) {
        $query .= " AND c.id IN (
            SELECT carwash_id FROM services 
            WHERE service_type = ? AND status = 'active'
        )";
        $params[] = $service_type;
        $types .= "s";
    }

    if ($price_range) {
        list($min, $max) = explode('-', $price_range);
        if ($max === '+') {
            $query .= " AND c.price_range >= ?";
            $params[] = $min;
            $types .= "i";
        } else {
            $query .= " AND c.price_range BETWEEN ? AND ?";
            $params[] = $min;
            $params[] = $max;
            $types .= "ii";
        }
    }

    // Group by to handle aggregates
    $query .= " GROUP BY c.id";

    // Add sorting
    switch ($sort_by) {
        case 'rating':
            $query .= " ORDER BY rating DESC";
            break;
        case 'price_low':
            $query .= " ORDER BY c.price_range ASC";
            break;
        case 'price_high':
            $query .= " ORDER BY c.price_range DESC";
            break;
        case 'distance':
            if ($lat && $lng) {
                $query .= " ORDER BY (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(lat)) * 
                        cos(radians(lng) - radians(?)) + 
                        sin(radians(?)) * sin(radians(lat))
                    )
                )";
                $params[] = $lat;
                $params[] = $lng;
                $params[] = $lat;
                $types .= "ddd";
            }
            break;
    }

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Format results
    $carwashes = [];
    while ($row = $result->fetch_assoc()) {
        // Add working hours availability
        $working_hours = json_decode($row['working_hours'], true);
        $current_day = strtolower(date('l'));
        $row['is_open'] = isCarWashOpen($working_hours, $current_day);

        // Format price range
        $row['price_range'] = formatPriceRange($row['price_range']);

        // Round rating to 1 decimal place
        $row['rating'] = round($row['rating'], 1);

        $carwashes[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $carwashes
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Arama sırasında bir hata oluştu.'
    ]);
}

/**
 * Check if carwash is currently open
 */
function isCarWashOpen($working_hours, $current_day)
{
    if (!isset($working_hours[$current_day])) {
        return false;
    }

    $hours = $working_hours[$current_day];
    if (!$hours['is_open']) {
        return false;
    }

    $current_time = date('H:i');
    return $current_time >= $hours['open'] && $current_time <= $hours['close'];
}

/**
 * Format price range for display
 */
function formatPriceRange($range)
{
    switch ($range) {
        case 1:
            return '₺';
        case 2:
            return '₺₺';
        case 3:
            return '₺₺₺';
        default:
            return '₺';
    }
}
