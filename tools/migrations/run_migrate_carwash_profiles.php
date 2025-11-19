<?php
// Run migration from carwash_profiles -> carwashes (dry-run by default)
// Usage: php run_migrate_carwash_profiles.php [--apply]

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../backend/includes/bootstrap.php';

use App\Classes\Database;

$apply = in_array('--apply', $argv, true);

echo "Carwash migration runner\n";
echo ($apply ? "RUN MODE: APPLY (will modify DB)\n" : "RUN MODE: DRY RUN (no changes)\n");

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check source table
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tbl");
    $stmt->execute(['tbl' => 'carwash_profiles']);
    $hasSource = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

    if (!$hasSource) {
        echo "Source table `carwash_profiles` not found. Nothing to migrate.\n";
        exit(0);
    }

    // Check target table
    $stmt->execute(['tbl' => 'carwashes']);
    $hasTarget = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

    // Counts
    $totalSrc = (int)$pdo->query('SELECT COUNT(*) FROM carwash_profiles')->fetchColumn();
    $totalTarget = $hasTarget ? (int)$pdo->query('SELECT COUNT(*) FROM carwashes')->fetchColumn() : 0;

    // Rows that would be inserted: source rows whose user_id not present in carwashes
    if ($hasTarget) {
        $q = $pdo->query("SELECT COUNT(*) FROM carwash_profiles cp LEFT JOIN carwashes cw ON cw.user_id = cp.user_id WHERE cw.user_id IS NULL");
        $wouldInsert = (int)$q->fetchColumn();
    } else {
        $wouldInsert = $totalSrc; // if target empty/non-existent, all would be inserted
    }

    echo "Source rows in carwash_profiles: {$totalSrc}\n";
    echo "Existing rows in carwashes: {$totalTarget}\n";
    echo "Rows that would be inserted into carwashes (no matching user_id): {$wouldInsert}\n";

    // Detect duplicate business_name+address groups in source
    $dupStmt = $pdo->query("SELECT business_name, address, COUNT(*) AS cnt FROM carwash_profiles GROUP BY business_name, address HAVING cnt > 1");
    $dups = $dupStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Duplicate business_name+address groups found: " . count($dups) . "\n";
    if (count($dups) > 0) {
        foreach ($dups as $d) {
            echo " - {$d['business_name']} | {$d['address']} -> {$d['cnt']} rows\n";
        }
    }

    // Extract mobile_phone from social_media in source (best-effort)
    $smStmt = $pdo->query("SELECT id, user_id, business_name, contact_phone, social_media FROM carwash_profiles WHERE social_media IS NOT NULL AND social_media <> '' LIMIT 1000");
    $rowsWithSm = $smStmt->fetchAll(PDO::FETCH_ASSOC);
    $mobileFound = 0;
    foreach ($rowsWithSm as $r) {
        $sm = $r['social_media'];
        $decoded = null;
        if ($sm) {
            $decoded = json_decode($sm, true);
        }
        if (is_array($decoded)) {
            foreach (['mobile_phone','mobile','phone','tel','telephone'] as $k) {
                if (!empty($decoded[$k])) {
                    $mobileFound++;
                    break;
                }
            }
            if (isset($decoded['whatsapp'])) {
                $mobileFound++;
            }
        }
    }
    echo "Source rows with social_media containing a mobile-like key (sample up to 1000 scanned): {$mobileFound}\n";

    if (!$apply) {
        echo "Dry run complete. To apply the migration run with: php run_migrate_carwash_profiles.php --apply\n";
        exit(0);
    }

    // APPLY MODE
    echo "Applying migration...\n";

    $pdo->beginTransaction();

    if (!$hasTarget) {
        // Create target table using source structure (safe when source exists)
        echo "Creating `carwashes` table using `CREATE TABLE carwashes LIKE carwash_profiles`...\n";
        $pdo->exec('CREATE TABLE carwashes LIKE carwash_profiles');
        // After this the structures match; later we will insert mapped columns
    }

    // Insert rows that don't have a matching user_id in carwashes
    echo "Inserting rows from carwash_profiles into carwashes where user_id not present...\n";

    // Use mapped columns where possible. This INSERT assumes the target table has these columns present.
    $insertSql = "INSERT INTO carwashes (user_id, name, address, phone, email, postal_code, logo_path, social_media, working_hours, created_at, updated_at)
        SELECT cp.user_id, cp.business_name AS name, cp.address, cp.contact_phone AS phone, cp.contact_email AS email, cp.postal_code, cp.featured_image AS logo_path, cp.social_media, cp.opening_hours AS working_hours, cp.created_at, cp.updated_at
        FROM carwash_profiles cp
        LEFT JOIN carwashes cw ON cw.user_id = cp.user_id
        WHERE cw.user_id IS NULL";

    $affected = $pdo->exec($insertSql);
    $affected = $affected === false ? 0 : $affected;
    echo "Inserted rows into carwashes: {$affected}\n";

    // Try to populate mobile_phone column in carwashes from social_media JSON where possible
    // This uses JSON_EXTRACT and will only work if social_media is valid JSON and MySQL supports JSON functions
    try {
        $updateMobileSql = "UPDATE carwashes cw
            JOIN (
                SELECT id, JSON_UNQUOTE(JSON_EXTRACT(social_media, '$$.mobile_phone')) AS mobile_from_sm
                FROM carwashes
                WHERE social_media IS NOT NULL AND social_media <> ''
            ) j ON j.id = cw.id
            SET cw.mobile_phone = COALESCE(cw.mobile_phone, j.mobile_from_sm)
            WHERE cw.mobile_phone IS NULL";
        // Note: using '$$.mobile_phone' in the PHP string; replace placeholder
        $updateMobileSql = str_replace('$$', '$', $updateMobileSql);
        $u = $pdo->exec($updateMobileSql);
        echo "Updated mobile_phone from social_media for rows: " . ($u === false ? 0 : $u) . "\n";
    } catch (Exception $e) {
        echo "Warning: could not update mobile_phone from JSON automatically: " . $e->getMessage() . "\n";
    }

    $pdo->commit();
    echo "Migration applied successfully. Please run application tests and clear caches.\n";

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Rolled back transaction.\n";
    }
    exit(1);
}
