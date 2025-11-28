<?php
include 'backend/includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query('SELECT id FROM carwash_profiles WHERE user_id = 27');
    $carwash = $stmt->fetch();
    $carwashId = $carwash ? $carwash['id'] : null;
    echo 'Carwash ID: ' . $carwashId . PHP_EOL;

    if ($carwashId) {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM services WHERE carwash_id = ' . $carwashId);
        $count = $stmt->fetch()['count'];
        echo 'Services for carwash ' . $carwashId . ': ' . $count . PHP_EOL;
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>