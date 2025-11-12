<?php
// Scans PHP/HTML files under backend/ for <label for="..."> where no element has id="..." in the same file
$root = isset($argv[1]) ? $argv[1] : (__DIR__ . '/../backend');
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$problems = [];
foreach ($rii as $f) {
    if (!$f->isFile()) continue;
    $path = $f->getPathname();
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), ['php', 'html', 'htm'])) continue;
    $s = file_get_contents($path);
    if ($s === false) continue;
    preg_match_all('/<label[^>]*\sfor=\"([^\"]+)\"/i', $s, $L);
    preg_match_all('/\sid=\"([^\"]+)\"/i', $s, $I);
    $labels = array_unique($L[1]);
    $ids = array_unique($I[1]);
    if (count($labels) === 0) continue;
    $idmap = array_flip($ids);

    // missing id occurrences
    foreach ($labels as $lab) {
        if (!isset($idmap[$lab])) {
            $problems[] = [
                'type' => 'missing_id',
                'file' => $path,
                'label' => $lab,
            ];
        }
    }

    // duplicate label occurrences (same for used more than once)
    $counts = array_count_values($L[1]);
    foreach ($counts as $lab => $cnt) {
        if ($cnt > 1) {
            // only report if there is at least one matching id (duplicates are unnecessary)
            $problems[] = [
                'type' => 'duplicate_label',
                'file' => $path,
                'label' => $lab,
                'count' => $cnt,
            ];
        }
    }
}
if (count($problems) === 0) {
    echo "No missing label->id matches found under backend/.\n";
    exit(0);
}
foreach ($problems as $p) {
    echo $p['file'] . " - missing id: " . $p['label'] . "\n";
}
exit(0);
