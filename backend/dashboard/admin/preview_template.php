<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/sms_template_manager.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    $templateManager = new SMSTemplateManager($conn);
    $preview = $templateManager->renderTemplate($data['template_id'], $data['variables']);

    echo json_encode([
        'success' => true,
        'preview' => $preview
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
