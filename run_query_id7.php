<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Classes\Database;
$db = Database::getInstance();
try {
    $row = $db->fetchOne('SELECT id, name, is_active, status FROM carwashes WHERE id = :id', ['id' => 7]);
    if (!$row) {
        echo "No row found for id=7\n";
        exit(0);
    }
    echo sprintf("ID: %d\nName: %s\nstatus (raw): '%s'\nis_active: %s\n", $row['id'], $row['name'] ?? '', $row['status'] ?? 'NULL', ($row['is_active'] ?? 'NULL'));
} catch (Exception $e) {
    echo "Query error: " . $e->getMessage() . "\n";
    exit(1);
}
