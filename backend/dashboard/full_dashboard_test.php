<?php
// full_dashboard_test.php
// A simple, robust plaintext diagnostic for the dashboard environment.

header('Content-Type: text/plain; charset=utf-8');

// =====================
// CONFIG
// =====================
$projectRoot = realpath(__DIR__ . '/../../'); // project root
$uploadsDir = $projectRoot . '/backend/uploads/vehicles';
$defaultCarCandidates = [
    $projectRoot . '/frontend/assets/images/default-car.png',
    $projectRoot . '/frontend/assets/default-car.png',
    $projectRoot . '/frontend/default-car.png',
];
$apiVehicleList = $projectRoot . '/backend/dashboard/vehicle_api.php';
$includesConfig = $projectRoot . '/backend/includes/config.php';

// =====================
// CSRF TOKEN CHECK
// =====================
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        // fallback
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
$csrf = $_SESSION['csrf_token'];
echo "✅ CSRF_TOKEN: $csrf\n\n";

// =====================
// FILES CHECK
// =====================
echo "=== Files Check ===\n";
// default car
$defaultCar = null;
foreach ($defaultCarCandidates as $cand) {
    if (file_exists($cand)) { $defaultCar = $cand; break; }
}
echo "- Checking default car image: ";
if ($defaultCar) echo "FOUND ✅  ($defaultCar)\n"; else echo "MISSING ❌ (looked for: " . implode(', ', $defaultCarCandidates) . ")\n";

// uploads dir
echo "- Checking uploads directory: $uploadsDir\n";
if (is_dir($uploadsDir)) {
    $vehicleFiles = array_values(array_filter(glob($uploadsDir . '/*'), 'is_file'));
    if (!empty($vehicleFiles)) {
        echo "  Found " . count($vehicleFiles) . " files ✅\n";
        $preview = array_slice($vehicleFiles, 0, 20);
        foreach ($preview as $f) echo "   - " . basename($f) . "\n";
        if (count($vehicleFiles) > count($preview)) echo "   ... (" . (count($vehicleFiles) - count($preview)) . " more)\n";
    } else {
        echo "  No files found ❌\n";
    }
} else {
    echo "  Uploads directory missing ❌\n";
}

echo "\n";

// =====================
// API VEHICLES CHECK
// =====================
echo "=== Vehicle API Check ===\n";
// Prepare environment for include - emulate a simple GET list call
// Preserve globals to restore after include
$oldGet = $_GET;
$oldServer = $_SERVER;
$oldPost = $_POST;

// If caller provided ?test_user_id=NN set session user for include-based API auth
if (!empty($_GET['test_user_id']) && is_numeric((string)$_GET['test_user_id'])) {
    $testUid = (int)$_GET['test_user_id'];
    $_SESSION['user_id'] = $testUid;
    // Optionally set common session fields used by dashboard
    if (empty($_SESSION['name'])) $_SESSION['name'] = 'Test User';
    if (empty($_SESSION['email'])) $_SESSION['email'] = 'test+'.$testUid.'@example.local';
    echo "Using test_user_id={$testUid} for API authentication (session simulated)\n";
}

// If caller provided ?test_role=ROLE set role in session and (if available) in App\Classes\Session
if (!empty($_GET['test_role'])) {
    $testRole = (string)$_GET['test_role'];
    // set legacy session vars
    $_SESSION['role'] = $testRole;
    $_SESSION['user_role'] = $testRole;
    // If Session wrapper available and has set method, use it
    if (class_exists('\App\Classes\Session') && method_exists('\App\Classes\Session', 'set')) {
        try {
            \App\Classes\Session::set('role', $testRole);
            \App\Classes\Session::set('user_role', $testRole);
        } catch (Throwable $e) {
            // ignore
        }
    }
    echo "Using test_role={$testRole} (session role simulated)\n";
}

$_GET['action'] = 'list';
$_SERVER['REQUEST_METHOD'] = 'GET';
// ensure script thinks it's same-origin/local
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

ob_start();
try {
    if (file_exists($apiVehicleList)) {
        include $apiVehicleList;
    } else {
        echo "API file not found: $apiVehicleList\n";
    }
} catch (Throwable $e) {
    // capture any fatal errors
    echo "Exception while including vehicle_api.php: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

// Try to extract JSON from output. The API may echo other debugging text before JSON - try to find the last JSON-looking object in output.
$decoded = null;
// Trim whitespace
$trim = trim($output);
if ($trim === '') {
    echo "❌ API returned empty response (no output)\n\n";
} else {
    // attempt direct parse
    $decoded = json_decode($trim, true);
    if ($decoded === null) {
        // try to locate a JSON substring (find first { and last } )
        $first = strpos($trim, '{');
        $last = strrpos($trim, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $sub = substr($trim, $first, $last - $first + 1);
            $decoded = json_decode($sub, true);
            if ($decoded !== null) {
                echo "✅ Extracted JSON payload from API output.\n";
            }
        }
    }

    if ($decoded === null) {
        echo "❌ API returned invalid/non-JSON response:\n";
        // limit amount printed
        $snippet = substr($trim, 0, 2000);
        echo $snippet . "\n";
    } else {
        // normalize vehicles list
        $vehicles = [];
        if (isset($decoded['data']) && isset($decoded['data']['vehicles']) && is_array($decoded['data']['vehicles'])) {
            $vehicles = $decoded['data']['vehicles'];
        } elseif (isset($decoded['vehicles']) && is_array($decoded['vehicles'])) {
            $vehicles = $decoded['vehicles'];
        } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
            // maybe data is the array
            $vehicles = $decoded['data'];
        } elseif (is_array($decoded)) {
            // maybe the API returned a plain array
            $vehicles = $decoded;
        }

        echo "✅ API returned " . count($vehicles) . " vehicles\n";
        if (count($vehicles) > 0) {
            echo "First 5 vehicles preview:\n";
            $preview = array_slice($vehicles, 0, 5);
            foreach ($preview as $v) {
                // mask large fields
                if (is_array($v)) {
                    $v['image_path'] = isset($v['image_path']) ? basename($v['image_path']) : '(no image)';
                }
                print_r($v);
            }
        }
    }
}

echo "\n";

// restore globals
$_GET = $oldGet;
$_SERVER = $oldServer;
$_POST = $oldPost;

// =====================
// DATABASE CHECK
// =====================
echo "=== Database Check ===\n";
if (file_exists($includesConfig)) {
    try {
        // load DB constants if available
        require_once $includesConfig;
    } catch (Throwable $e) {
        echo "Warning: failed to include config.php: " . $e->getMessage() . "\n";
    }
} else {
    echo "Warning: config.php not found at $includesConfig - will try environment variables.\n";
}

// Read constants or env vars
$dbHost = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
$dbName = defined('DB_NAME') ? DB_NAME : getenv('DB_NAME');
$dbUser = defined('DB_USER') ? DB_USER : getenv('DB_USER');
$dbPass = defined('DB_PASS') ? DB_PASS : getenv('DB_PASS');
$dbPort = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: 3306);

if (empty($dbName)) {
    echo "❌ DB name not configured (DB_NAME missing).\n";
} else {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost ?: '127.0.0.1', $dbPort, $dbName);
        $pdo = new PDO($dsn, $dbUser ?: 'root', $dbPass ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        // Try vehicles table first, fallback to user_vehicles
        $tableToCheck = null;
        foreach (['vehicles', 'user_vehicles'] as $t) {
            $stmt = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . addslashes($t) . "' LIMIT 1");
            if ($stmt && $stmt->fetchColumn()) { $tableToCheck = $t; break; }
        }
        if (!$tableToCheck) {
            echo "❌ Neither 'vehicles' nor 'user_vehicles' table found in DB.\n";
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) AS total FROM `" . $tableToCheck . "`");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            echo "✅ Table '$tableToCheck' has $count records\n";
            $stmt = $pdo->query("SELECT id, brand, model, license_plate FROM `" . $tableToCheck . "` ORDER BY id DESC LIMIT 5");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Last 5 rows:\n";
            print_r($rows);
        }

    } catch (PDOException $e) {
        echo "❌ DB Connection failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";

// mark todo item complete in the UI (developer note)
// You can run this file in CLI: php backend/dashboard/full_dashboard_test.php

