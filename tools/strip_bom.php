<?php
// Recursively scan the project for PHP files and strip any UTF-8 BOM found at start
$root = realpath(__DIR__ . '/..');
if ($root === false) {
    echo "project root not found\n";
    exit(1);
}
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$count = 0;
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    if (strtolower($file->getExtension()) !== 'php') continue;
    $f = $file->getPathname();
    $s = file_get_contents($f);
    if ($s === false) continue;
    if (substr($s,0,3) === "\xEF\xBB\xBF") {
        file_put_contents($f, substr($s,3));
        echo "Stripped BOM from: $f\n";
        $count++;
    }
}
if ($count === 0) {
    echo "No BOMs found in vendor PHP files\n";
} else {
    echo "Stripped BOMs from $count file(s)\n";
}
