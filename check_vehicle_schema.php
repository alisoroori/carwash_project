<?php
require_once 'backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();

echo "Checking vehicles table schema:\n";
echo str_repeat("=", 50) . "\n";

$result = $db->query('SHOW COLUMNS FROM vehicles');
foreach ($result as $col) {
    echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . "\n";
}

echo "\nChecking user_vehicles table schema:\n";
echo str_repeat("=", 50) . "\n";

$result2 = $db->query('SHOW COLUMNS FROM user_vehicles');
foreach ($result2 as $col) {
    echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . "\n";
}
