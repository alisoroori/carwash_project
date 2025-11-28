<?php
include 'backend/includes/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "All carwash profiles:\n";
    $stmt = $pdo->query('SELECT id, business_name FROM carwash_profiles');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', Name: ' . $row['business_name'] . "\n";
    }

    echo "\nAll services:\n";
    $stmt = $pdo->query('SELECT id, name, carwash_id FROM services');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', Name: ' . $row['name'] . ', Carwash ID: ' . $row['carwash_id'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>