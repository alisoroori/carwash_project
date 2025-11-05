<?php
// PHP fixer: insert minimal catch blocks after try{...} where missing
// Usage: php tools/fix_missing_catch.php

function find_matching_brace($src, $pos) {
    $len = strlen($src);
    $depth = 0;
    $i = $pos;
    $inSingle = false; $inDouble = false; $inComment = false; $inBlockComment = false;
    for (; $i < $len; $i++) {
        $ch = $src[$i];
        $next = ($i+1 < $len) ? $src[$i+1] : '';
        if ($inComment) {
            if ($ch === "\n") $inComment = false;
            continue;
        }
        if ($inBlockComment) {
            if ($ch === '*' && $next === '/') { $inBlockComment = false; $i++; continue; }
            continue;
        }
        if (!$inSingle && !$inDouble) {
            if ($ch === '/' && $next === '/') { $inComment = true; $i++; continue; }
            if ($ch === '/' && $next === '*') { $inBlockComment = true; $i++; continue; }
        }
        if ($ch === "\\") { $i++; continue; }
        if ($ch === "'" && !$inDouble) { $inSingle = !$inSingle; continue; }
        if ($ch === '"' && !$inSingle) { $inDouble = !$inDouble; continue; }
        if ($ch === '{' && !$inSingle && !$inDouble) { $depth++; }
        if ($ch === '}' && !$inSingle && !$inDouble) {
            $depth--;
            if ($depth === 0) return $i;
        }
    }
    return -1;
}

$target = __DIR__ . '/../backend/dashboard/Customer_Dashboard.php';
if (!file_exists($target)) {
    fwrite(STDERR, "Target file not found: {$target}\n");
    exit(2);
}

$src = file_get_contents($target);
$backup = $target . '.bak.' . time();
copy($target, $backup);
echo "Backup written to: $backup\n";

$offset = 0;
$changes = 0;
// find all try { occurrences using regex
if (preg_match_all('/try\s*\{/i', $src, $m, PREG_OFFSET_CAPTURE)) {
    // we'll process from end to start to avoid messing offsets
    $matches = $m[0];
    $count = count($matches);
    for ($mi = $count - 1; $mi >= 0; $mi--) {
        $match = $matches[$mi];
        $pos = $match[1];
        // find position of the first brace '{' after pos
        $bracePos = strpos($src, '{', $pos);
        if ($bracePos === false) continue;
        $closePos = find_matching_brace($src, $bracePos);
        if ($closePos === -1) continue;
        // find next non-whitespace/comment token after closePos
        $i = $closePos + 1;
        $len = strlen($src);
        // skip whitespace
        while ($i < $len && preg_match('/\s/', $src[$i])) $i++;
        // skip // comments
        if ($i+1 < $len && $src[$i] === '/' && $src[$i+1] === '/') {
            $i += 2; while ($i < $len && $src[$i] !== "\n") $i++; while ($i < $len && preg_match('/\s/', $src[$i])) $i++;
        }
        // skip /* */ comments
        if ($i+1 < $len && $src[$i] === '/' && $src[$i+1] === '*') {
            $i += 2; while ($i+1 < $len && !($src[$i] === '*' && $src[$i+1] === '/')) $i++; $i += 2; while ($i < $len && preg_match('/\s/', $src[$i])) $i++;
        }
        $next = strtolower(substr($src, $i, 7));
        if (strpos($next, 'catch') === 0 || strpos($next, 'finally') === 0) {
            continue; // ok
        }
        // insert catch block after closePos
        $catch = "\ncatch (error) { console.error(error); }\n";
        $src = substr($src, 0, $closePos+1) . $catch . substr($src, $closePos+1);
        $changes++;
    }
}

if ($changes > 0) {
    file_put_contents($target, $src);
    echo "Inserted $changes catch block(s) into $target\n";
} else {
    echo "No missing catch/finally blocks found.\n";
}

echo "Done. Review $backup if you need to revert.\n";
