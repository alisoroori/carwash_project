<?php
require_once 'backend/includes/db.php';

$stmt = $pdo->prepare('SELECT id, brand, model, license_plate, year, color, image_path, created_at FROM user_vehicles WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([14]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['vehicles' => $rows], JSON_PRETTY_PRINT);
?>