<?php
// Start output buffering early to prevent "headers already sent" when later includes call session_start()
if (!defined('CW_OB_STARTED')) {
    @ob_start();
    define('CW_OB_STARTED', true);
}
?>
<?php
// ...existing code...
// Replace the inline vehicle form submit handler section with the snippet below
// ...existing code...
?>
<script>
    (function(){
        // Robust vehicle form handler: ensure we reference the real form element and form controls
        const vehicleForm = document.getElementById('vehicleFormInline');
        if (!vehicleForm) return;

        async function getActionValue() {
            // Prefer an explicit input named 'action' or 'formAction' (id)
            const byName = vehicleForm.querySelector('[name="action"]');
            if (byName && byName.value) return byName.value;
            const byId = document.getElementById('formAction');
            if (byId && byId.value) return byId.value;
            // Fallback: use data-action on form or default to 'create'
            return vehicleForm.dataset.action || 'create';
        }

        vehicleForm.addEventListener('submit', async function(ev){
            ev.preventDefault();

            const action = (await getActionValue()) === 'create' ? 'create' : 'update';

            // Build FormData from the real form element so file inputs are included
            let fd = new FormData(vehicleForm);
            // Ensure action field exists and matches backend expectations
            fd.set('action', action);

            // Ensure backend expects id under 'id' (vehicle_api.php looks for 'id')
            const vidEl = vehicleForm.querySelector('#formVehicleId') || vehicleForm.querySelector('[name="id"]');
            const vid = vidEl ? vidEl.value : '';
            if (action !== 'create' && vid) fd.set('id', vid);

            // Ensure uploaded file input name matches backend: 'vehicle_image'
            // If the file input exists but with a different name, try to copy it
            try {
                const fileInput = vehicleForm.querySelector('input[type="file"]');
                if (fileInput && fileInput.name && fileInput.name !== 'vehicle_image' && fileInput.files && fileInput.files.length) {
                    // append the first file under the expected key as well
                    fd.set('vehicle_image', fileInput.files[0]);
                }
            } catch (e) {
                // ignore
            }

            // Derive api URL relative to current document so it works in different base paths
            const apiUrl = new URL('./vehicle_api.php', window.location.href).toString();

            // Add CSRF token if available (overwrite or set)
            try {
                const csrf = await fetchCsrfToken();
                if (csrf) fd.set('csrf_token', csrf);
            } catch (e) {
                // ignore csrf fetch failure and let backend validate if possible
            }

            try {
                let attemptedRetry = false;
                const doRequest = async () => {
                    const res = await fetch(apiUrl, { method:'POST', credentials:'same-origin', body: fd });
                    const raw = await res.text();
                    let json = null;
                    try { json = raw ? JSON.parse(raw) : null; } catch(e){ showMessage('Sunucudan beklenmeyen cevap alındı', 'error'); return { ok: false, parsed: null } }
                    return { ok: res.ok, parsed: json };
                };

                let result = await doRequest();

                // If server explicitly says Unknown action, attempt one retry with corrected action name
                if (result.parsed && (result.parsed.message === 'Unknown action' || result.parsed.error === 'Unknown action') && !attemptedRetry) {
                    attemptedRetry = true;
                    const corrected = (vid && String(vid).trim() !== '') ? 'update_vehicle' : 'add_vehicle';
                    try {
                        fd.set('action', corrected);
                    } catch (e) {
                        // Some browsers may not allow reusing FormData.set in certain contexts - rebuild FormData
                        const rebuilt = new FormData(vehicleForm);
                        rebuilt.set('action', corrected);
                        // copy vehicle_image if present
                        try {
                            const fileInput = vehicleForm.querySelector('input[type="file"]');
                            if (fileInput && fileInput.files && fileInput.files.length) rebuilt.set('vehicle_image', fileInput.files[0]);
                        } catch (ee) {}
                        // replace fd reference
                        fd = rebuilt;
                    }
                    // retry request once
                    result = await doRequest();
                }

                const json = result.parsed;
                if (json && (json.status === 'success' || json.success === true)) {
                    showMessage('İşlem başarılı');

                    // Close edit/create panel if present
                    const formPanel = document.getElementById('vehicleFormPanel') || document.querySelector('.vehicle-form-panel');
                    if (formPanel) formPanel.style.display = 'none';

                    // If this was an edit/update, refresh the whole page so UI updates reliably.
                    if (action === 'update') {
                        setTimeout(() => { try { location.reload(); } catch(e){ if (typeof loadVehicles === 'function') loadVehicles(); } }, 300);
                    } else {
                        if (typeof loadVehicles === 'function') loadVehicles();
                    }
                } else {
                    showMessage((json && (json.message||json.error)) ? (json.message||json.error) : 'İşlem başarısız', 'error');
                }
            } catch (e) {
                console.error(e);
                showMessage('İstek başarısız', 'error');
            }
        });
    })();
</script>
<?php
// ...existing code...?php
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Response;
use App\Classes\Logger;

if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// Expose a local $user_id convenience variable for handlers
$user_id = (int)($_SESSION['user_id'] ?? 0);

header('Content-Type: application/json; charset=utf-8');

// --- DEBUG: log incoming POST and FILES for diagnosis of 400 Bad Request ---
try {
    $dbgDir = __DIR__ . '/../../.logs';
    if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
    $dbgPath = $dbgDir . '/customer_dashboard_post_debug.log';
    $dump = [
        'ts' => date('c'),
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null,
        'headers' => function_exists('getallheaders') ? getallheaders() : [],
        'post' => $_POST,
        'files' => array_map(function($f){ return ['name'=>$f['name'] ?? null,'size'=>$f['size'] ?? null,'error'=>$f['error'] ?? null]; }, $_FILES),
        'raw_input_preview' => substr(file_get_contents('php://input'), 0, 4096)
    ];
    @file_put_contents($dbgPath, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n-----\n", FILE_APPEND | LOCK_EX);
} catch (Throwable $e) {
    // best effort logging
    error_log('customer_dashboard_post_debug log error: ' . $e->getMessage());
}

// Merge JSON body into POST
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        foreach ($parsed as $k => $v) if (!isset($_POST[$k])) $_POST[$k] = $v;
    }
}

// Simple auth check
if (class_exists(Auth::class) && method_exists(Auth::class, 'isAuthenticated')) {
    if (!Auth::isAuthenticated()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
} else {
    if (empty($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
}

try {
    // --- Begin: ensure DB connection available for vehicle/reservation handlers ---
    $dbConn = null;
    if (function_exists('getDBConnection')) {
        $dbConn = getDBConnection();
    } elseif (class_exists(\App\Classes\Database::class)) {
        try {
            $dbw = \App\Classes\Database::getInstance();
            if (method_exists($dbw, 'getPdo')) $dbConn = $dbw->getPdo();
            elseif ($dbw instanceof PDO) $dbConn = $dbw;
        } catch (Throwable $e) {
            $dbConn = null;
        }
    }
    // --- End: ensure DB connection ---

    // --- Ensure: user_vehicles table exists so frontend vehicle inserts don't fail ---
    try {
        if ($dbConn instanceof PDO) {
            $dbConn->exec("CREATE TABLE IF NOT EXISTS user_vehicles (\n                id INT AUTO_INCREMENT PRIMARY KEY,\n                user_id INT NOT NULL,\n                brand VARCHAR(191) DEFAULT NULL,\n                model VARCHAR(191) DEFAULT NULL,\n                license_plate VARCHAR(64) DEFAULT NULL,\n                year SMALLINT DEFAULT NULL,\n                color VARCHAR(64) DEFAULT NULL,\n                image_path VARCHAR(255) DEFAULT NULL,\n                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n                updated_at DATETIME DEFAULT NULL,\n                INDEX (user_id)\n            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    } catch (Throwable $e) {
        if (class_exists(\App\Classes\Logger::class) && method_exists(\App\Classes\Logger::class, 'exception')) {
            \App\Classes\Logger::exception($e, ['action' => 'ensure_user_vehicles_table']);
        } else {
            error_log('ensure_user_vehicles_table error: ' . $e->getMessage());
        }
    }

    // Ensure image_path column exists for backward compatibility (table might be old)
    try {
        if ($dbConn instanceof PDO) {
            $colStmt = $dbConn->query("SHOW COLUMNS FROM `user_vehicles` LIKE 'image_path'");
            $hasCol = $colStmt && $colStmt->fetch(PDO::FETCH_ASSOC);
            if (!$hasCol) {
                $dbConn->exec("ALTER TABLE `user_vehicles` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL AFTER `color`");
            }
        }
    } catch (Throwable $e) {
        if (class_exists(\App\Classes\Logger::class) && method_exists(\App\Classes\Logger::class, 'exception')) {
            \App\Classes\Logger::exception($e, ['action' => 'ensure_image_path']);
        } else {
            error_log('ensure_image_path error: ' . $e->getMessage());
        }
    }


    $action = $_POST['action'] ?? '';
    // We'll preserve reservation + vehicle listing handlers, but forward booking creation to the unified create endpoint
    switch ($action) {
        case 'fetch_reservations':
            if (!$dbConn) throw new RuntimeException('DB helper missing');
            $pdo = $dbConn;
            $stmt = $pdo->prepare('SELECT * FROM bookings WHERE user_id = :uid ORDER BY id DESC');
            $stmt->execute([':uid' => $_SESSION['user_id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'reservations' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'cancel_reservation':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('Invalid id');
            if (!$dbConn) throw new RuntimeException('DB helper missing');
            $pdo = $dbConn;
            $stmt = $pdo->prepare('UPDATE bookings SET status = :st WHERE id = :id AND user_id = :uid');
            $ok = $stmt->execute([':st' => 'cancelled', ':id' => $id, ':uid' => $_SESSION['user_id']]);
            if (!$ok) throw new RuntimeException('Could not cancel');
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'create_booking':
            // Forward booking creation to the central API endpoint
            // We will include the create.php directly so it runs in this request context
            // Ensure required booking fields exist
            $hasBookingPayload = (isset($_POST['service_id']) || isset($_POST['carwash_id']) || isset($_POST['date']));
            if (!$hasBookingPayload) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing booking payload']);
                break;
            }

            // Include the central create handler. It will output JSON and exit.
            $createPath = __DIR__ . '/../api/bookings/create.php';
            if (file_exists($createPath)) {
                require $createPath;
                // create.php should exit, but if it returns here, ensure we stop
                exit;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Create endpoint missing']);
            }
            break;

        case 'list_vehicles':
            if (!$dbConn) throw new RuntimeException('Database connection not available');
            $pdo = $dbConn;
            $stmt = $pdo->prepare('SELECT id, brand, model, license_plate, year, color, image_path, created_at FROM user_vehicles WHERE user_id = :uid ORDER BY created_at DESC');
            $stmt->execute([':uid' => $_SESSION['user_id']]);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Ensure vehicles is an indexed array and use canonical API shape: { success|status, message, data: { vehicles: [] } }
            $vehicles = is_array($vehicles) ? array_values($vehicles) : [];
            if (class_exists(\App\Classes\Response::class) && method_exists(\App\Classes\Response::class, 'success')) {
                \App\Classes\Response::success('Vehicles listed', ['vehicles' => $vehicles]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Vehicles listed', 'data' => ['vehicles' => $vehicles]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;

        case 'update_profile':
            // Handle profile update including optional profile photo upload
            $name = trim((string)($_POST['name'] ?? ''));
            $surname = trim((string)($_POST['surname'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));

            // Accept file under 'profile_photo' or fallback to first file
            $uploaded = null;
            if (!empty($_FILES['profile_photo']) && is_array($_FILES['profile_photo']) && ($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded = $_FILES['profile_photo'];
            } elseif (!empty($_FILES)) {
                $first = reset($_FILES);
                if (is_array($first) && ($first['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $uploaded = $first;
            }

            $webPath = null;
            if ($uploaded) {
                $uploadDir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $ext = pathinfo($uploaded['name'] ?? '', PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . ($ext ? '.' . $ext : '');
                $dest = $uploadDir . $filename;
                if (is_uploaded_file($uploaded['tmp_name'])) {
                    if (@move_uploaded_file($uploaded['tmp_name'], $dest)) {
                        $webPath = '/backend/uploads/profiles/' . $filename;
                    } else {
                        error_log('profile upload move failed for user ' . $user_id);
                    }
                }
            }

            // Persist profile info using PDO or mysqli fallback
            try {
                if ($dbConn instanceof PDO) {
                    // Update users table
                    $stmt = $dbConn->prepare('UPDATE users SET name = :name, phone = :phone, email = :email WHERE id = :id');
                    $stmt->execute([':name' => $name, ':phone' => $phone, ':email' => $email, ':id' => $user_id]);

                    // Upsert profile details
                    $dbConn->exec("CREATE TABLE IF NOT EXISTS user_profiles (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        user_id INT NOT NULL UNIQUE,
                        profile_image VARCHAR(255),
                        address TEXT,
                        preferences JSON,
                        last_updated DATETIME
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    $prefs = json_encode([]);
                    $stmt = $dbConn->prepare('INSERT INTO user_profiles (user_id, profile_image, address, preferences, last_updated) VALUES (:uid, :img, :addr, :prefs, NOW()) ON DUPLICATE KEY UPDATE profile_image = VALUES(profile_image), address = VALUES(address), preferences = VALUES(preferences), last_updated = NOW()');
                    $stmt->execute([':uid' => $user_id, ':img' => $webPath, ':addr' => $address, ':prefs' => $prefs]);
                } else {
                    // assume mysqli-like
                    if (!class_exists('ProfileManager')) {
                        require_once __DIR__ . '/../includes/profile_manager.php';
                    }
                    // If $dbConn is mysqli connection, use ProfileManager; otherwise, try getDBConnection()
                    $mmConn = $dbConn;
                    if (!($mmConn instanceof mysqli) && function_exists('getDBConnection')) $mmConn = getDBConnection();
                    if ($mmConn instanceof mysqli) {
                        $pm = new ProfileManager($mmConn);
                        $pm->updateProfile($user_id, ['name' => $name, 'phone' => $phone, 'address' => $address, 'preferences' => []]);
                        if ($webPath) {
                            $stmt = $mmConn->prepare('UPDATE users SET email = ? WHERE id = ?');
                            $stmt->bind_param('si', $email, $user_id);
                            $stmt->execute();
                        }
                        // store profile image separately
                        if ($webPath) {
                            $u = $mmConn->prepare('INSERT INTO user_profiles (user_id, profile_image, address, preferences, last_updated) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE profile_image = VALUES(profile_image), address = VALUES(address), preferences = VALUES(preferences), last_updated = NOW()');
                            $prefs = json_encode([]);
                            $u->bind_param('isss', $user_id, $webPath, $address, $prefs);
                            $u->execute();
                        }
                    } else {
                        throw new RuntimeException('No DB connection available for profile update');
                    }
                }

                // Success response
                echo json_encode(['success' => true, 'message' => 'Profile updated', 'data' => ['image' => $webPath ?? null]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Throwable $e) {
                error_log('profile update error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Profile update failed']);
            }
            break;

        default:
            if (class_exists(\App\Classes\Logger::class) && method_exists(\App\Classes\Logger::class, 'warn')) {
                \App\Classes\Logger::warn('Unknown action in Customer_Dashboard_process: ' . $action);
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (Throwable $e) {
    if (class_exists(\App\Classes\Logger::class) && method_exists(\App\Classes\Logger::class, 'exception')) {
        \App\Classes\Logger::exception($e);
    } else {
        error_log('Customer_Dashboard_process error: ' . $e->getMessage());
    }
    $env = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production')));
    $payload = ['success' => false, 'error_type' => get_class($e), 'message' => $e->getMessage()];
    if (in_array($env, ['dev', 'development'], true)) $payload['trace'] = $e->getTraceAsString();
    http_response_code(500);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
