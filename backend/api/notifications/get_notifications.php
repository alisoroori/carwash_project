<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/notification_manager.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $notificationManager = new NotificationManager($conn);
    $notifications = $notificationManager->getUserNotifications(
        $_SESSION['user_id'],
        $limit,
        $offset
    );

    $unreadCount = $notificationManager->getUnreadCount($_SESSION['user_id']);

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unreadCount' => $unreadCount
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
