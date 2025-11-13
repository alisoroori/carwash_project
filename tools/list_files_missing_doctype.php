<?php
// Scan repo for .php and .html files that contain <html|<head|<body but lack a <!DOCTYPE html> declaration
$root = realpath(__DIR__ . '/../');
$reportDir = __DIR__ . '/reports';
if (!is_dir($reportDir)) {
    @mkdir($reportDir, 0777, true);
}
$excludes = ['vendor','node_modules','dist','tools','tests','.git','logs','uploads','database'];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$results = [];
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($ext, ['php','html'])) continue;
    $path = $file->getRealPath();
    $skip = false;
    foreach ($excludes as $e) {
        if (strpos($path, DIRECTORY_SEPARATOR . $e . DIRECTORY_SEPARATOR) !== false) { $skip = true; break; }
    }
    if ($skip) continue;
    $content = @file_get_contents($path);
    if ($content === false) continue;
    if (preg_match('#<html|<head|<body#is', $content) && !preg_match('#<!DOCTYPE\s+html>|<!doctype\s+html>#i', $content)) {
        $results[] = $path;
    }
}
$out = $reportDir . '/missing_doctype.json';
file_put_contents($out, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Wrote $out (" . count($results) . " entries)\n";
