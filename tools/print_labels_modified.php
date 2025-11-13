<?php
$path = __DIR__ . '/../backend/.reports/labels-fix-report.json';
if (!file_exists($path)) { echo "Report not found: $path\n"; exit(1); }
$r = json_decode(file_get_contents($path), true);
if (!isset($r['modified'])) { echo "No modified entries\n"; exit(0); }
echo "Modified files: " . count($r['modified']) . "\n\n";
foreach ($r['modified'] as $m) {
    echo $m['file'] . "\n";
}
