<?php
/**
 * Form wiring checker
 *
 * Scans a target page for forms (default: Customer_Dashboard vehicle inline form),
 * submits a test payload (including file if needed), and verifies whether
 * the submission produced a DB row. It reports:
 *  - endpoint reachable (server responded)
 *  - response type (JSON/HTML)
 *  - registration (DB row found by unique marker)
 *  - editability (attempts to update the created row)
 *
 * Usage:
 *   php backend/tests/form_wiring_checker.php [page-path]
 *
 * Example:
 *   php backend/tests/form_wiring_checker.php /backend/dashboard/Customer_Dashboard.php
 *
 * Environment variables:
 *   TEST_BASE_URL      (default: http://localhost/carwash_project)
 *   TEST_DB_DSN        (optional PDO DSN; default uses DB_HOST/DB_NAME/DB_USER/DB_PASS envs)
 *   TEST_DB_USER
 *   TEST_DB_PASS
 *
 * Notes:
 * - The script expects the dev server to be running and accessible.
 * - It uses a temp cookie jar to preserve session between GET and POST.
 * - By default it will target the inline vehicle form (inputs named car_brand, license_plate).
 */

$base = rtrim(getenv('TEST_BASE_URL') ?: 'http://localhost/carwash_project', '/');
$arg = $argv[1] ?? '/backend/dashboard/Customer_Dashboard.php';
$targetUrl = (strpos($arg, 'http') === 0) ? $arg : $base . $arg;

$cookieFile = sys_get_temp_dir() . '/fwc_cookie_' . bin2hex(random_bytes(6));
$tmpImg = sys_get_temp_dir() . '/fwc_img_' . bin2hex(random_bytes(6)) . '.png';
$uniqueMarker = 'FWC-' . bin2hex(random_bytes(5));

function info($s){ echo "[INFO] $s\n"; }
function warn($s){ echo "[WARN] $s\n"; }
function err($s){ echo "[ERROR] $s\n"; }

info("Target page: $targetUrl");

// 1) GET the page to capture CSRF and session cookie
$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => 'FormWiringChecker/1.0'
]);
$resp = curl_exec($ch);
if ($resp === false) { err('Failed to fetch page: ' . curl_error($ch)); exit(2); }
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
info("GET returned HTTP $http");

// Separate headers and body (simple split)
$parts = preg_split("/\r?\n\r?\n/", $resp, 2);
$body = $parts[1] ?? $resp;

// 2) Parse HTML to find a form that contains vehicle fields
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($body);
$forms = $dom->getElementsByTagName('form');
if ($forms->length === 0) { warn('No forms found on page'); }

target_loop:
for ($i=0;$i<$forms->length;$i++){
    $form = $forms->item($i);
    // Collect input names
    $inputs = [];
    foreach ($form->getElementsByTagName('input') as $inp){ $name = $inp->getAttribute('name'); if ($name) $inputs[] = $name; }
    foreach ($form->getElementsByTagName('textarea') as $ta){ $name = $ta->getAttribute('name'); if ($name) $inputs[] = $name; }
    foreach ($form->getElementsByTagName('select') as $s){ $name = $s->getAttribute('name'); if ($name) $inputs[] = $name; }

    $names = array_unique($inputs);
    // Heuristic: prefer forms that mention vehicle fields
    $intersect = array_intersect($names, ['car_brand','car_model','license_plate','vehicle_image','car_year','car_color']);
    if (count($intersect) === 0) continue; // not the vehicle form

    // Found candidate form
    info('Found a vehicle-like form; form inputs: ' . implode(',', $names));
    $action = $form->getAttribute('action') ?: $targetUrl; // relative or empty
    $method = strtoupper($form->getAttribute('method') ?: 'GET');
    $enctype = $form->getAttribute('enctype') ?: 'application/x-www-form-urlencoded';

    // Resolve action URL
    if (parse_url($action, PHP_URL_HOST) === null) {
        // relative
        $baseParts = parse_url($targetUrl);
        $baseRoot = $baseParts['scheme'] . '://' . $baseParts['host'] . (isset($baseParts['port'])? ':' . $baseParts['port'] : '');
        if (strpos($action, '/') === 0) {
            $actionUrl = $baseRoot . $action;
        } else {
            // relative path
            $path = dirname($baseParts['path']);
            $actionUrl = $baseRoot . $path . '/' . $action;
        }
    } else {
        $actionUrl = $action;
    }

    info("Form method=$method enctype=$enctype action=$actionUrl");

    // Extract CSRF token if present in the form
    $csrf = '';
    if (in_array('csrf_token', $names)) {
        // find the input value
        foreach ($form->getElementsByTagName('input') as $inp){ if ($inp->getAttribute('name') === 'csrf_token'){ $csrf = $inp->getAttribute('value'); break; } }
    } else {
        // try to find anywhere in page hidden input
        if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([a-f0-9]+)["\']/i', $body, $m)) $csrf = $m[1];
    }
    if ($csrf) info('Found CSRF token in page/form'); else warn('No CSRF token found in form/page; requests may be rejected');

    // Build payload
    $post = [];
    // default test values
    $post['car_brand'] = 'FWC Brand';
    $post['car_model'] = 'Model-' . substr($uniqueMarker,0,6);
    $post['license_plate'] = $uniqueMarker;
    $post['car_year'] = date('Y');
    $post['car_color'] = 'Gray';
    if ($csrf) $post['csrf_token'] = $csrf;
    // If form expects action param, include common ones
    if (!in_array('action', $names)) {
        // do nothing; if action required, tests may fail — we'll handle response
    } else {
        $post['action'] = $form->getAttribute('data-action') ?: 'create';
    }

    // Create a small PNG file for upload if there's a vehicle_image field and enctype is multipart
    $needFile = in_array('vehicle_image', $names) && stripos($enctype,'multipart')!==false;
    if ($needFile) {
        $img = imagecreatetruecolor(80,40);
        $bg = imagecolorallocate($img, 180,200,220);
        $col = imagecolorallocate($img, 10,10,10);
        imagefilledrectangle($img,0,0,80,40,$bg);
        imagestring($img,3,6,12,'FWC',$col);
        imagepng($img, $tmpImg);
        imagedestroy($img);
        if (!file_exists($tmpImg)) { warn('Failed to create temp image for upload'); }
    }

    // 3) Submit form
    info('Submitting form to ' . $actionUrl);
    $ch = curl_init($actionUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'FormWiringChecker/1.0');
    if ($method === 'POST') {
        if ($needFile) {
            if (function_exists('curl_file_create')) {
                $post['vehicle_image'] = curl_file_create($tmpImg, 'image/png', basename($tmpImg));
            } else {
                $post['vehicle_image'] = '@' . $tmpImg;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_POST, true);
    }
    $resp = curl_exec($ch);
    if ($resp === false) { err('Submit failed: ' . curl_error($ch)); curl_close($ch); break; }
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    info("Submit returned HTTP $http");

    // 4) Inspect response: try parse JSON
    $json = json_decode($resp, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        info('Response parsed as JSON: ' . ($json['success'] ? 'success' : 'failure'));
        if (!empty($json['success'])) {
            info('Server responded success; looking up created record in DB by unique marker...');
        } else {
            warn('Server returned JSON but success != true: ' . json_encode($json));
        }
    } else {
        warn('Response not JSON (likely HTML). Saving snapshot to ' . sys_get_temp_dir() . '/fwc_last_response.html');
        file_put_contents(sys_get_temp_dir() . '/fwc_last_response.html', $resp);
    }

    // 5) DB verification heuristics: try connect and search candidate tables for the license_plate/marker
    $pdo = null;
    $dsn = getenv('TEST_DB_DSN') ?: null;
    $dbUser = getenv('TEST_DB_USER') ?: null;
    $dbPass = getenv('TEST_DB_PASS') ?: null;
    if (!$dsn) {
        // try reading common envs
        $h = getenv('DB_HOST') ?: '127.0.0.1';
        $n = getenv('DB_NAME') ?: 'carwash_db';
        $u = getenv('DB_USER') ?: 'root';
        $p = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host={$h};dbname={$n};charset=utf8mb4";
        $dbUser = $u; $dbPass = $p;
    }

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    } catch (Exception $e) {
        warn('DB connection failed: ' . $e->getMessage());
        // cleanup temp files and break
        if (file_exists($tmpImg)) @unlink($tmpImg);
        break;
    }

    $candidateTables = ['user_vehicles','vehicles','users','bookings'];
    $found = null;
    foreach ($candidateTables as $t) {
        try {
            // check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE '" . addslashes($t) . "'");
            $has = $stmt->fetch();
            if (!$has) continue;

            // search for any column that equals our marker
            $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll();
            $colNames = array_column($cols, 'Field');
            foreach ($colNames as $cname) {
                $q = $pdo->prepare("SELECT * FROM `{$t}` WHERE `{$cname}` = :val LIMIT 1");
                $q->execute([':val' => $uniqueMarker]);
                $row = $q->fetch();
                if ($row) { $found = ['table'=>$t,'column'=>$cname,'row'=>$row]; break 2; }
                // also check license_plate specifically
                if ($cname === 'license_plate') {
                    $q2 = $pdo->prepare("SELECT * FROM `{$t}` WHERE `license_plate` = :val LIMIT 1");
                    $q2->execute([':val' => $uniqueMarker]);
                    $r2 = $q2->fetch(); if ($r2) { $found = ['table'=>$t,'column'=>'license_plate','row'=>$r2]; break 2; }
                }
            }
        } catch (Exception $e) {
            // ignore and continue
        }
    }

    if ($found) {
        info('Found DB record in table ' . $found['table'] . ' column ' . $found['column']);
        info('Row: ' . json_encode($found['row']));
        // try editability: if row has id primary, attempt update
        $idCol = null; foreach (['id','ID'] as $c) if (isset($found['row'][$c])) { $idCol = $c; break; }
        if ($idCol) {
            $id = $found['row'][$idCol];
            info('Attempting an update on found row id=' . $id);
            // try calling vehicle_api.php update action if exists
            $updateUrl = $base . '/backend/dashboard/vehicle_api.php';
            $fd = new CURLFile(''); // placeholder to keep type
            $ch = curl_init($updateUrl);
            $post = ['action'=>'update','id'=>$id,'car_brand'=>'FWC-UPDATED','csrf_token'=>($csrf?:'')];
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $resp2 = curl_exec($ch);
            $http2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $j2 = json_decode($resp2, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($j2['success'])) {
                info('Update via vehicle_api.php succeeded');
            } else {
                warn('Update attempt did not return success; response saved to ' . sys_get_temp_dir() . '/fwc_update_response.html');
                file_put_contents(sys_get_temp_dir() . '/fwc_update_response.html', $resp2);
            }
        } else {
            warn('Could not detect primary id column to test update');
        }

        // cleanup: delete test row if possible
        try {
            // attempt to delete by id if available
            if (isset($found['row']['id'])) {
                $d = $pdo->prepare('DELETE FROM `' . $found['table'] . '` WHERE id = :id');
                $d->execute([':id' => $found['row']['id']]);
                info('Deleted test row id=' . $found['row']['id']);
            } else {
                // try delete by license_plate
                if (isset($found['row']['license_plate'])) {
                    $d = $pdo->prepare('DELETE FROM `' . $found['table'] . '` WHERE license_plate = :lp');
                    $d->execute([':lp' => $uniqueMarker]);
                    info('Deleted test rows by license_plate');
                }
            }
        } catch (Exception $e) { warn('Failed to delete test row: ' . $e->getMessage()); }

    } else {
        warn('Could not find a DB row matching the unique marker; the form may not be wired to DB or the action used a different column/table.');
    }

    // cleanup temp image
    if (file_exists($tmpImg)) @unlink($tmpImg);

    // finished first matched form
    break;
}

info('Done. Temp cookie file: ' . $cookieFile);
exit(0);
