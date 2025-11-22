<?php
require_once __DIR__ . '/../backend/includes/bootstrap.php';
use App\Classes\Database;
$db = Database::getInstance();
$b = $db->fetchOne('SELECT id,user_id,carwash_id,service_id,booking_date,booking_time,status,created_at FROM bookings ORDER BY id DESC LIMIT 1');
echo json_encode($b, JSON_PRETTY_PRINT);
