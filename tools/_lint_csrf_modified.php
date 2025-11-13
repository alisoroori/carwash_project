<?php
$report = json_decode(file_get_contents(__DIR__ . '/reports/csrf_autofix_report.json'), true);
if (!$report) {
    echo "Failed to read report\n";
    exit(2);
}
$errors = 0;
foreach ($report['results'] as $entry) {
    if (!isset($entry['path'])) continue;
    $path = $entry['path'];
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
    $path = str_replace('\\\\', '\\', $path);
    $path = str_replace('\n', '', $path);
    $path = trim($path);
    echo "LINT: $path\n";
    if (!file_exists($path)) {
        echo "MISSING: $path\n";
        $errors++;
        continue;
    }
    $out = [];
    $rc = 0;
    exec("php -l " . escapeshellarg($path) . " 2>&1", $out, $rc);
    foreach ($out as $line) echo $line . "\n";
    if ($rc !== 0) $errors++;
}
if ($errors > 0) {
    echo "$errors lint errors found\n";
    exit(1);
}
echo "All lint OK\n";
