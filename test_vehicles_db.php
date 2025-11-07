<?php
require_once __DIR__ . '/backend/includes/db.php';

// Check if vehicles exist
$stmt = $conn->query('SELECT COUNT(*) as count FROM user_vehicles');
$result = $stmt->fetch_assoc();

echo "Total vehicles in database: " . $result['count'] . "\n";

// Show first 5 vehicles
$stmt = $conn->query('SELECT id, user_id, brand, model, license_plate FROM user_vehicles LIMIT 5');
echo "\nFirst 5 vehicles:\n";
while ($row = $stmt->fetch_assoc()) {
    echo "  ID: {$row['id']}, User: {$row['user_id']}, Brand: {$row['brand']}, Model: {$row['model']}, Plate: {$row['license_plate']}\n";
}

$conn->close();
