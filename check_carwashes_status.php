<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();
$rows = $db->fetchAll('SELECT id, user_id, name, status, COALESCE(is_active,0) as is_active FROM carwashes ORDER BY id LIMIT 15');

echo "=== Current Carwashes Status ===\n";
foreach ($rows as $r) {
    echo sprintf("ID=%-3d user=%-3d status=%-10s is_active=%d  name=%s\n", 
        $r['id'], $r['user_id'], $r['status'] ?? 'NULL', $r['is_active'], $r['name']);
}

echo "\n=== Customer Dashboard Visible (status IN open tokens AND is_active=1) ===\n";
$visible = $db->fetchAll("SELECT id, name, status, COALESCE(is_active,0) as is_active FROM carwashes WHERE LOWER(COALESCE(status,'')) IN ('açık','acik','open','active','1') AND COALESCE(is_active,0) = 1 ORDER BY name");
foreach ($visible as $v) {
    echo sprintf("ID=%-3d status=%-10s is_active=%d  name=%s\n", $v['id'], $v['status'], $v['is_active'], $v['name']);
}
if (empty($visible)) {
    echo "(No visible carwashes)\n";
}
