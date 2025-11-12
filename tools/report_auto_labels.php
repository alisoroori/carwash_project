<?php
// Usage: php report_auto_labels.php [path]
$root = isset($argv[1]) ? $argv[1] : __DIR__ . '/../backend';
if (!is_dir($root) && !is_file($root)) {
    fwrite(STDERR, "Path not found: $root\n");
    exit(2);
}
$files = [];
if (is_file($root)) {
    $files[] = $root;
} else {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($rii as $f) {
        if (!$f->isFile()) continue;
        $ext = strtolower(pathinfo($f->getPathname(), PATHINFO_EXTENSION));
        if (!in_array($ext, ['php','html','htm'])) continue;
        $files[] = $f->getPathname();
    }
}

$totalLabels = 0;
$totalCorrect = 0;
$totalIssues = 0;
$filesWithIssues = [];

foreach ($files as $file) {
    $s = @file_get_contents($file);
    if ($s === false) continue;
    $lines = preg_split("/\r?\n/", $s);

    // find all ids in file
    preg_match_all('/\sid\s*=\s*"([^"]+)"/i', $s, $IdMatches);
    $ids = $IdMatches[1];
    $idCounts = array_count_values($ids);

    // find label occurrences with auto_label pattern
    preg_match_all('/<label[^>]*for\s*=\s*"(auto_label_\d+)"[^>]*>(.*?)<\/label>/is', $s, $L);
    $labels = $L[1];
    $labelHtml = $L[0];
    $labelText = $L[2];
    if (count($labels) === 0) continue;

    $fileTotal = count($labels);
    $fileCorrect = 0;
    $fileIssues = [];

    for ($i=0;$i<$fileTotal;$i++) {
        $for = $labels[$i];
        $text = trim(strip_tags($labelText[$i]));
        // find approximate line number by searching raw labelHtml occurrence in lines
        $snippet = $labelHtml[$i];
        $ln = null;
        foreach ($lines as $n => $line) {
            if (strpos($line, $snippet) !== false) { $ln = $n+1; break; }
        }
        if ($ln === null) {
            // fallback: search for 'for="..."'
            foreach ($lines as $n => $line) {
                if (strpos($line, 'for="'.$for.'"') !== false) { $ln = $n+1; break; }
            }
        }
        $hasField = in_array($for, $ids);
        $duplicateId = isset($idCounts[$for]) && $idCounts[$for] > 1;
        $textIssue = ($text === '' || preg_match('/^\s*(form|label|input|field)\s*$/i', $text));

        $labelOk = $hasField && !$duplicateId && !$textIssue;
        if ($labelOk) $fileCorrect++; else {
            $fileIssues[] = [
                'for' => $for,
                'line' => $ln,
                'text' => $text,
                'has_field' => $hasField,
                'duplicate_id' => $duplicateId,
                'text_issue' => $textIssue,
            ];
        }
    }

    $totalLabels += $fileTotal;
    $totalCorrect += $fileCorrect;
    $totalIssues += count($fileIssues);

    if (count($fileIssues) > 0) {
        $filesWithIssues[$file] = [
            'total' => $fileTotal,
            'correct' => $fileCorrect,
            'issues' => $fileIssues,
        ];
    }
}

// Print report
echo "Auto-label scan report\n";
echo "Root: $root\n\n";
echo "Total labels checked: $totalLabels\n";
echo "Correct associations: $totalCorrect\n";
echo "Issues remaining: $totalIssues\n\n";
if (count($filesWithIssues) === 0) {
    echo "No files with auto_label issues found.\n";
    exit(0);
}

echo "Files with issues:\n";
foreach ($filesWithIssues as $f => $info) {
    echo "- $f (labels: {$info['total']}, correct: {$info['correct']}, issues: " . count($info['issues']) . ")\n";
    foreach ($info['issues'] as $iss) {
        $line = $iss['line'] ?? 'N/A';
        echo "    * for=\"{$iss['for']}\" line={$line} has_field=".($iss['has_field']? 'yes':'no')." duplicate_id=".($iss['duplicate_id']? 'yes':'no')." text_issue=".($iss['text_issue']? 'yes':'no')." text='".($iss['text']?:'[empty]')."'\n";
    }
}

exit(0);
