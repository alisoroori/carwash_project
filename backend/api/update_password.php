<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    // Validate input
    if (!isset($_POST['currentPassword']) || !isset($_POST['newPassword']) || !isset($_POST['confirmPassword'])) {
        throw new Exception('Tüm alanları doldurun');
    }

    if ($_POST['newPassword'] !== $_POST['confirmPassword']) {
        throw new Exception('Yeni şifreler eşleşmiyor');
    }

    // Check current password
    $stmt = $conn->prepare("
        SELECT password 
        FROM users 
        WHERE id = ?
    ");

    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!password_verify($_POST['currentPassword'], $user['password'])) {
        throw new Exception('Mevcut şifre yanlış');
    }

    // Update password
    $newPasswordHash = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users 
        SET password = ? 
        WHERE id = ?
    ");

    $stmt->bind_param('si', $newPasswordHash, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Şifre başarıyla güncellendi'
        ]);
    } else {
        throw new Exception('Şifre güncellenirken bir hata oluştu');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
