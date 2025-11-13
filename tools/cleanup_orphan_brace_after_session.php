<?php
// Cleanup orphan closing brace immediately after session_start(); left by naive replacement
$files = [];
exec('git ls-files "backend/api/*.php" "backend/api/**/**/*.php" "backend/api/**/*.php"', $files);
$count = 0;
foreach ($files as $file) {
    if (!is_file($file)) continue;
    $content = file_get_contents($file);
    // Look for pattern: session_start(); followed by a standalone closing brace on next line (within first 50 lines)
    $firstPart = substr($content, 0, 2000);
    if (preg_match("~session_start\s*\(\s*\)\s*;\s*\r?\n\s*\}\s*\r?\n~i", $firstPart)) {
        $new = preg_replace("~(session_start\s*\(\s*\)\s*;\s*)\r?\n\s*\}\s*\r?\n~i", "\\1\n\n", $content, 1);
        copy($file, $file . '.bak_cleanup');
        file_put_contents($file, $new);
        echo "Cleaned orphan brace in: $file\n";
        $count++;
    }
}
echo "Done. Cleaned $count files.\n";
