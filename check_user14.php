<?php
require_once 'backend/includes/bootstrap.php';
require_once 'backend/includes/db.php';

$pdo = getDBConnection();

$stmt = $pdo->query('SELECT id, image_path FROM user_vehicles WHERE user_id = 14');
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo 'Vehicles for user 14:' . PHP_EOL;
foreach ($vehicles as $v) {
    echo 'ID ' . $v['id'] . ': ' . ($v['image_path'] ?: '(empty)') . PHP_EOL;
}
?>