<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
$db = App\Classes\Database::getInstance();
try {
    $statuses = $db->fetchAll("SELECT DISTINCT status FROM carwashes ORDER BY status");
    echo "Distinct statuses:\n";
    echo json_encode($statuses, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
    $rows = $db->fetchAll("SELECT id, name, status, is_active FROM carwashes ORDER BY name LIMIT 10");
    echo "Sample carwashes (first 10):\n";
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

    // Run the new filter used by customer dashboard (Kapalı overrides is_active)
    $filterSql = "SELECT id, name, status, is_active FROM carwashes WHERE (status IN ('Açık','open','active') OR (COALESCE(is_active,0) = 1 AND COALESCE(status,'') NOT IN ('Kapalı'))) ORDER BY name";
    $filtered = $db->fetchAll($filterSql);
    echo "Carwashes matching open-indicators filter:\n";
    echo json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
