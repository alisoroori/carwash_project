<?php
// tools/csrf_autofix.php
// Read a CSRF audit JSON produced by tools/csrf_audit.php and
// insert a hidden csrf_token input into server-rendered forms where missing.
// Usage:
// php tools/csrf_autofix.php --input=tools/reports/csrf_audit_full.json --dry-run
// php tools/csrf_autofix.php --input=tools/reports/csrf_audit_full.json --apply

$opts = getopt('', ['input:', 'dry-run', 'apply']);
if (empty($opts['input'])) {
    fwrite(STDERR, "Usage: php tools/csrf_autofix.php --input=PATH [--dry-run|--apply]\n");
    exit(2);
}
$input = $opts['input'];
$dryRun = isset($opts['dry-run']);
$apply = isset($opts['apply']);
if ($dryRun && $apply) {
    fwrite(STDERR, "Specify only one of --dry-run or --apply\n");
    exit(2);
}
if (!file_exists($input)) {
    fwrite(STDERR, "Input file not found: $input\n");
    exit(2);
}
$raw = file_get_contents($input);
// strip UTF-8 BOM if present
if (substr($raw, 0, 3) === "\xEF\xBB\xBF") { $raw = substr($raw, 3); }
$json = json_decode($raw, true);
if (!is_array($json)) {
    fwrite(STDERR, "Invalid JSON in $input\n");
    exit(3);
}

$forms = $json['formsMissingCsrf'] ?? [];
if (empty($forms)) {
    echo "No forms to process found in audit JSON.\n";
    exit(0);
}
foreach ($forms as $entry) {
    $reported = $entry['path'] ?? '';
    $candidates = [];

    // try locating the file by several heuristics
    $candidates[] = $reported;
    $candidates[] = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ltrim($reported, '\\/'));
    $candidates[] = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ltrim($reported, '\\/');

    $found = null;
    foreach ($candidates as $cand) {
        if (!$cand) continue;
        if (file_exists($cand)) { $found = $cand; break; }
    }
    if (!$found) {
        $modifications[] = ['path' => $reported, 'status' => 'missing_file'];
        continue;
    }

    $content = file_get_contents($found);
    if ($content === false) { $modifications[] = ['path' => $found, 'status' => 'read_error']; continue; }

    // find PHP blocks to avoid editing inside them
    preg_match_all('/<\?(?:php|=)?[\s\S]*?\?>/i', $content, $phpBlocks, PREG_OFFSET_CAPTURE);
    $phpRanges = [];
    foreach ($phpBlocks[0] as $b) { $phpRanges[] = [$b[1], $b[1] + strlen($b[0])]; }

    // find occurrences of <form outside PHP blocks
    $pos = 0; $foundForms = [];
    while (($fp = stripos($content, '<form', $pos)) !== false) {
        $inPhp = false; foreach ($phpRanges as $r) { if ($fp >= $r[0] && $fp < $r[1]) { $inPhp = true; break; } }
        if (!$inPhp) $foundForms[] = $fp;
        $pos = $fp + 5;
    }

    if (empty($foundForms)) {
        // file contains forms but only inside PHP blocks or none; flag for manual review
        $modifications[] = ['path' => $found, 'status' => 'manual_review_required'];
        continue;
    }

    // For each found form, propose an insertion snippet (dry-run only)
    $patches = [];
    foreach ($foundForms as $formPos) {
        // find end of opening tag
        $len = strlen($content); $inD=false;$inS=false;$endOpen=-1;
        for ($j=$formPos;$j<$len;$j++) {
            $ch = $content[$j]; if ($ch==="\"" && !$inS) $inD = !$inD; if ($ch==="'" && !$inD) $inS = !$inS; if ($ch==='>' && !$inD && !$inS) { $endOpen=$j; break; }
        }
        if ($endOpen === -1) { $patches[] = ['formPos'=>$formPos,'note'=>'malformed_opening_tag']; continue; }
        $nl = strrpos(substr($content,0,$endOpen),"\n"); $indent = '';
        if ($nl !== false) { $lineStart = $nl+1; $indent = str_repeat(' ', max(0, min(8, strspn(substr($content,$lineStart),' \t')))); }
        $phpEcho = '<' . '?php echo htmlspecialchars(' . '$_SESSION' . "['csrf_token'] ?? ''" . '); ?' . '>';
        $snippet = "\n" . $indent . '<input type="hidden" name="csrf_token" value="' . $phpEcho . '">' . "\n";
        $patches[] = ['formPos'=>$formPos,'insert'=>$snippet,'snippetBefore'=>substr($content,max(0,$formPos-80), min(200,$endOpen-max(0,$formPos-80)))];
    }

        $modifications[] = ['path'=>$found,'status'=>'dryrun','patches'=>$patches];
    }

// Output a readable summary
$count = count($modifications);
if ($dryRun) {
    echo "CSRF autofix DRYRUN summary:\n";
} elseif ($apply) {
    echo "CSRF autofix APPLY summary:\n";
} else {
    echo "CSRF autofix summary:\n";
}
foreach ($modifications as $m) {
    echo '- ' . (is_string($m['path']) ? $m['path'] : json_encode($m['path'])) . ': ' . ($m['status'] ?? '') . "\n";
    if (!empty($m['patches'])) {
        foreach ($m['patches'] as $p) {
            echo '  snippet before: ' . trim(str_replace("\n"," ", $p['snippetBefore'])) . "\n";
            echo '  insertion: ' . trim($p['insert']) . "\n";
        }
    }
    if (!empty($m['note'])) { echo '  note: ' . $m['note'] . "\n"; }
}

// Save a machine-readable report alongside the audit
$reportPath = __DIR__ . '/reports/csrf_autofix_report.json';
if (!is_dir(dirname($reportPath))) mkdir(dirname($reportPath), 0755, true);
file_put_contents($reportPath, json_encode(['meta'=>['dryRun'=>$dryRun,'apply'=>$apply,'timestamp'=>date('c')],'results'=>$modifications], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\nReport saved to: $reportPath\n";

exit(0);
