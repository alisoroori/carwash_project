<?php
declare(strict_types=1);

/**
 * Test page: Customer Dashboard diagnostics
 *
 * - Loads existing DB config (backend/includes/config.php) if available.
 * - Attempts to use an existing $conn (mysqli) or creates one from DB_* constants.
 * - Checks if a user is logged in via $_SESSION; if not, can use a test user id via ?test_user_id=1
 * - For each dashboard-relevant table (bookings, carwash_profiles, users, services):
 *     - Checks whether the table exists
 *     - Runs a simple COUNT/SELECT query (scoped to the user where applicable)
 *     - Reports success/failure and record counts
 * - Outputs a readable HTML report and dumps $_SESSION for debugging
 * - All errors are caught and reported; the page will not crash.
 *
 * See: [config.php](http://_vscodecontentref_/0) and App\Classes\Database (backend/classes/Database.php)
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();

// Try to include Composer autoloader if this is a modern workspace
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Attempt to load project config to reuse connection/constants. Prefer backend/includes when present.
$configIncluded = false;
if (file_exists(__DIR__ . '/../backend/includes/config.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__ . '/../backend/includes/config.php';
    $configIncluded = true;
} elseif (file_exists(__DIR__ . '/../includes/config.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__ . '/../includes/config.php';
    $configIncluded = true;
}

// Helper: establish a mysqli connection using existing $conn or DB_* constants
function get_db_connection(): ?\mysqli
{
    global $conn;
    // If an existing $conn variable is available and is mysqli, use it.
    if (isset($conn) && $conn instanceof \mysqli) {
        return $conn;
    }

    // Try Database PSR-4 class if available
    if (class_exists(\App\Classes\Database::class)) {
        try {
            $db = \App\Classes\Database::getInstance();
            // Attempt to extract underlying mysqli if Database exposes it (best-effort)
            if (isset($db) && method_exists($db, 'getConnection')) {
                $internal = $db->getConnection();
                if ($internal instanceof \mysqli) {
                    return $internal;
                }
            }
        } catch (\Throwable $e) {
            // ignore and fallback to procedural mysqli
        }
    }

    // Fallback: use DB constants if defined
    $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1');
    $user = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
    $pass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');
    $name = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: '');

    if ($name === '') {
        return null;
    }

    $mysqli = @new \mysqli($host, $user, $pass, $name);
    if ($mysqli->connect_errno) {
        return null;
    }

    return $mysqli;
}

function table_exists(\mysqli $db, string $table): bool
{
    $safe = $db->real_escape_string($table);
    $res = $db->query("SHOW TABLES LIKE '{$safe}'");
    if ($res === false) {
        return false;
    }
    $exists = $res->num_rows > 0;
    $res->free();
    return $exists;
}

/**
 * Run a prepared COUNT query and return [success(bool), count(int|null), errorMsg|null]
 */
function run_count_query(\mysqli $db, string $sql, string $types = '', array $params = []): array
{
    $stmt = $db->prepare($sql);
    if ($stmt === false) {
        return [false, null, 'prepare failed: ' . $db->error];
    }

    if ($types !== '' && !empty($params)) {
        // bind params dynamically
        $refs = [];
        foreach ($params as $k => $v) {
            $refs[$k] = &$params[$k];
        }
        array_unshift($refs, $types);
        if (!call_user_func_array([$stmt, 'bind_param'], $refs)) {
            $err = $stmt->error ?: 'bind_param failed';
            $stmt->close();
            return [false, null, $err];
        }
    }

    if (!$stmt->execute()) {
        $err = $stmt->error ?: 'execute failed';
        $stmt->close();
        return [false, null, $err];
    }

    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_row();
        $count = isset($row[0]) ? (int)$row[0] : 0;
        $result->free();
        $stmt->close();
        return [true, $count, null];
    }

    // If get_result() is not available (non-mysqlnd), use bind_result/fetch as a fallback
    if (!$stmt->bind_result($count)) {
        $err = $stmt->error ?: 'bind_result failed';
        $stmt->close();
        return [false, null, $err];
    }

    $fetched = $stmt->fetch();
    if ($fetched === false) {
        $err = $stmt->error ?: 'fetch failed';
        $stmt->close();
        return [false, null, $err];
    }

    $stmt->close();
    return [true, (int)$count, null];
}

// Prepare report state
$report = [];
$db = get_db_connection();
if ($db === null) {
    $report[] = [
        'title' => 'Database connection',
        'ok' => false,
        'message' => 'No database connection available. Ensure [config.php](http://_vscodecontentref_/1) defines DB credentials or $conn, or environment variables are set.'
    ];
} else {
    $report[] = [
        'title' => 'Database connection',
        'ok' => true,
        'message' => 'Connected to MySQL as ' . htmlspecialchars($db->real_escape_string((string)$db->server_info))
    ];
}

// Determine user context
$loggedIn = false;
$userId = null;
$userRole = null;

if (!empty($_SESSION['user_id'])) {
    $loggedIn = true;
    $userId = (int)$_SESSION['user_id'];
    $userRole = $_SESSION['role'] ?? null;
} elseif (isset($_GET['test_user_id'])) {
    // allow ad-hoc test user via query param
    $loggedIn = true;
    $userId = (int)$_GET['test_user_id'];
    $userRole = 'customer (test)';
} else {
    // Not logged in - report and allow queries that don't require a user
    $report[] = [
        'title' => 'Authentication',
        'ok' => false,
        'message' => 'No logged-in user found in session. You can append ?test_user_id=1 to the URL to run customer-scoped queries as a test user.'
    ];
}

if ($loggedIn) {
    $report[] = [
        'title' => 'Authentication',
        'ok' => true,
        'message' => 'User context: user_id=' . (int)$userId . ', role=' . htmlspecialchars((string)$userRole)
    ];
}

// Only proceed with table checks if we have a DB connection
$tablesToCheck = [
    // Core dashboard tables
    'bookings' => [
        'count_sql_user' => 'SELECT COUNT(*) FROM bookings WHERE user_id = ?',
        'count_types' => 'i',
    ],
    // Carwash profiles may be named carwash_profiles or carwashes depending on schema
    'carwash_profiles' => [
        'count_sql_user' => 'SELECT COUNT(*) FROM carwash_profiles WHERE user_id = ?',
        'count_types' => 'i',
    ],
    // fallback common table name
    'carwashes' => [
        'count_sql_user' => 'SELECT COUNT(*) FROM carwashes WHERE user_id = ?',
        'count_types' => 'i',
    ],
    'users' => [
        'count_sql_user' => 'SELECT COUNT(*) FROM users',
        'count_types' => '',
    ],
    'services' => [
        'count_sql_user' => 'SELECT COUNT(*) FROM services',
        'count_types' => '',
    ],
    // optional join table
    'booking_services' => [
        'count_sql_user' => 'SELECT COUNT(*) FROM booking_services bs JOIN bookings b ON b.id = bs.booking_id WHERE b.user_id = ?',
        'count_types' => 'i',
    ],
];

if ($db instanceof \mysqli) {
    foreach ($tablesToCheck as $table => $info) {
        try {
            $exists = table_exists($db, $table);
            $entry = [
                'title' => "Table: {$table}",
                'exists' => $exists,
                'query_success' => null,
                'count' => null,
                'error' => null,
            ];

            if (!$exists) {
                $entry['error'] = "Table '{$table}' does not exist.";
                $report[] = $entry;
                continue;
            }

            // choose whether to run user-scoped count
            $sql = $info['count_sql_user'];
            $types = $info['count_types'];
            $params = [];

            if ($types !== '' && $userId !== null) {
                $params = [$userId];
            } elseif ($types !== '' && $userId === null) {
                // cannot run user-scoped query due to missing user id
                $entry['query_success'] = false;
                $entry['error'] = 'User-scoped query skipped because no user id available.';
                $report[] = $entry;
                continue;
            }

            [$ok, $count, $err] = run_count_query($db, $sql, $types, $params);
            $entry['query_success'] = $ok;
            $entry['count'] = $count;
            $entry['error'] = $err;
            $report[] = $entry;
        } catch (\Throwable $e) {
            $report[] = [
                'title' => "Table: {$table}",
                'exists' => null,
                'query_success' => false,
                'count' => null,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
} else {
    $report[] = [
        'title' => 'Checks',
        'ok' => false,
        'message' => 'Skipping table checks because there is no valid mysqli connection.'
    ];
}

// Safe dump of session
$sessionDump = htmlspecialchars(var_export($_SESSION, true), ENT_QUOTES | ENT_SUBSTITUTE);

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Customer Dashboard - Diagnostic Test</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 18px; background:#f7fafc; color:#111827; }
        .card { background: #fff; border-radius:8px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06); }
        .ok { color: #16a34a; font-weight:600; }
        .fail { color: #dc2626; font-weight:600; }
        pre { background:#0f172a; color:#e6edf3; padding:12px; border-radius:6px; overflow:auto; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { padding:8px; border-bottom:1px solid #e5e7eb; text-align:left; }
        th { background:#f3f4f6; }
    </style>
</head>
<body>
    <h1>Customer Dashboard Diagnostic</h1>
    <p class="card">This page reports on DB connectivity, authentication context, and basic table/query health for the Customer Dashboard. It is safe to run in development environments and will not modify data.</p>

    <div class="card">
        <h2>Summary</h2>
        <table>
            <thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead>
            <tbody>
            <?php foreach ($report as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['title'] ?? $r['title'] ?? 'Check'); ?></td>
                    <td>
                        <?php
                        $status = 'Unknown';
                        $details = $r['message'] ?? ($r['error'] ?? '');
                        if (isset($r['ok'])) {
                            $status = $r['ok'] ? '<span class="ok">OK</span>' : '<span class="fail">FAIL</span>';
                        } elseif (isset($r['exists'])) {
                            $status = ($r['exists'] === true) ? '<span class="ok">EXISTS</span>' : '<span class="fail">MISSING</span>';
                        } elseif (isset($r['query_success'])) {
                            $status = ($r['query_success'] === true) ? '<span class="ok">QUERY OK</span>' : '<span class="fail">QUERY FAIL</span>';
                        }
                        echo $status;
                        ?>
                    </td>
                    <td>
                        <?php
                        if (isset($r['count']) && $r['count'] !== null) {
                            echo 'Records: ' . (int)$r['count'] . '. ';
                        }
                        if (!empty($details)) {
                            echo htmlspecialchars((string)$details);
                        } else {
                            // print any additional array keys
                            $other = $r;
                            unset($other['title'], $other['ok'], $other['message'], $other['exists'], $other['query_success'], $other['count'], $other['error']);
                            if (!empty($other)) {
                                echo htmlspecialchars(var_export($other, true));
                            }
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>$_SESSION Dump</h2>
        <pre><?php echo $sessionDump ?: 'No session data'; ?></pre>
        <p>If you want to simulate a logged-in customer, append <code>?test_user_id=1</code> to the URL.</p>
    </div>

    <div class="card">
        <h2>Notes & Troubleshooting</h2>
        <ul>
            <li>If database connection fails, verify <code>backend/includes/config.php</code> or environment variables <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code>.</li>
            <li>Some table names may differ in your schema (e.g., <code>carwash</code> vs <code>carwashes</code>, <code>carwash_profiles</code>). Update the <code>$tablesToCheck</code> list in this script if needed.</li>
            <li>All errors are non-fatal and will be shown above for debugging.</li>
        </ul>
    </div>
</body>
</html>