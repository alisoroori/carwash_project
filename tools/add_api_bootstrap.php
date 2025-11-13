<?php
// Usage: php add_api_bootstrap.php --dry-run
//        php add_api_bootstrap.php --apply
// Inserts a small API bootstrap into backend/api PHP files that
// enables output buffering and logs accidental HTML output via Logger::warn.

$argv = $_SERVER['argv'];
$dryRun = in_array('--dry-run', $argv);
$apply = in_array('--apply', $argv);
if (!$dryRun && !$apply) {
    fwrite(STDERR, "Usage: php add_api_bootstrap.php --dry-run|--apply\n");
    exit(2);
}

$root = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
$apiDir = $root . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'api';
if (!is_dir($apiDir)) {
    fwrite(STDERR, "Cannot find backend/api directory at expected path: $apiDir\n");
    exit(2);
}

$snippet = <<<'SNIP'
// API bootstrap (added by tools/add_api_bootstrap.php)
if (!defined('API_BOOTSTRAP_V1')) {
    define('API_BOOTSTRAP_V1', true);
    ob_start();
    register_shutdown_function(function() {
        try {
            $out = (string) @ob_get_clean();
            if ($out !== '') {
                if (class_exists('App\Classes\Logger')) {
                    try {
                        App\Classes\Logger::warn('API emitted HTML: ' . substr($out, 0, 200));
                    } catch (Throwable $e) {
                        error_log('Logger::warn failed: ' . $e->getMessage());
                    }
                } else {
                    error_log('API emitted HTML: ' . substr(strip_tags($out), 0, 200));
                }
            }
        } catch (Throwable $e) {
            error_log('API bootstrap shutdown handler error: ' . $e->getMessage());
        }
    });
}

SNIP;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apiDir));
$candidates = [];
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getRealPath();
    // skip vendor and tests under api or any files already containing API_BOOTSTRAP_V1
    if (stripos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
    $contents = file_get_contents($path);
    if ($contents === false) continue;
    if (strpos($contents, "API_BOOTSTRAP_V1") !== false || strpos($contents, 'ob_start();') !== false) {
        continue;
    }
    // Only consider files that start with <?php or contain code (conservative)
    if (preg_match('/^\s*<\?php/is', $contents)) {
        $candidates[] = $path;
    }
}

if (count($candidates) === 0) {
    echo "No API PHP files found to modify.\n";
    exit(0);
}

echo "Found " . count($candidates) . " API PHP files to consider.\n";
if ($dryRun) {
    foreach ($candidates as $c) echo "DRY-RUN: $c\n";
    $report = ['meta'=>['dryRun'=>true,'timestamp'=>date(DATE_ATOM)], 'results'=>array_map(function($p){return ['path'=>$p,'status'=>'dryrun'];}, $candidates)];
    if (!is_dir(__DIR__ . '/reports')) mkdir(__DIR__ . '/reports', 0777, true);
    file_put_contents(__DIR__ . '/reports/api_bootstrap_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Dry-run report saved to tools/reports/api_bootstrap_report.json\n";
    exit(0);
}

$report = ['meta'=>['dryRun'=>false,'apply'=>true,'timestamp'=>date(DATE_ATOM)], 'results'=>[]];
foreach ($candidates as $path) {
    $orig = file_get_contents($path);
    if ($orig === false) { $report['results'][] = ['path'=>$path,'status'=>'read_error']; continue; }
    $pos = strpos($orig, '<?php');
    if ($pos === false) { $report['results'][] = ['path'=>$path,'status'=>'no_php_tag']; continue; }
    $afterTagPos = $pos + 5;
    $rest = substr($orig, $afterTagPos);
    $insertionPos = $afterTagPos;
    // If there's a declare(...) at the top, insert after it
    if (preg_match('/^\s*declare\s*\(.*?\)\s*;\s*/is', $rest, $m)) {
        $insertionPos += strlen($m[0]);
    } else {
        if (preg_match('/^(\s*\n?)/', $rest, $match)) {
            $insertionPos = $afterTagPos + strlen($match[0]);
        }
    }

    // Compose final snippet with opening PHP context
    $toInsert = "\n" . $snippet;

    $new = substr($orig, 0, $insertionPos) . $toInsert . substr($orig, $insertionPos);

    // create backup
    $bak = $path . '.bak'; $idx = 1; while (file_exists($bak)) { $bak = $path . '.bak' . $idx; $idx++; }
    if (file_put_contents($bak, $orig) === false) { $report['results'][] = ['path'=>$path,'status'=>'backup_failed']; continue; }
    if (file_put_contents($path, $new) === false) { copy($bak,$path); $report['results'][] = ['path'=>$path,'status'=>'write_failed']; continue; }
    $report['results'][] = ['path'=>$path,'status'=>'modified','backup'=>$bak];
    echo "Modified: $path (backup: $bak)\n";
}

if (!is_dir(__DIR__ . '/reports')) mkdir(__DIR__ . '/reports', 0777, true);
file_put_contents(__DIR__ . '/reports/api_bootstrap_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Apply complete. Report saved to tools/reports/api_bootstrap_report.json\n";

// Run php -l on modified files
$errors = 0;
foreach ($report['results'] as $r) {
    if (!isset($r['path']) || $r['status'] !== 'modified') continue;
    echo "Linting: {$r['path']}\n";
    exec('php -l ' . escapeshellarg($r['path']) . ' 2>&1', $out, $rc);
    foreach ($out as $line) echo $line . "\n";
    if ($rc !== 0) $errors++;
    $out = null;
}
if ($errors > 0) { echo "$errors files failed lint after modification. Please inspect backups.\n"; exit(1); }
echo "All modified files passed php -l\n";
