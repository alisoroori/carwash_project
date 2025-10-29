<?php
declare(strict_types=1);

/**
 * End-to-end Customer Dashboard test (CLI)
 *
 * - Uses PSR-4 classes when available: App\Classes\Database
 * - Uses session-based auth by POSTing to backend/auth/login.php and preserving cookies
 * - Exercises flows:
 *     1) Load Customer_Dashboard.php (GET)
 *     2) List bookings (API)
 *     3) Create booking (API)
 *     4) Edit booking (API)
 *     5) Proceed to payment (API)
 * - Verifies DB state with Database class (or PDO/mysqli fallback)
 * - Logs requests/responses and DB checks to .reports/e2e-customer-dashboard.json
 *
 * Usage:
 *   php tools/tests/e2e/customer_dashboard_e2e.php
 *
 * Notes:
 * - Adjust $BASE and test user credentials if needed for your environment.
 */

$ROOT = realpath(__DIR__ . '/..');
if ($ROOT === false) {
    $ROOT = dirname(__DIR__);
}
chdir($ROOT);

@mkdir('.reports', 0755, true);
$reportFile = '.reports/e2e-customer-dashboard.json';

$BASE = 'http://localhost/carwash_project';
$LOGIN_URL = $BASE . '/backend/auth/login.php';
$DASH_URL = $BASE . '/backend/dashboard/Customer_Dashboard.php';
$BOOKING_LIST_API = $BASE . '/backend/api/bookings/list.php';
$BOOKING_CREATE_API = $BASE . '/backend/api/bookings/create.php';
$BOOKING_UPDATE_API = $BASE . '/backend/api/bookings/update.php';
$PAYMENT_API = $BASE . '/backend/api/payment/process.php';

// Test account
$testEmail = 'e2e_test@example.com';
$testPassword = 'E2ePass123!';

$cookieJar = sys_get_temp_dir() . '/e2e_cookies_' . bin2hex(random_bytes(4)) . '.txt';
$logs = [];
$summary = [];

// Load autoloader & bootstrap if present
if (file_exists($ROOT . '/vendor/autoload.php')) {
    require_once $ROOT . '/vendor/autoload.php';
}
if (file_exists($ROOT . '/backend/includes/bootstrap.php')) {
    @require_once $ROOT . '/backend/includes/bootstrap.php';
}

// Helper: HTTP request (curl) with cookie support
function http_request(string $url, string $method = 'GET', array $headers = [], $body = null, string $cookieJar = ''): array {
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 20,
    ];
    if (!empty($cookieJar)) {
        $opts[CURLOPT_COOKIEJAR] = $cookieJar;
        $opts[CURLOPT_COOKIEFILE] = $cookieJar;
    }
    if (!empty($headers)) $opts[CURLOPT_HTTPHEADER] = $headers;
    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = $body;
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'error' => $err, 'info' => $info];
    }
    $headerLen = $info['header_size'] ?? 0;
    $respHeaders = substr($raw, 0, $headerLen);
    $body = substr($raw, $headerLen);
    curl_close($ch);
    return ['ok' => true, 'status' => $info['http_code'] ?? 0, 'headers' => $respHeaders, 'body' => $body, 'info' => $info];
}

function postForm(string $url, array $data, array $extraHeaders = [], string $cookieJar = ''): array {
    $body = http_build_query($data);
    $headers = array_merge(['Content-Type: application/x-www-form-urlencoded'], $extraHeaders);
    return http_request($url, 'POST', $headers, $body, $cookieJar);
}

function postJson(string $url, $data, array $extraHeaders = [], string $cookieJar = ''): array {
    $body = is_string($data) ? $data : json_encode($data);
    $headers = array_merge(['Content-Type: application/json', 'Accept: application/json'], $extraHeaders);
    return http_request($url, 'POST', $headers, $body, $cookieJar);
}

function safeJsonDecode(string $s) {
    $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // remove BOM
    $s = trim($s);
    if ($s === '') return null;
    $parsed = json_decode($s, true);
    return $parsed === null ? ['__raw' => substr($s,0,4096)] : $parsed;
}

// Database helper using App\Classes\Database when present
$db = null;
$useDbClass = false;
if (class_exists(\App\Classes\Database::class)) {
    try {
        $db = \App\Classes\Database::getInstance();
        $useDbClass = true;
    } catch (Throwable $e) {
        $db = null;
    }
}

// Fallback PDO/mysqli using backend/includes/db.php if available
if (!$db && file_exists($ROOT . '/backend/includes/db.php')) {
    require_once $ROOT . '/backend/includes/db.php';
    // try $conn variable (mysqli)
    if (isset($conn) && $conn instanceof \mysqli) {
        $db = $conn; // mysqli instance
    } elseif (function_exists('getDBConnection')) {
        try {
            $maybe = getDBConnection();
            if ($maybe instanceof \PDO || $maybe instanceof \mysqli) $db = $maybe;
        } catch (Throwable $e) { /* ignore */ }
    }
}

// Utility: ensure test user exists (via Database class if possible)
function ensureTestUser($db, $email, $password) {
    if ($db === null) return ['ok'=>false,'msg'=>'No DB'];

    // Helper: determine which columns exist in `users` table so we insert only valid columns
    $columns = [];
    try {
        if ($db instanceof \App\Classes\Database || (method_exists($db, 'fetchOne') && method_exists($db, 'fetchAll'))) {
            // Use Database class PDO under the hood
            $rows = $db->fetchAll("SHOW COLUMNS FROM users");
            foreach ($rows as $r) $columns[] = $r['Field'] ?? $r['field'] ?? null;
        } elseif ($db instanceof \PDO) {
            $stmt = $db->query("SHOW COLUMNS FROM users");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) $columns[] = $r['Field'];
        } elseif ($db instanceof \mysqli) {
            $res = $db->query("SHOW COLUMNS FROM users");
            while ($r = $res->fetch_assoc()) $columns[] = $r['Field'];
        }
    } catch (Throwable $e) {
        // If SHOW COLUMNS fails, fall back to attempting a simple select structure
        $columns = [];
    }

    // Normalize
    $columns = array_filter(array_map('strval', $columns));

    // Check existing user
    try {
        if ($db instanceof \App\Classes\Database || (method_exists($db, 'fetchOne') && method_exists($db, 'fetchAll'))) {
            $user = $db->fetchOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
            if ($user) return ['ok' => true, 'user' => $user];

            // Build insert data with only allowed columns
            $data = [];
            if (in_array('name', $columns)) $data['name'] = 'E2E Test';
            if (in_array('email', $columns)) $data['email'] = $email;
            if (in_array('password', $columns)) $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            if (in_array('role', $columns)) $data['role'] = 'customer';
            if (in_array('status', $columns)) $data['status'] = 'active';
            if (in_array('created_at', $columns)) $data['created_at'] = date('Y-m-d H:i:s');

            $id = $db->insert('users', $data);
            $u = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $id]);
            return ['ok' => true, 'user' => $u];
        }

        if ($db instanceof \PDO) {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user) return ['ok' => true, 'user' => $user];

            $cols = [];
            $placeholders = [];
            $params = [];
            if (in_array('name', $columns)) { $cols[] = 'name'; $placeholders[] = ':n'; $params[':n'] = 'E2E Test'; }
            if (in_array('email', $columns)) { $cols[] = 'email'; $placeholders[] = ':e'; $params[':e'] = $email; }
            if (in_array('password', $columns)) { $cols[] = 'password'; $placeholders[] = ':p'; $params[':p'] = password_hash($password, PASSWORD_DEFAULT); }
            if (in_array('role', $columns)) { $cols[] = 'role'; $placeholders[] = ':r'; $params[':r'] = 'customer'; }
            if (in_array('status', $columns)) { $cols[] = 'status'; $placeholders[] = ':s'; $params[':s'] = 'active'; }
            if (in_array('created_at', $columns)) { $cols[] = 'created_at'; $placeholders[] = 'NOW()'; }

            if (empty($cols)) {
                // As a last resort, create minimal user columns email/password
                $cols = ['email','password'];
                $placeholders = [':e', ':p'];
                $params = [':e'=>$email,':p'=>password_hash($password, PASSWORD_DEFAULT)];
            }

            $colsStr = implode(',', $cols);
            // replace any NOW() placeholders
            $placeholdersStr = implode(',', array_map(function($p){ return $p==='NOW()'? 'NOW()' : $p; }, $placeholders));
            $query = "INSERT INTO users ($colsStr) VALUES ($placeholdersStr)";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $id = (int)$db->lastInsertId();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id'=>$id]);
            return ['ok'=>true,'user'=>$stmt->fetch(\PDO::FETCH_ASSOC)];
        }

        if ($db instanceof \mysqli) {
            // mysqli: check existing
            $stmt = $db->prepare("SELECT id,email,name FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            // Debug: write detection info to a temporary debug file so we can inspect why ensureTestUser may fail
            @file_put_contents('.reports/e2e-db-debug.json', json_encode([ 'db_type' => is_object($db) ? get_class($db) : gettype($db), 'timestamp' => date('c'), 'ensure' => $ensure ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

            // Also echo a short debug line for immediate feedback
            echo "[E2E DEBUG] db_type=" . (is_object($db) ? get_class($db) : gettype($db)) . "\n";
            if (is_array($ensure)) { echo "[E2E DEBUG] ensure_ok=" . ($ensure['ok'] ? 'true' : 'false') . "\n"; if (isset($ensure['msg'])) echo "[E2E DEBUG] msg=".$ensure['msg']."\n"; }
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            if ($user) return ['ok'=>true,'user'=>$user];

            // Build insert query dynamically
            $cols = [];
            $placeholders = [];
            $values = [];
            if (in_array('name', $columns)) { $cols[] = 'name'; $placeholders[] = '?'; $values[] = 'E2E Test'; }
            if (in_array('email', $columns)) { $cols[] = 'email'; $placeholders[] = '?'; $values[] = $email; }
            if (in_array('password', $columns)) { $cols[] = 'password'; $placeholders[] = '?'; $values[] = password_hash($password, PASSWORD_DEFAULT); }
            if (in_array('role', $columns)) { $cols[] = 'role'; $placeholders[] = '?'; $values[] = 'customer'; }
            // status column may not exist
            if (in_array('status', $columns)) { $cols[] = 'status'; $placeholders[] = '?'; $values[] = 'active'; }

            if (in_array('created_at', $columns)) {
                $cols[] = 'created_at';
                $placeholders[] = 'NOW()';
            }

            if (empty($cols)) {
                // minimal
                $cols = ['email','password'];
                $placeholders = ['?','?'];
                $values = [$email, password_hash($password, PASSWORD_DEFAULT)];
            }

            $colsStr = implode(',', $cols);
            $placeholdersStr = implode(',', $placeholders);
            $query = "INSERT INTO users ($colsStr) VALUES ($placeholdersStr)";
            $prep = $db->prepare($query);
            // bind params excluding NOW()
            $bindValues = array_filter($values, function($v) { return true; });
            if (!empty($bindValues)) {
                // build type string
                $types = str_repeat('s', count($bindValues));
                $prep->bind_param($types, ...$bindValues);
            }
            $prep->execute();
            $id = $db->insert_id;
            $stmt = $db->prepare("SELECT id,email,name FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            return ['ok'=>true,'user'=>$res->fetch_assoc()];
        }
    } catch (Throwable $e) {
        return ['ok'=>false,'msg'=>'DB error: ' . $e->getMessage()];
    }

    return ['ok'=>false,'msg'=>'Unsupported DB object'];
}

// 0) Ensure test user exists
$logs[] = "Ensuring test user exists: $testEmail";
$ensure = ensureTestUser($db, $testEmail, $testPassword);
// Debug: always write ensure result for diagnosis
@file_put_contents('.reports/e2e-db-debug-after-ensure.json', json_encode(['ensure'=>$ensure,'db_type'=> is_object($db)? get_class($db): gettype($db),'time'=>date('c')], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "[E2E AFTER ENSURE] "; var_export($ensure); echo "\n";
if (!$ensure['ok']) {
    $summary[] = ['step' => 'ensure_test_user', 'ok' => false, 'message' => $ensure['msg'] ?? 'failed'];
    file_put_contents($reportFile, json_encode(['logs'=>$logs,'summary'=>$summary], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo "Failed to ensure test user. Check DB.\n";
    exit(1);
}
$testUser = $ensure['user'];
$logs[] = "Test user id: " . ($testUser['id'] ?? $testUser['ID'] ?? '(unknown)');

// 1) Login to obtain session cookie
$logs[] = "Logging in via $LOGIN_URL";
$post = ['email' => $testEmail, 'password' => $testPassword];
$loginResp = postForm($LOGIN_URL, $post, [], $cookieJar);
$logs[] = ['request' => ['url'=>$LOGIN_URL,'method'=>'POST','body'=>$post], 'response' => $loginResp];

$loginOk = $loginResp['ok'] && ($loginResp['status'] === 200 || $loginResp['status'] === 302);
$summary[] = ['step'=>'login','ok'=>$loginOk,'status'=>$loginResp['status']];
if (!$loginOk) {
    file_put_contents($reportFile, json_encode(['logs'=>$logs,'summary'=>$summary], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo "Login failed. See report.\n";
    exit(1);
}

// 2) Load Customer Dashboard page (GET)
$logs[] = "Loading dashboard page $DASH_URL";
$dashResp = http_request($DASH_URL, 'GET', ['Accept: text/html'], null, $cookieJar);
$logs[] = ['request'=>['url'=>$DASH_URL,'method'=>'GET'],'response'=>$dashResp];
$ok = $dashResp['ok'] && $dashResp['status'] === 200 && strpos((string)$dashResp['body'],'Customer Dashboard') !== false;
$summary[] = ['step'=>'load_dashboard','ok'=>$ok,'status'=>$dashResp['status']];
if (!$ok) {
    $summary[] = ['step'=>'load_dashboard_error_details', 'body_preview' => substr($dashResp['body'] ?? '',0,200)];
}

// 3) List existing bookings via API
$logs[] = "Listing bookings via $BOOKING_LIST_API";
$listResp = http_request($BOOKING_LIST_API, 'GET', ['Accept: application/json'], null, $cookieJar);
$listParsed = $listResp['ok'] ? safeJsonDecode($listResp['body'] ?? '') : null;
$logs[] = ['request'=>['url'=>$BOOKING_LIST_API,'method'=>'GET'],'response'=>$listResp,'parsed'=>$listParsed];
$listOk = $listResp['ok'] && in_array((int)$listResp['status'], [200,201], true);
$summary[] = ['step'=>'list_bookings','ok'=>$listOk,'status'=>$listResp['status'],'count'=>is_array($listParsed)?count($listParsed):null];

// 4) Create a new booking (choose first available service or fallback)
$serviceId = null;
$vehicleId = null;
$today = date('Y-m-d', strtotime('+2 days'));
$time = '10:00';

if (is_array($listParsed) && count($listParsed) > 0 && isset($listParsed[0]['service_id'])) {
    $serviceId = (int)$listParsed[0]['service_id'];
}
if (!$serviceId) {
    // Try to discover an active service via DB
    try {
        if ($useDbClass && method_exists($db, 'fetchOne')) {
            $s = $db->fetchOne("SELECT id FROM services WHERE status = 'active' LIMIT 1");
            $serviceId = $s['id'] ?? null;
        } elseif ($db instanceof \PDO) {
            $stmt = $db->prepare("SELECT id FROM services WHERE status = 'active' LIMIT 1");
            $stmt->execute();
            $s = $stmt->fetch(\PDO::FETCH_ASSOC);
            $serviceId = $s['id'] ?? null;
        } elseif ($db instanceof \mysqli) {
            $res = $db->query("SELECT id FROM services WHERE status = 'active' LIMIT 1");
            $s = $res->fetch_assoc();
            $serviceId = $s['id'] ?? null;
        }
    } catch (Throwable $e) { /* ignore */ }
}
if (!$serviceId) {
    $summary[] = ['step'=>'create_booking','ok'=>false,'message'=>'No service found to create booking'];
} else {
    $payload = ['service_id' => $serviceId, 'date' => $today, 'time' => $time];
    $logs[] = "Creating booking with payload " . json_encode($payload);
    $createResp = postJson($BOOKING_CREATE_API, $payload, ['Accept: application/json'], $cookieJar);
    $createParsed = $createResp['ok'] ? safeJsonDecode($createResp['body'] ?? '') : null;
    $logs[] = ['request'=>['url'=>$BOOKING_CREATE_API,'method'=>'POST','body'=>$payload],'response'=>$createResp,'parsed'=>$createParsed];
    $createdOk = $createResp['ok'] && in_array((int)$createResp['status'], [200,201]) && is_array($createParsed) && (!empty($createParsed['success']) || !empty($createParsed['booking_id']) || !empty($createParsed['bookingId']));
    $bookingId = $createParsed['booking_id'] ?? $createParsed['bookingId'] ?? null;
    if ($createdOk && !$bookingId) {
        // attempt to discover booking by recent insert in DB
        try {
            if ($useDbClass) {
                $recent = $db->fetchOne("SELECT id FROM bookings WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1", ['uid'=>$testUser['id']]);
                $bookingId = $recent['id'] ?? $recent['ID'] ?? null;
            } elseif ($db instanceof \PDO) {
                $stmt = $db->prepare("SELECT id FROM bookings WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([':uid'=>$testUser['id']]);
                $recent = $stmt->fetch(\PDO::FETCH_ASSOC);
                $bookingId = $recent['id'] ?? null;
            } elseif ($db instanceof \mysqli) {
                $stmt = $db->prepare("SELECT id FROM bookings WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                $uid = (int)$testUser['id'];
                $stmt->bind_param('i',$uid);
                $stmt->execute();
                $res = $stmt->get_result();
                $recent = $res->fetch_assoc();
                $bookingId = $recent['id'] ?? null;
            }
        } catch (Throwable $e) { /* ignore */ }
    }
    $summary[] = ['step'=>'create_booking','ok'=>$createdOk,'status'=>$createResp['status'] ?? null,'booking_id'=>$bookingId ?? null];
}

// 5) Edit the booking (if created)
if (!empty($bookingId)) {
    $newTime = '11:30';
    $updatePayload = ['booking_id' => $bookingId, 'date' => $today, 'time' => $newTime];
    $logs[] = "Updating booking $bookingId to time $newTime";
    $updateResp = postJson($BOOKING_UPDATE_API, $updatePayload, ['Accept: application/json'], $cookieJar);
    $updateParsed = $updateResp['ok'] ? safeJsonDecode($updateResp['body'] ?? '') : null;
    $logs[] = ['request'=>['url'=>$BOOKING_UPDATE_API,'method'=>'POST','body'=>$updatePayload],'response'=>$updateResp,'parsed'=>$updateParsed];
    $updateOk = $updateResp['ok'] && in_array((int)$updateResp['status'], [200,201]) && is_array($updateParsed) && !empty($updateParsed['success']);
    $summary[] = ['step'=>'update_booking','ok'=>$updateOk,'status'=>$updateResp['status'] ?? null,'booking_id'=>$bookingId];
} else {
    $summary[] = ['step'=>'update_booking','ok'=>false,'message'=>'No booking id available'];
}

// 6) Proceed to payment for the booking (simulate)
if (!empty($bookingId)) {
    $paymentPayload = ['booking_id' => $bookingId, 'method' => 'test', 'amount' => 0.0];
    $logs[] = "Processing payment for booking $bookingId";
    $payResp = postJson($PAYMENT_API, $paymentPayload, ['Accept: application/json'], $cookieJar);
    $payParsed = $payResp['ok'] ? safeJsonDecode($payResp['body'] ?? '') : null;
    $logs[] = ['request'=>['url'=>$PAYMENT_API,'method'=>'POST','body'=>$paymentPayload],'response'=>$payResp,'parsed'=>$payParsed];
    $payOk = $payResp['ok'] && in_array((int)$payResp['status'], [200,201]) && is_array($payParsed) && !empty($payParsed['success']);
    $summary[] = ['step'=>'payment','ok'=>$payOk,'status'=>$payResp['status'] ?? null,'payment' => $payParsed];
} else {
    $summary[] = ['step'=>'payment','ok'=>false,'message'=>'No booking id available'];
}

// 7) Verify DB state for booking
$dbChecks = [];
if (!empty($bookingId)) {
    try {
        if ($useDbClass && method_exists($db,'fetchOne')) {
            $b = $db->fetchOne("SELECT * FROM bookings WHERE id = :id LIMIT 1", ['id' => $bookingId]);
        } elseif ($db instanceof \PDO) {
            $stmt = $db->prepare("SELECT * FROM bookings WHERE id = :id LIMIT 1");
            $stmt->execute([':id'=>$bookingId]);
            $b = $stmt->fetch(\PDO::FETCH_ASSOC);
        } elseif ($db instanceof \mysqli) {
            $stmt = $db->prepare("SELECT id, user_id, carwash_id, service_id, vehicle_id, booking_date, booking_time, status, price FROM bookings WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $bookingId);
            $stmt->execute();
            $res = $stmt->get_result();
            $b = $res->fetch_assoc();
        } else {
            $b = null;
        }
        $dbChecks[] = ['booking_found' => (bool)$b, 'record' => $b];
        $summary[] = ['step'=>'db_verify_booking','ok'=>(bool)$b,'booking'=>$b ? ['id'=>$b['id'],'date'=>$b['booking_date'] ?? $b['date'] ?? null,'time'=>$b['booking_time'] ?? $b['time'] ?? null] : null];
    } catch (Throwable $e) {
        $dbChecks[] = ['error'=>$e->getMessage()];
        $summary[] = ['step'=>'db_verify_booking','ok'=>false,'error'=>$e->getMessage()];
    }
} else {
    $summary[] = ['step'=>'db_verify_booking','ok'=>false,'message'=>'No booking id available'];
}

// Clean up cookie file
@unlink($cookieJar);

// Final report
$report = [
    'run_at' => date('c'),
    'base' => $BASE,
    'test_user' => $testEmail,
    'logs' => $logs,
    'summary' => $summary,
    'db_checks' => $dbChecks
];
file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "E2E run complete. Report: $reportFile\n";

// Insert test user directly (forcing username, etc.)
try {
    if ($useDbClass) {
        $db->query("INSERT INTO users (username, email, name, password, role, is_active, created_at, updated_at)
VALUES ('e2e_test', 'e2e_test@example.com', 'E2E Test', '$2y$10$REPLACE_WITH_HASH', 'customer', 1, NOW(), NOW())");
    } elseif ($db instanceof \PDO) {
        $db->exec("INSERT INTO users (username, email, name, password, role, is_active, created_at, updated_at)
VALUES ('e2e_test', 'e2e_test@example.com', 'E2E Test', '$2y$10$REPLACE_WITH_HASH', 'customer', 1, NOW(), NOW())");
    } elseif ($db instanceof \mysqli) {
        $stmt = $db->prepare("INSERT INTO users (username, email, name, password, role, is_active, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $username = 'e2e_test';
        $email = 'e2e_test@example.com';
        $name = 'E2E Test';
        $password = password_hash('E2ePass123!', PASSWORD_DEFAULT);
        $role = 'customer';
        $isActive = 1;
        $stmt->bind_param('sssssis', $username, $email, $name, $password, $role, $isActive);
        $stmt->execute();
    }
} catch (Throwable $e) {
    echo "Failed to insert test user directly: " . $e->getMessage() . "\n";
}