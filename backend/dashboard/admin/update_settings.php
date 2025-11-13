<?php
session_start();
// CSRF helper
$csrf_helper = __DIR__ . '/../../includes/csrf_helper.php';
if (file_exists($csrf_helper)) require_once $csrf_helper;
require_once '../../includes/db.php';

// Set JSON response header
header('Content-Type: application/json');

// CSRF validation for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !function_exists('hash_equals') || !hash_equals((string)$_SESSION['csrf_token'], (string)$token)) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit();
    }
}

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Validate section parameter
if (!isset($_POST['section'])) {
    echo json_encode(['success' => false, 'error' => 'Missing section parameter']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Define allowed settings for each section
    $allowed_settings = [
        'general' => ['site_title', 'contact_email', 'contact_phone'],
        'booking' => ['min_booking_duration', 'max_advance_booking_days', 'cancellation_limit_hours'],
        'email' => ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass']
    ];

    $section = filter_var($_POST['section'], FILTER_SANITIZE_STRING);

    if (!array_key_exists($section, $allowed_settings)) {
        throw new Exception('Invalid section');
    }

    // Process each setting in the section
    foreach ($allowed_settings[$section] as $key) {
        if (isset($_POST[$key])) {
            $value = filter_var($_POST[$key], FILTER_SANITIZE_STRING);

            // Validate specific settings
            switch ($key) {
                case 'contact_email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Invalid email format');
                    }
                    break;

                case 'min_booking_duration':
                case 'max_advance_booking_days':
                case 'cancellation_limit_hours':
                case 'smtp_port':
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        throw new Exception("Invalid numeric value for $key");
                    }
                    break;
            }

            // Update or insert setting
            $stmt = $conn->prepare("
                INSERT INTO system_settings (`key`, value, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    value = VALUES(value),
                    updated_at = VALUES(updated_at)
            ");
            $stmt->bind_param("ss", $key, $value);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update setting: $key");
            }
        }
    }

    // Log the settings update
    $stmt = $conn->prepare("
        INSERT INTO admin_logs (
            admin_id,
            action,
            target_type,
            details,
            created_at
        ) VALUES (?, 'update_settings', 'settings', ?, NOW())
    ");
    $details = json_encode([
        'section' => $section,
        'updated_keys' => array_keys($_POST)
    ]);
    $stmt->bind_param("is", $_SESSION['user_id'], $details);
    $stmt->execute();

    // Create/update settings cache file
    $cache_path = __DIR__ . '/../../includes/cache/settings.php';
    $settings_query = "SELECT * FROM system_settings";
    $settings = $conn->query($settings_query)->fetch_all(MYSQLI_ASSOC);

    $cache_content = "<?php\nreturn " . var_export(array_column($settings, 'value', 'key'), true) . ";\n";
    file_put_contents($cache_path, $cache_content);

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Settings updated successfully'
    ]);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
