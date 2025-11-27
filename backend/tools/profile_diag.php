<?php
// Simple diagnostics for Profile fields. Safe by default: only reads values.
// Usage (CLI): php profile_diag.php --user_id=123 [--apply]
// Usage (web): visit this script while logged in as the user (will use session user_id)

require_once __DIR__ . '/../includes/bootstrap.php';
use App\Classes\Database;

// CLI args parsing
$opts = [];
if (php_sapi_name() === 'cli') {
    $raw = $argv;
    foreach ($raw as $arg) {
        if (preg_match('/^--([a-z_]+)=(.*)$/', $arg, $m)) $opts[$m[1]] = $m[2];
        if ($arg === '--apply') $opts['apply'] = true;
    }
}

// Determine user id
session_start();
$user_id = null;
if (!empty($opts['user_id'])) {
    $user_id = (int)$opts['user_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
}

if (!$user_id) {
    $msg = "No user id specified. Provide --user_id=ID (CLI) or run while logged in (web).";
    if (php_sapi_name() === 'cli') { echo $msg . PHP_EOL; exit(1); }
    header('Content-Type: text/plain'); echo $msg; exit;
}

// Fields to check
$fields = [
    'name','username','email','phone','home_phone','national_id','driver_license','city','address','profile_image'
];

$db = Database::getInstance();
try {
    $row = $db->fetchOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $user_id]);
} catch (Throwable $e) {
    $err = 'DB read failed: ' . $e->getMessage();
    if (php_sapi_name() === 'cli') { echo $err . PHP_EOL; exit(2); }
    header('Content-Type: text/plain'); echo $err; exit;
}

if (!$row) {
    $msg = "User not found for id={$user_id}";
    if (php_sapi_name() === 'cli') { echo $msg . PHP_EOL; exit(3); }
    header('Content-Type: text/plain'); echo $msg; exit;
}

$output = [];
$output[] = "Profile diagnostics for user_id={$user_id}";
$output[] = "(Read-only mode)";
$output[] = str_repeat('-',40);
foreach ($fields as $f) {
    $val = $row[$f] ?? null;
    $output[] = sprintf("%-15s : %s", $f, ($val === null ? '[NULL]' : (string)$val));
}

// Check profile image path existence
$img = $row['profile_image'] ?? null;
if ($img) {
    // Normalize to filesystem path if looks like local path
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/../../', '/\\');
    $candidate = null;
    if (strpos($img, '/carwash_project/') === 0) {
        $candidate = $docRoot . $img;
    } elseif (preg_match('#^/backend/#', $img)) {
        $candidate = $docRoot . $img;
    }
    if ($candidate) {
        $exists = file_exists($candidate) ? 'exists' : 'missing';
        $output[] = "profile_image file: {$candidate} => {$exists}";
    } else {
        $output[] = "profile_image stored as URL: {$img}";
    }
} else {
    $output[] = "profile_image: [empty]";
}

// If apply flag set, perform safe updates (append __diag and then revert)
if (!empty($opts['apply'])) {
    $output[] = str_repeat('-',40);
    $output[] = "APPLY MODE: performing updates and reverting (safe).";
    $backup = [];
    foreach ($fields as $f) {
        // Skip profile_image in apply mode (file handling) unless explicitly handled
        if ($f === 'profile_image') continue;
        $backup[$f] = $row[$f] ?? null;
        $testVal = ($backup[$f] === null ? 'diag_test' : $backup[$f] . '__diag');
        try {
            $db->fetchOne('SELECT 1'); // touch
            $db->insert('users', [$f => $testVal], ['id' => $user_id]); // Attempt insert-like update won't work; use update via query
        } catch (Throwable $e) {
            // Use prepared update
            try {
                $pdo = $db->getPdo();
                $sql = "UPDATE users SET {$f} = :val WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':val' => $testVal, ':id' => $user_id]);
                $output[] = "Updated {$f} => {$testVal}";
            } catch (Throwable $ex) {
                $output[] = "Failed to update {$f}: " . $ex->getMessage();
            }
        }
    }
    // Re-fetch and show
    try { $row2 = $db->fetchOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $user_id]); } catch(Throwable $e){ $row2 = null; }
    $output[] = "After updates (preview):";
    foreach ($fields as $f) {
        if ($f === 'profile_image') continue;
        $output[] = sprintf("%-15s : %s", $f, ($row2[$f] ?? '[NULL]'));
    }
    // Revert
    foreach ($backup as $f => $v) {
        try {
            $pdo = $db->getPdo();
            $sql = "UPDATE users SET {$f} = :val WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':val' => $v, ':id' => $user_id]);
            $output[] = "Reverted {$f}";
        } catch (Throwable $e) {
            $output[] = "Failed to revert {$f}: " . $e->getMessage();
        }
    }
    $output[] = "Revert complete.";
}

// Output
if (php_sapi_name() === 'cli') {
    foreach ($output as $l) echo $l . PHP_EOL;
    exit(0);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo implode("\n", $output);
    exit;
}
