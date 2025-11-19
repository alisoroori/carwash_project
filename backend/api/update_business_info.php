<?php
/**
 * Update Business Information API
 * Non-destructive updates for `carwashes` (preferred) or `business_profiles`.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;
use App\Classes\Validator;
use App\Classes\Session;

Session::start();

// Debugging helper: log incoming CSRF/session headers for diagnosis (temporary)
// Moved to run before auth checks so unauthenticated/forbidden attempts are captured.
try {
    $dbgDir = __DIR__ . '/../../logs';
    if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
    $dbgFile = $dbgDir . '/csrf_debug.log';
    $now = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'UNK';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $cookieHeader = $_SERVER['HTTP_COOKIE'] ?? '';
    $providedHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_SERVER['HTTP_X_CSRFTOKEN'] ?? ($_SERVER['HTTP_X_XSRF_TOKEN'] ?? ''));
    // When using FormData, PHP only populates $_POST after input parsing; try to capture raw POST body token too
    $providedField = $_POST['csrf_token'] ?? null;
    $sessId = session_id();
    $sessCsrf = $_SESSION['csrf_token'] ?? null;
    $sessUser = isset($_SESSION['user_id']) ? (string)$_SESSION['user_id'] : (isset($_SESSION['user']['id']) ? (string)$_SESSION['user']['id'] : 'none');
    $mask = function($s){ if (!$s) return ''; $s = (string)$s; return substr($s,0,6) . '...' . strlen($s); };
    $line = sprintf("%s | %s | %s %s | sid=%s | user=%s | hdr=%s | fld=%s | sess_csrf=%s | cookie_len=%d\n",
        $now, $ip, $method, $uri, $sessId, $sessUser, $mask($providedHeader), $mask($providedField), $mask($sessCsrf), strlen($cookieHeader)
    );
    @file_put_contents($dbgFile, $line, FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    // ignore debug logging failures
}

Auth::requireAuth();
Auth::requireRole(['carwash', 'admin']);

// Enforce CSRF protection for API POSTs
// uses backend/includes/csrf_protect.php -> require_valid_csrf()
$csrfHelper = __DIR__ . '/../includes/csrf_protect.php';
if (file_exists($csrfHelper)) {
    require_once $csrfHelper;
    if (function_exists('require_valid_csrf')) {
        require_valid_csrf();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) Response::unauthorized();

    $getPosted = function($k) { return array_key_exists($k, $_POST) ? trim($_POST[$k]) : null; };
    $businessName = $getPosted('business_name');
    $address = $getPosted('address');
    $phone = $getPosted('phone');
    $mobilePhone = $getPosted('mobile_phone');
    $email = array_key_exists('email', $_POST) ? Validator::sanitizeEmail($_POST['email']) : null;
    $postal_code = $getPosted('postal_code');
    $license_number = $getPosted('license_number');
    $tax_number = $getPosted('tax_number');
    $city = $getPosted('city');
    $district = $getPosted('district');

    // Partial validation
    $validator = new Validator();
    if (array_key_exists('business_name', $_POST)) {
        $validator->required($businessName, 'İşletme Adı')->minLength($businessName, 3, 'İşletme Adı');
    }
    if (array_key_exists('email', $_POST) && $email !== null) {
        $validator->email($email, 'E-posta');
    }
    if ($validator->fails()) Response::validationError($validator->getErrors());

    // Logo upload (optional) - store only filename in DB, move into standardized uploads/business_logo folder
    $logoPath = null; // will hold filename only
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/jpg','image/webp','image/gif'];
        $type = $_FILES['logo']['type'] ?? '';
        $size = $_FILES['logo']['size'] ?? 0;
        if (!in_array($type, $allowed)) Response::error('Invalid file type', 400);
        if ($size > 5 * 1024 * 1024) Response::error('File too large', 400);
        // Use absolute path relative to this file for reliable local development
        $uploadDir = __DIR__ . '/../uploads/business_logo/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $fn = 'logo_' . $userId . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $fn;
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            // log failure
            @file_put_contents(__DIR__ . '/../../logs/logo_missing.log', date('Y-m-d H:i:s') . " - failed move uploaded logo for user {$userId} to {$dest}\n", FILE_APPEND | LOCK_EX);
            Response::error('Upload failed', 500);
        }
        // Save only filename in DB
        $logoPath = $fn;
    }

    // Quick session updates for UX
    if ($businessName !== null) { $_SESSION['business_name'] = $businessName; $_SESSION['name'] = $businessName; }
    if ($logoPath) $_SESSION['logo_path'] = $logoPath; // filename only

    $pdo->beginTransaction();

    // Ensure `carwashes` is present and use it as the authoritative table
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'carwashes'");
    $check->execute();
    $hasCarwashes = (bool)$check->fetchColumn();
    if (!$hasCarwashes) {
        $pdo->rollBack();
        Response::error('Carwashes table not found on this installation', 500);
    }

    $stmt = $pdo->prepare('SELECT * FROM carwashes WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $updates = [];
        if (array_key_exists('business_name', $_POST)) $updates['name'] = $businessName;
        if (array_key_exists('address', $_POST)) $updates['address'] = $address;
        if (array_key_exists('phone', $_POST)) $updates['phone'] = $phone;
        if (array_key_exists('mobile_phone', $_POST)) $updates['mobile_phone'] = $mobilePhone;
        if (array_key_exists('email', $_POST)) $updates['email'] = $email;
        if (array_key_exists('postal_code', $_POST)) $updates['postal_code'] = $postal_code;
        if (array_key_exists('license_number', $_POST)) $updates['license_number'] = $license_number;
        if (array_key_exists('tax_number', $_POST)) $updates['tax_number'] = $tax_number;
        if (array_key_exists('city', $_POST)) $updates['city'] = $city;
        if (array_key_exists('district', $_POST)) $updates['district'] = $district;

        // Merge working_hours if any day fields present
        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        $postedWorking = false;
        foreach ($days as $d) { if (array_key_exists("{$d}_start", $_POST) || array_key_exists("{$d}_end", $_POST)) { $postedWorking = true; break; } }
        if ($postedWorking) {
            $existingHours = !empty($existing['working_hours']) ? json_decode($existing['working_hours'], true) : [];
            $wh = [];
            foreach ($days as $day) {
                $start = $_POST["{$day}_start"] ?? ($existingHours[$day]['start'] ?? '09:00');
                $end = $_POST["{$day}_end"] ?? ($existingHours[$day]['end'] ?? '18:00');
                $wh[$day] = ['start' => $start, 'end' => $end];
            }
            $updates['working_hours'] = json_encode($wh);
        }

        // Merge social_media
        if (array_key_exists('social_media', $_POST)) {
            $posted = is_string($_POST['social_media']) ? json_decode($_POST['social_media'], true) : $_POST['social_media'];
            $existingSm = !empty($existing['social_media']) ? json_decode($existing['social_media'], true) : [];
            $mergedSm = array_merge($existingSm, is_array($posted) ? $posted : []);
            $updates['social_media'] = json_encode($mergedSm);
        }

        // Merge services: union by normalized name
        if (array_key_exists('services', $_POST)) {
            $posted = is_string($_POST['services']) ? json_decode($_POST['services'], true) : $_POST['services'];
            $existingSv = !empty($existing['services']) ? json_decode($existing['services'], true) : [];
            $keyed = [];
            foreach (array_merge($existingSv, is_array($posted) ? $posted : []) as $svc) {
                if (is_array($svc) && isset($svc['name'])) {
                    $k = trim(strtolower($svc['name']));
                    if (!isset($keyed[$k])) $keyed[$k] = $svc; else { if (empty($keyed[$k]['price']) && !empty($svc['price'])) $keyed[$k]['price'] = $svc['price']; }
                } else {
                    $k = is_string($svc) ? trim(strtolower($svc)) : md5(json_encode($svc));
                    if (!isset($keyed[$k])) $keyed[$k] = is_array($svc) ? $svc : ['name' => $svc];
                }
            }
            $updates['services'] = json_encode(array_values($keyed));
        }

        if ($logoPath) $updates['logo_path'] = $logoPath; // store filename only
        if (!empty($updates)) $updates['updated_at'] = date('Y-m-d H:i:s');

    if (!empty($existing)) {
        if (!empty($updates)) {
            $sets = [];
            $params = ['user_id' => $userId];
            foreach ($updates as $col => $val) { $sets[] = "{$col} = :{$col}"; $params[$col] = $val; }
            $sql = 'UPDATE carwashes SET ' . implode(', ', $sets) . ' WHERE user_id = :user_id';
            $upd = $pdo->prepare($sql);
            $upd->execute($params);
        }
    } else {
        // Insert new row with provided columns only into carwashes
        $cols = ['user_id','created_at','updated_at'];
        $insParams = ['user_id' => $userId, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $map = [
            'business_name' => 'name', 'address' => 'address', 'phone' => 'phone', 'mobile_phone' => 'mobile_phone',
            'email' => 'email', 'postal_code' => 'postal_code', 'license_number' => 'license_number',
            'tax_number' => 'tax_number', 'city' => 'city', 'district' => 'district'
        ];
        foreach ($map as $postKey => $colName) {
            if (array_key_exists($postKey, $_POST)) { $cols[] = $colName; $insParams[$colName] = ${$postKey}; }
        }
        foreach (['working_hours','social_media','services','logo_path','certificate_path'] as $c) {
            if (isset($updates[$c])) { $cols[] = $c; $insParams[$c] = $updates[$c]; }
        }
        $placeholders = ':' . implode(',:', $cols);
        $sql = 'INSERT INTO carwashes (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
        $ins = $pdo->prepare($sql);
        $ins->execute($insParams);
    }

    $pdo->commit();

    // Fetch fresh record from `carwashes` and return normalized shape
    $fetch = $pdo->prepare('SELECT id, user_id, name AS business_name, address, COALESCE(postal_code, zip_code) AS postal_code, phone, mobile_phone, email, COALESCE(working_hours, opening_hours) AS working_hours, COALESCE(logo_path, logo_image, profile_image, image) AS logo_path, COALESCE(license_number, "") AS license_number, COALESCE(tax_number, "") AS tax_number, COALESCE(city, "") AS city, COALESCE(district, "") AS district, social_media, services, COALESCE(certificate_path, "") AS certificate_path, created_at, updated_at FROM carwashes WHERE user_id = :user_id LIMIT 1');
    $fetch->execute(['user_id' => $userId]);
    $row = $fetch->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($row && isset($row['working_hours'])) $row['working_hours'] = json_decode($row['working_hours'], true) ?: $row['working_hours'];
    if ($row && isset($row['services'])) $row['services'] = json_decode($row['services'], true) ?: $row['services'];
    if ($row && isset($row['social_media'])) $row['social_media'] = json_decode($row['social_media'], true) ?: $row['social_media'];

    // Normalize logo_path to full web URL for client while keeping filename in DB
    if ($row && !empty($row['logo_path'])) {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';
        $lp = $row['logo_path'];
        // If the stored value already looks like a path or url, prefer it; otherwise treat as filename
        if (preg_match('#^(?:https?://|/)#i', $lp)) {
            $row['logo_path'] = $lp;
        } else {
            // map filename to business_logo folder
            $row['logo_path'] = $base_url . '/backend/uploads/business_logo/' . ltrim($lp, '/');
        }
        // Verify file exists on disk; if missing, log and replace with default
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\/');
        // If stored value was treated as filename, map it to uploads/business_logo
        if (!preg_match('#^(?:https?://|/)#i', $lp)) {
            $filePath = $docRoot . '/carwash_project/backend/uploads/business_logo/' . ltrim($lp, '/');
        } else {
            $filePath = $docRoot . parse_url($row['logo_path'], PHP_URL_PATH);
        }
        if (!file_exists($filePath)) {
            @file_put_contents(__DIR__ . '/../../logs/logo_missing.log', date('Y-m-d H:i:s') . " - missing logo for user {$userId}: {$filePath}\n", FILE_APPEND | LOCK_EX);
            $row['logo_path'] = $base_url . '/backend/logo01.png';
        }
    }

    if (!$row) Response::error('Could not fetch updated record', 500);
    Response::success('İşletme bilgileri başarıyla güncellendi', ['data' => $row]);

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if (!empty($logoPath)) {
        // Remove the uploaded file from the business_logo directory (filename-only stored)
        $fileToRemove = __DIR__ . '/../uploads/business_logo/' . $logoPath;
        if (file_exists($fileToRemove)) @unlink($fileToRemove);
    }
    if (class_exists('\App\Classes\Logger')) { \App\Classes\Logger::exception($e); }
    Response::error('An error occurred while updating business information', 500);
}


