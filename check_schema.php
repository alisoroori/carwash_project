<?php
require_once 'backend/includes/bootstrap.php';
use App\Classes\Database;

try {
    $db = Database::getInstance();

    $tables = ['bookings', 'services', 'carwashes', 'users', 'user_vehicles'];
    foreach ($tables as $table) {
        echo "Table: $table\n";
        $columns = $db->fetchAll('DESCRIBE ' . $table);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>