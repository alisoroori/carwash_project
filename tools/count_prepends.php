<?php
$report = json_decode(file_get_contents(__DIR__ . '/reports/prepend_header_report.json'), true);
$counts = ['would_prepend'=>0,'already_includes'=>0,'has_doctype'=>0,'skipped_api_or_class'=>0,'other'=>0];
foreach ($report as $p => $v) {
    if (isset($counts[$v])) $counts[$v]++; else $counts['other']++;
}
echo json_encode($counts, JSON_PRETTY_PRINT) . "\n";
// Also print a short list of candidates
echo "\nSample 'would_prepend' files:\n";
$printed = 0;
foreach ($report as $p => $v) {
    if ($v === 'would_prepend') {
        echo " - $p\n";
        $printed++; if ($printed>=20) break;
    }
}
