<?php
// Usage: php add_csrf_check_to_api.php --dry-run
//        php add_csrf_check_to_api.php --apply
// Scans backend/api recursively for PHP files that look like POST handlers and
// inserts at the top (after <?php) the lines:
// require_once 'backend/includes/csrf_check.php';
// csrf_check(true);
// Creates .bak backups when --apply is used.

$argv = $_SERVER['argv'];
$dryRun = in_array('--dry-run', $argv);
$apply = in_array('--apply', $argv);
if (!$dryRun && !$apply) {
    fwrite(STDERR, "Usage: php add_csrf_check_to_api.php --dry-run|--apply\n");
    exit(2);
}

$root = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$apiDir = realpath($root . 'backend' . DIRECTORY_SEPARATOR . 'api');
if (!$apiDir || !is_dir($apiDir)) {
    fwrite(STDERR, "Cannot find backend/api directory at expected path: $apiDir\n");
    exit(2);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apiDir));
$patternCandidates = [];

foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getRealPath();
    $contents = file_get_contents($path);
    if ($contents === false) continue;

    // Skip files that already include csrf_check (idempotence)
    if (strpos($contents, "csrf_check(") !== false || strpos($contents, "csrf_check.php") !== false) {
        continue;
    }

    // Heuristics to detect POST handlers:
    // - uses $_POST
    // - checks REQUEST_METHOD == 'POST'
    // - strpos 'method="POST"' (literal HTML form, but API endpoints are server scripts)
    // - uses $_SERVER['REQUEST_METHOD']
    $hasPostPatterns = false;
    $lower = strtolower($contents);
    if (strpos($lower, '$_post') !== false || strpos($lower, "\$_server['request_method']") !== false || strpos($lower, '$_server["request_method"]') !== false || strpos($lower, "request_method') == 'post") !== false) {
        $hasPostPatterns = true;
    }
    // also look for "REQUEST_METHOD" occurrences
    if (strpos($contents, 'REQUEST_METHOD') !== false) $hasPostPatterns = true;

    // Also treat endpoints that contain 'action' like 'process' or 'create' and perform POST-like behaviour
    // but we prefer conservative detection.

    if ($hasPostPatterns) {
        $patternCandidates[] = $path;
    }
}

if (count($patternCandidates) === 0) {
    echo "No candidate POST handler files found under backend/api using conservative heuristics.\n";
    echo "If you want a broader pass, run this script after adjusting heuristics.\n";
    exit(0);
}

echo "Found " . count($patternCandidates) . " candidate files for CSRF insertion (conservative detection).\n";
if ($dryRun) {
    foreach ($patternCandidates as $p) {
        echo "DRY-RUN: $p\n";
    }
    // Write a report file
    $report = ['meta'=>['dryRun'=>true,'timestamp'=>date(DATE_ATOM)], 'results'=>[]];
    foreach ($patternCandidates as $p) {
        $report['results'][] = ['path'=>$p, 'status'=>'dryrun'];
    }
    file_put_contents(__DIR__ . '/reports/csrf_api_add_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Dry-run report saved to tools/reports/csrf_api_add_report.json\n";
    exit(0);
}

// Apply mode
$report = ['meta'=>['dryRun'=>false,'apply'=>true,'timestamp'=>date(DATE_ATOM)], 'results'=>[]];
foreach ($patternCandidates as $path) {
    $orig = file_get_contents($path);
    if ($orig === false) {
        $report['results'][] = ['path'=>$path,'status'=>'read_error'];
        continue;
    }

    // Find the first <?php tag
    $pos = strpos($orig, '<?php');
    if ($pos === false) {
        // no php opening tag; skip
        $report['results'][] = ['path'=>$path,'status'=>'no_php_open_tag'];
        continue;
    }

    // Find end of the php open tag
    $afterTagPos = $pos + 5; // length of '<?php'
    // Insert after the opening tag. If a declare(...) exists immediately after
    // the open tag (e.g. declare(strict_types=1);) we must insert after it so
    // the declare remains the first statement.
    $rest = substr($orig, $afterTagPos);
    $insertionPos = $afterTagPos;
    // If there's a declare(...) at the top, advance insertion position past it
    if (preg_match('/^\s*declare\s*\(.*?\)\s*;\s*/is', $rest, $m)) {
        $insertionPos += strlen($m[0]);
    } else {
        // Otherwise keep insertion after initial whitespace/newlines
        if (preg_match('/^(\s*\n?)/', $rest, $match)) {
            $insertionPos = $afterTagPos + strlen($match[0]);
        }
    }

    $insertSnippet = "\nrequire_once 'backend/includes/csrf_check.php';\ncsrf_check(true);\n\n";

    // Avoid double-inserting if somehow the snippet exists
    if (strpos($orig, "require_once 'backend/includes/csrf_check.php'") !== false || strpos($orig, 'csrf_check(true)') !== false) {
        $report['results'][] = ['path'=>$path,'status'=>'already_has_check'];
        continue;
    }

    $new = substr($orig, 0, $insertionPos) . $insertSnippet . substr($orig, $insertionPos);

    // create backup
    $bak = $path . '.bak';
    $bakIdx = 1;
    while (file_exists($bak)) {
        $bak = $path . '.bak' . $bakIdx;
        $bakIdx++;
    }
    $ok = file_put_contents($bak, $orig);
    if ($ok === false) {
        $report['results'][] = ['path'=>$path,'status'=>'backup_failed'];
        continue;
    }

    $ok2 = file_put_contents($path, $new);
    if ($ok2 === false) {
        $report['results'][] = ['path'=>$path,'status'=>'write_failed'];
        // attempt restore
        copy($bak, $path);
        continue;
    }

    $report['results'][] = ['path'=>$path,'status'=>'modified','backup'=>$bak];
    echo "Modified: $path (backup: $bak)\n";
}

file_put_contents(__DIR__ . '/reports/csrf_api_add_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Apply complete. Report saved to tools/reports/csrf_api_add_report.json\n";

// Run php -l on modified files and print results
$errors = 0;
foreach ($report['results'] as $r) {
    if (!isset($r['path'])) continue;
    if ($r['status'] !== 'modified') continue;
    $p = $r['path'];
    $cmd = 'php -l ' . escapeshellarg($p) . ' 2>&1';
    echo "Linting $p\n";
    exec($cmd, $out, $rc);
    foreach ($out as $line) echo $line . "\n";
    if ($rc !== 0) $errors++;
    $out = null;
}
if ($errors > 0) {
    echo "$errors files failed lint after modification. Please inspect and restore backups if needed.\n";
    exit(1);
}

echo "All modified files passed php -l\n";
exit(0);
