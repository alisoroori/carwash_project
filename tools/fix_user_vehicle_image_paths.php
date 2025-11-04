<?php
/**
 * CLI helper: Back up and update user_vehicles.image_path where NULL or empty
 * Usage (from project root):
 *   php tools/fix_user_vehicle_image_paths.php --dry-run
 *   php tools/fix_user_vehicle_image_paths.php --apply
 *
 * This script will attempt to use App\Classes\Database if available; otherwise it falls back to PDO
 * using DB constants defined in backend/includes/config.php
 */

chdir(__DIR__ . '/..'); // ensure project root
require_once 'backend/includes/bootstrap.php';

use App\Classes\Database;

// CLI args
$opts = array_slice($argv, 1);
$apply = in_array('--apply', $opts, true);
$dry = in_array('--dry-run', $opts, true) || !$apply;

echo "[fix_user_vehicle_image_paths] Starting (dry-run=" . ($dry ? 'yes' : 'no') . ")\n";

// Determine DB connection
$pdo = null;
try {
    if (class_exists(Database::class)) {
        $db = Database::getInstance();
        if (method_exists($db, 'getPdo')) $pdo = $db->getPdo();
        elseif ($db instanceof PDO) $pdo = $db;
    }
} catch (Throwable $e) {
    // ignore and fallback
}

if (!$pdo) {
    // try legacy getDBConnection()
    if (function_exists('getDBConnection')) {
        $maybe = getDBConnection();
        if ($maybe instanceof PDO) $pdo = $maybe;
    }
}

if (!$pdo) {
    // fallback to manual PDO using constants
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
        echo "Database configuration not found. Ensure backend/includes/config.php is loaded.\n";
        exit(1);
    }
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        echo "Failed to connect to DB: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Ensure table exists
$checkStmt = $pdo->query("SHOW TABLES LIKE 'user_vehicles'");
if ($checkStmt->rowCount() === 0) {
    echo "Table user_vehicles not found. Nothing to do.\n";
    exit(0);
}

// Fetch affected rows
$sel = $pdo->prepare("SELECT id, user_id, image_path FROM user_vehicles WHERE image_path IS NULL OR TRIM(image_path) = ''");
$sel->execute();
$rows = $sel->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($rows) . " vehicle(s) with NULL/empty image_path.\n";
if (count($rows) > 0) {
    // Save a JSON backup of affected rows
    $backupDir = __DIR__ . '/../.backups';
    if (!is_dir($backupDir)) @mkdir($backupDir, 0755, true);
    $timestamp = date('Ymd_His');
    $backupFile = $backupDir . "/user_vehicles_image_backup_{$timestamp}.json";
    file_put_contents($backupFile, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Backup of affected rows written to: {$backupFile}\n";

    if ($dry) {
        echo "Dry-run mode: no changes applied. To apply changes run with --apply\n";
        exit(0);
    }

    // Apply update
    $default = defined('DEFAULT_VEHICLE_IMAGE') ? DEFAULT_VEHICLE_IMAGE : '/carwash_project/frontend/assets/images/default-car.png';

    $upd = $pdo->prepare("UPDATE user_vehicles SET image_path = :default WHERE image_path IS NULL OR TRIM(image_path) = ''");
    $upd->execute([':default' => $default]);
    $count = $upd->rowCount();
    echo "Updated {$count} row(s) to default image: {$default}\n";
    echo "Done.\n";
} else {
    echo "No records to update.\n";
}

exit(0);
