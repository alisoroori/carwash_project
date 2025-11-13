<?php
// Replace inlined API bootstrap blocks (added by tools/add_api_bootstrap.php)
// with a short require_once to the centralized backend/includes/api_bootstrap.php

$files = [];
// gather files tracked by git under backend/api
exec('git ls-files "backend/api/*.php" "backend/api/**/**/*.php" "backend/api/**/*.php"', $files);

$marker = "// API bootstrap (added by tools/add_api_bootstrap.php)";
$changed = [];

foreach ($files as $file) {
    if (!is_file($file)) continue;
    $content = file_get_contents($file);
    if (strpos($content, $marker) === false) continue;

    // Find marker position
    $pos = strpos($content, $marker);
    // Find session_start() after the marker
    $sessionPos = strpos($content, 'session_start()', $pos);
    $anchorPos = $sessionPos;
    if ($anchorPos === false) {
        // Try other anchors: namespace, require_once, header(, or opening try
        $anchors = ['\nnamespace ', "\nrequire_once ", "\nheader(", "\ntry {", "\n//"]; 
        $found = false;
        foreach ($anchors as $a) {
            $p = strpos($content, $a, $pos);
            if ($p !== false) {
                $anchorPos = $p;
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "Skipping (no anchor found): $file\n";
            continue;
        }
    }

    // Compute relative path from file to backend/includes/api_bootstrap.php
    // File path starts with 'backend/api/'. We need to climb up (number of segments after backend/api) + 1
    $rel = '';
    $afterPrefix = substr($file, strlen('backend/api/'));
    $segments = explode('/', $afterPrefix);
    $depth = 0;
    if (count($segments) > 0) {
        // If last segment contains .php, reduce count by 1
        $depth = max(0, count($segments) - 1);
    }
    // When at backend/api root, depth = 0 => need one ../ to reach backend/includes
    $up = $depth + 1;
    $rel = str_repeat('../', $up) . 'includes/api_bootstrap.php';

    // Replace from marker start up to the selected anchor
    $newHeader = "require_once '{$rel}';\n\n";
    $newContent = substr($content, 0, $pos) . $newHeader . substr($content, $anchorPos);

    // Backup
    copy($file, $file . '.bak_replace');
    file_put_contents($file, $newContent);
    $changed[] = $file;
    echo "Patched: $file -> requires {$rel}\n";
}

echo "Done. Patched " . count($changed) . " files.\n";
