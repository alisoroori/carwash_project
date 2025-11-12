<?php
// Usage: php check_index_labels.php path/to/file
$file = isset($argv[1]) ? $argv[1] : __DIR__ . '/../backend/index.php';
if (!is_file($file)) {
    fwrite(STDERR, "File not found: $file\n");
    exit(2);
}
$s = file_get_contents($file);
if ($s === false) {
    fwrite(STDERR, "Could not read file: $file\n");
    exit(2);
}
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTML($s);
$xpath = new DOMXPath($doc);

$idNodes = $xpath->query('//*[@id]');
$ids = [];
foreach ($idNodes as $n) $ids[] = $n->getAttribute('id');
$ids = array_unique($ids);

$labels = $xpath->query('//label');
$missing = [];
foreach ($labels as $lab) {
    $for = $lab->getAttribute('for');
    if ($for === '') {
        // if label wraps an input/select/textarea, consider it associated
        $hasChild = false;
        foreach (['input','select','textarea'] as $t) {
            if ($lab->getElementsByTagName($t)->length > 0) { $hasChild = true; break; }
        }
        if (!$hasChild) {
            $missing[] = [
                'type' => 'no_for',
                'text' => trim($lab->textContent)
            ];
        }
    } else {
        if (!in_array($for, $ids)) {
            $missing[] = [
                'type' => 'for_missing_id',
                'for' => $for,
                'text' => trim($lab->textContent)
            ];
        }
    }
}

if (count($missing) === 0) {
    echo "No missing label associations in: $file\n";
    exit(0);
}
echo "Missing label associations in: $file\n";
foreach ($missing as $m) {
    if ($m['type'] === 'no_for') {
        echo " - label with no 'for' and no wrapped control: '" . ($m['text'] ?: '[no text]') . "'\n";
    } else {
        echo " - label for=\"{$m['for']}\" has no matching id\n";
    }
}
exit(1);
