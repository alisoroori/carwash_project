<?php
// tools/auto_fix_label_ids.php
// Auto-fix label for/id mismatches in PHP/HTML files under project root
// Usage: php tools/auto_fix_label_ids.php

$root = __DIR__ . '/../';
$dryRun = in_array('--dry-run', $argv);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$files = [];
foreach ($rii as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($ext, ['php','html','htm'])) continue;
    $files[] = $f->getPathname();
}

$report = [];
$modified = [];
foreach ($files as $path) {
    $content = file_get_contents($path);
    if ($content === false) continue;
    $original = $content;

    // find all labels with for attribute
    if (!preg_match_all('/<label[^>]*\bfor=["\']([^"\']+)["\'][^>]*>/i', $content, $m, PREG_OFFSET_CAPTURE)) {
        continue;
    }

    // gather existing ids
    preg_match_all('/\bid=["\']([^"\']+)["\']/i', $content, $idm);
    $ids = $idm[1];

    // process labels left-to-right; we'll maintain an offset delta for modifications
    $delta = 0;

    foreach ($m[0] as $idx => $labelMatch) {
        $labelHtml = $labelMatch[0];
        $labelPos = $labelMatch[1] + $delta; // adjusted
        $forValue = $m[1][$idx][0];

        // skip if id exists now
        if (in_array($forValue, $ids)) {
            continue;
        }

        // search for next control after label (input/select/textarea)
        $searchStart = $labelPos + strlen($labelHtml);
        $rest = substr($content, $searchStart);
        if (preg_match('/<(input|select|textarea)\b([^>]*)>/i', $rest, $ctl, PREG_OFFSET_CAPTURE)) {
            $ctlTag = $ctl[0][0];
            $ctlPos = $searchStart + $ctl[0][1];
            $attrs = $ctl[2][0] ?? '';
            // if control already has id
            if (preg_match('/\bid=["\']([^"\']+)["\']/i', $ctlTag, $idmatch)) {
                $existingId = $idmatch[1];
                // replace label's for value with existingId
                $newLabel = preg_replace('/(for=["\'])' . preg_quote($forValue, '/') . '(["\'])/i', '\1' . $existingId . '\2', $labelHtml, 1);
                // insert comment above label
                $comment = "<!-- Fixed label-for/id mismatch for accessibility -->\n";
                $content = substr_replace($content, $comment, $labelPos, 0);
                $delta += strlen($comment);
                // replace labelHtml at adjusted position
                $content = substr_replace($content, $newLabel, $labelPos + strlen($comment), strlen($labelHtml));
                $delta += strlen($newLabel) - strlen($labelHtml);
                $report[] = "$path: updated label for '$forValue' -> '$existingId'";
                // update ids list
                $ids[] = $existingId;
                continue;
            } else {
                // add id attribute to control tag before closing >
                $newCtlTag = preg_replace('/\s*\/?>$/', ' id="' . $forValue . '"$0', $ctlTag, 1);
                // Insert comment above control
                $comment = "<!-- Fixed label-for/id mismatch for accessibility -->\n";
                $content = substr_replace($content, $comment . $newCtlTag, $ctlPos, strlen($ctlTag));
                $delta += strlen($comment) + strlen($newCtlTag) - strlen($ctlTag);
                $report[] = "$path: added id '$forValue' to control following label";
                $ids[] = $forValue;
                continue;
            }
        } else {
            // try to find previous control within 500 chars
            $backStart = max(0, $labelPos - 500);
            $backLen = $labelPos - $backStart;
            $back = substr($content, $backStart, $backLen);
            if (preg_match_all('/<(input|select|textarea)\b([^>]*)>/i', $back, $backs, PREG_OFFSET_CAPTURE)) {
                $last = end($backs[0]);
                $ctlTag = $last[0][0];
                $ctlPos = $backStart + $last[0][1];
                if (preg_match('/\bid=["\']([^"\']+)["\']/i', $ctlTag, $idmatch)) {
                    $existingId = $idmatch[1];
                    // replace label's for value with existingId
                    $newLabel = preg_replace('/(for=["\'])' . preg_quote($forValue, '/') . '(["\'])/i', '\1' . $existingId . '\2', $labelHtml, 1);
                    $comment = "<!-- Fixed label-for/id mismatch for accessibility -->\n";
                    $content = substr_replace($content, $comment, $labelPos, 0);
                    $delta += strlen($comment);
                    $content = substr_replace($content, $newLabel, $labelPos + strlen($comment), strlen($labelHtml));
                    $delta += strlen($newLabel) - strlen($labelHtml);
                    $report[] = "$path: updated label for '$forValue' -> '$existingId' (matched previous control)";
                    $ids[] = $existingId;
                    continue;
                } else {
                    // add id to previous control
                    $newCtlTag = preg_replace('/\s*\/?>$/', ' id="' . $forValue . '"$0', $ctlTag, 1);
                    $comment = "<!-- Fixed label-for/id mismatch for accessibility -->\n";
                    $content = substr_replace($content, $comment . $newCtlTag, $ctlPos, strlen($ctlTag));
                    $delta += strlen($comment) + strlen($newCtlTag) - strlen($ctlTag);
                    $report[] = "$path: added id '$forValue' to previous control";
                    $ids[] = $forValue;
                    continue;
                }
            }
            // couldn't find nearby control to fix
            $report[] = "$path: couldn't auto-fix label for '$forValue' (no control found)";
        }
    }

    if ($content !== $original) {
        if ($dryRun) {
            $modified[] = $path; // record candidate
            $report[] = "$path: (DRYRUN) would modify file";
        } else {
            // backup
            $bak = $path . '.bak';
            if (!file_exists($bak)) copy($path, $bak);
            file_put_contents($path, $content);
            $modified[] = $path;
        }
    }
}

if ($dryRun) {
    echo "Auto-fix DRYRUN completed. Candidate files: " . count($modified) . "\n";
} else {
    echo "Auto-fix completed. Files modified: " . count($modified) . "\n";
}
foreach ($report as $r) echo $r . "\n";
