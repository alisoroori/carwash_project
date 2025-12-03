<?php
// tests/toggle_sync_test.php
// Usage: php tests\toggle_sync_test.php [carwash_id]
require_once __DIR__ . '/../backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();
$pdo = $db->getPdo();

// Create temporary user + carwash for safe testing (will be cleaned up)
$tempUserId = null;
$tempCarwashId = null;
try {
    $pdo->beginTransaction();
    $username = 'ci_toggle_' . bin2hex(random_bytes(4));
    $email = $username . '@local.test';
    $pwd = password_hash('secret', PASSWORD_DEFAULT);
    $insUser = $pdo->prepare('INSERT INTO users (username, email, password, role, created_at) VALUES (:u,:e,:p,\'carwash\', NOW())');
    $insUser->execute(['u' => $username, 'e' => $email, 'p' => $pwd]);
    $tempUserId = (int)$pdo->lastInsertId();

    $cwName = 'CI Toggle Test ' . bin2hex(random_bytes(3));
    $insCw = $pdo->prepare('INSERT INTO carwashes (user_id, name, status, is_active, created_at, updated_at) VALUES (:uid, :name, :status, :ia, NOW(), NOW())');
    $insCw->execute(['uid' => $tempUserId, 'name' => $cwName, 'status' => 'Kapalı', 'ia' => 0]);
    $tempCarwashId = (int)$pdo->lastInsertId();
    $pdo->commit();
    echo "Created temp user={$tempUserId} carwash={$tempCarwashId}\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Setup failed: " . $e->getMessage() . "\n";
    exit(2);
}

function logline($msg) { echo '[' . date('Y-m-d H:i:s') . "] " . $msg . PHP_EOL; }

try {
    $carwashId = $tempCarwashId;
    $orig = $db->fetchOne('SELECT id, name, status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE id = :id', ['id' => $carwashId]);
    if (!$orig) throw new Exception('Carwash not found: ' . $carwashId);
    logline("Initial: id={$orig['id']} name='{$orig['name']}' status='{$orig['status']}' is_active={$orig['is_active']}");

    // Define visibility query used by Customer Dashboard
    $visibilitySql = "SELECT id, name FROM carwashes WHERE LOWER(COALESCE(status,'')) IN ('açık','acik','open','active','1') AND COALESCE(is_active,0) = 1 ORDER BY name";

    // Helper to check visibility
    $checkVisibility = function() use ($db, $visibilitySql, $carwashId) {
        $rows = $db->fetchAll($visibilitySql);
        $ids = array_map(function($r){ return (int)$r['id']; }, $rows);
        return in_array($carwashId, $ids, true);
    };

    // 1) Toggle ON (Açık / 1)
    logline('Action: Set to Açık / is_active=1');
    $upd = $pdo->prepare('UPDATE carwashes SET status = :s, is_active = :ia, updated_at = NOW() WHERE id = :id');
    $upd->execute(['s' => 'Açık', 'ia' => 1, 'id' => $carwashId]);
    $afterOn = $db->fetchOne('SELECT id, name, status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE id = :id', ['id' => $carwashId]);
    logline("DB after ON: status='{$afterOn['status']}' is_active={$afterOn['is_active']}");
    $visibleOn = $checkVisibility();
    logline('Customer Dashboard visibility after ON: ' . ($visibleOn ? 'VISIBLE' : 'NOT VISIBLE'));

    // 2) Toggle OFF (Kapalı / 0)
    logline('Action: Set to Kapalı / is_active=0');
    $upd2 = $pdo->prepare('UPDATE carwashes SET status = :s, is_active = :ia, updated_at = NOW() WHERE id = :id');
    $upd2->execute(['s' => 'Kapalı', 'ia' => 0, 'id' => $carwashId]);
    $afterOff = $db->fetchOne('SELECT id, name, status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE id = :id', ['id' => $carwashId]);
    logline("DB after OFF: status='{$afterOff['status']}' is_active={$afterOff['is_active']}");
    $visibleOff = $checkVisibility();
    logline('Customer Dashboard visibility after OFF: ' . ($visibleOff ? 'VISIBLE' : 'NOT VISIBLE'));

    // Restore original and cleanup
    logline('Restoring original status and cleaning up');
    $r = $pdo->prepare('UPDATE carwashes SET status = :s, is_active = :ia, updated_at = NOW() WHERE id = :id');
    $r->execute(['s' => $orig['status'], 'ia' => (int)$orig['is_active'], 'id' => $carwashId]);
    // Remove temp rows
    try {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM carwashes WHERE id = :id')->execute(['id' => $carwashId]);
        if ($tempUserId) $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $tempUserId]);
        $pdo->commit();
        logline('Cleanup done.');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        logline('Cleanup failed: ' . $e->getMessage());
    }

    logline('Test complete.');
} catch (Exception $e) {
    logline('ERROR: ' . $e->getMessage());
    exit(1);
}
