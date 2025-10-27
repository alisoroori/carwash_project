<?php
session_start();
require_once '../../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

try {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=admin_logs_' . date('Y-m-d_H-i-s') . '.csv');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Write CSV header
    fputcsv($output, [
        'Tarih/Saat',
        'Admin Adı',
        'Admin Email',
        'İşlem',
        'Hedef Tip',
        'Detaylar',
        'IP Adresi'
    ]);

    // Get all logs
    $stmt = $conn->prepare("
        SELECT 
            al.*,
            u.name as admin_name,
            u.email as admin_email
        FROM admin_logs al
        JOIN users u ON al.admin_id = u.id
        ORDER BY al.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    // Write logs to CSV
    while ($log = $result->fetch_assoc()) {
        $details = json_decode($log['details'], true);
        $ip = isset($details['ip']) ? $details['ip'] : 'N/A';

        // Format details for CSV
        $formatted_details = formatDetailsForCsv($details);

        fputcsv($output, [
            date('d.m.Y H:i:s', strtotime($log['created_at'])),
            $log['admin_name'],
            $log['admin_email'],
            getActionText($log['action']),
            $log['target_type'],
            $formatted_details,
            $ip
        ]);
    }

    // Close the output stream
    fclose($output);
} catch (Exception $e) {
    // Log error
    error_log("Log export error: " . $e->getMessage());

    // Redirect back with error
    $_SESSION['error'] = "Dışa aktarma sırasında bir hata oluştu.";
    header('Location: logs.php');
    exit();
}

/**
 * Format action text for CSV
 */
function getActionText($action)
{
    switch ($action) {
        case 'update_settings':
            return 'Ayar Güncelleme';
        case 'clear_cache':
            return 'Önbellek Temizleme';
        case 'backup_db':
            return 'Veritabanı Yedekleme';
        default:
            return ucfirst(str_replace('_', ' ', $action));
    }
}

/**
 * Format details for CSV export
 */
function formatDetailsForCsv($details)
{
    if (!is_array($details)) return 'N/A';

    $output = [];
    foreach ($details as $key => $value) {
        if ($key === 'ip') continue; // Skip IP as it's in separate column

        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $output[] = ucfirst($key) . ': ' . $value;
    }

    return implode(' | ', $output);
}
