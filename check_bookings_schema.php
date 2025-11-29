<?php
$pdo = new PDO("mysql:host=localhost;dbname=carwash_db", 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Checking bookings table schema ===\n\n";

$stmt = $pdo->query("DESCRIBE bookings");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Columns in bookings table:\n";
foreach ($columns as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}

echo "\n";

// Check if vehicle_id column exists
$vehicleIdExists = false;
foreach ($columns as $col) {
    if ($col['Field'] === 'vehicle_id') {
        $vehicleIdExists = true;
        break;
    }
}

if ($vehicleIdExists) {
    echo "✅ vehicle_id column EXISTS\n";
} else {
    echo "❌ vehicle_id column DOES NOT EXIST\n";
    echo "\nLooking for alternative columns...\n";
    foreach ($columns as $col) {
        if (stripos($col['Field'], 'vehicle') !== false || stripos($col['Field'], 'car') !== false) {
            echo "  Found: {$col['Field']}\n";
        }
    }
}
