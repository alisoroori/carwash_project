<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Classes\Database;
$db = Database::getInstance();
try {
    $pdo = $db->getPdo();
} catch (Exception $e) {
    echo "Failed to get PDO: " . $e->getMessage() . "\n";
    exit(1);
}

// Create backup table if not exists (include old_is_active for safe rollback)
$pdo->exec("CREATE TABLE IF NOT EXISTS carwash_status_backup (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carwash_id INT NOT NULL,
    old_status TEXT,
    old_is_active TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Find rows to normalize (open-like tokens)
$sql = "SELECT id, name, status FROM carwashes WHERE (LOWER(COALESCE(status,'')) IN ('açık','acik','open','active') OR status = '1' OR status = 1) AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive') AND COALESCE(status,'') != '0'";
$rows = $db->fetchAll($sql);
if (empty($rows)) {
    echo "No carwash rows found to normalize.\n";
    exit(0);
}

echo "Found " . count($rows) . " rows to normalize to 'Açık'\n";
$upd = $pdo->prepare('UPDATE carwashes SET status = :new, is_active = :ia, updated_at = NOW() WHERE id = :id');
$ins = $pdo->prepare('INSERT INTO carwash_status_backup (carwash_id, old_status, old_is_active) VALUES (:id, :old, :oldia)');
$pdo->beginTransaction();
try {
    foreach ($rows as $r) {
        echo sprintf("- ID=%d name='%s' old_status='%s'\n", $r['id'], $r['name'] ?? '', $r['status'] ?? '');
        // Record previous status and is_active for safe rollback
        $prevIsActive = isset($r['is_active']) ? (int)$r['is_active'] : 0;
        $ins->execute(['id' => $r['id'], 'old' => $r['status'], 'oldia' => $prevIsActive]);
        // Normalization sets canonical open status and marks is_active=1
        $upd->execute(['new' => 'Açık', 'ia' => 1, 'id' => $r['id']]);
    }
    $pdo->commit();
    echo "Normalization complete. Backup written to carwash_status_backup table.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Normalization failed: " . $e->getMessage() . "\n";
    exit(1);
}
