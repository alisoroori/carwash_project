<?php
// CLI helper: check services table for orphaned or mislinked rows
// Usage (PowerShell): php .\tools\check_orphaned_services.php [carwash_id]

chdir(__DIR__ . '/..'); // ensure repo root

require_once __DIR__ . '/../backend/includes/bootstrap.php';
use App\Classes\Database;

try {
    $db = Database::getInstance();
    // try to get PDO
    if (method_exists($db, 'getPdo')) {
        $pdo = $db->getPdo();
    } else {
        // fallback: assume Database has a raw pdo property
        $pdo = $db->pdo ?? null;
    }

    if (!$pdo) {
        throw new Exception('Unable to obtain PDO from Database class');
    }

    echo "Connected to DB. Running checks...\n\n";

    // 1) Describe services
    echo "1) services table columns:\n";
    $stmt = $pdo->query("DESCRIBE services");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo sprintf(" - %s %s %s\n", $c['Field'], $c['Type'], $c['Null']);
    }
    echo "\n";

    // 2) Find orphaned services (carwash_id null/0 or points to non-existing carwash)
    echo "2) Orphaned / mislinked services (carwash missing):\n";
    $sql = "SELECT s.id, COALESCE(s.name, '') AS service_name, s.carwash_id FROM services s LEFT JOIN carwashes c ON s.carwash_id = c.id WHERE s.carwash_id IS NULL OR s.carwash_id = 0 OR c.id IS NULL ORDER BY s.id LIMIT 1000";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo " - None found.\n\n";
    } else {
        foreach ($rows as $r) {
            echo sprintf(" - id=%s | name=%s | carwash_id=%s\n", $r['id'], $r['service_name'], $r['carwash_id']);
        }
        echo "\n";
    }

    // 3) Count of services per carwash (top 50)
    echo "3) Service counts per carwash (top 50):\n";
    $sql = "SELECT COALESCE(s.carwash_id, 0) AS carwash_id, COUNT(*) AS cnt FROM services s GROUP BY COALESCE(s.carwash_id,0) ORDER BY cnt DESC LIMIT 50";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo sprintf(" - carwash_id=%s => %d services\n", $r['carwash_id'], $r['cnt']);
    }
    echo "\n";

    // 4) Show sample services for a specific carwash if passed as CLI arg
    $carwashArg = $argv[1] ?? null;
    if ($carwashArg) {
        echo "4) Sample services for carwash_id={$carwashArg}:\n";
        $stmt = $pdo->prepare("SELECT id, COALESCE(name,'') AS name, price, duration, status FROM services WHERE carwash_id = :cw ORDER BY name LIMIT 200");
        $stmt->execute(['cw' => $carwashArg]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            echo " - None found for this carwash_id.\n";
        } else {
            foreach ($rows as $r) {
                echo sprintf(" - id=%s | name=%s | price=%s | duration=%s | status=%s\n", $r['id'], $r['name'], $r['price'], $r['duration'], $r['status']);
            }
        }
        echo "\n";
    }

    echo "Checks complete. If you want full CSV output, re-run with > output.csv\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure your DB is available and `backend/includes/bootstrap.php` sets correct DB config.\n";
    exit(1);
}

return 0;

