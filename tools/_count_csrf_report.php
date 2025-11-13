<?php
$r = json_decode(file_get_contents(__DIR__ . '/reports/csrf_autofix_report.json'), true);
if (!$r) { echo "failed to read\n"; exit(2); }
$c = ['dryrun'=>0,'manual'=>0];
foreach ($r['results'] as $e) {
    $s = $e['status'] ?? '';
    if ($s === 'dryrun') $c['dryrun']++;
    if ($s === 'manual_review_required') $c['manual']++;
}
echo "dryrun:" . $c['dryrun'] . " manual:" . $c['manual'] . "\n";
