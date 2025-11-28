<?php
include 'backend/includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check services and their carwash_id
    echo 'Checking services...' . PHP_EOL;
    $stmt = $pdo->query('SELECT id, name, carwash_id FROM services WHERE id IN (5,6,7,17,18)');
    while ($row = $stmt->fetch()) {
        echo 'Service ID: ' . $row['id'] . ', Name: ' . $row['name'] . ', Carwash: ' . $row['carwash_id'] . PHP_EOL;
    }

    // Check carwash ID
    $stmt = $pdo->query('SELECT id FROM carwash_profiles WHERE user_id = 27');
    $carwash = $stmt->fetch();
    echo 'Carwash ID for user 27: ' . ($carwash ? $carwash['id'] : 'none') . PHP_EOL;

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>