<?php
declare(strict_types=1);

/**
 * File Name: test_customer_dashboard.php
 * Path: backend/dashboard/test_customer_dashboard.php
 * Summary: Single-page diagnostic that checks DB connectivity, required tables/columns,
 * runs key dashboard queries for a logged-in customer or a test user (via ?test_user_id=),
 * and outputs a readable PASS/FAIL HTML report with masked $_SESSION dump and collapsible debug.
 *
 * Run locally:
 * http://localhost/carwash_project/backend/dashboard/test_customer_dashboard.php?test_user_id=1
 *
 * Notes:
 * - The script will attempt to require project bootstrap/autoload if available.
 * - It never prints secrets (DB passwords are never shown).
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Helpers for output
function badge(string $status): string {
    return $status === 'PASS' ? '<span class="badge pass">PASS</span>' : '<span class="badge fail">FAIL</span>';
}

function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Attempt to bootstrap project (vendor/autoload.php or includes/bootstrap.php)
$bootedPaths = [];
$possibleBootstrap = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../includes/bootstrap.php',
    __DIR__ . '/../includes/config.php'
];
foreach ($possibleBootstrap as $p) {
    if (file_exists($p)) {
        try {
            require_once $p;
            $bootedPaths[] = $p;
        } catch (\Throwable $e) {
            // ignore - we'll fall back to env
        }
    }
}

// Determine mode: session vs test param
$mode = 'auto';
$testedUserId = null;
if (!empty($_GET['test_user_id']) && is_numeric((string)$_GET['test_user_id'])) {
    $mode = 'test_param';
    $testedUserId = (int)$_GET['test_user_id'];
} else {
    // Try to detect logged-in user from Session wrappers or legacy keys
    $detected = null;
    // Common session wrappers
    if (class_exists(\App\Classes\Session::class) && method_exists(\App\Classes\Session::class, 'get')) {
        try {
            $s = \App\Classes\Session::get('user_id') ?? \App\Classes\Session::get('user')['id'] ?? null;
            if (!empty($s)) $detected = (int)$s;
        } catch (\Throwable $e) { /* ignore */ }
    }
    // Legacy session keys
    if ($detected === null) {
        if (!empty($_SESSION['user_id'])) $detected = (int)$_SESSION['user_id'];
        elseif (!empty($_SESSION['user']['id'])) $detected = (int)$_SESSION['user']['id'];
    }
    if ($detected !== null) {
        $mode = 'session';
        $testedUserId = $detected;
    } else {
        $mode = 'no_session';
    }
}

// Database connection: prefer getDBConnection() or Database::getInstance(), else use env/constants
$pdo = null;
$dbInfo = ['host' => 'unknown', 'name' => 'unknown'];
$connectionErrors = [];

try {
    // 1) If function getDBConnection exists (legacy), use it
    if (function_exists('getDBConnection')) {
        try {
            $pdo = getDBConnection();
            if ($pdo instanceof \PDO) {
                // attempt to read DB constants if defined
                $dbInfo['host'] = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: $dbInfo['host']);
                $dbInfo['name'] = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: $dbInfo['name']);
            }
        } catch (\Throwable $e) {
            $connectionErrors[] = 'getDBConnection() failed: ' . $e->getMessage();
            $pdo = null;
        }
    }
    // 2) If App\Classes\Database exists and exposes PDO, try that
    if (!$pdo && class_exists(\App\Classes\Database::class)) {
        try {
            $dbInstance = \App\Classes\Database::getInstance();
            if (method_exists($dbInstance, 'getPdo')) {
                $maybePdo = $dbInstance->getPdo();
                if ($maybePdo instanceof \PDO) {
                    $pdo = $maybePdo;
                }
            }
            // best-effort to read constants
            $dbInfo['host'] = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: $dbInfo['host']);
            $dbInfo['name'] = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: $dbInfo['name']);
        } catch (\Throwable $e) {
            $connectionErrors[] = 'Database::getInstance() failed: ' . $e->getMessage();
            $pdo = null;
        }
    }
    // 3) Fallback: use env vars or constants
    if (!$pdo) {
        $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1');
        $name = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: '');
        $user = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
        $pass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');
        $port = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: 3306);
        $dbInfo['host'] = $host;
        $dbInfo['name'] = $name ?: 'unknown';

        if (empty($name)) {
            $connectionErrors[] = 'DB name not provided via config or environment';
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            try {
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (\Throwable $e) {
                $connectionErrors[] = 'PDO connection failed: ' . $e->getMessage();
                $pdo = null;
            }
        }
    }
} catch (\Throwable $e) {
    $connectionErrors[] = 'Unexpected connection error: ' . $e->getMessage();
    $pdo = null;
}

// Define checks container
$checks = [];

// Check A: Database connection
if ($pdo instanceof \PDO) {
    $checks[] = [
        'id' => 'db_connection',
        'label' => 'Database connection (PDO)',
        'ok' => true,
        'details' => 'Connected to DB',
        'meta' => ['host' => $dbInfo['host'], 'database' => $dbInfo['name']],
    ];
} else {
    $checks[] = [
        'id' => 'db_connection',
        'label' => 'Database connection (PDO)',
        'ok' => false,
        'details' => 'No PDO connection available',
        'meta' => ['errors' => $connectionErrors],
    ];
}

// Utility: table exists via information_schema
function tableExists(PDO $pdo, string $tableName, string $schema = null): bool {
    $schema = $schema ?? $pdo->query('select database()')->fetchColumn();
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':schema' => $schema, ':table' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $tableName, string $columnName, string $schema = null): bool {
    $schema = $schema ?? $pdo->query('select database()')->fetchColumn();
    $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table AND column_name = :col LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':schema' => $schema, ':table' => $tableName, ':col' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

// Tables and required columns to check
$required = [
    'bookings' => ['id', 'user_id', 'status', 'carwash_id', 'created_at'],
    'users' => ['id', 'email', 'name'],
    'carwash_profiles' => ['id', 'business_name', 'address'],
    'services' => ['id', 'name', 'price'],
    'payments' => ['id', 'booking_id', 'total_amount', 'status'],
    'booking_status' => ['id', 'name'],
];

// Check B: Required tables and columns
if ($pdo instanceof \PDO) {
    $schema = $pdo->query('select database()')->fetchColumn();
    foreach ($required as $table => $cols) {
        $exists = tableExists($pdo, $table, $schema);
        $colResults = [];
        if ($exists) {
            foreach ($cols as $c) {
                $colResults[$c] = columnExists($pdo, $table, $c, $schema);
            }
        }
        $allColsOk = $exists && !in_array(false, $colResults, true);
        $checks[] = [
            'id' => 'table_' . $table,
            'label' => "Table exists and columns: {$table}",
            'ok' => $allColsOk,
            'details' => $exists ? 'Table found' : 'Table missing',
            'meta' => ['columns' => $colResults],
        ];
    }
}

// Helper to run query with timing and return info
function timedQuery(PDO $pdo, string $sql, array $params = []): array {
    $start = microtime(true);
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $time = microtime(true) - $start;
        return ['ok' => true, 'rows' => $rows, 'count' => count($rows), 'time' => $time, 'sql' => $sql, 'params' => $params];
    } catch (\Throwable $e) {
        $time = microtime(true) - $start;
        return ['ok' => false, 'error' => $e->getMessage(), 'time' => $time, 'sql' => $sql, 'params' => $params];
    }
}

// If we don't have a user ID yet and no session, note that some checks will be skipped
if ($testedUserId === null) {
    $checks[] = [
        'id' => 'user_context',
        'label' => 'User context (session or ?test_user_id=)',
        'ok' => false,
        'details' => 'No user id detected; use ?test_user_id=123 to run user-scoped checks',
        'meta' => ['mode' => $mode],
    ];
} else {
    $checks[] = [
        'id' => 'user_context',
        'label' => 'User context (session or ?test_user_id=)',
        'ok' => true,
        'details' => 'Using user_id ' . $testedUserId,
        'meta' => ['mode' => $mode, 'user_id' => $testedUserId],
    ];
}

// Check C: Core queries (profile, recent bookings, counts, services)
// Only if PDO and user id available
$coreQueryResults = [];
if ($pdo instanceof \PDO && $testedUserId !== null) {
    // 1) Customer profile
    // Use COALESCE to support schemas that use `name`, `full_name` or `username`
    $q1 = 'SELECT id, email, COALESCE(name, full_name, username) AS name FROM users WHERE id = :uid LIMIT 1';
    $r1 = timedQuery($pdo, $q1, [':uid' => $testedUserId]);
    $checks[] = [
        'id' => 'query_profile',
        'label' => 'Customer profile query',
        'ok' => $r1['ok'] && ($r1['count'] > 0),
        'details' => $r1['ok'] ? ($r1['count'] . ' row(s)') : 'Query error',
        'meta' => $r1,
    ];
    $coreQueryResults['profile'] = $r1;

    // 2) Recent bookings for user (limit 10)
    $q2 = 'SELECT b.id, b.user_id, b.status, b.carwash_id, b.created_at,
                 cp.business_name AS carwash_name, s.name AS service_name
           FROM bookings b
           LEFT JOIN carwash_profiles cp ON cp.id = b.carwash_id
           LEFT JOIN services s ON s.id = b.service_id
           WHERE b.user_id = :uid
           ORDER BY b.created_at DESC
           LIMIT 10';
    $r2 = timedQuery($pdo, $q2, [':uid' => $testedUserId]);
    $checks[] = [
        'id' => 'query_recent_bookings',
        'label' => 'Recent bookings (limit 10)',
        'ok' => $r2['ok'],
        'details' => $r2['ok'] ? ($r2['count'] . ' row(s), ' . round($r2['time']*1000,2) . ' ms') : 'Query error',
        'meta' => $r2,
    ];
    $coreQueryResults['bookings'] = $r2;

    // 3) Booking counts (total, pending, completed)
    // Rewritten booking counts to use WHERE user_id = :uid for clearer parameter handling
    $q3 = 'SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status IN ("completed","done") THEN 1 ELSE 0 END) AS completed
       FROM bookings
       WHERE user_id = :uid';
    $r3 = timedQuery($pdo, $q3, [':uid' => $testedUserId]);
    $checks[] = [
        'id' => 'query_booking_counts',
        'label' => 'Booking counts (total/pending/completed)',
        'ok' => $r3['ok'],
        'details' => $r3['ok'] ? 'Counts retrieved in ' . round($r3['time']*1000,2) . ' ms' : 'Query error',
        'meta' => $r3,
    ];
    $coreQueryResults['counts'] = $r3;

    // 4) Available services (from services or carwash_profiles -> services relation)
    $servicesFound = false;
    if (tableExists($pdo, 'services')) {
        $q4 = 'SELECT id, name, price FROM services WHERE status = "active" LIMIT 50';
        $r4 = timedQuery($pdo, $q4, []);
        $servicesFound = $r4['ok'] && $r4['count'] > 0;
    } else {
        // attempt to find services via carwash_profiles -> maybe a JSON column or related table
        $r4 = ['ok' => false, 'error' => 'No services table', 'sql' => '', 'params' => []];
    }
    $checks[] = [
        'id' => 'query_services',
        'label' => 'Available services query',
        'ok' => $r4['ok'],
        'details' => $r4['ok'] ? ($r4['count'] . ' row(s)') : 'Query error or no services table',
        'meta' => $r4,
    ];
    $coreQueryResults['services'] = $r4;

    // D) Referential check: for a sample booking verify related user and carwash_profile exist
    $referentialOk = true;
    $referentialMeta = [];
    if ($r2['ok'] && !empty($r2['rows'])) {
        $sample = $r2['rows'][0];
        $sampleBookingId = $sample['id'] ?? null;
        $sampleUserId = $sample['user_id'] ?? null;
        $sampleCarwashId = $sample['carwash_id'] ?? null;
        // check user exists
        if ($sampleUserId) {
            $u = timedQuery($pdo, 'SELECT id FROM users WHERE id = :id LIMIT 1', [':id' => $sampleUserId]);
            if (!$u['ok'] || $u['count'] === 0) $referentialOk = false;
            $referentialMeta['user_lookup'] = $u;
        }
        if ($sampleCarwashId) {
            $c = timedQuery($pdo, 'SELECT id FROM carwash_profiles WHERE id = :id LIMIT 1', [':id' => $sampleCarwashId]);
            if (!$c['ok'] || $c['count'] === 0) $referentialOk = false;
            $referentialMeta['carwash_lookup'] = $c;
        }
        $checks[] = [
            'id' => 'referential_sample_booking',
            'label' => 'Referential check for sample booking',
            'ok' => $referentialOk,
            'details' => $referentialOk ? 'Related user and carwash_profile found' : 'Missing related records',
            'meta' => array_merge(['sample_booking_id' => $sampleBookingId], $referentialMeta),
        ];
    } else {
        $checks[] = [
            'id' => 'referential_sample_booking',
            'label' => 'Referential check for sample booking',
            'ok' => false,
            'details' => 'No sample booking available for this user',
            'meta' => $r2,
        ];
    }
} else {
    // Report that core queries were skipped
    if (!($pdo instanceof \PDO)) {
        $checks[] = [
            'id' => 'core_queries_skipped',
            'label' => 'Core queries',
            'ok' => false,
            'details' => 'Skipped because DB connection unavailable',
            'meta' => [],
        ];
    } else {
        $checks[] = [
            'id' => 'core_queries_skipped',
            'label' => 'Core queries',
            'ok' => false,
            'details' => 'Skipped because no user context (use ?test_user_id=)',
            'meta' => [],
        ];
    }
}

// E) Session dump (mask secrets)
function maskSession(array $session): array {
    $masked = [];
    $sensitiveKeys = ['pass','password','pwd','token','secret','csrf','api_key','db_pass','db_passwd'];
    foreach ($session as $k => $v) {
        $lower = strtolower((string)$k);
        $isSensitive = false;
        foreach ($sensitiveKeys as $sk) {
            if (strpos($lower, $sk) !== false) { $isSensitive = true; break; }
        }
        if ($isSensitive) {
            $masked[$k] = '***masked***';
        } else {
            // limit large binary data
            if (is_string($v) && strlen($v) > 1024) {
                $masked[$k] = substr($v,0,1024) . '...';
            } else {
                $masked[$k] = $v;
            }
        }
    }
    return $masked;
}
$sessionDump = maskSession($_SESSION ?? []);

// F) Notification stubs: detect send functions/classes (do not send)
$notificationChecks = [];
// Common global functions
$notificationChecks['mail_function'] = function_exists('mail');
// Project-specific helpers
$notificationChecks['send_email'] = function_exists('send_email') || (class_exists(\App\Classes\EmailHelper::class) && method_exists(\App\Classes\EmailHelper::class, 'sendBookingConfirmation'));
$notificationChecks['send_sms'] = function_exists('send_sms') || (class_exists(\App\Classes\SmsNotifier::class) && method_exists(\App\Classes\SmsNotifier::class, 'send'));
$checks[] = [
    'id' => 'notification_stubs',
    'label' => 'Notification stubs available (no dispatch performed)',
    'ok' => in_array(true, $notificationChecks, true),
    'details' => 'Detected functions/classes: ' . implode(', ', array_keys(array_filter($notificationChecks))),
    'meta' => $notificationChecks,
];

// Prepare debug payload for collapsible details
$debugPayload = [
    'booted_paths' => $bootedPaths,
    'db_info' => $dbInfo,
    'connection_errors' => $connectionErrors,
    'core_queries' => $coreQueryResults,
    'session' => $sessionDump,
    'required_checks' => $required,
];

// HTML Output
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Customer Dashboard Diagnostic</title>
<style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; background:#f4f6f8; color:#111; margin:18px;}
    .container{max-width:1100px;margin:0 auto;}
    header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
    h1{font-size:1.25rem;margin:0;}
    .meta{font-size:0.9rem;color:#444;}
    .card{background:#fff;border-radius:8px;padding:14px;margin:12px 0;box-shadow:0 1px 3px rgba(0,0,0,0.06);}
    .badge{display:inline-block;padding:4px 8px;border-radius:999px;font-weight:700;font-size:0.8rem}
    .pass{background:#e6ffef;color:#064e3b;border:1px solid #a7f3d0}
    .fail{background:#ffe6e6;color:#7f1d1d;border:1px solid #fca5a5}
    .check-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px dashed #eee;}
    .check-left{flex:1}
    .check-title{font-weight:600}
    .check-details{color:#555;font-size:0.95rem;margin-top:4px}
    .meta-inline{font-size:0.85rem;color:#666}
    .debug{background:#0b1320;color:#dbeafe;padding:12px;border-radius:6px;font-family:monospace;white-space:pre-wrap;overflow:auto}
    .toggle{cursor:pointer;color:#2563eb;text-decoration:underline;font-size:0.9rem}
    .small{font-size:0.85rem;color:#666}
    .sql{background:#0f172a;color:#e6edf3;padding:10px;border-radius:6px;font-family:monospace;white-space:pre-wrap;overflow:auto}
</style>
</head>
<body>
<div class="container">
    <header>
        <div>
            <h1>Customer Dashboard Diagnostic</h1>
            <div class="meta">Mode: <strong><?php echo esc($mode); ?></strong> |
                Tested user_id: <strong><?php echo esc((string)$testedUserId ?: 'n/a'); ?></strong> |
                DB host: <strong><?php echo esc($dbInfo['host']); ?></strong> |
                DB name: <strong><?php echo esc($dbInfo['name']); ?></strong>
            </div>
        </div>
        <div class="small">This page only reads data and never sends messages or prints secrets.</div>
    </header>

    <section class="card">
        <h2 style="margin:0 0 8px 0">Checks</h2>
        <?php foreach ($checks as $c): ?>
            <div class="check-row" id="<?php echo esc($c['id']); ?>">
                <div class="check-left">
                    <div class="check-title"><?php echo esc($c['label']); ?></div>
                    <div class="check-details"><?php echo esc($c['details'] ?? ''); ?></div>
                </div>
                <div style="margin-left:12px;text-align:right;">
                    <?php echo badge($c['ok'] ? 'PASS' : 'FAIL'); ?>
                    <div class="meta-inline" style="margin-top:6px;">
                        <a class="toggle" data-id="<?php echo esc($c['id']); ?>-debug">Details</a>
                    </div>
                </div>
            </div>
            <div class="card" id="<?php echo esc($c['id']); ?>-debug" style="display:none;">
                <div class="small">Meta (click to copy):</div>
                <pre class="debug"><?php echo esc(var_export($c['meta'] ?? [], true)); ?></pre>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h2 style="margin:0 0 8px 0">Core Query Samples</h2>
        <?php if (!empty($coreQueryResults)): ?>
            <?php foreach ($coreQueryResults as $key => $res): ?>
                <div style="margin-bottom:12px;">
                    <div style="font-weight:700;text-transform:capitalize;"><?php echo esc($key); ?></div>
                    <div class="small">Status: <?php echo $res['ok'] ? '<span style="color:green">OK</span>' : '<span style="color:red">ERROR</span>'; ?> |
                        Time: <?php echo round($res['time']*1000,2) ?? '-'; ?> ms |
                        Rows: <?php echo (int)($res['count'] ?? 0); ?></div>
                    <?php if (!empty($res['sql'])): ?>
                        <div style="margin-top:8px;">
                            <div class="small">SQL:</div>
                            <div class="sql"><?php echo esc($res['sql']); ?></div>
                            <?php if (!empty($res['params'])): ?>
                                <div class="small">Params: <?php echo esc(var_export($res['params'], true)); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($res['rows'])): ?>
                        <div style="margin-top:8px;">
                            <div class="small">Sample results (up to 5 rows):</div>
                            <pre class="debug"><?php
                                $sample = array_slice($res['rows'], 0, 5);
                                echo esc(var_export($sample, true));
                            ?></pre>
                        </div>
                    <?php elseif (!$res['ok']): ?>
                        <div style="margin-top:8px;color:#a00;">Error: <?php echo esc($res['error'] ?? 'Unknown error'); ?></div>
                    <?php else: ?>
                        <div class="small" style="margin-top:8px;color:#666">No rows returned.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="small">No core queries were executed. Ensure you supplied a user id or have a session.</div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 style="margin:0 0 8px 0">Session Debug</h2>
        <div class="small">Masked $_SESSION dump below. Sensitive keys are masked.</div>
        <pre class="debug"><?php echo esc(var_export($sessionDump, true)); ?></pre>
    </section>

    <section class="card">
        <h2 style="margin:0 0 8px 0">Developer Notes & Next Steps</h2>
        <ul class="small">
            <li>If DB connection failed: check config in <code>backend/includes/config.php</code> or environment variables <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code>.</li>
            <li>To run user-scoped checks without logging in: append <code>?test_user_id=123</code> to the URL.</li>
            <li>If tables/columns are missing: run migrations in <code>database/</code> or review <code>database/carwash.sql</code>.</li>
        </ul>
    </section>

    <footer style="margin-top:18px;font-size:0.9rem;color:#666">
        <div class="small">FAQ / Quick fixes:
            <ol>
                <li>Check DB credentials in <code>backend/includes/config.php</code> or environment variables.</li>
                <li>Ensure required tables exist: <code>bookings</code>, <code>users</code>, <code>carwash_profiles</code>, <code>services</code>, <code>payments</code>.</li>
                <li>Run migrations or import <code>database/carwash.sql</code>.</li>
            </ol>
        </div>
    </footer>
</div>

<script>
document.querySelectorAll('.toggle').forEach(function(el){
    el.addEventListener('click', function(){
        var id = el.getAttribute('data-id');
        var node = document.getElementById(id);
        if (!node) return;
        node.style.display = node.style.display === 'none' ? 'block' : 'none';
    });
});
</script>
</body>
</html>