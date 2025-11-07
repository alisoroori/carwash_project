<?php
require_once __DIR__ . '/backend/includes/db.php';

echo "=== Vehicle Image Paths in Database ===\n\n";

$stmt = $conn->query('SELECT id, brand, model, image_path FROM user_vehicles ORDER BY id DESC LIMIT 10');

while ($row = $stmt->fetch_assoc()) {
    $imagePath = $row['image_path'] ?? 'NULL';
    echo "ID: {$row['id']} | Brand: {$row['brand']} | Model: {$row['model']}\n";
    echo "  Image Path: {$imagePath}\n";
    
    if ($imagePath && $imagePath !== 'NULL') {
        // Check if file exists
        $fullPath = __DIR__ . $imagePath;
        $exists = file_exists($fullPath) ? '✅ EXISTS' : '❌ NOT FOUND';
        echo "  Full Path: {$fullPath}\n";
        echo "  Status: {$exists}\n";
    }
    echo "\n";
}

$conn->close();
