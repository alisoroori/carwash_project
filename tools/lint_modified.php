<?php
$reportPath = __DIR__ . '/../backend/.reports/labels-fix-report.json';
if (!file_exists($reportPath)) { echo "Report not found: $reportPath\n"; exit(1); }
$r = json_decode(file_get_contents($reportPath), true);
if (!isset($r['modified'])) { echo "No modified entries\n"; exit(0); }
$results = [];
foreach ($r['modified'] as $m) {
    $f = $m['file'];
    if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) !== 'php') continue;
    $out = [];
    $ret = 0;
    exec("php -l " . escapeshellarg($f) . " 2>&1", $out, $ret);
    $results[$f] = ['exit' => $ret, 'output' => $out];
}
foreach ($results as $f => $res) {
    echo "$f : exit {$res['exit']}\n";
    foreach ($res['output'] as $line) echo "    $line\n";
}
