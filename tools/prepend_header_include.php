<?php
// Prepend include for backend/includes/header.php to page files that are missing DOCTYPE or header include
// Usage: php prepend_header_include.php [--root=/path/to/repo] [--dry-run] [--apply] [--dirs=dir1,dir2]

$opts = getopt('', ['root::','dry-run','apply','dirs::']);
$root = isset($opts['root']) ? realpath($opts['root']) : realpath(__DIR__ . '/../');
$dryRun = isset($opts['dry-run']);
$apply = isset($opts['apply']);
$dirs = isset($opts['dirs']) ? explode(',', $opts['dirs']) : ['backend/dashboard','backend/admin','backend/auth','frontend'];

if (!$root || !is_dir($root)) {
    fwrite(STDERR, "Invalid root path: $root\n");
    exit(2);
}

$report = [];
$excludePatterns = ['/.git/', '/vendor/', '/node_modules/', '/dist/', '/tools/', '/tests/', '/database/', '/docs/', '/uploads/'];

function startsWithAny($path, $arr) {
    foreach ($arr as $a) {
        if (strpos($path, $a) !== false) return true;
    }
    return false;
}

$includeLine = "<?php include_once __DIR__ . '/header.php'; ?>\n";

foreach ($dirs as $d) {
    $dirPath = $root . DIRECTORY_SEPARATOR . $d;
    if (!is_dir($dirPath)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($ext, ['php','html'])) continue;
        $path = $file->getRealPath();
        if (startsWithAny($path, $excludePatterns)) continue;

        $content = @file_get_contents($path);
        if ($content === false) continue;

        // Skip if already includes header.php or index-header.php or dashboard_header
        if (preg_match("#/includes/(header|index-header|dashboard_header)\.php#", $content)) {
            $report[$path] = 'already_includes';
            continue;
        }

        // Skip API endpoints (ending with _api.php) and files that appear to be fragments (<?php return or class definitions)
        $base = basename($path);
        if (preg_match('/_api\.php$/i', $base) || preg_match('/^(class|interface|trait)\b/i', ltrim($content))) {
            $report[$path] = 'skipped_api_or_class';
            continue;
        }

        // If file contains DOCTYPE or <html>, assume header provided elsewhere; but we still may want to include header if DOCTYPE missing
        $hasDoctype = preg_match('#<!DOCTYPE\s+html>|<!doctype\s+html>#i', $content);
        if ($hasDoctype) {
            $report[$path] = 'has_doctype';
            continue;
        }

        // Candidate to prepend include
        $report[$path] = 'would_prepend';
        if ($apply && !$dryRun) {
            $bak = $path . '.bak.' . date('YmdHis');
            copy($path, $bak);
            $new = $includeLine . $content;
            file_put_contents($path, $new);
            $report[$path] = 'prepended';
            // lint check for php files
            if ($ext === 'php') {
                $out = null; $rc = null;
                exec("php -l " . escapeshellarg($path) . " 2>&1", $out, $rc);
                $report[$path] .= ($rc === 0) ? ' (php-lint-ok)' : ' (php-lint-fail: ' . implode(' | ', $out) . ')';
            }
        }
    }
}

$outPath = __DIR__ . '/reports/prepend_header_report.json';
@mkdir(dirname($outPath), 0777, true);
file_put_contents($outPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Wrote report to $outPath\n";
foreach ($report as $p => $r) {
    echo "$r : $p\n";
}

if ($apply && !$dryRun) {
    echo "Apply completed. Review .bak files if needed.\n";
}
