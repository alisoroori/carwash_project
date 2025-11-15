<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Database;
use App\Classes\Auth;

if (session_status() === PHP_SESSION_NONE) session_start();
try {
    Auth::requireAuth();
} catch (Exception $e) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $db = Database::getInstance();

    $rows = $db->fetchAll(
        'SELECT id, business_name AS name, address, city, district, contact_phone AS phone, average_rating AS rating, verified, profile_image AS logo FROM carwash_profiles ORDER BY business_name'
    );

    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'id' => (int)$r['id'],
            'name' => $r['name'] ?? '',
            'address' => $r['address'] ?? '',
            'city' => $r['city'] ?? '',
            'district' => $r['district'] ?? '',
            'phone' => $r['phone'] ?? '',
            'rating' => isset($r['rating']) ? (float)$r['rating'] : null,
            'verified' => !empty($r['verified']) ? true : false,
            'logo' => !empty($r['logo']) ? $r['logo'] : null
        ];
    }

    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal server error']);
}
