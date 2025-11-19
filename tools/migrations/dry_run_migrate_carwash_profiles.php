<?php
/**
 * Dry-run migration preview: carwash_profiles -> carwashes
 * Generates SQL with INSERT...ON DUPLICATE KEY UPDATE that updates only empty fields
 * Produces a conflict CSV for manual review and creates an audit table if missing.
 */
require_once __DIR__ . '/../../backend/includes/bootstrap.php';
use App\Classes\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check tables
    $check = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('carwash_profiles','carwashes')");
    $check->execute();
    $found = $check->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasLegacy = in_array('carwash_profiles', $found, true);
    $hasTarget = in_array('carwashes', $found, true);

    if (!$hasLegacy) {
        echo "No legacy table `carwash_profiles` found. Nothing to preview.\n";
        exit(0);
    }

    if (!$hasTarget) {
        echo "Target table `carwashes` does not exist. Create it first or run schema-fix helper.\n";
        exit(1);
    }

    $outDir = __DIR__;
    $sqlFile = $outDir . DIRECTORY_SEPARATOR . 'migration_preview.sql';
    $csvFile = $outDir . DIRECTORY_SEPARATOR . 'migration_conflicts.csv';

    $columns = [
        'user_id',
        'business_name',
        'address',
        'postal_code',
        'city',
        'district',
        'contact_phone',
        'mobile_phone',
        'contact_email',
        'featured_image',
        'social_media',
        'opening_hours',
        'services',
        'rating',
        'status',
        'created_at',
        'updated_at'
    ];

    // Build INSERT...SELECT preview. Column mapping to carwashes:
    $insertCols = [
        'user_id','name','address','postal_code','city','district','phone','mobile_phone','email','logo_path','social_media','working_hours','services','rating','status','created_at','updated_at'
    ];

    $selectCols = [
        'user_id',
        'business_name',
        'address',
        'postal_code',
        'city',
        'district',
        'contact_phone',
        'mobile_phone',
        'contact_email',
        'featured_image',
        'social_media',
        'opening_hours',
        'services',
        'rating',
        'status',
        'created_at',
        'updated_at'
    ];

    $insertColsSql = implode(', ', $insertCols);
    $selectColsSql = implode(', ', array_map(function($c){ return $c; }, $selectCols));

    $sql = "-- Dry-run migration preview: do NOT run without review\n";
    $sql .= "INSERT INTO carwashes ({$insertColsSql})\n";
    $sql .= "SELECT {$selectColsSql} FROM carwash_profiles\n";
    $sql .= "ON DUPLICATE KEY UPDATE\n";

    $updates = [];
    $map = [
        'phone' => 'contact_phone',
        'mobile_phone' => 'mobile_phone',
        'email' => 'contact_email',
        'logo_path' => 'featured_image',
        'social_media' => 'social_media',
        'working_hours' => 'opening_hours',
        'services' => 'services'
    ];

    foreach ($map as $targetCol => $srcCol) {
        // Update only when target is empty
        $updates[] = "{$targetCol} = IF(carwashes.{$targetCol} IS NULL OR carwashes.{$targetCol} = '', VALUES({$targetCol}), carwashes.{$targetCol})";
    }

    $updates[] = "updated_at = GREATEST(IFNULL(carwashes.updated_at, '1970-01-01'), VALUES(updated_at))";
    $sql .= implode(",\n", $updates) . ";\n";

    file_put_contents($sqlFile, $sql);
    echo "Wrote migration preview to: {$sqlFile}\n";

    // Conflict detection: find rows where both legacy and target exist but differ in non-empty fields
    $conflictSql = "SELECT p.id AS legacy_id, c.id AS target_id, p.user_id AS legacy_user_id, c.user_id AS target_user_id, p.business_name AS legacy_name, c.name AS target_name, p.contact_phone AS legacy_phone, c.phone AS target_phone, p.contact_email AS legacy_email, c.email AS target_email, p.address AS legacy_address, c.address AS target_address FROM carwash_profiles p LEFT JOIN carwashes c ON (p.user_id = c.user_id OR (p.business_name = c.name AND COALESCE(NULLIF(p.address,''),'') = COALESCE(NULLIF(c.address,''),''))) WHERE c.id IS NOT NULL AND ( (p.contact_phone IS NOT NULL AND c.phone IS NOT NULL AND p.contact_phone <> c.phone) OR (p.contact_email IS NOT NULL AND c.email IS NOT NULL AND p.contact_email <> c.email) OR (p.business_name IS NOT NULL AND c.name IS NOT NULL AND p.business_name <> c.name) OR (p.address IS NOT NULL AND c.address IS NOT NULL AND p.address <> c.address) )";

    $stmt = $pdo->prepare($conflictSql);
    $stmt->execute();
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($conflicts)) {
        $fp = fopen($csvFile, 'w');
        fputcsv($fp, array_keys($conflicts[0]));
        foreach ($conflicts as $row) fputcsv($fp, $row);
        fclose($fp);
        echo "Wrote conflict CSV to: {$csvFile} (" . count($conflicts) . " rows)\n";

        // Create audit table if missing and insert conflicts
        $pdo->exec("CREATE TABLE IF NOT EXISTS carwash_migration_audit (id INT AUTO_INCREMENT PRIMARY KEY, legacy_id INT NULL, target_id INT NULL, field_name VARCHAR(128), legacy_value TEXT, target_value TEXT, resolved TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $ins = $pdo->prepare("INSERT INTO carwash_migration_audit (legacy_id,target_id,field_name,legacy_value,target_value) VALUES (:legacy_id,:target_id,:field,:legacy,:target)");
        foreach ($conflicts as $c) {
            // for each differing field, insert one audit row
            if ($c['legacy_phone'] !== $c['target_phone']) {
                $ins->execute(['legacy_id'=>$c['legacy_id'],'target_id'=>$c['target_id'],'field'=>'phone','legacy'=>$c['legacy_phone'],'target'=>$c['target_phone']]);
            }
            if ($c['legacy_email'] !== $c['target_email']) {
                $ins->execute(['legacy_id'=>$c['legacy_id'],'target_id'=>$c['target_id'],'field'=>'email','legacy'=>$c['legacy_email'],'target'=>$c['target_email']]);
            }
            if ($c['legacy_name'] !== $c['target_name']) {
                $ins->execute(['legacy_id'=>$c['legacy_id'],'target_id'=>$c['target_id'],'field'=>'name','legacy'=>$c['legacy_name'],'target'=>$c['target_name']]);
            }
            if ($c['legacy_address'] !== $c['target_address']) {
                $ins->execute(['legacy_id'=>$c['legacy_id'],'target_id'=>$c['target_id'],'field'=>'address','legacy'=>$c['legacy_address'],'target'=>$c['target_address']]);
            }
        }
        echo "Inserted " . count($conflicts) . " conflict rows into carwash_migration_audit.\n";
    } else {
        echo "No conflicts detected between `carwash_profiles` and `carwashes`.\n";
    }

    echo "Dry-run preview complete. Review {$sqlFile} and {$csvFile} before applying.\n";

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
