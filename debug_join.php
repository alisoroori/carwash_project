<?php
include 'backend/includes/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Testing carwash-services join...\n";

    $stmt = $pdo->query('SELECT cp.id, cp.business_name, s.id as service_id, s.name as service_name
                        FROM carwash_profiles cp
                        INNER JOIN services s ON cp.id = s.carwash_id');
    $results = $stmt->fetchAll();

    if (empty($results)) {
        echo "No results from join query!\n";

        // Check carwash_profiles
        $stmt = $pdo->query('SELECT id, business_name FROM carwash_profiles');
        $carwashes = $stmt->fetchAll();
        echo "Carwashes:\n";
        foreach ($carwashes as $cw) {
            echo "  ID: {$cw['id']}, Name: {$cw['business_name']}\n";
        }

        // Check services
        $stmt = $pdo->query('SELECT id, name, carwash_id FROM services');
        $services = $stmt->fetchAll();
        echo "Services:\n";
        foreach ($services as $s) {
            echo "  ID: {$s['id']}, Name: {$s['name']}, Carwash ID: {$s['carwash_id']}\n";
        }
    } else {
        echo "Join results:\n";
        foreach ($results as $row) {
            echo "  Carwash: {$row['business_name']} (ID: {$row['id']}) -> Service: {$row['service_name']} (ID: {$row['service_id']})\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>