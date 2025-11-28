<?php
include 'backend/includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get existing users (customers)
    echo '=== EXISTING CUSTOMER USERS ===' . PHP_EOL;
    $stmt = $pdo->query('SELECT id, full_name, email FROM users WHERE role = \'customer\' LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', Name: ' . $row['full_name'] . ', Email: ' . $row['email'] . PHP_EOL;
    }

    // Get existing carwash owners
    echo PHP_EOL . '=== EXISTING CARWASH OWNERS ===' . PHP_EOL;
    $stmt = $pdo->query('SELECT id, full_name, email FROM users WHERE role = \'carwash\' LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', Name: ' . $row['full_name'] . ', Email: ' . $row['email'] . PHP_EOL;
    }

    // Get existing carwashes
    echo PHP_EOL . '=== EXISTING CARWASHES ===' . PHP_EOL;
    $stmt = $pdo->query('SELECT id, business_name, city FROM carwash_profiles LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', Name: ' . $row['business_name'] . ', City: ' . $row['city'] . PHP_EOL;
    }

    // Get existing services
    echo PHP_EOL . '=== EXISTING SERVICES ===' . PHP_EOL;
    $stmt = $pdo->query('SELECT id, name, price FROM services LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', Name: ' . $row['name'] . ', Price: ' . $row['price'] . PHP_EOL;
    }

    // Get existing vehicles
    echo PHP_EOL . '=== EXISTING VEHICLES ===' . PHP_EOL;
    $stmt = $pdo->query('SELECT id, user_id, brand, model, license_plate FROM vehicles LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', User: ' . $row['user_id'] . ', Vehicle: ' . $row['brand'] . ' ' . $row['model'] . ' (' . $row['license_plate'] . ')' . PHP_EOL;
    }

    // Check time slots
    echo PHP_EOL . '=== EXISTING TIME SLOTS ===' . PHP_EOL;
    $stmt = $pdo->query('SELECT id, carwash_id, start_time, end_time FROM time_slots LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['id'] . ', Carwash: ' . $row['carwash_id'] . ', Time: ' . $row['start_time'] . ' - ' . $row['end_time'] . PHP_EOL;
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>