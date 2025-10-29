<<?php
// ...existing code...
// Replace the inline vehicle form submit handler section with the snippet below
// ...existing code...
?>
<script>
(function(){
  const vehicleFormInline = document.getElementById('vehicleFormInline');
  if (vehicleFormInline) {
    vehicleFormInline.addEventListener('submit', async (ev)=>{
      ev.preventDefault();
      const action = formAction.value;
      const fd = new FormData(vehicleForm);
      fd.set('action', action === 'create' ? 'create' : 'update');
      // Ensure backend expects id under 'id' (vehicle_api.php looks for 'id')
      const vid = document.getElementById('formVehicleId').value;
      if (action !== 'create' && vid) {
        // keep existing vehicle_id field but also set 'id' for backend compatibility
        fd.set('id', vid);
      }
      const csrf = await fetchCsrfToken();
      if (csrf) fd.append('csrf_token', csrf);
      try {
        const res = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', { method:'POST', credentials:'same-origin', body: fd });
        const raw = await res.text();
        let json = null;
        try { json = raw ? JSON.parse(raw) : null; } catch(e){ showMessage('Sunucudan beklenmeyen cevap alındı', 'error'); return; }

        if (json && json.success) {
          showMessage('İşlem başarılı');

          // Close edit/create panel
          formPanel.style.display = 'none';

          // If this was an edit/update, refresh the whole page once so every UI piece updates.
          // For create, reload the vehicles list only to avoid full page reload.
          if (action === 'update') {
            // small delay to allow UI to close cleanly
            setTimeout(() => { try { location.reload(); } catch(e){ loadVehicles(); } }, 300);
          } else {
            // create path: update list inline
            loadVehicles();
          }
        } else {
          showMessage(json && (json.message||json.error) ? (json.message||json.error) : 'İşlem başarısız', 'error');
        }
      } catch (e) {
        console.error(e);
        showMessage('İstek başarısız', 'error');
      }
    });
  }
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

header('Content-Type: application/json; charset=utf-8');

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
            if (class_exists(\App\Classes\Response::class) && method_exists(\App\Classes\Response::class, 'success')) {
                \App\Classes\Response::success('Vehicles listed', ['vehicles' => $vehicles]);
            } else {
                echo json_encode(['success' => true, 'vehicles' => $vehicles], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
