<?php
declare(strict_types=1);
/**
 * Customer_Dashboard_Test.php
 * Automated diagnostic for the Customer Dashboard area.
 * Runs in CLI or browser. Produces an HTML report (or plaintext for CLI).
 */

// Small runtime checks
if (session_status() === PHP_SESSION_NONE) @session_start();

$IS_CLI = php_sapi_name() === 'cli';
if ($IS_CLI) {
    echo "Customer Dashboard Diagnostic\n";
    echo "==========================\n\n";
}

$root = realpath(__DIR__ . '/../../');
$dashboardDir = realpath(__DIR__);
$results = [];

function add_result(array &$results, string $file, string $location, string $type, string $message, string $suggestion = '', bool $ok = false) {
    $results[] = [
        'file' => $file,
        'location' => $location,
        'type' => $type,
        'message' => $message,
        'suggestion' => $suggestion,
        'ok' => $ok,
    ];
}

// 1) Scan files
add_result($results, 'scan', '', 'info', "Scanning dashboard directory: $dashboardDir", '', true);

$patterns = [
    $dashboardDir . '/**/*.php',
    $dashboardDir . '/**/*.js',
    $dashboardDir . '/**/*.css',
    $dashboardDir . '/**/*.html',
];

// Simple glob that supports ** by using RecursiveDirectoryIterator
function rr_files(string $dir, array $exts) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
        if (in_array($ext, $exts, true)) $files[] = $f->getPathname();
    }
    return $files;
}

$files = rr_files($dashboardDir, ['php','js','css','html']);
add_result($results, 'scan', '', 'info', 'Files found: ' . count($files), '', true);

// 2) PHP syntax check for PHP files
foreach ($files as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext !== 'php') continue;
    // Use PHP lint
    $cmd = escapeshellarg((PHP_BINARY ?? 'php')) . ' -l ' . escapeshellarg($file) . ' 2>&1';
    $output = null; $rc = 0;
    @exec($cmd, $output, $rc);
    $out = is_array($output) ? implode("\n", $output) : (string)$output;
    if ($rc !== 0) {
        add_result($results, $file, 'php -l', 'syntax-error', trim($out), 'Fix PHP syntax on this file', false);
    } else {
        add_result($results, $file, 'php -l', 'syntax', 'No syntax errors', '', true);
    }
}

// 3) Look for csrf token in dashboard page and meta
$dashboardIndex = $dashboardDir . '/Customer_Dashboard.php';
if (file_exists($dashboardIndex)) {
    $content = file_get_contents($dashboardIndex);
    if (strpos($content, 'csrf_token') !== false || strpos($content, 'meta name="csrf-token"') !== false) {
        add_result($results, $dashboardIndex, 'csrf', 'check', 'CSRF token appears to be present in dashboard markup', '', true);
    } else {
        add_result($results, $dashboardIndex, 'csrf', 'missing', 'CSRF token not found in dashboard markup', 'Ensure a meta[name=csrf-token] or hidden input is rendered', false);
    }
} else {
    add_result($results, $dashboardIndex, 'file', 'missing', 'Customer_Dashboard.php not found', 'Confirm file location', false);
}

// 4) API endpoints check (include-based safe list)
$apiCandidates = [
    $dashboardDir . '/vehicle_api.php',
    $dashboardDir . '/vehicle_api.php',
    realpath($root . '/backend/api/booking_api.php'),
    realpath($root . '/backend/dashboard/vehicle_api.php'),
];
$apiCandidates = array_filter(array_unique($apiCandidates));
foreach ($apiCandidates as $api) {
    if (!$api || !file_exists($api)) continue;
    // attempt a safe include emulating a GET list action
    $oldGet = $_GET; $oldPost = $_POST; $oldServer = $_SERVER;
    $_GET = ['action' => 'list'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    try {
        include $api;
        $out = ob_get_clean();
    } catch (Throwable $e) {
        $out = 'EXCEPTION: ' . $e->getMessage(); ob_end_clean();
    }
    // attempt to extract JSON
    $json = null; $trim = trim($out);
    if ($trim !== '') {
        $json = json_decode($trim, true);
        if ($json === null) {
            // try to find JSON substring
            $first = strpos($trim, '{'); $last = strrpos($trim, '}');
            if ($first !== false && $last !== false && $last > $first) {
                $sub = substr($trim, $first, $last - $first + 1);
                $json = json_decode($sub, true);
            }
        }
    }
    if (is_array($json)) {
        $hasSuccess = array_key_exists('success', $json);
        $hasMessage = array_key_exists('message', $json);
        if ($hasSuccess && $hasMessage) {
            add_result($results, $api, 'include?action=list', 'api', 'API responded with JSON and has success/message', '', true);
        } else {
            add_result($results, $api, 'include?action=list', 'api-structure', 'API JSON missing success or message', 'Standardize API to return {success,message,data}', false);
        }
    } else {
        add_result($results, $api, 'include?action=list', 'api-response', 'API did not return JSON or returned HTML/text. See captured output', 'Ensure endpoint returns JSON for AJAX endpoints', false);
    }
    $_GET = $oldGet; $_POST = $oldPost; $_SERVER = $oldServer;
}

// 5) Database checks
$conf = $root . '/backend/includes/config.php';
$dbInfo = ['host'=>'127.0.0.1','name'=>'','user'=>'root','pass'=>'','port'=>3306];
if (file_exists($conf)) {
    try { include_once $conf; } catch (Throwable $e) {}
    $dbInfo['host'] = defined('DB_HOST') ? DB_HOST : $dbInfo['host'];
    $dbInfo['name'] = defined('DB_NAME') ? DB_NAME : $dbInfo['name'];
    $dbInfo['user'] = defined('DB_USER') ? DB_USER : $dbInfo['user'];
    $dbInfo['pass'] = defined('DB_PASS') ? DB_PASS : $dbInfo['pass'];
    $dbInfo['port'] = defined('DB_PORT') ? DB_PORT : $dbInfo['port'];
}
if (empty($dbInfo['name'])) {
    add_result($results, $conf, 'db', 'missing', 'Database name not configured. Skipping DB checks.', 'Set DB_NAME in config.php or environment', false);
} else {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbInfo['host'], $dbInfo['port'], $dbInfo['name']);
        $pdo = new PDO($dsn, $dbInfo['user'], $dbInfo['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        add_result($results, 'database', '', 'connect', 'Connected to database ' . $dbInfo['name'], '', true);

        $requiredTables = ['users','vehicles','bookings','services','payments'];
        foreach ($requiredTables as $t) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
            $stmt->execute([':t'=>$t]);
            $found = (bool)$stmt->fetchColumn();
            if ($found) add_result($results, $dbInfo['name'], $t, 'table', 'Table exists', '', true);
            else add_result($results, $dbInfo['name'], $t, 'table-missing', 'Table missing', "Consider running migrations or importing database/carwash.sql (missing table: $t)", false);
        }
    } catch (Throwable $e) {
        add_result($results, 'database', '', 'connect-error', 'DB connection failed: ' . $e->getMessage(), 'Check DB credentials', false);
    }
}

// 6) Frontend JS analysis: find fetch() URLs and event bindings
foreach ($files as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext !== 'js' && $ext !== 'php' && $ext !== 'html') continue;
    $txt = @file_get_contents($file);
    if ($txt === false) continue;
    // find fetch() calls
    if (preg_match_all('/fetch\(([^)]+)\)/i', $txt, $m)) {
        foreach ($m[1] as $call) {
            // crude extract of url string
            if (preg_match('/["\']([^"\']+vehicle_api\.php[^"\']*)["\']/i', $call, $u)) {
                add_result($results, $file, 'fetch', 'fetch-url', 'Found fetch() to vehicle_api.php -> ' . $u[1], '', true);
            }
        }
    }
    // check for event bindings for vehicle form
    if (strpos($txt, 'vehicleFormInline') !== false || strpos($txt, 'vehicleInlineSubmit') !== false) {
        add_result($results, $file, 'events', 'binding', 'References vehicle form or submit button; ensure event listeners are attached in DOMContentLoaded', '', true);
    }
}

// 7) Check that form submissions send 'action' fields correctly by inspecting JS wiring
$customerFormsJs = $dashboardDir . '/customer_dashboard_forms.js';
if (file_exists($customerFormsJs)) {
    $cf = file_get_contents($customerFormsJs);
    if (strpos($cf, "fd.set('action'") !== false || strpos($cf, "form.querySelector('#vehicleFormAction')") !== false) {
        add_result($results, $customerFormsJs, 'action', 'ok', "JS sets 'action' field for vehicle form", '', true);
    } else {
        add_result($results, $customerFormsJs, 'action', 'missing', "JS doesn't appear to set 'action' field on FormData; backend may receive unknown action", "Ensure code sets fd.set('action', 'create'|'update'|'delete') before fetch", false);
    }
} else {
    add_result($results, $customerFormsJs, 'file', 'missing', 'customer_dashboard_forms.js not found', '', false);
}

// 8) Summarize and output report
function render_report(array $results, bool $cli=true) {
    if ($cli) {
        foreach ($results as $r) {
            $status = $r['ok'] ? 'OK' : 'FAIL';
            echo "[{$status}] ({$r['type']}) {$r['file']} @ {$r['location']}: {$r['message']}" . PHP_EOL;
            if (!$r['ok'] && $r['suggestion']) echo "    Suggestion: {$r['suggestion']}" . PHP_EOL;
        }
        return;
    }
    // HTML output
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Customer Dashboard Test Report</title>';
    echo '<style>body{font-family:system-ui,Segoe UI,Arial;background:#fafafa;color:#111} .ok{color:green}.fail{color:red} .card{background:#fff;padding:12px;border-radius:8px;margin:8px 0;box-shadow:0 1px 3px rgba(0,0,0,0.06)}</style>';
    echo '</head><body><h1>Customer Dashboard Test Report</h1>';
    echo '<div>'; 
    foreach ($results as $r) {
        $cls = $r['ok'] ? 'ok' : 'fail';
        echo "<div class=\"card\"><div><strong class=\"{$cls}\">" . ($r['ok']? '✅ OK':'❌ ERROR') . "</strong> ";
        echo "<strong> [{$r['type']}]</strong> <em>{$r['file']}</em> at <code>{$r['location']}</code><div>{$r['message']}</div>";
        if (!$r['ok'] && $r['suggestion']) echo "<div><strong>Suggestion:</strong> {$r['suggestion']}</div>";
        echo '</div></div>';
    }
    echo '</div></body></html>';
}

render_report($results, $IS_CLI);

// Update todo list item completion (developer note)
// End of test file

