<?php
session_start();
require_once '../../includes/db.php';

// Set JSON response header
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Validate action parameter
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'error' => 'Missing action parameter']);
    exit();
}

try {
    $action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);

    switch ($action) {
        case 'clear_cache':
            clearSystemCache();
            break;

        case 'backup_db':
            backupDatabase();
            break;

        case 'toggle_maintenance':
            toggleMaintenanceMode();
            break;

        default:
            throw new Exception('Invalid maintenance action');
    }

    // Log maintenance action
    logMaintenanceAction($action);

    echo json_encode([
        'success' => true,
        'message' => 'Maintenance action completed successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Clear system cache files
 */
function clearSystemCache()
{
    $cache_dirs = [
        __DIR__ . '/../../includes/cache/',
        __DIR__ . '/../../uploads/temp/'
    ];

    foreach ($cache_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file) && !in_array(basename($file), ['.gitkeep', '.htaccess'])) {
                    unlink($file);
                }
            }
        }
    }
}

/**
 * Backup database
 */
function backupDatabase()
{
    global $host, $user, $pass, $dbname;

    $backup_dir = __DIR__ . '/../../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $date = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . "backup_{$date}.sql";

    // Create backup command
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s > %s',
        escapeshellarg($host),
        escapeshellarg($user),
        escapeshellarg($pass),
        escapeshellarg($dbname),
        escapeshellarg($backup_file)
    );

    // Execute backup
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        throw new Exception('Database backup failed');
    }

    // Compress backup
    $zip = new ZipArchive();
    $zip_file = $backup_file . '.zip';

    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($backup_file, basename($backup_file));
        $zip->close();
        unlink($backup_file); // Remove uncompressed file
    }

    // Clean old backups (keep last 5)
    $backups = glob($backup_dir . '*.zip');
    if (count($backups) > 5) {
        sort($backups);
        $old_backups = array_slice($backups, 0, -5);
        foreach ($old_backups as $old_backup) {
            unlink($old_backup);
        }
    }
}

/**
 * Toggle maintenance mode
 */
function toggleMaintenanceMode()
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT value 
        FROM system_settings 
        WHERE `key` = 'maintenance_mode'
    ");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $new_status = ($result && $result['value'] === 'on') ? 'off' : 'on';

    $stmt = $conn->prepare("
        INSERT INTO system_settings (`key`, value, updated_at)
        VALUES ('maintenance_mode', ?, NOW())
        ON DUPLICATE KEY UPDATE 
            value = VALUES(value),
            updated_at = VALUES(updated_at)
    ");
    $stmt->bind_param("s", $new_status);

    if (!$stmt->execute()) {
        throw new Exception('Failed to toggle maintenance mode');
    }

    // Create/update maintenance flag file
    $flag_file = __DIR__ . '/../../.maintenance';
    if ($new_status === 'on') {
        file_put_contents($flag_file, time());
    } else {
        if (file_exists($flag_file)) {
            unlink($flag_file);
        }
    }
}

/**
 * Log maintenance action
 */
function logMaintenanceAction($action)
{
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO admin_logs (
            admin_id,
            action,
            target_type,
            details,
            created_at
        ) VALUES (?, ?, 'maintenance', ?, NOW())
    ");

    $details = json_encode([
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);

    $stmt->bind_param("iss", $_SESSION['user_id'], $action, $details);
    $stmt->execute();
}
