<?php
// Simple integration test for merge_signups_into_carwashes.php (dry-run)
$cwd = __DIR__ . '/../../tools/migrations';
$cmd = "php " . escapeshellarg($cwd . DIRECTORY_SEPARATOR . 'merge_signups_into_carwashes.php') . " --dry-run";
echo "Running: $cmd\n";
exec($cmd, $out, $rc);
echo implode("\n", $out) . "\n";
if ($rc !== 0) {
    echo "Merge script returned non-zero status: {$rc}\n";
    exit(1);
}

$sqlFile = $cwd . DIRECTORY_SEPARATOR . 'migration_apply.sql';
$csvFile = $cwd . DIRECTORY_SEPARATOR . 'merge_conflicts.csv';

if (!file_exists($sqlFile)) {
    echo "Expected SQL file not found: {$sqlFile}\n";
    exit(2);
}

echo "Found SQL preview: {$sqlFile}\n";
echo "Found conflicts CSV (may be empty): {$csvFile}\n";

// Basic sanity checks
$sql = file_get_contents($sqlFile);
if (stripos($sql, 'INSERT INTO carwashes') === false) {
    echo "SQL preview does not contain expected INSERT statement.\n";
    exit(3);
}

echo "Integration dry-run completed successfully.\n";
exit(0);
