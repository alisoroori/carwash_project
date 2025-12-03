<?php
/**
 * automated_toggle_test.php
 *
 * Toggles a carwash status between 'Açık' and 'Kapalı' (and is_active 1/0),
 * runs the Customer Dashboard visibility query, verifies presence/absence,
 * repeats the sequence, logs every step, and restores original state.
 *
 * Usage: php automated_toggle_test.php [carwash_id]
 * If carwash_id not provided, the script will pick the first carwash row.
 */

require_once __DIR__ . '/vendor/autoload.php';
use App\Classes\Database;

function logline($s) { echo '[' . date('Y-m-d H:i:s') . '] ' . $s . PHP_EOL; }

$db = Database::getInstance();
$pdo = $db->getPdo();

$argv_id = isset($argv[1]) ? (int)$argv[1] : 0;

try {
    if ($argv_id > 0) {
        $row = $db->fetchOne('SELECT id, name, status, COALESCE(is_active,1) AS is_active FROM carwashes WHERE id = :id', ['id' => $argv_id]);
        if (!$row) {
            logline("Carwash with id={$argv_id} not found. Exiting.");
            exit(1);
        }
    } else {
        $row = $db->fetchOne('SELECT id, name, status, COALESCE(is_active,1) AS is_active FROM carwashes ORDER BY id LIMIT 1');
        if (!$row) {
            logline("No carwash rows found in DB. Exiting.");
            exit(1);
        }
    }

    $cw_id = (int)$row['id'];
    $cw_name = $row['name'] ?? '';
    $orig_status = $row['status'];
    $orig_active = (int)$row['is_active'];

    logline("Selected carwash ID={$cw_id} name='{$cw_name}' orig_status='{$orig_status}' is_active={$orig_active}");

    // Helper: query customer-visible carwashes using canonical query used in dashboard
    $visibilityQuery = "SELECT id, name, status FROM carwashes WHERE (status = 'Açık' OR LOWER(COALESCE(status,'')) IN ('açık','acik','open','active') OR status = '1') AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive') AND COALESCE(status,'') != '0' ORDER BY name";
    function visibleIds($db, $sql) {
        $rows = $db->fetchAll($sql);
        $ids = [];
        foreach ($rows as $r) $ids[] = (int)$r['id'];
        return $ids;
    }

    // We'll perform two toggle cycles: open -> closed, repeated
    $cycles = 2;
    $results = [];

    // Backup original in local vars to restore later (we will also double-check DB restore)

    for ($cycle = 1; $cycle <= $cycles; $cycle++) {
        logline("=== Cycle {$cycle} : SET OPEN ('Açık') ===");
        // Start transaction for atomicity of the update and verification per step
        $pdo->beginTransaction();
        try {
            // Update status and is_active
            $upd = $pdo->prepare('UPDATE carwashes SET status = :s, is_active = :ia, updated_at = NOW() WHERE id = :id');
            $upd->execute(['s' => 'Açık', 'ia' => 1, 'id' => $cw_id]);

            $pdo->commit();
            logline("DB update to 'Açık' committed.");
        } catch (Exception $e) {
            $pdo->rollBack();
            logline("DB update error: " . $e->getMessage());
            $results[] = [ 'cycle' => $cycle, 'phase' => 'open', 'status' => 'db_update_failed', 'error' => $e->getMessage() ];
            continue;
        }

        // Verify DB value
        $after = $db->fetchOne('SELECT id, name, status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE id = :id', ['id' => $cw_id]);
        $db_status = $after['status'];
        $db_active = (int)$after['is_active'];
        $ok_db = ($db_status === 'Açık' && $db_active === 1);
        logline("DB verification after OPEN: status='{$db_status}' is_active={$db_active} => " . ($ok_db ? 'OK' : 'MISMATCH'));

        // Check visibility from customer query
        $ids = visibleIds($db, $visibilityQuery);
        $visible = in_array($cw_id, $ids, true);
        logline("Customer_Dashboard visibility after OPEN: visible=" . ($visible ? 'true' : 'false'));

        $results[] = [ 'cycle' => $cycle, 'phase' => 'open', 'db_status' => $db_status, 'db_active' => $db_active, 'visible' => $visible, 'ok_db' => $ok_db ];

        // Now set to closed
        logline("--- SET CLOSED ('Kapalı') ---");
        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare('UPDATE carwashes SET status = :s, is_active = :ia, updated_at = NOW() WHERE id = :id');
            $upd->execute(['s' => 'Kapalı', 'ia' => 0, 'id' => $cw_id]);
            $pdo->commit();
            logline("DB update to 'Kapalı' committed.");
        } catch (Exception $e) {
            $pdo->rollBack();
            logline("DB update error: " . $e->getMessage());
            $results[] = [ 'cycle' => $cycle, 'phase' => 'closed', 'status' => 'db_update_failed', 'error' => $e->getMessage() ];
            continue;
        }

        // Verify DB value
        $after = $db->fetchOne('SELECT id, name, status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE id = :id', ['id' => $cw_id]);
        $db_status = $after['status'];
        $db_active = (int)$after['is_active'];
        $ok_db = ($db_status === 'Kapalı' && $db_active === 0);
        logline("DB verification after CLOSED: status='{$db_status}' is_active={$db_active} => " . ($ok_db ? 'OK' : 'MISMATCH'));

        // Check visibility from customer query
        $ids = visibleIds($db, $visibilityQuery);
        $visible = in_array($cw_id, $ids, true);
        logline("Customer_Dashboard visibility after CLOSED: visible=" . ($visible ? 'true' : 'false'));

        $results[] = [ 'cycle' => $cycle, 'phase' => 'closed', 'db_status' => $db_status, 'db_active' => $db_active, 'visible' => $visible, 'ok_db' => $ok_db ];

    }

    // Restore original state
    logline("Restoring original status='{$orig_status}' is_active={$orig_active}");
    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare('UPDATE carwashes SET status = :s, is_active = :ia, updated_at = NOW() WHERE id = :id');
        $upd->execute(['s' => $orig_status, 'ia' => $orig_active, 'id' => $cw_id]);
        $pdo->commit();
        logline('Restore committed.');
    } catch (Exception $e) {
        $pdo->rollBack();
        logline('Restore failed: ' . $e->getMessage());
    }

    // Summary
    logline('=== SUMMARY ===');
    $toggleCount = count($results);
    $pass = 0; $fail = 0;
    foreach ($results as $r) {
        $ok = ($r['phase'] === 'open') ? ($r['ok_db'] && $r['visible']) : ($r['ok_db'] && !$r['visible']);
        if ($ok) $pass++; else $fail++;
        logline(json_encode($r));
    }
    logline("Toggles tested: {$toggleCount}");
    logline("Pass: {$pass}  Fail: {$fail}");

    if ($fail > 0) {
        logline('Some checks failed. Inspect logs and DB.');
        exit(2);
    }

    logline('All checks passed.');
    exit(0);

} catch (Exception $e) {
    logline('Fatal error: ' . $e->getMessage());
    exit(1);
}
