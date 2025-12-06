<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Database;

$db = Database::getInstance();

echo "=== Carwash Status Check ===\n\n";

// Get all carwashes with their status
$rows = $db->fetchAll('SELECT id, name, status, is_active FROM carwashes ORDER BY id LIMIT 20');

echo "Current carwashes in database:\n";
foreach ($rows as $row) {
    echo sprintf(
        "ID: %d | Name: %s | Status: '%s' | is_active: %s\n",
        $row['id'],
        $row['name'] ?? 'N/A',
        $row['status'] ?? 'NULL',
        $row['is_active'] ?? 'NULL'
    );
}

echo "\n=== Testing Visibility Query ===\n";

// Test the visibility query
$sql = "SELECT id, name, status FROM carwashes
        WHERE (
            status = 'Açık'
            OR LOWER(COALESCE(status,'')) IN ('açık','acik','open','active')
            OR status = '1'
        )
          AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive')
          AND COALESCE(status,'') != '0'
        ORDER BY name";

$visible = $db->fetchAll($sql);

echo "Visible carwashes (should appear to customers): " . count($visible) . "\n";
foreach ($visible as $row) {
    echo sprintf("  - ID: %d | Name: %s | Status: '%s'\n", $row['id'], $row['name'] ?? 'N/A', $row['status'] ?? 'NULL');
}

// Count distinct status values
echo "\n=== Status Value Distribution ===\n";
$stats = $db->fetchAll("SELECT status, COUNT(*) as cnt FROM carwashes GROUP BY status");
foreach ($stats as $s) {
    echo sprintf("Status '%s': %d carwashes\n", $s['status'] ?? 'NULL', $s['cnt']);
}
