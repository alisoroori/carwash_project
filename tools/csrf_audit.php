<?php
// Simple CSRF audit tool for this workspace
// Usage: php csrf_audit.php
$root = __DIR__ . '/../backend';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$formsMissing = [];
$apiChecks = [];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (substr($path, -4) !== '.php') continue;
    $rel = str_replace(getcwd() . '\\', '', $path);
    $content = file_get_contents($path);
    if ($content === false) continue;

    $hasForm = stripos($content, '<form') !== false;
    $hasCsrf = stripos($content, 'csrf_token') !== false;
    if ($hasForm && !$hasCsrf) {
        // capture few lines around first <form
        $lines = preg_split("/\r?\n/", $content);
        $matchLine = -1;
        foreach ($lines as $i => $line) {
            if (stripos($line, '<form') !== false) { $matchLine = $i; break; }
        }
        $snippet = '';
        if ($matchLine >= 0) {
            $start = max(0, $matchLine - 3);
            $end = min(count($lines) - 1, $matchLine + 3);
            $snippet = implode("\n", array_slice($lines, $start, $end - $start + 1));
        }
        $formsMissing[] = [
            'path' => $path,
            'snippet' => $snippet,
        ];
    }

    // For API-style files: check if they reference hash_equals or verify_csrf_token and then ensure session_start exists
    $isApi = preg_match('#/api/#i', $path);
    $validatesCsrf = stripos($content, 'hash_equals') !== false || stripos($content, 'verify_csrf_token') !== false || stripos($content, 'csrf_token') !== false;
    if ($isApi && $validatesCsrf) {
        $hasSessionStart = stripos($content, 'session_start(') !== false || stripos($content, 'Session::start') !== false || stripos($content, 'session_status()') !== false;
        if (!$hasSessionStart) {
            $apiChecks[] = [
                'path' => $path,
                'reason' => 'validates CSRF but no session_start/session_status call found (may rely on includes)',
            ];
        }
    }
}

$out = [
    'formsMissingCsrf' => $formsMissing,
    'apiSessionIssues' => $apiChecks,
    'summary' => [
        'formsMissing' => count($formsMissing),
        'apiSessionIssues' => count($apiChecks),
    ],
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
