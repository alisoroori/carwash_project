<?php
include 'backend/includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if image_path column exists
    $stmt = $pdo->query('DESCRIBE vehicles');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!in_array('image_path', $columns)) {
        echo 'Adding image_path column to vehicles table...' . PHP_EOL;
        $pdo->exec('ALTER TABLE vehicles ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER notes');
        echo 'Column added successfully.' . PHP_EOL;
    } else {
        echo 'image_path column already exists.' . PHP_EOL;
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>