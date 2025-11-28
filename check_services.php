<?php
include 'backend/includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query('SELECT id, name, price, carwash_id FROM services');
    while ($row = $stmt->fetch()) {
        echo 'Service ID: ' . $row['id'] . ', Name: ' . $row['name'] . ', Carwash ID: ' . $row['carwash_id'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>