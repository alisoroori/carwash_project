<?php
// Reconstruct API files from their .bak_replace backups by replacing the inlined bootstrap
// block in the backup with a short require_once to backend/includes/api_bootstrap.php

$backups = glob(__DIR__ . '/../backend/api/**/*.bak_replace');
$backups = array_merge($backups, glob(__DIR__ . '/../backend/api/*.bak_replace'));
$processed = [];
foreach ($backups as $bakPath) {
    $orig = preg_replace('/\.bak_replace$/', '', $bakPath);
    if (!is_file($bakPath) || !is_file($orig)) continue;
    $bak = file_get_contents($bakPath);
    $marker = "// API bootstrap (added by tools/add_api_bootstrap.php)";
    $pos = strpos($bak, $marker);
    if ($pos === false) continue;

    // Find anchor in backup. Prefer preserving surrounding "if (session_status...)" blocks
    $anchors = ["\nif (session_status", 'session_start()', "\ntry {", "\nnamespace ", "\nrequire_once ", "\nheader("];
    $anchorPos = false;
    foreach ($anchors as $a) {
        $p = strpos($bak, $a, $pos);
        if ($p !== false) { $anchorPos = $p; break; }
    }
    if ($anchorPos === false) {
        echo "Skipping reconstruct (no anchor found in backup): $orig\n";
        continue;
    }

    // Compute relative path from orig to backend/includes
    $rel = '';
    $relativeToApi = substr($orig, strlen(__DIR__ . '/../'));
    // orig path like backend/api/..., compute segments after backend/api/
    $prefix = 'backend/api/';
    $after = substr($orig, strlen(__DIR__ . '/../') + strlen($prefix));
    $segments = explode(DIRECTORY_SEPARATOR, $after);
    $depth = max(0, count($segments) - 1);
    $up = $depth + 1;
    $rel = str_repeat('../', $up) . 'includes/api_bootstrap.php';

    $newHeader = "require_once '{$rel}';\n\n";
    $newContent = substr($bak, 0, $pos) . $newHeader . substr($bak, $anchorPos);

    // Backup current file before overwrite
    copy($orig, $orig . '.bak_before_reconstruct');
    file_put_contents($orig, $newContent);
    echo "Reconstructed: $orig\n";
    $processed[] = $orig;
}

echo "Done. Processed " . count($processed) . " files.\n";
