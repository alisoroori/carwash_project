<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/report_manager.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['review_id']) || !isset($data['reason'])) {
        throw new Exception('Missing required fields');
    }

    $reportManager = new ReportManager($conn);

    if ($reportManager->submitReport(
        $data['review_id'],
        $_SESSION['user_id'],
        $data['reason'],
        $data['description'] ?? ''
    )) {
        echo json_encode([
            'success' => true,
            'message' => 'Bildiriminiz başarıyla gönderildi'
        ]);
    } else {
        throw new Exception('Bildirim gönderilemedi');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
