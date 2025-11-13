<?php
// Scan backend/ and frontend/ for <img> tags without alt attributes
// and add a placeholder alt="TODO: add alt text". Back up modified files to .bak_alt

$root = getcwd();
$targets = ['backend', 'frontend'];
$exts = ['php','html','htm','js'];

$changedFiles = [];

function shouldProcess($file) {
    global $root, $targets, $exts;
    $rel = str_replace('\\', '/', substr($file, strlen($root) + 1));
    foreach ($targets as $t) {
        if (strpos($rel, $t . '/') === 0) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), $exts)) return true;
        }
    }
    return false;
}

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (!shouldProcess($path)) continue;

    $content = file_get_contents($path);
    // regex: match <img ...> where there is no alt= attribute
    $pattern = '/<img\b(?=[^>]*>)(?![^>]*\balt\s*=)[^>]*>/i';
    $matches = [];
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $new = $content;
        $offsetAdjustment = 0;
        $count = 0;
        foreach ($matches[0] as $m) {
            $tag = $m[0];
            $pos = $m[1] + $offsetAdjustment;
            // Build replacement tag by inserting placeholder alt before the final > or />
            $replacement = $tag;
            if (substr($tag, -2) === '/>') {
                $replacement = substr($tag, 0, -2) . ' alt="TODO: add alt text" />';
            } else {
                // ends with >
                $replacement = substr($tag, 0, -1) . ' alt="TODO: add alt text">';
            }
            $new = substr_replace($new, $replacement, $pos, strlen($tag));
            $offsetAdjustment += strlen($replacement) - strlen($tag);
            $count++;
        }

        if ($count > 0) {
            // backup
            $bak = $path . '.bak_alt';
            if (!file_exists($bak)) copy($path, $bak);
            file_put_contents($path, $new);
            $changedFiles[$path] = $count;
            echo "Patched $path (added $count alt placeholders)\n";
        }
    }
}

echo "\nDone. Files modified: " . count($changedFiles) . "\n";
if (count($changedFiles) > 0) {
    foreach ($changedFiles as $f => $c) echo " - $f : $c images\n";
}
