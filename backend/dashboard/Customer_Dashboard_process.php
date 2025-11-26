<?php
// Backend API endpoint - outputs JSON only
error_reporting(E_ALL);
ini_set('display_errors', '1'); // Enable to see errors during development
ini_set('log_errors', '1');

// Start output buffering FIRST to catch any stray output
ob_start();

// Try to load bootstrap
try {
    require_once __DIR__ . '/../includes/bootstrap.php';
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Bootstrap error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Response;
use App\Classes\Logger;

// Start session BEFORE setting JSON header
try {
    if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
        Session::start();
    } else {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Session error: ' . $e->getMessage()
    ]);
    exit;
}

// NOW set JSON header (after session)
header('Content-Type: application/json; charset=utf-8');

// Expose a local $user_id convenience variable for handlers
$user_id = (int)($_SESSION['user_id'] ?? 0);

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
if (empty($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// === CSRF protection for state-changing requests ===
$reqMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($reqMethod, ['POST', 'PUT', 'PATCH'], true)) {
    // Prefer centralized helper
    $csrf_helper = __DIR__ . '/../includes/csrf_protect.php';
    $csrf_ok = false;

    // Merge JSON body into POST already happened above, so token may be in $_POST
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (file_exists($csrf_helper)) {
        require_once $csrf_helper;
        if (function_exists('require_valid_csrf')) {
            // require_valid_csrf() will emit error/exit on failure; call it safely
            try {
                require_valid_csrf();
                $csrf_ok = true;
            } catch (Throwable $e) {
                // allow fallback to inline check below
            }
        } elseif (function_exists('verify_csrf_token') && verify_csrf_token($token)) {
            $csrf_ok = true;
        }
    }

    if (!$csrf_ok) {
        // Inline fallback: simple session compare
        if (!empty($_SESSION['csrf_token']) && !empty($token) && function_exists('hash_equals')) {
            if (hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$token)) {
                $csrf_ok = true;
            }
        }
    }

    if (!$csrf_ok) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
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


    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Log the action for debugging
    error_log("Customer_Dashboard_process: action='$action', user_id=$user_id");
    
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
            // Ensure database connection is available
            if (!$dbConn) {
                ob_end_clean();
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Database connection not available'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Handle profile update including optional profile photo upload
            $name = trim((string)($_POST['name'] ?? ''));
            $username = trim((string)($_POST['username'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $home_phone = trim((string)($_POST['home_phone'] ?? ''));
            $national_id = trim((string)($_POST['national_id'] ?? ''));
            $driver_license = trim((string)($_POST['driver_license'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            $city = trim((string)($_POST['city'] ?? ''));
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            $errors = [];
            $fieldErrors = []; // map field => message for client-side highlighting

            // Fetch current user values to detect whether fields changed
            $currentUser = ['username' => null, 'email' => null, 'name' => null];
            try {
                $cuStmt = $dbConn->prepare('SELECT username, email, name FROM users WHERE id = :id LIMIT 1');
                $cuStmt->execute([':id' => $user_id]);
                $row = $cuStmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $currentUser['username'] = $row['username'] ?? null;
                    $currentUser['email'] = $row['email'] ?? null;
                    $currentUser['name'] = $row['name'] ?? null;
                }
            } catch (Throwable $e) {
                // Non-fatal - proceed without change-detection if DB read fails
                error_log('Could not fetch current user for change-detection: ' . $e->getMessage());
            }

            // Detect image-only update. Support common file keys: avatar, profile_image, profile_photo
            $hasAvatarFile = !empty($_FILES['avatar']) && is_array($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $hasProfileImageFile = !empty($_FILES['profile_image']) && is_array($_FILES['profile_image']) && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $hasProfilePhotoFile = !empty($_FILES['profile_photo']) && is_array($_FILES['profile_photo']) && ($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $hasAnyFile = $hasAvatarFile || $hasProfileImageFile || $hasProfilePhotoFile;

            $isImageOnlyUpdate = false;
            if ($hasAnyFile) {
                $postedUsername = $username;
                $postedEmail = $email;
                $postedName = $name;
                $isImageOnlyUpdate = empty($new_password)
                    && ($postedUsername === ($currentUser['username'] ?? $postedUsername))
                    && ($postedEmail === ($currentUser['email'] ?? $postedEmail))
                    && ($postedName === ($currentUser['name'] ?? $postedName));
            }

            // Detect if no meaningful changes were submitted (no file and no changed fields)
            $changesDetected = $hasAnyFile
                || ($name !== ($currentUser['name'] ?? ''))
                || ($username !== ($currentUser['username'] ?? ''))
                || ($email !== ($currentUser['email'] ?? ''))
                || $phone !== ''
                || $home_phone !== ''
                || $national_id !== ''
                || $driver_license !== ''
                || $address !== ''
                || $city !== ''
                || !empty($new_password);

            if (!$changesDetected) {
                ob_end_clean();
                // Return explicit 'no changes' response (client expects JSON)
                echo json_encode([
                    'success' => false,
                    'message' => 'No changes detected.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // If image-only update, validate only the uploaded image and persist it, then return early with JSON
            if ($isImageOnlyUpdate) {
                // Determine uploaded file array
                $uploadedFile = null;
                if ($hasAvatarFile) $uploadedFile = $_FILES['avatar'];
                elseif ($hasProfileImageFile) $uploadedFile = $_FILES['profile_image'];
                elseif ($hasProfilePhotoFile) $uploadedFile = $_FILES['profile_photo'];

                $imgErrors = [];
                $imgFieldErrors = [];
                $maxSize = 3 * 1024 * 1024; // 3MB

                if (!$uploadedFile || ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    $imgErrors[] = 'No avatar file uploaded.';
                    $imgFieldErrors['avatar'] = 'missing';
                } else {
                    $tmp = $uploadedFile['tmp_name'] ?? '';
                    $origName = $uploadedFile['name'] ?? '';
                    $fsize = (int)($uploadedFile['size'] ?? 0);

                    if (!is_uploaded_file($tmp)) {
                        $imgErrors[] = 'Uploaded avatar appears invalid.';
                        $imgFieldErrors['avatar'] = 'invalid_upload';
                    } else {
                        $info = @getimagesize($tmp);
                        $mime = $info['mime'] ?? '';
                        $allowed = ['image/jpeg' => ['jpg','jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']];
                        if (!$info || !isset($allowed[$mime])) {
                            $imgErrors[] = 'Avatar must be a JPG, PNG, or WEBP image.';
                            $imgFieldErrors['avatar'] = 'unsupported_type';
                        }

                        if ($fsize > $maxSize) {
                            $imgErrors[] = 'Avatar exceeds maximum size of 3MB.';
                            $imgFieldErrors['avatar'] = 'too_large';
                        }
                    }
                }

                if (!empty($imgErrors)) {
                    ob_end_clean();
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $imgErrors,
                        'fieldErrors' => $imgFieldErrors
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }

                // Passed image validation - move file into uploads and update DB
                $uploadDir = PROFILE_UPLOAD_PATH;
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $safeExt = preg_replace('/[^a-z0-9]/', '', strtolower(pathinfo($uploadedFile['name'] ?? '', PATHINFO_EXTENSION)));
                if ($safeExt === '') $safeExt = 'jpg';
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $safeExt;
                $dest = $uploadDir . '/' . $filename;

                if (!@move_uploaded_file($uploadedFile['tmp_name'], $dest)) {
                    ob_end_clean();
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Could not save uploaded avatar on server.',
                        'errors' => ['Server error storing avatar.'],
                        'fieldErrors' => ['avatar' => 'store_failed']
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }

                // Remove old image file if exists
                try {
                    if (!empty($existingImage)) {
                        $oldFull = str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'] . '/carwash_project', $existingImage);
                        $oldFull = preg_replace('/\?ts=\d+$/', '', $oldFull); // Remove timestamp
                        if (file_exists($oldFull)) @unlink($oldFull);
                    }
                } catch (Throwable $e) {
                    // non-fatal
                }

                $webPath = PROFILE_UPLOAD_URL . '/' . $filename . '?ts=' . time();

                // Persist to DB (user_profiles preferred, fallback to users table)
                try {
                    if ($dbConn instanceof PDO) {
                        $u = $dbConn->prepare('UPDATE user_profiles SET profile_image = :img WHERE user_id = :id');
                        $u->execute([':img' => $webPath, ':id' => $user_id]);
                        if ($u->rowCount() === 0) {
                            $i = $dbConn->prepare('INSERT INTO user_profiles (user_id, profile_image) VALUES (:id, :img)');
                            $i->execute([':id' => $user_id, ':img' => $webPath]);
                        }
                        // Also update users table for backward compatibility
                        $dbConn->prepare('UPDATE users SET profile_image = :img WHERE id = :id')->execute([':img' => $webPath, ':id' => $user_id]);
                    }
                } catch (Throwable $e) {
                    // non-fatal DB error: continue but log
                    error_log('Could not persist avatar path: ' . $e->getMessage());
                }

                // Update session
                $_SESSION['profile_image'] = $webPath;

                // Return success JSON per requirements
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile image updated successfully',
                    'avatarUrl' => $webPath
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // --- Display name validation (only if changed) ---
            if ($name !== ($currentUser['name'] ?? '')) {
                if ($name === '') {
                    $errors[] = 'Display name must be 2–50 characters and contain only letters, numbers, or spaces.';
                    $fieldErrors['name'] = 'required';
                } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 50 || !preg_match('/^[\p{L}0-9 _]+$/u', $name)) {
                    $errors[] = 'Display name must be 2–50 characters and contain only letters, numbers, or spaces.';
                    $fieldErrors['name'] = 'invalid';
                }
            }

            // --- Username (validate only if modified) ---
            if ($username !== ($currentUser['username'] ?? '')) {
                if ($username === '') {
                    $errors[] = 'Username must be at least 3 characters and contain no spaces.';
                    $fieldErrors['username'] = 'required';
                } elseif (!preg_match('/^[A-Za-z0-9_]{3,}$/', $username)) {
                    $errors[] = 'Username must be at least 3 characters and contain no spaces.';
                    $fieldErrors['username'] = 'invalid';
                } else {
                    // check uniqueness
                    try {
                        $stmt = $dbConn->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
                        $stmt->execute([':username' => $username, ':id' => $user_id]);
                        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                            $errors[] = 'This username is already taken.';
                            $fieldErrors['username'] = 'duplicate';
                        }
                    } catch (Throwable $e) {
                        // ignore DB uniqueness check failure for now, log it
                        error_log('Username uniqueness check failed: ' . $e->getMessage());
                    }
                }
            }

            // --- Email validation (only if changed) ---
            if ($email !== ($currentUser['email'] ?? '')) {
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format.';
                    $fieldErrors['email'] = 'invalid';
                } else {
                    try {
                        $stmt = $dbConn->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
                        $stmt->execute([':email' => $email, ':id' => $user_id]);
                        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                            $errors[] = 'This email is already taken.';
                            $fieldErrors['email'] = 'duplicate';
                        }
                    } catch (Throwable $e) {
                        error_log('Email uniqueness check failed: ' . $e->getMessage());
                    }
                }
            }

            // --- National ID validation ---
            if ($national_id === '') {
                $errors[] = 'T.C. Kimlik No gereklidir';
                $fieldErrors['national_id'] = 'required';
            } elseif (!preg_match('/^[0-9]{11}$/', $national_id)) {
                $errors[] = 'T.C. Kimlik No 11 haneli olmalıdır';
                $fieldErrors['national_id'] = 'invalid';
            }

            // --- Password change handling ---
            $doPasswordChange = ($current_password !== '' || $new_password !== '' || $confirm_password !== '');
            if ($doPasswordChange) {
                // Fetch stored hash
                try {
                    $pwdStmt = $dbConn->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
                    $pwdStmt->execute([':id' => $user_id]);
                    $storedHash = $pwdStmt->fetchColumn();
                    if (!$storedHash || !password_verify($current_password, $storedHash)) {
                        $errors[] = 'Current password is incorrect.';
                        $fieldErrors['current_password'] = 'mismatch';
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Could not verify current password.';
                    error_log('Password verify error: ' . $e->getMessage());
                }

                if (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                    $errors[] = 'New password must be at least 8 characters and contain letters and numbers.';
                    $fieldErrors['new_password'] = 'weak';
                }

                if ($new_password === $current_password) {
                    $errors[] = 'New password must not be the same as the old password.';
                    $fieldErrors['new_password'] = 'same_as_old';
                }

                if ($new_password !== $confirm_password) {
                    $errors[] = 'New password and confirmation do not match.';
                    $fieldErrors['confirm_password'] = 'mismatch';
                }
            }

            // --- Profile image validation ---
            $uploaded = null;
            if (!empty($_FILES['profile_image']) && is_array($_FILES['profile_image']) && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded = $_FILES['profile_image'];
            } elseif (!empty($_FILES['profile_photo']) && is_array($_FILES['profile_photo']) && ($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded = $_FILES['profile_photo'];
            } elseif (!empty($_FILES)) {
                $first = reset($_FILES);
                if (is_array($first) && ($first['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $uploaded = $first;
            }

            $webPath = null;

            // If no uploaded file, check if user already has a profile image
            try {
                $existingImage = null;
                $imgStmt = $dbConn->prepare('SELECT COALESCE(up.profile_image, u.profile_image, NULL) AS img FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = :id LIMIT 1');
                $imgStmt->execute([':id' => $user_id]);
                $row = $imgStmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['img'])) $existingImage = $row['img'];
            } catch (Throwable $e) {
                error_log('Could not check existing profile image: ' . $e->getMessage());
            }

            if ($uploaded) {
                $maxSize = 3 * 1024 * 1024; // 3MB per spec
                $fsize = (int)($uploaded['size'] ?? 0);
                $tmp = $uploaded['tmp_name'] ?? '';
                $origName = $uploaded['name'] ?? '';

                // Validate file actually uploaded
                if (!is_uploaded_file($tmp)) {
                    $errors[] = 'Profile image is invalid, too large, or unsupported. Upload JPG, PNG, or WEBP under 3MB.';
                    $fieldErrors['profile_image'] = 'invalid_upload';
                } else {
                    // Use getimagesize to validate image and mime
                    $imgInfo = @getimagesize($tmp);
                    $mime = $imgInfo['mime'] ?? '';
                    $allowed = ['image/jpeg' => ['jpg','jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']];
                    if (!$imgInfo || !isset($allowed[$mime])) {
                        $errors[] = 'Profile image is invalid, too large, or unsupported. Upload JPG, PNG, or WEBP under 3MB.';
                        $fieldErrors['profile_image'] = 'unsupported_type';
                    } else {
                        // extension check
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        $validExts = $allowed[$mime];
                        if ($ext === '') {
                            // fallback: choose first allowed ext
                            $ext = $validExts[0];
                        }
                        if (!in_array($ext, $validExts, true)) {
                            $errors[] = 'Profile image is invalid, too large, or unsupported. Upload JPG, PNG, or WEBP under 3MB.';
                            $fieldErrors['profile_image'] = 'ext_mismatch';
                        }

                        // Allow server-side resize attempt for files larger than $maxSize up to $allowedUploadMax
                        $allowedUploadMax = 10 * 1024 * 1024; // 10MB input cap
                        if ($fsize > $allowedUploadMax) {
                            $errors[] = 'Profile image exceeds the maximum allowed upload size of 10MB.';
                            $fieldErrors['profile_image'] = 'too_large_input';
                        }
                    }
                }
            } else {
                if (empty($existingImage)) {
                    $errors[] = 'Profile image is missing.';
                    $fieldErrors['profile_image'] = 'missing';
                }
            }

            // If validation errors, respond with array of errors and field hints
            if (!empty($errors)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors,
                    'fieldErrors' => $fieldErrors
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // If we reach here, validations passed. If file uploaded, store it now.
            if ($uploaded) {
                $uploadDir = __DIR__ . '/../uploads/profile_images/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $safeExt = preg_replace('/[^a-z0-9]/', '', strtolower(pathinfo($uploaded['name'] ?? '', PATHINFO_EXTENSION)));
                if ($safeExt === '') $safeExt = 'jpg';
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $safeExt;
                $dest = $uploadDir . $filename;

                // Helper: attempt to resize/compress an image to meet $maxSize using GD
                $attemptResize = function($source, $target, $mime, $maxBytes) {
                    if (!function_exists('imagecreatetruecolor')) return false;
                    // Create source image
                    switch ($mime) {
                        case 'image/jpeg':
                            if (!function_exists('imagecreatefromjpeg')) return false;
                            $srcImg = @imagecreatefromjpeg($source);
                            break;
                        case 'image/png':
                            if (!function_exists('imagecreatefrompng')) return false;
                            $srcImg = @imagecreatefrompng($source);
                            break;
                        case 'image/webp':
                            if (!function_exists('imagecreatefromwebp')) return false;
                            $srcImg = @imagecreatefromwebp($source);
                            break;
                        default:
                            return false;
                    }
                    if (!$srcImg) return false;

                    $w = imagesx($srcImg);
                    $h = imagesy($srcImg);
                    // Target max width to reduce size
                    $maxW = 1600;
                    $scale = ($w > $maxW) ? ($maxW / $w) : 1.0;
                    $tw = max(1, (int)($w * $scale));
                    $th = max(1, (int)($h * $scale));

                    $dst = imagecreatetruecolor($tw, $th);
                    if ($mime === 'image/png' || $mime === 'image/webp') {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                        imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
                    }

                    imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $tw, $th, $w, $h);

                    // Attempt save with decreasing quality if needed
                    $quality = 85;
                    $saved = false;
                    while ($quality >= 30) {
                        switch ($mime) {
                            case 'image/jpeg':
                                @imagejpeg($dst, $target, $quality);
                                break;
                            case 'image/png':
                                // map quality(0-100) to compression level 0-9
                                $comp = (int)round((100 - $quality) / 11.1111); if ($comp < 0) $comp = 0; if ($comp > 9) $comp = 9;
                                @imagepng($dst, $target, $comp);
                                break;
                            case 'image/webp':
                                @imagewebp($dst, $target, $quality);
                                break;
                        }

                        clearstatcache(true, $target);
                        if (file_exists($target) && filesize($target) <= $maxBytes) { $saved = true; break; }
                        $quality -= 10;
                    }

                    imagedestroy($srcImg);
                    imagedestroy($dst);
                    return $saved;
                };

                // Delete old profile image if exists
                try {
                    if ($dbConn instanceof PDO) {
                        $oldStmt = $dbConn->prepare('SELECT COALESCE(up.profile_image, u.profile_image, NULL) AS img FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = :id LIMIT 1');
                        $oldStmt->execute([':id' => $user_id]);
                        $oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC);
                        $oldImage = $oldRow['img'] ?? null;
                        if ($oldImage) {
                            $oldPath = __DIR__ . '/../..' . str_replace('/carwash_project', '', $oldImage);
                            if ($oldPath && file_exists($oldPath)) @unlink($oldPath);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Could not delete old profile image: ' . $e->getMessage());
                }

                // Decide how to persist file: move directly if small, otherwise attempt server-side resize/compression
                $imgInfo2 = @getimagesize($uploaded['tmp_name']);
                $mime2 = $imgInfo2['mime'] ?? '';
                $inputSize = (int)($uploaded['size'] ?? 0);
                if ($inputSize <= $maxSize) {
                    if (!@move_uploaded_file($uploaded['tmp_name'], $dest)) {
                        ob_end_clean();
                        http_response_code(500);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Failed to save uploaded file on the server.',
                            'errors' => ['Server error storing uploaded image.'],
                            'fieldErrors' => ['profile_image' => 'store_failed']
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        exit;
                    }
                } else {
                    // Attempt to resize/compress server-side (only if GD present)
                    // If GD is not available, fail fast with a clear message
                    if (!function_exists('imagecreatetruecolor')) {
                        ob_end_clean();
                        http_response_code(500);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Server does not have image processing available (GD disabled). Please enable GD or upload an image under 3MB.',
                            'errors' => ['Server image processing unavailable'],
                            'fieldErrors' => ['profile_image' => 'gd_disabled']
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        exit;
                    }

                    $resized = false;
                    try {
                        $resized = $attemptResize($uploaded['tmp_name'], $dest, $mime2, $maxSize);
                    } catch (Throwable $e) {
                        error_log('Image resize error: ' . $e->getMessage());
                        $resized = false;
                    }

                    if (!$resized) {
                        // If resize failed, return validation error (too large)
                        ob_end_clean();
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Profile image is too large and could not be resized. Please upload an image under 3MB or enable server-side image functions.',
                            'errors' => ['Profile image is invalid, too large, or unsupported. Upload JPG, PNG, or WEBP under 3MB.'],
                            'fieldErrors' => ['profile_image' => 'too_large']
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        exit;
                    }
                }

                $webPath = '/carwash_project/backend/uploads/profile_images/' . $filename;
            }

            // Persist profile info using PDO or mysqli fallback
            try {
                if ($dbConn instanceof PDO) {
                    // Check if new columns exist, add them if not
                    try {
                        $checkColumns = $dbConn->query("SHOW COLUMNS FROM users LIKE 'home_phone'");
                        if ($checkColumns->rowCount() === 0) {
                            // Add new columns
                            $dbConn->exec("ALTER TABLE users 
                                ADD COLUMN home_phone VARCHAR(20) DEFAULT NULL AFTER phone,
                                ADD COLUMN national_id VARCHAR(20) DEFAULT NULL AFTER home_phone,
                                ADD COLUMN driver_license VARCHAR(20) DEFAULT NULL AFTER national_id");
                            error_log('Added new profile columns to users table');
                        }
                    } catch (Exception $e) {
                        error_log('Column check/add error: ' . $e->getMessage());
                    }
                    
                    // Update users table with all fields
                    $updateFields = [
                        'name = :name',
                        'phone = :phone',
                        'home_phone = :home_phone',
                        'national_id = :national_id',
                        'driver_license = :driver_license',
                        'email = :email',
                        'username = :username'
                    ];

                    $params = [
                        ':name' => $name,
                        ':phone' => $phone,
                        ':home_phone' => $home_phone,
                        ':national_id' => $national_id,
                        ':driver_license' => $driver_license,
                        ':email' => $email,
                        ':username' => $username,
                        ':id' => $user_id
                    ];

                    if (!empty($doPasswordChange) && !empty($new_password)) {
                        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $updateFields[] = 'password = :password';
                        $params[':password'] = $hashed;
                    }

                    if ($webPath) {
                        $updateFields[] = 'profile_image = :profile_image';
                        $params[':profile_image'] = $webPath;
                    }
                    
                    $sql = 'UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = :id';
                    $stmt = $dbConn->prepare($sql);
                    $stmt->execute($params);

                    // Ensure user_profiles table exists with proper schema
                    $dbConn->exec("CREATE TABLE IF NOT EXISTS user_profiles (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        user_id INT NOT NULL UNIQUE,
                        profile_image VARCHAR(255),
                        address TEXT,
                        city VARCHAR(100),
                        preferences JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    
                    // Add updated_at column if it doesn't exist (for backward compatibility)
                    try {
                        $checkCol = $dbConn->query("SHOW COLUMNS FROM user_profiles LIKE 'updated_at'");
                        if ($checkCol->rowCount() === 0) {
                            $dbConn->exec("ALTER TABLE user_profiles ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                        }
                    } catch (Exception $e) {
                        error_log('Could not add updated_at column: ' . $e->getMessage());
                    }
                    
                    // Remove old last_updated column if it exists
                    try {
                        $checkOldCol = $dbConn->query("SHOW COLUMNS FROM user_profiles LIKE 'last_updated'");
                        if ($checkOldCol->rowCount() > 0) {
                            $dbConn->exec("ALTER TABLE user_profiles DROP COLUMN last_updated");
                        }
                    } catch (Exception $e) {
                        error_log('Could not drop last_updated column: ' . $e->getMessage());
                    }
                    
                    $prefs = json_encode([]);
                    $stmt = $dbConn->prepare('INSERT INTO user_profiles (user_id, profile_image, address, city, preferences) 
                        VALUES (:uid, :img, :addr, :city, :prefs) 
                        ON DUPLICATE KEY UPDATE 
                            profile_image = COALESCE(:img2, profile_image),
                            address = :addr2, 
                            city = :city2,
                            preferences = :prefs2');
                    $stmt->execute([
                        ':uid' => $user_id, 
                        ':img' => $webPath, 
                        ':addr' => $address, 
                        ':city' => $city,
                        ':prefs' => $prefs,
                        ':img2' => $webPath,
                        ':addr2' => $address,
                        ':city2' => $city,
                        ':prefs2' => $prefs
                    ]);
                    
                    // Update session variables
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    if (!empty($username)) $_SESSION['username'] = $username;
                    if (!empty($webPath)) $_SESSION['profile_image'] = $webPath;
                    
                } else {
                    // assume mysqli-like
                    if (!class_exists('ProfileManager')) {
                        require_once __DIR__ . '/../includes/profile_manager.php';
                    }
                    // If $dbConn is mysqli connection, use ProfileManager; otherwise, try getDBConnection()
                    $mmConn = $dbConn;
                    if (!($mmConn instanceof mysqli) && function_exists('getDBConnection')) $mmConn = getDBConnection();
                    if ($mmConn instanceof mysqli) {
                        // Check if columns exist
                        try {
                            $checkColumns = $mmConn->query("SHOW COLUMNS FROM users LIKE 'home_phone'");
                            if ($checkColumns->num_rows === 0) {
                                $mmConn->query("ALTER TABLE users 
                                    ADD COLUMN home_phone VARCHAR(20) DEFAULT NULL AFTER phone,
                                    ADD COLUMN national_id VARCHAR(20) DEFAULT NULL AFTER home_phone,
                                    ADD COLUMN driver_license VARCHAR(20) DEFAULT NULL AFTER national_id");
                            }
                        } catch (Exception $e) {
                            error_log('MySQLi column check error: ' . $e->getMessage());
                        }
                        
                        $stmt = $mmConn->prepare('UPDATE users SET name = ?, phone = ?, home_phone = ?, national_id = ?, driver_license = ?, email = ?, profile_image = ? WHERE id = ?');
                        $stmt->bind_param('sssssssi', $name, $phone, $home_phone, $national_id, $driver_license, $email, $webPath, $user_id);
                        $stmt->execute();
                        
                        // Update user_profiles (no last_updated column, use updated_at instead)
                        $u = $mmConn->prepare('INSERT INTO user_profiles (user_id, profile_image, address, city, preferences) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE profile_image = COALESCE(VALUES(profile_image), profile_image), address = VALUES(address), city = VALUES(city), preferences = VALUES(preferences)');
                        $prefs = json_encode([]);
                        $u->bind_param('issss', $user_id, $webPath, $address, $city, $prefs);
                        $u->execute();
                        
                        // Update session
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $email;
                        if (!empty($username)) $_SESSION['username'] = $username;
                        if (!empty($webPath)) $_SESSION['profile_image'] = $webPath;
                    } else {
                        throw new RuntimeException('No DB connection available for profile update');
                    }
                }

                // Success response - clean buffer first
                ob_end_clean();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profil başarıyla güncellendi', 
                    'data' => ['image' => $webPath ?? null]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
                
            } catch (Throwable $e) {
                // Ensure a safe JSON error is returned for unexpected exceptions during profile update
                ob_end_clean();
                error_log('Profile update error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                error_log('Profile update stack trace: ' . $e->getTraceAsString());
                http_response_code(500);
                $payload = [
                    'success' => false,
                    'message' => 'Unexpected server error',
                    'error_detail' => $e->getMessage()
                ];
                // Include trace only in development mode
                $env = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production')));
                if (in_array($env, ['dev', 'development'], true)) {
                    $payload['trace'] = $e->getTraceAsString();
                }
                echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
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
    
    // If we reach here, clean buffer and exit
    ob_end_clean();
    
} catch (Throwable $e) {
    // Clean any buffered output before sending error
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (class_exists(\App\Classes\Logger::class) && method_exists(\App\Classes\Logger::class, 'exception')) {
        \App\Classes\Logger::exception($e);
    } else {
        error_log('Customer_Dashboard_process error: ' . $e->getMessage());
    }
    $env = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production')));
    $payload = [
        'success' => false,
        'message' => 'Unexpected server error',
        'error_detail' => $e->getMessage()
    ];
    if (in_array($env, ['dev', 'development'], true)) $payload['trace'] = $e->getTraceAsString();
    http_response_code(500);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

exit;
