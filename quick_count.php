<?php
require_once 'backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();
$result = $db->fetchOne("SELECT COUNT(*) as cnt FROM bookings WHERE user_id=14 AND status='completed'");
echo "Completed bookings: " . $result['cnt'];
