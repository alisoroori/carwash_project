<?php
// Fix double-backslash namespace occurrences inserted by the bootstrap tool
$reportPath = __DIR__ . '/reports/api_bootstrap_report.json';
if (!file_exists($reportPath)) { echo "Report not found: $reportPath\n"; exit(2); }
$report = json_decode(file_get_contents($reportPath), true);
if (!$report) { echo "Failed to parse report\n"; exit(2); }
$modified = array_filter($report['results'], fn($r)=> ($r['status'] ?? '') === 'modified');
$errors = 0;
foreach ($modified as $m) {
    $path = $m['path'];
    if (!file_exists($path)) { echo "Missing file: $path\n"; $errors++; continue; }
    $orig = file_get_contents($path);
    if ($orig === false) { echo "Read error: $path\n"; $errors++; continue; }
    // Only change the bootstrap block to replace double backslashes with single
    $marker = "// API bootstrap (added by tools/add_api_bootstrap.php)";
    $pos = strpos($orig, $marker);
    if ($pos === false) { echo "No marker in $path, skipping\n"; continue; }
    // find end of block (we inserted a blank line after the block)
    $end = strpos($orig, "\n\n", $pos);
    if ($end === false) $end = $pos + 400; // fallback
    $block = substr($orig, $pos, $end - $pos);
    $fixed = str_replace('\\\\', '\\', $block); // replace double backslashes with single backslashes in the block (literal replacement)
    if ($fixed === $block) { echo "No double-backslashes in marker block for $path\n"; continue; }
    $new = substr($orig, 0, $pos) . $fixed . substr($orig, $end);
    // backup
    $bak = $path . '.bak_fix'; $i=1; while (file_exists($bak)) { $bak = $path . '.bak_fix' . $i; $i++; }
    file_put_contents($bak, $orig);
    file_put_contents($path, $new);
    echo "Fixed backslashes in $path (backup: $bak)\n";
    // lint
    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $rc);
    foreach ($out as $line) echo $line . "\n";
    if ($rc !== 0) { echo "Lint failed for $path\n"; $errors++; }
}
if ($errors > 0) { echo "$errors errors during fix\n"; exit(1); }
echo "Backslash fix complete.\n";
