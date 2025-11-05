<?php

// Vehicle API: embedded HTML/JS removed because this is a backend JSON API endpoint.
// JavaScript belongs in frontend assets; keep this file as pure PHP to avoid parse errors.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Logger;
use App\Classes\Response;
use App\Classes\FileUploader;

// Start session (Session wrapper if present)
if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// Ensure JSON content-type early
header('Content-Type: application/json; charset=utf-8');

// Start output buffering to catch accidental HTML/warnings
if (!defined('VEHICLE_API_OB_STARTED')) {
    @ob_start();
    define('VEHICLE_API_OB_STARTED', true);
}

// Helper: unified JSON responder - normalizes response shape and handles any unexpected pre-output
function send_json_response(array $payload, int $httpCode = 200): void {
    // Normalize shape
    $normalized = [];
    if (!empty($payload['success']) || isset($payload['status']) && $payload['status'] === 'success') {
        $normalized['status'] = 'success';
        $normalized['message'] = $payload['message'] ?? ($payload['success'] ? 'OK' : '');
        if (!empty($payload['data'])) $normalized['data'] = $payload['data'];
        // allow 'vehicles' or 'vehicle_id' etc to be included under data
        $extra = $payload;
        unset($extra['success'], $extra['message'], $extra['data'], $extra['status']);
        if (!empty($extra)) $normalized['data'] = array_merge($normalized['data'] ?? [], $extra);
    } else {
        $normalized['status'] = 'error';
        $normalized['message'] = $payload['message'] ?? $payload['error'] ?? 'Unknown error';
        if (!empty($payload['error_type'])) $normalized['details'] = $payload['error_type'];
        if (!empty($payload['errors'])) $normalized['errors'] = $payload['errors'];
    }

    // Capture any buffered raw output (warnings, HTML)
    $raw = '';
    if (ob_get_length() !== false) {
        $raw = ob_get_clean();
    }

    // If buffer contains non-empty HTML or DOCTYPE, include it and log it for debugging
    if ($raw !== '') {
        $hasHtml = preg_match('/\<!(doctype|html)|\<html|\<\/html\>/i', $raw) || strlen(trim(strip_tags($raw))) === 0 ? false : (stripos($raw, '<!DOCTYPE') !== false || stripos($raw, '<html') !== false || preg_match('/\<[a-z]+\s+[^>]*>/i', $raw));
        // if HTML or large non-json output detected, attach raw and log
        if ($hasHtml || strlen($raw) > 0) {
            $normalized['raw'] = mb_strimwidth($raw, 0, 4096, '...'); // include truncated raw
            // Log full raw output for debugging
            $logDir = __DIR__ . '/../../.logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $debugPath = $logDir . '/vehicle_api_raw_output.log';
            @file_put_contents($debugPath, "[".date('c')."] HTTP_CODE={$httpCode} RAW_OUTPUT:\n" . $raw . "\n\n", FILE_APPEND | LOCK_EX);
        }
    }

    // Ensure header and output
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Require authenticated user (simplified to avoid HTML output from Auth class)
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Merge JSON request body into $_POST for application/json requests (without overwriting)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        foreach ($parsed as $k => $v) {
            if (!isset($_POST[$k])) $_POST[$k] = $v;
        }
    }
}

// Helper: read CSRF token from POST or X-CSRF-Token header
function request_csrf_token(): ?string {
    $token = $_POST['csrf_token'] ?? null;
    if ($token) return $token;
    if (function_exists('getallheaders')) {
        $h = getallheaders();
        if (!empty($h['X-CSRF-Token'])) return $h['X-CSRF-Token'];
        if (!empty($h['x-csrf-token'])) return $h['x-csrf-token'];
    }
    return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
}

// Helper: handle uploaded vehicle image -> returns array with 'web' and 'server' paths or null
function handle_vehicle_image_upload(array $file, int $userId): ?array {
    if (empty($file) || empty($file['tmp_name'])) return null;

    // Ensure uploads folder exists under backend/uploads/vehicles
    $uploadDir = __DIR__ . '/../uploads/vehicles/';
    if (!is_dir($uploadDir)) {
        // attempt to create with safe permissions
        @mkdir($uploadDir, 0755, true);
    }

    // Use FileUploader for secure upload into the backend uploads directory
    $uploader = new FileUploader($uploadDir);
    $uploader->imagesOnly();

    $result = $uploader->upload($file);
    if (!$result) {
        error_log('FileUploader failed for user ' . $userId . ': ' . implode(', ', $uploader->getErrors()));
        throw new RuntimeException('Upload failed: ' . implode(', ', $uploader->getErrors()));
    }

    // Determine final filename and public web path (relative to project webroot)
    $filename = basename($result['filepath']);
    $web_path = '/backend/uploads/vehicles/' . $filename; // stored in DB as requested

    // Filesystem path where the file should be accessible by the webserver
    $server_path = rtrim($_SERVER['DOCUMENT_ROOT'], '\/') . '/backend/uploads/vehicles/' . $filename;

    // Log final server path for diagnostics
    if (class_exists(Logger::class) && method_exists(Logger::class, 'info')) {
        Logger::info('vehicle_api uploaded file to: ' . $server_path, ['user' => $userId]);
    } else {
        error_log('vehicle_api uploaded file to: ' . $server_path);
    }

    return ['web' => $web_path, 'server' => $server_path];
}

// Get DB (prefer Database class, fallback to getDBConnection)
$pdo = null;
if (class_exists(\App\Classes\Database::class)) {
    try {
        $dbw = \App\Classes\Database::getInstance();
        if (method_exists($dbw, 'getPdo')) $pdo = $dbw->getPdo();
        elseif ($dbw instanceof PDO) $pdo = $dbw;
    } catch (Throwable $e) {
        // fallback below
    }
}
if (!$pdo && function_exists('getDBConnection')) {
    $maybe = getDBConnection();
    if ($maybe instanceof PDO) $pdo = $maybe;
}

// Ensure user id
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Ensure table exists (non-fatal)
try {
    if ($pdo instanceof PDO) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            brand VARCHAR(191) DEFAULT NULL,
            model VARCHAR(191) DEFAULT NULL,
            license_plate VARCHAR(64) DEFAULT NULL,
            year SMALLINT DEFAULT NULL,
            color VARCHAR(64) DEFAULT NULL,
            image_path VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
} catch (Throwable $e) {
    if (class_exists(Logger::class) && method_exists(Logger::class, 'exception')) {
        Logger::exception($e, ['user' => $user_id]);
    } else {
        error_log('vehicle_api: ensure table error: ' . $e->getMessage());
    }
}

// Ensure image_path column exists (in case the table was created earlier without it)
try {
    if ($pdo instanceof PDO) {
        $colStmt = $pdo->query("SHOW COLUMNS FROM `user_vehicles` LIKE 'image_path'");
        $hasCol = $colStmt && $colStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasCol) {
            $pdo->exec("ALTER TABLE `user_vehicles` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL AFTER `color`");
        }
    }
} catch (Throwable $e) {
    if (class_exists(Logger::class) && method_exists(Logger::class, 'exception')) {
        Logger::exception($e, ['user' => $user_id, 'action' => 'ensure_image_path']);
    } else {
        error_log('vehicle_api ensure_image_path error: ' . $e->getMessage());
    }
}

// Ensure CSRF token validation for all sensitive requests
$csrfToken = request_csrf_token();
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    if (!$csrfToken || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        send_json_response(['error' => 'Invalid CSRF token'], 403);
    }
}

// Validate and sanitize inputs
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Example usage for POST data
foreach ($_POST as $key => $value) {
    $_POST[$key] = sanitize_input($value);
}

// Improve error handling
set_exception_handler(function ($exception) {
    error_log('Unhandled exception: ' . $exception->getMessage());
    send_json_response(['error' => 'An unexpected error occurred. Please try again later.'], 500);
});

// Refactor CSRF validation into a reusable function
function validate_csrf_token(string $token): void {
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        send_json_response(['error' => 'Invalid CSRF token'], 403);
    }
}

// Refactor database schema management to setup scripts
function ensure_database_schema(PDO $pdo): void {
    // Log a warning instead of dynamically creating tables
    error_log('Database schema changes should be handled in migrations.');
}

// Consolidate error logging
function log_error(Throwable $e, array $context = []): void {
    if (class_exists(Logger::class) && method_exists(Logger::class, 'exception')) {
        Logger::exception($e, $context);
    } else {
        error_log('Error: ' . $e->getMessage());
    }
}

// Optimize database queries
function fetch_user_vehicles(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare('SELECT id, image_path FROM user_vehicles WHERE user_id = :uid AND image_path IS NOT NULL AND image_path != ""');
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Main handler
try {
    if ($method === 'GET') {
        $getAction = $_GET['action'] ?? 'list';
        
        if ($getAction === 'check_images') {
            // Check which vehicle images exist on disk
            if (!$pdo) throw new RuntimeException('Database connection not available');
            $stmt = $pdo->prepare('SELECT id, image_path FROM user_vehicles WHERE user_id = :uid AND image_path IS NOT NULL AND image_path != ""');
            $stmt->execute([':uid' => $user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $missing = [];
            $existing = [];
            
            foreach ($rows as $row) {
                $path = $row['image_path'];
                if (empty($path)) continue;
                
                // Convert web path to filesystem path
                if (strpos($path, '/carwash_project/') === 0) {
                    $relativePath = substr($path, strlen('/carwash_project/'));
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/' . $relativePath;
                } else {
                    // Assume it's already a relative path from web root
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
                }
                
                if (file_exists($fullPath)) {
                    $existing[] = ['id' => $row['id'], 'path' => $path, 'filesystem_path' => $fullPath];
                } else {
                    $missing[] = ['id' => $row['id'], 'path' => $path, 'filesystem_path' => $fullPath];
                }
            }
            
            if (class_exists(Response::class) && method_exists(Response::class, 'success')) {
                Response::success('Image check completed', ['existing' => $existing, 'missing' => $missing]);
            } else {
                echo json_encode(['success' => true, 'existing' => $existing, 'missing' => $missing], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        } elseif ($getAction === 'list') {
            // list vehicles
            if (!$pdo) throw new RuntimeException('Database connection not available');
            $stmt = $pdo->prepare('SELECT id, brand, model, license_plate, year, color, image_path, created_at FROM user_vehicles WHERE user_id = :uid ORDER BY created_at DESC');
            $stmt->execute([':uid' => $user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process image paths to ensure they are valid public URLs
            foreach ($rows as &$row) {
                $imagePath = $row['image_path'];
                
                if (empty($imagePath)) {
                    // Empty path - use default image
                    $row['image_path'] = defined('DEFAULT_VEHICLE_IMAGE') ? DEFAULT_VEHICLE_IMAGE : '/carwash_project/frontend/assets/images/default-car.png';
                } elseif (strpos($imagePath, '/carwash_project/') !== 0) {
                    // Relative path without full prefix - add it
                    if (strpos($imagePath, '/') === 0) {
                        $row['image_path'] = '/carwash_project' . $imagePath;
                    } else {
                        // Invalid path - use default
                        $row['image_path'] = defined('DEFAULT_VEHICLE_IMAGE') ? DEFAULT_VEHICLE_IMAGE : '/carwash_project/frontend/assets/images/default-car.png';
                    }
                }
                // If it already starts with /carwash_project/, keep as-is
            }
            
            // Normalize to indexed array and return canonical shape
            $rows = is_array($rows) ? array_values($rows) : [];
            if (class_exists(Response::class) && method_exists(Response::class, 'success')) {
                Response::success('Vehicles listed', ['vehicles' => $rows]);
            } else {
                echo json_encode(['status' => 'success', 'message' => 'Vehicles listed', 'data' => ['vehicles' => $rows]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            exit;
        } else {
            // Unknown GET action
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown GET action'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        $action = $_POST['action'] ?? 'create';

        // CSRF enforcement only if session token exists
        $postedCsrf = request_csrf_token();
        $sessionCsrf = $_SESSION['csrf_token'] ?? '';
        if ($sessionCsrf !== '' || $postedCsrf !== '') {
            if (empty($postedCsrf) || !hash_equals((string)$sessionCsrf, (string)$postedCsrf)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF']);
                exit;
            }
        }

        if (!$pdo) throw new RuntimeException('Database connection not available');

        if ($action === 'create') {
            $brand = trim((string)($_POST['car_brand'] ?? ''));
            $model = trim((string)($_POST['car_model'] ?? ''));
            $license = trim((string)($_POST['license_plate'] ?? ''));
            $year = isset($_POST['car_year']) ? (int)$_POST['car_year'] : null;
            $color = trim((string)($_POST['car_color'] ?? ''));
            // Insert without image_path first; we'll handle file upload and update afterwards
            $stmt = $pdo->prepare('INSERT INTO user_vehicles (user_id, brand, model, license_plate, year, color, created_at) VALUES (:uid, :brand, :model, :license, :year, :color, NOW())');
            $ok = $stmt->execute([':uid' => $user_id, ':brand' => $brand, ':model' => $model, ':license' => $license, ':year' => $year ?: null, ':color' => $color]);
            if (!$ok) throw new RuntimeException('Could not save vehicle');
            $id = (int)$pdo->lastInsertId();

            // If a file was uploaded, handle it and update the record
            $webPath = null;
            try {
                if (!empty($_FILES['vehicle_image']) && is_array($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    error_log('vehicle_api create upload attempt for user: ' . $user_id);
                    $uploadResult = handle_vehicle_image_upload($_FILES['vehicle_image'], $user_id);
                    if (is_array($uploadResult) && !empty($uploadResult['web'])) {
                        $webPath = $uploadResult['web'];
                        // store web path in DB
                        $u = $pdo->prepare('UPDATE user_vehicles SET image_path = :path WHERE id = :id AND user_id = :uid');
                        $u->execute([':path' => $webPath, ':id' => $id, ':uid' => $user_id]);
                    }
                }
            } catch (Throwable $e) {
                // don't crash the main flow on upload failure; log and continue
                error_log('vehicle_api create upload error: ' . $e->getMessage());
                if (class_exists(Logger::class) && method_exists(Logger::class, 'exception')) {
                    Logger::exception($e, ['user' => $user_id, 'vehicle_id' => $id]);
                } else {
                    error_log('vehicle_api upload error: ' . $e->getMessage());
                }
            }

            $respData = ['vehicle_id' => $id];
            if (!empty($webPath)) $respData['image_url'] = $webPath;
            send_json_response(['status'=>'success','message'=>'Vehicle created','data'=>$respData], 201);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('Invalid vehicle id');
            $brand = trim((string)($_POST['car_brand'] ?? ''));
            $model = trim((string)($_POST['car_model'] ?? ''));
            $license = trim((string)($_POST['license_plate'] ?? ''));
            $year = isset($_POST['car_year']) ? (int)$_POST['car_year'] : null;
            $color = trim((string)($_POST['car_color'] ?? ''));
            // If an image file is present, upload first and include in update
            $imagePathToSet = null;
            try {
                if (!empty($_FILES['vehicle_image']) && is_array($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    error_log('vehicle_api update upload attempt for user: ' . $user_id . ' vehicle: ' . $id);
                    $uploadResult = handle_vehicle_image_upload($_FILES['vehicle_image'], $user_id);
                    if (is_array($uploadResult) && !empty($uploadResult['web'])) {
                        $imagePathToSet = $uploadResult['web'];
                        if (!empty($uploadResult['server'])) {
                            if (class_exists(Logger::class) && method_exists(Logger::class, 'info')) {
                                Logger::info('vehicle_api server_path (update): ' . $uploadResult['server'], ['user' => $user_id, 'vehicle_id' => $id]);
                            } else {
                                error_log('vehicle_api server_path (update): ' . $uploadResult['server']);
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('vehicle_api update upload error: ' . $e->getMessage());
                if (class_exists(Logger::class) && method_exists(Logger::class, 'exception')) {
                    Logger::exception($e, ['user' => $user_id, 'vehicle_id' => $id]);
                } else {
                    error_log('vehicle_api upload error: ' . $e->getMessage());
                }
                // we don't abort the update; image upload failure should not block other updates
            }

            if ($imagePathToSet !== null) {
                $stmt = $pdo->prepare('UPDATE user_vehicles SET brand = :brand, model = :model, license_plate = :license, year = :year, color = :color, image_path = :img, updated_at = NOW() WHERE id = :id AND user_id = :uid');
                $ok = $stmt->execute([':brand' => $brand, ':model' => $model, ':license' => $license, ':year' => $year ?: null, ':color' => $color, ':img' => $imagePathToSet, ':id' => $id, ':uid' => $user_id]);
            } else {
                $stmt = $pdo->prepare('UPDATE user_vehicles SET brand = :brand, model = :model, license_plate = :license, year = :year, color = :color, updated_at = NOW() WHERE id = :id AND user_id = :uid');
                $ok = $stmt->execute([':brand' => $brand, ':model' => $model, ':license' => $license, ':year' => $year ?: null, ':color' => $color, ':id' => $id, ':uid' => $user_id]);
            }

            if (!$ok) throw new RuntimeException('Update failed');
            $resp = ['status' => 'success', 'message' => 'Vehicle updated'];
            if (!empty($imagePathToSet)) $resp['data'] = ['image_url' => $imagePathToSet];
            send_json_response($resp, 200);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('Invalid vehicle id');
            error_log('vehicle_api delete attempt: id=' . $id . ' user_id=' . $user_id);
            $stmt = $pdo->prepare('DELETE FROM user_vehicles WHERE id = :id AND user_id = :uid');
            $ok = $stmt->execute([':id' => $id, ':uid' => $user_id]);
            if (!$ok) throw new RuntimeException('Delete failed');
            send_json_response(['status'=>'success','message'=>'Vehicle deleted'], 200);
            exit;
        }

        // unknown action
        if (class_exists(Logger::class) && method_exists(Logger::class, 'warn')) {
            Logger::warn('Unknown action in vehicle_api: ' . $action);
        } else {
            error_log('Unknown action in vehicle_api: ' . $action);
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if (class_exists(Logger::class) && method_exists(Logger::class, 'exception')) {
        Logger::exception($e, ['user' => $user_id]);
    } else {
        error_log('vehicle_api error: ' . $e->getMessage());
    }
    $env = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production')));
    $payload = ['success' => false, 'error_type' => get_class($e), 'message' => $e->getMessage()];
    if (in_array($env, ['dev', 'development'], true)) $payload['trace'] = $e->getTraceAsString();
    send_json_response($payload, 500);
    exit;
}
