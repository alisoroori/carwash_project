<?php
include 'backend/includes/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Carwashes with services:\n";
    $stmt = $pdo->query('SELECT DISTINCT cp.id, cp.business_name FROM carwash_profiles cp INNER JOIN services s ON cp.id = s.carwash_id');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', Name: ' . $row['business_name'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>