<?php
$report = json_decode(file_get_contents(__DIR__ . '/reports/csrf_api_add_report.json'), true);
if (!$report) { echo "failed to read report\n"; exit(2); }
$errors = 0;
foreach ($report['results'] as $entry) {
    if (!isset($entry['path'])) continue;
    if ($entry['status'] !== 'modified') continue;
    $path = $entry['path'];
    echo "Lint: $path\n";
    $out = [];
    $rc = 0;
    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $rc);
    foreach ($out as $line) echo $line . "\n";
    if ($rc !== 0) $errors++;
}
if ($errors > 0) {
    echo "$errors lint errors found\n";
    exit(1);
}
echo "All lint OK\n";
