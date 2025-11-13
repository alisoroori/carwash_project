<?php
/**
 * create_backup.php
 * - Dumps MySQL database to SQL file
 * - Zips the dump with timestamp and stores it in backend/backups/
 * - Keeps only last N backups (rotation)
 * - Sets secure permissions where possible
 *
 * Usage (CLI): php create_backup.php
 */

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';

$backupDir = __DIR__ . '/../backups';
$rawArchiveDir = $backupDir . '/raw_bak_archive';
if (!is_dir($backupDir)) mkdir($backupDir, 0750, true);
if (!is_dir($rawArchiveDir)) mkdir($rawArchiveDir, 0750, true);

// Read DB config from constants defined in includes/config.php
$dbHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$dbName = defined('DB_NAME') ? DB_NAME : 'carwash_db';
$dbUser = defined('DB_USER') ? DB_USER : 'root';
$dbPass = defined('DB_PASS') ? DB_PASS : '';

$timestamp = date('Ymd-His');
$sqlFile = $backupDir . "/dump-{$timestamp}.sql";
$zipFile = $backupDir . "/backup-{$timestamp}.zip";

// For security avoid passing password on CLI directly when possible.
// Create a temporary defaults file for mysqldump if mysqldump is available.
$tmpCnf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mysqldump-{$timestamp}.cnf";
$cnfContent = "[client]\nuser={$dbUser}\npassword={$dbPass}\nhost={$dbHost}\n";
file_put_contents($tmpCnf, $cnfContent);
@chmod($tmpCnf, 0600);

// Build mysqldump command
$mysqldump = 'mysqldump';
$cmd = escapeshellcmd($mysqldump) . ' --defaults-extra-file=' . escapeshellarg($tmpCnf) . ' --single-transaction --quick --lock-tables=false ' . escapeshellarg($dbName) . ' > ' . escapeshellarg($sqlFile);

echo "Running: $cmd\n";
exec($cmd, $output, $returnVar);
if ($returnVar !== 0) {
    echo "mysqldump failed (exit={$returnVar}). You may need to ensure mysqldump is on PATH or adjust credentials.\n";
    // Cleanup tmp cnf
    @unlink($tmpCnf);
    exit(2);
}

// Create zip from SQL
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
    echo "Could not create zip file: {$zipFile}\n";
    @unlink($tmpCnf);
    exit(3);
}
$zip->addFile($sqlFile, basename($sqlFile));
$zip->close();

// Set secure permissions on zip and sql (best-effort; Windows may ignore)
@chmod($zipFile, 0600);
@chmod($sqlFile, 0600);

// Optionally remove raw sql after zipping (we'll keep it for now and then remove to save space)
if (file_exists($sqlFile)) {
    unlink($sqlFile); // remove SQL after archiving
}

// Rotation: keep only last N backups
$keep = 5;
$files = glob($backupDir . '/backup-*.zip');
usort($files, function($a, $b){ return filemtime($b) - filemtime($a); });
if (count($files) > $keep) {
    $toDelete = array_slice($files, $keep);
    foreach ($toDelete as $f) {
        @unlink($f);
        echo "Rotated out: {$f}\n";
    }
}

// Set strict permissions on backup directory and files
@chmod($backupDir, 0700);
foreach (glob($backupDir . '/*') as $f) {
    @chmod($f, 0600);
}

// Cleanup temp cnf
@unlink($tmpCnf);

echo "Backup completed: {$zipFile}\n";
exit(0);
