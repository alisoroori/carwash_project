<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Classes\Database;
$db = Database::getInstance();
$pdo = $db->getPdo();

function runTest($pdo, $db, $name, $status, $shouldBeVisible) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO carwashes (name, status, created_at, updated_at) VALUES (:name, :status, NOW(), NOW())");
        $stmt->execute(['name' => $name, 'status' => $status]);
        $id = $pdo->lastInsertId();

        $sql = "SELECT id FROM carwashes WHERE (status = 'Açık' OR LOWER(COALESCE(status,'')) IN ('açık','acik','open','active') OR status = '1') AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive') AND COALESCE(status,'') != '0'";
        $rows = $db->fetchAll($sql);
        $ids = array_map(function($r){ return (int)$r['id']; }, $rows);
        $visible = in_array((int)$id, $ids, true);
        $result = ($visible === $shouldBeVisible) ? 'PASS' : 'FAIL';
        echo sprintf("Test %s (status='%s') => expected visible=%s => %s\n", $name, $status, $shouldBeVisible ? 'true' : 'false', $result);

        $pdo->rollBack();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error running test: " . $e->getMessage() . "\n";
    }
}

// Run tests
runTest($pdo, $db, 'TEST_OPEN', 'Açık', true);
runTest($pdo, $db, 'TEST_ACIK_lower', 'acik', true);
runTest($pdo, $db, 'TEST_OPEN_legacy', 'open', true);
runTest($pdo, $db, 'TEST_ACTIVE_legacy', 'active', true);
runTest($pdo, $db, 'TEST_NUMERIC', '1', true);
runTest($pdo, $db, 'TEST_CLOSED', 'Kapalı', false);

echo "Done tests.\n";
