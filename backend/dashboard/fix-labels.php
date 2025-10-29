<?php
declare(strict_types=1);

/**
 * fix-labels.php
 *
 * - Scans .php and .html files under repo (skips vendor, node_modules, .git)
 * - For each <label> without a 'for' attribute and not wrapping an input:
 *     - Finds the next meaningful form control (input/select/textarea) nearby
 *     - If control has id -> add label @for
 *     - Else -> generate stable id (basename + sanitized label text + counter), set id on control and set label @for
 * - Makes .bak backups before writing
 * - Supports --dry-run (report only) and --apply (write files)
 * - Creates .reports/labels-fix-report.json with details
 * - Runs php -l on modified PHP files (when --apply)
 *
 * Usage:
 *  php tools/fix-labels.php --dry-run
 *  php tools/fix-labels.php --apply
 */

$ROOT = realpath(__DIR__ . '/..');
if (!$ROOT) { echo "Unable to locate repo root\n"; exit(1); }
chdir($ROOT);

$opts = getopt('', ['apply', 'dry-run']);
$apply = isset($opts['apply']);
$dry = isset($opts['dry-run']) || !$apply;

$skipDirs = ['vendor', 'node_modules', '.git', 'logs', 'uploads'];
$extensions = ['php','html','htm'];

$report = [
    'run_at' => date('c'),
    'mode' => $apply ? 'apply' : 'dry-run',
    'files' => [],
    'modified' => []
];

function isBinaryPath($path) {
    return false;
}

function iterFiles($root, $exts, $skipDirs) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        $lower = strtolower($path);
        $skip = false;
        foreach ($skipDirs as $d) if (strpos($lower, DIRECTORY_SEPARATOR . $d . DIRECTORY_SEPARATOR) !== false) { $skip=true; break; }
        if ($skip) continue;
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), $exts, true)) continue;
        yield $path;
    }
}

// Helper: extract PHP blocks and replace with placeholders
function extractPhpBlocks(string $content, array &$blocks): string {
    $blocks = [];
    return preg_replace_callback('/(<\?(?:php)?[\s\S]*?\?>)/i', function($m) use (&$blocks) {
        $blocks[] = $m[1];
        return "__PHP_BLOCK_".(count($blocks)-1)."__";
    }, $content);
}

function restorePhpBlocks(string $content, array $blocks): string {
    return preg_replace_callback('/__PHP_BLOCK_(\d+)__/', function($m) use ($blocks) {
        $i = (int)$m[1];
        return $blocks[$i] ?? $m[0];
    }, $content);
}

function sanitizeIdPart(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}\s_-]+/u','', $s);
    $s = preg_replace('/[\s_]+/','-', $s);
    $s = preg_replace('/^-+|-+$/','', $s);
    if ($s === '') $s = 'field';
    // Ensure starts with letter (prefix if necessary)
    if (!preg_match('/^[a-z]/', $s)) $s = 'f-' . $s;
    return $s;
}

function nextFormControlForLabel(DOMXPath $xpath, DOMElement $label) {
    // Skip labels that wrap inputs (they often contain controls)
    foreach ($label->childNodes as $c) {
        if ($c instanceof DOMElement && in_array(strtolower($c->tagName), ['input','select','textarea'], true)) {
            return null;
        }
    }
    // Find following controls using XPath relative 'following' axis
    $expr = 'following::input|following::select|following::textarea';
    $nodes = $xpath->query($expr, $label);
    foreach ($nodes as $node) {
        if (!($node instanceof DOMElement)) continue;
        // Ensure sensible proximity: same parent or within next 3 ancestor levels
        $labelParent = $label->parentNode;
        $candidateParent = $node->parentNode;
        if ($labelParent->isSameNode($candidateParent)) return $node;
        // Allow if candidate is inside next sibling element
        $p = $candidateParent;
        $allowed = false;
        for ($i=0;$i<3 && $p; $i++) {
            if ($p->isSameNode($labelParent)) { $allowed = true; break; }
            $p = $p->parentNode;
        }
        if ($allowed) return $node;
        // As a last resort, accept if it's in the same form element
        $labelForm = null; $candForm = null;
        $a = $label; while($a && !($a instanceof DOMDocument)) { if (strtolower($a->nodeName)==='form') { $labelForm = $a; break;} $a = $a->parentNode; }
        $b = $node; while($b && !($b instanceof DOMDocument)) { if (strtolower($b->nodeName)==='form') { $candForm = $b; break;} $b = $b->parentNode; }
        if ($labelForm && $labelForm->isSameNode($candForm)) return $node;
        // otherwise skip far-away controls
    }
    return null;
}

$globalIdSet = [];

foreach (iterFiles($ROOT, $extensions, $skipDirs) as $file) {
    $orig = file_get_contents($file);
    if ($orig === false) continue;
    $phpBlocks = [];
    $stripped = extractPhpBlocks($orig, $phpBlocks);

    // Wrap in minimal HTML for DOM parsing
    $wrap = "<!doctype html><html><head><meta http-equiv='Content-Type' content='text/html;charset=utf-8'></head><body>$stripped</body></html>";

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML($wrap, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    if (!$loaded) {
        // skip files that cannot be parsed
        $report['files'][] = ['file' => $file, 'skipped' => 'unparseable'];
        continue;
    }
    $xpath = new DOMXPath($dom);
    $labels = $xpath->query('//label[not(@for)]');
    $fileChanges = [];
    if ($labels->length === 0) {
        $report['files'][] = ['file'=>$file,'labels_checked'=>0];
        continue;
    }

    $counter = 0;
    foreach ($labels as $label) {
        if (!($label instanceof DOMElement)) continue;
        // Skip labels that contain controls (wrapping)
        $containsControl = false;
        foreach ($label->getElementsByTagName('input') as $i) { $containsControl = true; break; }
        if ($containsControl) continue;
        foreach ($label->getElementsByTagName('select') as $i) { $containsControl = true; break; }
        if ($containsControl) continue;
        foreach ($label->getElementsByTagName('textarea') as $i) { $containsControl = true; break; }
        if ($containsControl) continue;

        // Skip labels that appear to be headings (no text or short punctuation)
        $text = trim($label->textContent ?? '');
        if ($text === '' || strlen(preg_replace('/\s+/', '', $text)) < 2) continue;
        // find next control
        $control = nextFormControlForLabel($xpath, $label);
        if (!$control) continue;

        $ctrlTag = strtolower($control->tagName);
        // don't attach to buttons, hidden inputs, or submit controls
        $type = strtolower($control->getAttribute('type') ?? '');
        if ($ctrlTag === 'input' && in_array($type, ['hidden','submit','button','reset','image'], true)) continue;

        $existingId = $control->getAttribute('id');
        if ($existingId) {
            // simply add label for
            $label->setAttribute('for', $existingId);
            $fileChanges[] = [
                'label_text' => $text,
                'action' => 'add-for',
                'for' => $existingId,
                'control' => $ctrlTag,
                'control_name' => $control->getAttribute('name') ?: null
            ];
            $globalIdSet[$existingId] = true;
            continue;
        }

        // generate stable id
        $pathBase = pathinfo($file, PATHINFO_FILENAME);
        $part = sanitizeIdPart($text);
        $try = "{$pathBase}-{$part}";
        $i = 1;
        $candidate = $try;
        while (isset($globalIdSet[$candidate]) || $dom->getElementById($candidate)) {
            $i++;
            $candidate = "{$try}-{$i}";
        }
        $generatedId = $candidate;
        // apply id to control and for on label
        $control->setAttribute('id', $generatedId);
        $label->setAttribute('for', $generatedId);
        $fileChanges[] = [
            'label_text' => $text,
            'action' => 'generate-id-and-link',
            'generated_id' => $generatedId,
            'control' => $ctrlTag,
            'control_name' => $control->getAttribute('name') ?: null
        ];
        $globalIdSet[$generatedId] = true;
        $counter++;
    }

    if (!empty($fileChanges)) {
        // Extract body innerHTML
        $body = $dom->getElementsByTagName('body')->item(0);
        $newInner = '';
        foreach ($body->childNodes as $c) {
            $newInner .= $dom->saveHTML($c);
        }
        // restore php blocks
        $restored = restorePhpBlocks($newInner, $phpBlocks);

        // Write report entry
        $report['modified'][] = ['file'=>$file,'changes'=>$fileChanges];

        // If apply, backup and write
        if ($apply) {
            $bak = $file . '.bak.' . date('YmdHis');
            copy($file, $bak);
            file_put_contents($file, $restored);
        } else {
            // Dry-run: create a side-by-side patch snippet
            $report['files'][] = ['file'=>$file,'changes_preview'=>$fileChanges];
        }
    } else {
        $report['files'][] = ['file'=>$file,'labels_checked'=>$labels->length,'changes'=>0];
    }
}

// Write report file
@mkdir('.reports', 0755, true);
$reportPath = '.reports/labels-fix-report.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// If applied, run php -l for modified PHP files
$phpLintResults = [];
if ($apply) {
    foreach ($report['modified'] as $m) {
        $f = $m['file'];
        if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) !== 'php') continue;
        $out = null;
        $ret = null;
        exec("php -l " . escapeshellarg($f) . " 2>&1", $out, $ret);
        $phpLintResults[$f] = ['output' => $out, 'exit' => $ret];
    }
    // append lint results
    file_put_contents($reportPath, json_encode(array_merge($report, ['php_lint'=>$phpLintResults]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Print concise summary
echo "Mode: " . ($apply ? "apply (files written, backups created)" : "dry-run (no changes written)") . PHP_EOL;
echo "Report: $reportPath" . PHP_EOL;
echo "Files modified: " . count($report['modified']) . PHP_EOL;
if ($apply) {
    echo "PHP lint results (non-zero exit means syntax error):" . PHP_EOL;
    foreach ($phpLintResults as $file=>$res) {
        echo "- $file: exit {$res['exit']}" . PHP_EOL;
        foreach ($res['output'] as $line) echo "    $line" . PHP_EOL;
    }
}
exit(0);
?>