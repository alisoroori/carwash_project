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

// Create backup table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS carwash_status_backup (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carwash_id INT NOT NULL,
    old_status TEXT,
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
$upd = $pdo->prepare('UPDATE carwashes SET status = :new WHERE id = :id');
$ins = $pdo->prepare('INSERT INTO carwash_status_backup (carwash_id, old_status) VALUES (:id, :old)');
$pdo->beginTransaction();
try {
    foreach ($rows as $r) {
        echo sprintf("- ID=%d name='%s' old_status='%s'\n", $r['id'], $r['name'] ?? '', $r['status'] ?? '');
        $ins->execute(['id' => $r['id'], 'old' => $r['status']]);
        $upd->execute(['new' => 'Açık', 'id' => $r['id']]);
    }
    $pdo->commit();
    echo "Normalization complete. Backup written to carwash_status_backup table.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Normalization failed: " . $e->getMessage() . "\n";
    exit(1);
}
