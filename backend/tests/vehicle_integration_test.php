<?php
/**
 * Vehicle integration test (CLI)
 *
 * This script performs an end-to-end test of the "Add vehicle" flow in the
 * Customer Dashboard. It:
 *  - Fetches the test page to initialize a session and obtain a CSRF token
 *  - Submits a multipart/form-data POST to Customer_Dashboard_process.php with
 *    action=create_vehicle including an uploaded image
 *  - Parses the JSON response and verifies success
 *  - Connects to the configured DB and verifies the inserted row exists
 *  - Cleans up the inserted DB row and uploaded file
 *
 * Usage:
 *   php backend/tests/vehicle_integration_test.php
 *
 * Environment variables (optional):
 *   TEST_BASE_URL     e.g. http://localhost/carwash_project  (defaults to http://localhost/carwash_project)
 *   TEST_DB_DSN       e.g. mysql:host=127.0.0.1;dbname=carwash_db;charset=utf8mb4
 *   TEST_DB_USER      DB user (default: root)
 *   TEST_DB_PASS      DB pass (default: empty)
 *   TEST_USER_ID      The user_id that the test page will set (default: 1)
 *
 * NOTE: this script expects the application to be served at TEST_BASE_URL.
 * The helper test page `backend/tests/vehicle_form_test.php` (added earlier)
 * is used to create a PHP session and CSRF token for a test user.
 */

$base = rtrim(getenv('TEST_BASE_URL') ?: 'http://localhost/carwash_project', '/');
$testPage = $base . '/backend/tests/vehicle_form_test.php';
$processUrl = $base . '/backend/dashboard/Customer_Dashboard_process.php';

$dbDsn = getenv('TEST_DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=carwash_db;charset=utf8mb4';
$dbUser = getenv('TEST_DB_USER') ?: 'root';
$dbPass = getenv('TEST_DB_PASS') ?: '';

$cookieFile = sys_get_temp_dir() . '/cw_test_cookie_' . bin2hex(random_bytes(6));
$tmpImage = sys_get_temp_dir() . '/cw_test_img_' . bin2hex(random_bytes(6)) . '.png';
$uploadedPathFromDb = null;
$insertedVehicleId = null;

function fail($msg, $code = 1) {
    fwrite(STDERR, "FAIL: $msg\n");
    exit($code);
}

function info($msg) { fwrite(STDOUT, "INFO: $msg\n"); }

// 1) GET the test page to create a session and obtain CSRF token
info("Fetching test page to initialize session: $testPage");
$ch = curl_init($testPage);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => 'CarWash-Integration-Test/1.0'
]);
$resp = curl_exec($ch);
if ($resp === false) {
    fail('Failed to fetch test page: ' . curl_error($ch));
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpCode >= 400) {
    fail("Test page returned HTTP $httpCode; ensure your dev server is running and TEST_BASE_URL is correct ($base)");
}

// extract csrf token from the HTML hidden input
if (!preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $resp, $m)) {
    // try looser pattern
    if (!preg_match('/<input[^ id="$id">]+name=["\']csrf_token["\'][^>]*value=["\']([^"\']+)["\']/i', $resp, $m)) {
        fail('Could not locate csrf_token in test page response. Response snippet: ' . substr(strip_tags($resp),0,500));
    }
}
$csrf = $m[1];
info("Got CSRF token: $csrf");

// 2) create a tiny PNG to upload
info('Creating temporary image: ' . $tmpImage);
$img = imagecreatetruecolor(120, 60);
$bg = imagecolorallocate($img, 200, 220, 240);
$txtc = imagecolorallocate($img, 10, 10, 10);
imagefilledrectangle($img, 0, 0, 120, 60, $bg);
imagestring($img, 5, 6, 20, 'TestCar', $txtc);
imagepng($img, $tmpImage);
imagedestroy($img);

if (!file_exists($tmpImage)) fail('Failed to create temporary image');

// 3) POST multipart/form-data to create vehicle
$license = 'TEST-' . bin2hex(random_bytes(4));
$post = [
    'action' => 'create_vehicle',
    'csrf_token' => $csrf,
    'car_brand' => 'IntegrationBrand',
    'car_model' => 'IntegrationModel',
    'license_plate' => $license,
    'car_year' => '2021',
    'car_color' => 'Cyan'
];

// prepare the file field
if (function_exists('curl_file_create')) {
    $post['vehicle_image'] = curl_file_create($tmpImage, 'image/png', basename($tmpImage));
} else {
    $post['vehicle_image'] = '@' . $tmpImage;
}

info('Submitting create_vehicle POST to ' . $processUrl . ' with license_plate=' . $license);
$ch = curl_init($processUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => 'CarWash-Integration-Test/1.0',
    CURLOPT_HTTPHEADER => [ 'Accept: application/json' ]
]);
$resp = curl_exec($ch);
if ($resp === false) {
    fail('Vehicle POST failed: ' . curl_error($ch));
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

info('Server returned HTTP ' . $httpCode);
// Try parse JSON
$json = json_decode($resp, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // print response for debugging
    file_put_contents(sys_get_temp_dir() . '/cw_last_response.html', $resp);
    fail('Response was not valid JSON. Raw response saved to ' . sys_get_temp_dir() . '/cw_last_response.html');
}

if (empty($json['success'])) {
    fail('Server returned an error: ' . json_encode($json, JSON_UNESCAPED_UNICODE));
}

$insertedVehicleId = $json['vehicle_id'] ?? null;
if (!$insertedVehicleId) {
    fail('Server did not return vehicle_id in success response: ' . json_encode($json));
}
info('Vehicle created via API with id: ' . $insertedVehicleId);

// 4) Verify DB row exists
try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Exception $e) {
    fail('DB connection failed: ' . $e->getMessage());
}

$stmt = $pdo->prepare('SELECT * FROM user_vehicles WHERE id = :id AND license_plate = :lp LIMIT 1');
$stmt->execute([':id' => $insertedVehicleId, ':lp' => $license]);
$row = $stmt->fetch();
if (!$row) {
    // Try lookup by license plate only
    $stmt2 = $pdo->prepare('SELECT * FROM user_vehicles WHERE license_plate = :lp ORDER BY id DESC LIMIT 1');
    $stmt2->execute([':lp' => $license]);
    $row = $stmt2->fetch();
    if (!$row) fail('No DB row found for inserted vehicle id/license: ' . $insertedVehicleId . '/' . $license);
}

info('DB verification succeeded. Row: ' . json_encode($row));
$uploadedPathFromDb = $row['image_path'] ?? null;

// 5) Cleanup: delete DB row and uploaded file
try {
    $del = $pdo->prepare('DELETE FROM user_vehicles WHERE id = :id');
    $del->execute([':id' => $row['id']]);
    info('Deleted test DB row id=' . $row['id']);
} catch (Exception $e) {
    info('Warning: failed to delete test DB row: ' . $e->getMessage());
}

if ($uploadedPathFromDb) {
    // map relative upload path to filesystem
    $projectRoot = realpath(__DIR__ . '/../../');
    $candidate = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($uploadedPathFromDb, '/'));
    if (file_exists($candidate)) {
        unlink($candidate);
        info('Deleted uploaded image: ' . $candidate);
    } else {
        info('Uploaded image not found on disk: ' . $candidate);
    }
}

// Remove temporary files
@unlink($tmpImage);
@unlink($cookieFile);
info('Temporary files removed.');

info('Integration test PASSED');
exit(0);

