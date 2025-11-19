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
// Enforce session and role checks with clear 403 messages and logging
$userId = $_SESSION['user_id'] ?? null;
if (empty($userId)) {
    // Log and return 403 for missing session
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logLine = sprintf("%s | %s | user_id=none | Missing session - update attempt blocked\n", date('c'), $_SERVER['REMOTE_ADDR'] ?? 'cli');
    @file_put_contents($logDir . '/csrf_blocked.log', $logLine, FILE_APPEND | LOCK_EX);
    Response::forbidden('Missing session: authentication required');
}

// Ensure the user has required role (carwash or admin). Use Auth::hasRole which will populate role from DB if missing.
if (!\App\Classes\Auth::hasRole(['carwash', 'admin'])) {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logLine = sprintf("%s | %s | user_id=%s | Insufficient role - update attempt blocked\n", date('c'), $_SERVER['REMOTE_ADDR'] ?? 'cli', $userId);
    @file_put_contents($logDir . '/csrf_blocked.log', $logLine, FILE_APPEND | LOCK_EX);
    Response::forbidden('Insufficient permissions: carwash or admin role required');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// CSRF validation: require X-CSRF-Token header (or form field) and verify against session
$csrfHelper = __DIR__ . '/../includes/csrf_protect.php';
if (file_exists($csrfHelper)) {
    require_once $csrfHelper;
    // Prefer header X-CSRF-Token, fall back to POST field
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
    if (empty($csrfToken)) {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $userIdLog = $_SESSION['user_id'] ?? 'unknown';
        $logLine = sprintf("%s | %s | user_id=%s | Missing CSRF token - update attempt blocked\n", date('c'), $_SERVER['REMOTE_ADDR'] ?? 'cli', $userIdLog);
        @file_put_contents($logDir . '/csrf_blocked.log', $logLine, FILE_APPEND | LOCK_EX);
        Response::forbidden('Missing CSRF token');
    }
    if (!function_exists('verify_csrf_token') || !verify_csrf_token($csrfToken)) {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $userIdLog = $_SESSION['user_id'] ?? 'unknown';
        $logLine = sprintf("%s | %s | user_id=%s | Invalid CSRF token - update attempt blocked\n", date('c'), $_SERVER['REMOTE_ADDR'] ?? 'cli', $userIdLog);
        @file_put_contents($logDir . '/csrf_blocked.log', $logLine, FILE_APPEND | LOCK_EX);
        Response::forbidden('Invalid CSRF token');
    }
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

    // Logo upload (optional) - standardize destination to backend/uploads/ and store filename in DB
    $logoPath = null; // filename only
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/jpg','image/webp','image/gif'];
        $type = $_FILES['logo']['type'] ?? '';
        $size = $_FILES['logo']['size'] ?? 0;
        if (!in_array($type, $allowed)) Response::error('Invalid file type', 400);
        if ($size > 5 * 1024 * 1024) Response::error('File too large', 400);
        $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\\/') . '/carwash_project/backend/uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $fn = 'logo_' . $userId . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $fn;
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            @file_put_contents(__DIR__ . '/../../logs/logo_missing.log', date('Y-m-d H:i:s') . " - failed move uploaded logo for user {$userId} to {$dest}\n", FILE_APPEND | LOCK_EX);
            Response::error('Upload failed', 500);
        }
        $logoPath = $fn;
    }

    // Quick session updates for UX
    if ($businessName !== null) { $_SESSION['business_name'] = $businessName; $_SESSION['name'] = $businessName; }
    if ($logoPath) $_SESSION['logo_path'] = $logoPath; // store filename only

    $pdo->beginTransaction();

    // Which profile table exists?
    $check = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('carwashes','business_profiles')");
    $check->execute();
    $found = $check->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasCarwashes = in_array('carwashes', $found, true);
    $hasBusinessProfiles = in_array('business_profiles', $found, true);

    if ($hasCarwashes) {
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

        if ($logoPath) $updates['logo_path'] = $logoPath; // filename only
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
            // Insert new row with provided columns only
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
            foreach (['working_hours','social_media','services','logo_path'] as $c) {
                if (isset($updates[$c])) { $cols[] = $c; $insParams[$c] = $updates[$c]; }
            }
            $placeholders = ':' . implode(',:', $cols);
            $sql = 'INSERT INTO carwashes (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
            $ins = $pdo->prepare($sql);
            $ins->execute($insParams);
        }

    } elseif ($hasBusinessProfiles) {
        // Simpler mapping for legacy business_profiles
        $stmt = $pdo->prepare('SELECT * FROM business_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $updates = [];
        if (array_key_exists('business_name', $_POST)) $updates['business_name'] = $businessName;
        if (array_key_exists('address', $_POST)) $updates['address'] = $address;
        if (array_key_exists('phone', $_POST)) $updates['phone'] = $phone;
        if (array_key_exists('mobile_phone', $_POST)) $updates['mobile_phone'] = $mobilePhone;
        if (array_key_exists('postal_code', $_POST)) $updates['postal_code'] = $postal_code;
        if (array_key_exists('license_number', $_POST)) $updates['license_number'] = $license_number;
        if (array_key_exists('tax_number', $_POST)) $updates['tax_number'] = $tax_number;
        if (array_key_exists('city', $_POST)) $updates['city'] = $city;
        if (array_key_exists('district', $_POST)) $updates['district'] = $district;
        if ($logoPath) $updates['logo_path'] = $logoPath;
        if (!empty($updates)) $updates['updated_at'] = date('Y-m-d H:i:s');

        if (!empty($existing)) {
            if (!empty($updates)) {
                $sets = []; $params = ['user_id' => $userId];
                foreach ($updates as $col => $val) { $sets[] = "{$col} = :{$col}"; $params[$col] = $val; }
                $sql = 'UPDATE business_profiles SET ' . implode(', ', $sets) . ' WHERE user_id = :user_id';
                $upd = $pdo->prepare($sql); $upd->execute($params);
            }
        } else {
            $cols = ['user_id','business_name','created_at','updated_at'];
            $insParams = ['user_id' => $userId, 'business_name' => $businessName ?? '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            foreach (['address','phone','mobile_phone','postal_code','license_number','tax_number','city','district','logo_path'] as $c) {
                if (array_key_exists($c, $_POST) || ($c === 'logo_path' && $logoPath)) { $cols[] = $c; $insParams[$c] = ${$c} ?? ($c === 'logo_path' ? $logoPath : null); }
            }
            $placeholders = ':' . implode(',:', $cols);
            $sql = 'INSERT INTO business_profiles (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
            $ins = $pdo->prepare($sql); $ins->execute($insParams);
        }

    } else {
        $pdo->rollBack();
        Response::error('No profile table found (carwashes or business_profiles)', 500);
    }

    $pdo->commit();

    // Fetch fresh record
    if ($hasCarwashes) {
        $fetch = $pdo->prepare('SELECT id,user_id,COALESCE(name,business_name) AS business_name,address,postal_code,COALESCE(phone,contact_phone) AS phone,COALESCE(mobile_phone,NULL) AS mobile_phone,COALESCE(email,contact_email) AS email,COALESCE(working_hours,opening_hours) AS working_hours,COALESCE(logo_path,featured_image) AS logo_path,COALESCE(license_number,"") AS license_number,COALESCE(tax_number,"") AS tax_number,COALESCE(city,"") AS city,COALESCE(district,"") AS district,social_media,services,created_at,updated_at FROM carwashes WHERE user_id = :user_id LIMIT 1');
        $fetch->execute(['user_id' => $userId]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($row && isset($row['working_hours'])) $row['working_hours'] = json_decode($row['working_hours'], true) ?: $row['working_hours'];
        if ($row && isset($row['services'])) $row['services'] = json_decode($row['services'], true) ?: $row['services'];
        if ($row && isset($row['social_media'])) $row['social_media'] = json_decode($row['social_media'], true) ?: $row['social_media'];
        // Normalize logo_path to web URL
        if ($row && !empty($row['logo_path'])) {
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';
            $lp = $row['logo_path'];
            if (preg_match('#^(?:https?://|/)#i', $lp)) {
                $row['logo_path'] = $lp;
            } else {
                $row['logo_path'] = $base_url . '/backend/uploads/business_logo/' . ltrim($lp, '/');
            }
            $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\\/');
            $filePath = $docRoot . parse_url($row['logo_path'], PHP_URL_PATH);
            if (!file_exists($filePath)) {
                @file_put_contents(__DIR__ . '/../../logs/logo_missing.log', date('Y-m-d H:i:s') . " - missing logo for user {$userId}: {$filePath}\n", FILE_APPEND | LOCK_EX);
                $row['logo_path'] = $base_url . '/backend/logo01.png';
            }
        }
    } elseif ($hasBusinessProfiles) {
        $fetch = $pdo->prepare('SELECT id,user_id,business_name AS business_name,address,postal_code,phone,mobile_phone,email,working_hours,logo_path,license_number,tax_number,city,district,created_at,updated_at FROM business_profiles WHERE user_id = :user_id LIMIT 1');
        $fetch->execute(['user_id' => $userId]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($row && isset($row['working_hours'])) $row['working_hours'] = json_decode($row['working_hours'], true) ?: $row['working_hours'];
    } else {
        $row = null;
    }

    if (!$row) Response::error('Could not fetch updated record', 500);
    Response::success('İşletme bilgileri başarıyla güncellendi', ['data' => $row]);

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if (!empty($logoPath)) {
        $fileToRemove = $_SERVER['DOCUMENT_ROOT'] . $logoPath;
        if (file_exists($fileToRemove)) @unlink($fileToRemove);
    }
    if (class_exists('\App\Classes\Logger')) { \App\Classes\Logger::exception($e); }
    Response::error('An error occurred while updating business information', 500);
}


