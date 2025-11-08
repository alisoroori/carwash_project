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
            $surname = trim((string)($_POST['surname'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $home_phone = trim((string)($_POST['home_phone'] ?? ''));
            $national_id = trim((string)($_POST['national_id'] ?? ''));
            $driver_license = trim((string)($_POST['driver_license'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            $city = trim((string)($_POST['city'] ?? ''));

            // Validation: Required fields
            $errors = [];
            if (empty($name)) $errors[] = 'Ad Soyad gereklidir';
            if (empty($email)) $errors[] = 'E-posta gereklidir';
            if (empty($home_phone)) $errors[] = 'Ev telefonu gereklidir';
            if (empty($national_id)) $errors[] = 'T.C. Kimlik No gereklidir';
            
            // Validate National ID format (11 digits)
            if (!empty($national_id) && !preg_match('/^[0-9]{11}$/', $national_id)) {
                $errors[] = 'T.C. Kimlik No 11 haneli olmalıdır';
            }
            
            if (!empty($errors)) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // Accept file under 'profile_image' or 'profile_photo' or fallback to first file
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
            if ($uploaded) {
                // Validate uploaded file
                $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($uploaded['type'], $allowedTypes)) {
                    ob_end_clean();
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Geçersiz dosya türü. JPG, PNG veya WEBP yükleyin.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
                
                if ($uploaded['size'] > $maxSize) {
                    ob_end_clean();
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Dosya boyutu 2MB\'dan küçük olmalıdır.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
                
                $uploadDir = __DIR__ . '/../uploads/profile_images/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $ext = pathinfo($uploaded['name'] ?? '', PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . ($ext ? '.' . $ext : '');
                $dest = $uploadDir . $filename;
                
                if (is_uploaded_file($uploaded['tmp_name'])) {
                    // Delete old profile image if exists
                    try {
                        if ($dbConn instanceof PDO) {
                            $oldStmt = $dbConn->prepare('SELECT profile_image FROM users WHERE id = :id');
                            $oldStmt->execute([':id' => $user_id]);
                            $oldImage = $oldStmt->fetchColumn();
                            if ($oldImage) {
                                $oldPath = __DIR__ . '/../..' . str_replace('/carwash_project', '', $oldImage);
                                if (file_exists($oldPath)) @unlink($oldPath);
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Could not delete old profile image: ' . $e->getMessage());
                    }
                    
                    if (@move_uploaded_file($uploaded['tmp_name'], $dest)) {
                        // Store with /carwash_project prefix for consistency
                        $webPath = '/carwash_project/backend/uploads/profile_images/' . $filename;
                    } else {
                        error_log('profile upload move failed for user ' . $user_id);
                    }
                }
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
                        'email = :email'
                    ];
                    
                    $params = [
                        ':name' => $name,
                        ':phone' => $phone,
                        ':home_phone' => $home_phone,
                        ':national_id' => $national_id,
                        ':driver_license' => $driver_license,
                        ':email' => $email,
                        ':id' => $user_id
                    ];
                    
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
                ob_end_clean();
                error_log('Profile update error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                error_log('Profile update stack trace: ' . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Profil güncellenirken bir hata oluştu: ' . $e->getMessage(),
                    'debug' => [
                        'error' => $e->getMessage(),
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine()
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    $payload = ['success' => false, 'error_type' => get_class($e), 'message' => $e->getMessage()];
    if (in_array($env, ['dev', 'development'], true)) $payload['trace'] = $e->getTraceAsString();
    http_response_code(500);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

exit;
