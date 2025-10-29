<?php
declare(strict_types=1);

/**
 * Customer_Dashboard_process.php
 *
 * - Uses App\Classes\Database and App\Classes\Response when available.
 * - Maintains legacy mysqli fallback (backend/includes/db.php).
 * - Supports file uploads (vehicle images / profile photo).
 * - Handles create_reservation and update_reservation (inline).
 * - Always returns JSON responses (no HTML).
 *
 * Usage: AJAX POST from dashboard forms. Must include csrf_token for CSRF protection.
 */

@session_start();
header('Content-Type: application/json; charset=utf-8');

// Bootstrap/autoload if available
$bootstrap = __DIR__ . '/../includes/bootstrap.php';
$vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($bootstrap)) {
    require_once $bootstrap;
} elseif (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Legacy includes (provides getDBConnection() / $conn if present)
$legacyDbIncluded = false;
$legacyDbPath = __DIR__ . '/../includes/db.php';
if (file_exists($legacyDbPath)) {
    require_once $legacyDbPath;
    $legacyDbIncluded = true;
}

use App\Classes\Database;
use App\Classes\Response;
use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Logger;

// Helper: unified JSON responder (uses Response class if possible)
function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    if (class_exists(Response::class)) {
        // Try to use Response::success / Response::error conventions
        if (!empty($payload['success']) && method_exists(Response::class, 'success')) {
            // Response::success($message, $data = null)
            $msg = $payload['message'] ?? null;
            $data = $payload['data'] ?? null;
            Response::success($msg ?? '', $data);
            exit;
        }
        if (empty($payload['success']) && method_exists(Response::class, 'error')) {
            $msg = $payload['message'] ?? ($payload['error'] ?? 'Error');
            Response::error($msg, $status);
            exit;
        }
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Start session via Session wrapper if present
if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// Require auth and role (customer) if Auth available
if (class_exists(Auth::class) && method_exists(Auth::class, 'requireRole')) {
    try {
        Auth::requireRole('customer');
    } catch (Throwable $e) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
} else {
    // Fallback auth check
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// CSRF validation if token exists in session
$postedCsrf = $_POST['csrf_token'] ?? '';
$sessionCsrf = $_SESSION['csrf_token'] ?? '';
if ($sessionCsrf !== '' || $postedCsrf !== '') {
    if (empty($postedCsrf) || empty($sessionCsrf) || !hash_equals((string)$sessionCsrf, (string)$postedCsrf)) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }
}

// Determine DB layer: prefer App\Classes\Database (PDO-like), else legacy $conn (mysqli or PDO)
$dbWrapper = null;
$pdo = null;
$mysqli = null;

if (class_exists(Database::class)) {
    try {
        $dbWrapper = Database::getInstance();
        // Try to get PDO if wrapper exposes it
        if (method_exists($dbWrapper, 'getPdo')) {
            $pdo = $dbWrapper->getPdo();
        } elseif ($dbWrapper instanceof \PDO) {
            $pdo = $dbWrapper;
        }
    } catch (Throwable $e) {
        // ignore and fallback
        $dbWrapper = null;
        $pdo = null;
    }
}

// Legacy fallback: $conn or getDBConnection()
if (!$pdo) {
    if (isset($conn) && ($conn instanceof \mysqli || $conn instanceof \PDO)) {
        if ($conn instanceof \PDO) $pdo = $conn;
        else $mysqli = $conn;
    } elseif (function_exists('getDBConnection')) {
        try {
            $maybe = getDBConnection();
            if ($maybe instanceof \PDO) $pdo = $maybe;
            elseif ($maybe instanceof \mysqli) $mysqli = $maybe;
        } catch (Throwable $e) {
            // fallback failure
        }
    }
}

// Ensure at least one DB connection available
if (!$pdo && !$mysqli) {
    jsonResponse(['success' => false, 'message' => 'Database connection not available'], 500);
}

// Utility: ensure user_vehicles table exists (uses PDO or mysqli)
function ensureUserVehiclesTable($pdoOrMysqli): void
{
    $sql = "CREATE TABLE IF NOT EXISTS user_vehicles (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($pdoOrMysqli instanceof \PDO) {
        $pdoOrMysqli->exec($sql);
    } elseif ($pdoOrMysqli instanceof \mysqli) {
        $pdoOrMysqli->query($sql);
    }
}

// Helper: check if a table has a specific column (PDO or mysqli)
function tableHasColumn($pdoOrMysqli, string $table, string $column): bool
{
    try {
        if ($pdoOrMysqli instanceof \PDO) {
            $stmt = $pdoOrMysqli->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE :col');
            $stmt->execute([':col' => $column]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (bool)$row;
        } elseif ($pdoOrMysqli instanceof \mysqli) {
            $colEsc = $pdoOrMysqli->real_escape_string($column);
            $res = $pdoOrMysqli->query("SHOW COLUMNS FROM `" . $pdoOrMysqli->real_escape_string($table) . "` LIKE '" . $colEsc . "'");
            return ($res && $res->num_rows > 0);
        }
    } catch (Throwable $e) {
        return false;
    }
    return false;
}

// Helper: sanitize incoming integers
function intFrom($v): ?int {
    if ($v === null || $v === '') return null;
    return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int)$v : null;
}

// File upload helper
function handleUpload(array $file, string $subDir = 'vehicles'): ?string
{
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error code: ' . $file['error']);
    }

    $base = __DIR__ . '/../../uploads/' . trim($subDir, '/');
    if (!is_dir($base)) {
        if (!mkdir($base, 0755, true) && !is_dir($base)) {
            throw new RuntimeException('Failed to create upload directory');
        }
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeExt = preg_replace('/[^a-z0-9]/i', '', $ext);
    $name = bin2hex(random_bytes(8)) . '.' . ($safeExt ?: 'bin');
    $target = $base . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Failed to move uploaded file');
    }

    // Return relative path from project root
    return 'uploads/' . $subDir . '/' . $name;
}

// Begin action handling
$action = trim((string)($_POST['action'] ?? ''));

try {
    switch ($action) {
        case 'list_vehicles':
            $userId = (int)($_SESSION['user_id'] ?? 0);
            // Ensure table exists (attempt to migrate if missing)
            ensureUserVehiclesTable($pdo ?? $mysqli);
            $vehicles = [];
            // Be tolerant: only select image_path if the column exists
            $hasImage = tableHasColumn($pdo ?? $mysqli, 'user_vehicles', 'image_path');
            $cols = 'id, brand, model, license_plate, year, color' . ($hasImage ? ', image_path' : '');
            if ($pdo) {
                $sql = "SELECT {$cols} FROM user_vehicles WHERE user_id = :uid ORDER BY created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':uid' => $userId]);
                $vehicles = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } elseif ($mysqli) {
                $sql = "SELECT {$cols} FROM user_vehicles WHERE user_id = ? ORDER BY created_at DESC";
                $st = $mysqli->prepare($sql);
                $st->bind_param('i', $userId);
                $st->execute();
                $res = $st->get_result();
                while ($row = $res->fetch_assoc()) $vehicles[] = $row;
            }

            jsonResponse(['success' => true, 'vehicles' => $vehicles]);
            break;
        case 'create_reservation':
        case 'update_reservation':
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $carwashId = intFrom($_POST['carwash_id'] ?? null);
            $serviceId = intFrom($_POST['service_id'] ?? null);
            $servicePrice = isset($_POST['service_price']) ? (float)$_POST['service_price'] : null;
            $vehicleId = intFrom($_POST['vehicle_id'] ?? null);
            $reservationDate = trim((string)($_POST['reservation_date'] ?? ''));
            $reservationTime = trim((string)($_POST['reservation_time'] ?? ''));
            $notes = trim((string)($_POST['notes'] ?? ''));

            if (!$carwashId || !$reservationDate || !$reservationTime) {
                jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
            }

            // Validate date/time
            $datetime = strtotime($reservationDate . ' ' . $reservationTime);
            if ($datetime === false || $datetime <= time()) {
                jsonResponse(['success' => false, 'message' => 'Invalid reservation date/time'], 400);
            }

            // Determine price if service_id provided
            if ($serviceId && !$servicePrice) {
                if ($pdo) {
                    $stmt = $pdo->prepare('SELECT price FROM services WHERE id = :id LIMIT 1');
                    $stmt->execute([':id' => $serviceId]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $servicePrice = $row ? (float)$row['price'] : 0.0;
                } elseif ($mysqli) {
                    $st = $mysqli->prepare('SELECT price FROM services WHERE id = ? LIMIT 1');
                    $st->bind_param('i', $serviceId);
                    $st->execute();
                    $res = $st->get_result();
                    $row = $res->fetch_assoc();
                    $servicePrice = $row ? (float)$row['price'] : 0.0;
                }
            }
            $price = $servicePrice ?? 0.0;

            if ($action === 'create_reservation') {
                // Insert booking
                if ($pdo) {
                    $pdo->beginTransaction();
                    $sql = "INSERT INTO bookings (user_id, carwash_id, service_id, vehicle_id, booking_date, booking_time, price, status, created_at)
                            VALUES (:uid, :cw, :sid, :vid, :date, :time, :price, 'pending', NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':uid' => $userId,
                        ':cw' => $carwashId,
                        ':sid' => $serviceId,
                        ':vid' => $vehicleId,
                        ':date' => $reservationDate,
                        ':time' => $reservationTime,
                        ':price' => $price
                    ]);
                    $bookingId = (int)$pdo->lastInsertId();
                    $pdo->commit();
                } else { // mysqli
                    $mysqli->begin_transaction();
                    $sql = "INSERT INTO bookings (user_id, carwash_id, service_id, vehicle_id, booking_date, booking_time, price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('iiiissd', $userId, $carwashId, $serviceId, $vehicleId, $reservationDate, $reservationTime, $price);
                    if (!$stmt->execute()) {
                        $mysqli->rollback();
                        throw new RuntimeException('Failed to create reservation');
                    }
                    $bookingId = (int)$mysqli->insert_id;
                    $mysqli->commit();
                }

                // Return success JSON
                jsonResponse(['success' => true, 'message' => 'Reservation created', 'booking_id' => $bookingId], 201);
            } else {
                // update_reservation
                $bookingId = intFrom($_POST['booking_id'] ?? null);
                if (!$bookingId) jsonResponse(['success' => false, 'message' => 'Missing booking_id for update'], 400);

                // verify ownership
                $ownerOk = false;
                if ($pdo) {
                    $stmt = $pdo->prepare('SELECT user_id FROM bookings WHERE id = :id LIMIT 1');
                    $stmt->execute([':id' => $bookingId]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $ownerOk = ($row && (int)$row['user_id'] === $userId);
                } elseif ($mysqli) {
                    $st = $mysqli->prepare('SELECT user_id FROM bookings WHERE id = ? LIMIT 1');
                    $st->bind_param('i', $bookingId);
                    $st->execute();
                    $res = $st->get_result();
                    $row = $res->fetch_assoc();
                    $ownerOk = ($row && (int)$row['user_id'] === $userId);
                }
                if (!$ownerOk) jsonResponse(['success' => false, 'message' => 'Booking not found or unauthorized'], 404);

                // perform update
                if ($pdo) {
                    $pdo->beginTransaction();
                    $sql = "UPDATE bookings SET carwash_id = :cw, service_id = :sid, vehicle_id = :vid, booking_date = :date, booking_time = :time, price = :price, updated_at = NOW() WHERE id = :id AND user_id = :uid";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':cw' => $carwashId,
                        ':sid' => $serviceId,
                        ':vid' => $vehicleId,
                        ':date' => $reservationDate,
                        ':time' => $reservationTime,
                        ':price' => $price,
                        ':id' => $bookingId,
                        ':uid' => $userId
                    ]);
                    $pdo->commit();
                } else {
                    $mysqli->begin_transaction();
                    $sql = "UPDATE bookings SET carwash_id = ?, service_id = ?, vehicle_id = ?, booking_date = ?, booking_time = ?, price = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('iiiissdi', $carwashId, $serviceId, $vehicleId, $reservationDate, $reservationTime, $price, $bookingId, $userId);
                    if (!$stmt->execute()) {
                        $mysqli->rollback();
                        throw new RuntimeException('Failed to update reservation');
                    }
                    $mysqli->commit();
                }
                jsonResponse(['success' => true, 'message' => 'Reservation updated', 'booking_id' => $bookingId]);
            }
            break;

        case 'create_vehicle':
        case 'update_vehicle':
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $vehicleId = intFrom($_POST['vehicle_id'] ?? null);
            $brand = trim((string)($_POST['car_brand'] ?? ''));
            $model = trim((string)($_POST['car_model'] ?? ''));
            $plate = trim((string)($_POST['license_plate'] ?? ''));
            $year = intFrom($_POST['car_year'] ?? null);
            $color = trim((string)($_POST['car_color'] ?? ''));

            if ($brand === '' || $model === '' || $plate === '') {
                jsonResponse(['success' => false, 'message' => 'Please provide brand, model and license plate'], 400);
            }

            // Prepare uploads
            $imagePath = null;
            if (!empty($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $imagePath = handleUpload($_FILES['vehicle_image'], 'vehicles');
                } catch (Throwable $e) {
                    jsonResponse(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 400);
                }
            }

            // Ensure table exists
            ensureUserVehiclesTable($pdo ?? $mysqli);

            // Check if image_path column exists to avoid SQL errors on older schemas
            $hasImage = tableHasColumn($pdo ?? $mysqli, 'user_vehicles', 'image_path');

            if ($action === 'create_vehicle') {
                if ($pdo) {
                    if ($hasImage) {
                        $stmt = $pdo->prepare("INSERT INTO user_vehicles (user_id, brand, model, license_plate, year, color, image_path, created_at) VALUES (:uid, :brand, :model, :plate, :year, :color, :img, NOW())");
                        $params = [
                            ':uid' => $userId,
                            ':brand' => $brand,
                            ':model' => $model,
                            ':plate' => $plate,
                            ':year' => $year,
                            ':color' => $color,
                            ':img' => $imagePath
                        ];
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO user_vehicles (user_id, brand, model, license_plate, year, color, created_at) VALUES (:uid, :brand, :model, :plate, :year, :color, NOW())");
                        $params = [
                            ':uid' => $userId,
                            ':brand' => $brand,
                            ':model' => $model,
                            ':plate' => $plate,
                            ':year' => $year,
                            ':color' => $color
                        ];
                    }
                    $stmt->execute($params);
                    $vId = (int)$pdo->lastInsertId();
                } else {
                    if ($hasImage) {
                        $stmt = $mysqli->prepare("INSERT INTO user_vehicles (user_id, brand, model, license_plate, year, color, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param('isssiss', $userId, $brand, $model, $plate, $year, $color, $imagePath);
                    } else {
                        $stmt = $mysqli->prepare("INSERT INTO user_vehicles (user_id, brand, model, license_plate, year, color, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param('isssis', $userId, $brand, $model, $plate, $year, $color);
                    }
                    if (!$stmt->execute()) jsonResponse(['success' => false, 'message' => 'Failed to save vehicle'], 500);
                    $vId = (int)$mysqli->insert_id;
                }
                jsonResponse(['success' => true, 'message' => 'Vehicle saved', 'vehicle_id' => $vId], 201);
            } else {
                if (empty($vehicleId)) jsonResponse(['success' => false, 'message' => 'vehicle_id required for update'], 400);
                // Verify ownership
                $ownerOk = false;
                if ($pdo) {
                    $st = $pdo->prepare('SELECT user_id FROM user_vehicles WHERE id = :id LIMIT 1');
                    $st->execute([':id' => $vehicleId]);
                    $row = $st->fetch(\PDO::FETCH_ASSOC);
                    $ownerOk = ($row && (int)$row['user_id'] === $userId);
                } else {
                    $st = $mysqli->prepare('SELECT user_id FROM user_vehicles WHERE id = ? LIMIT 1');
                    $st->bind_param('i', $vehicleId);
                    $st->execute();
                    $res = $st->get_result();
                    $row = $res->fetch_assoc();
                    $ownerOk = ($row && (int)$row['user_id'] === $userId);
                }
                if (!$ownerOk) jsonResponse(['success' => false, 'message' => 'Vehicle not found or unauthorized'], 404);

                if ($pdo) {
                    $sql = "UPDATE user_vehicles SET brand = :brand, model = :model, license_plate = :plate, year = :year, color = :color";
                    if ($hasImage && $imagePath !== null) $sql .= ", image_path = :img";
                    $sql .= " , updated_at = NOW() WHERE id = :id AND user_id = :uid";
                    $st = $pdo->prepare($sql);
                    $params = [':brand'=>$brand, ':model'=>$model, ':plate'=>$plate, ':year'=>$year, ':color'=>$color, ':id'=>$vehicleId, ':uid'=>$userId];
                    if ($hasImage && $imagePath !== null) $params[':img'] = $imagePath;
                    $st->execute($params);
                } else {
                    $sql = "UPDATE user_vehicles SET brand = ?, model = ?, license_plate = ?, year = ?, color = ?";
                    if ($hasImage && $imagePath !== null) $sql .= ", image_path = ?";
                    $sql .= ", updated_at = NOW() WHERE id = ? AND user_id = ?";
                    if ($hasImage && $imagePath !== null) {
                        $stmt = $mysqli->prepare($sql);
                        $stmt->bind_param('sssi ssii', $brand, $model, $plate, $year, $color, $imagePath, $vehicleId, $userId);
                        // Note: binding string types carefully; if strict binding fails, convert to simpler path.
                    } else {
                        $stmt = $mysqli->prepare($sql);
                        $stmt->bind_param('sssi ii', $brand, $model, $plate, $year, $vehicleId, $userId);
                    }
                    $stmt->execute();
                }
                jsonResponse(['success' => true, 'message' => 'Vehicle updated', 'vehicle_id' => $vehicleId]);
            }
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    // Log server error if logger available
    if (class_exists(Logger::class) && method_exists(Logger::class, 'exception')) {
        Logger::exception($e, ['action' => $action, 'user' => $_SESSION['user_id'] ?? null]);
    } else {
        error_log('Customer_Dashboard_process error: ' . $e->getMessage());
    }

    // Ensure no stray output; return JSON error
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
