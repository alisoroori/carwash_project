<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Classes\Database;

try {
    $db = Database::getInstance();
    $data = [
        'user_id' => 1,
        'carwash_id' => 1,
        'service_id' => 1,
        'booking_date' => date('Y-m-d'),
        'booking_time' => date('H:i'),
        'status' => 'pending',
        'total_price' => 99.99,
        'notes' => 'E2E test booking',
        'created_at' => date('Y-m-d H:i:s')
    ];
    $id = $db->insert('bookings', $data);
    echo json_encode(['success' => true, 'id' => $id]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
