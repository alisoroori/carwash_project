<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

try {
    $db = Database::getInstance();
    $userId = 14;
    
    echo "Testing bookings table columns..." . PHP_EOL;
    
    // First, let's see what columns exist in bookings
    $stmt = $db->query("DESCRIBE bookings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Bookings table columns:" . PHP_EOL;
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")" . PHP_EOL;
    }
    
    echo PHP_EOL . "Testing simple query..." . PHP_EOL;
    
    // Try a simple query first
    $simple = $db->fetchAll("SELECT * FROM bookings WHERE user_id = :user_id LIMIT 1", ['user_id' => $userId]);
    echo "Simple query worked! Found " . count($simple) . " records" . PHP_EOL;
    
    if (count($simple) > 0) {
        print_r($simple[0]);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
