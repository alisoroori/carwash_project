<?php
// export_logs_pdf.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/pdf_generator.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

try {
    // Create PDF instance
    $pdf = new PDFGenerator();

    // Add header
    $pdf->addHeader('AquaTR System Logs Report');
    $pdf->addText('Generated: ' . date('d.m.Y H:i'));

    // Get logs data
    $stmt = $conn->prepare("
        SELECT 
            al.*,
            u.name as admin_name,
            u.email as admin_email
        FROM admin_logs al
        JOIN users u ON al.admin_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $logs = $stmt->get_result();

    // Prepare table data
    $headers = ['Tarih/Saat', 'Admin', 'İşlem', 'Detaylar'];
    $data = [];

    while ($log = $logs->fetch_assoc()) {
        $data[] = [
            date('d.m.Y H:i:s', strtotime($log['created_at'])),
            $log['admin_name'] . "\n" . $log['admin_email'],
            getActionText($log['action']),
            formatDetailsForPdf(json_decode($log['details'], true))
        ];
    }

    // Add table to PDF
    $pdf->addTable($headers, $data);

    // Set headers for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="admin_logs_' . date('Y-m-d_H-i-s') . '.pdf"');

    // Output PDF
    $pdf->save('php://output');
} catch (Exception $e) {
    error_log("PDF export error: " . $e->getMessage());
    $_SESSION['error'] = "PDF oluşturulurken bir hata oluştu.";
    header('Location: logs.php');
    exit();
}

/**
 * Format action text for PDF
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
 * Format details for PDF display
 */
function formatDetailsForPdf($details)
{
    if (!is_array($details)) return 'N/A';

    $output = [];
    foreach ($details as $key => $value) {
        if ($key === 'ip') continue;

        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $output[] = '<b>' . ucfirst($key) . ':</b> ' . $value;
    }

    return implode('<br>', $output);
}
