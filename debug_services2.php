<?php
include 'backend/includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check services for carwash 6
    echo 'Services for carwash 6:' . PHP_EOL;
    $stmt = $pdo->query('SELECT id, name FROM services WHERE carwash_id = 6');
    $services = $stmt->fetchAll();
    if (empty($services)) {
        echo 'No services found for carwash 6' . PHP_EOL;
    } else {
        foreach ($services as $service) {
            echo 'ID: ' . $service['id'] . ', Name: ' . $service['name'] . PHP_EOL;
        }
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>