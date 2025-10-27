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

// Validate input parameters
if (!isset($_POST['carwash_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    $carwash_id = filter_var($_POST['carwash_id'], FILTER_SANITIZE_NUMBER_INT);
    $action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);

    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }

    // Get carwash details for email notification
    $stmt = $conn->prepare("
        SELECT c.*, u.email, u.name as owner_name 
        FROM carwash_profiles c
        JOIN users u ON c.owner_id = u.id
        WHERE c.id = ? AND c.status = 'pending'
    ");
    $stmt->bind_param("i", $carwash_id);
    $stmt->execute();
    $carwash = $stmt->get_result()->fetch_assoc();

    if (!$carwash) {
        throw new Exception('Carwash not found or already processed');
    }

    // Update carwash status
    $new_status = $action === 'approve' ? 'active' : 'rejected';
    $stmt = $conn->prepare("
        UPDATE carwash_profiles 
        SET status = ?,
            processed_at = NOW(),
            processed_by = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sii", $new_status, $_SESSION['user_id'], $carwash_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update carwash status');
    }

    // If approved, update user account status
    if ($action === 'approve') {
        $stmt = $conn->prepare("
            UPDATE users 
            SET status = 'active'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $carwash['owner_id']);
        $stmt->execute();
    }

    // Send email notification
    $subject = $action === 'approve'
        ? 'Carwash Başvurunuz Onaylandı'
        : 'Carwash Başvurunuz Reddedildi';

    $message = "Sayın {$carwash['owner_name']},\n\n";
    $message .= $action === 'approve'
        ? "Carwash başvurunuz onaylanmıştır. Artık sisteme giriş yapabilirsiniz."
        : "Üzgünüz, carwash başvurunuz reddedilmiştir. Detaylı bilgi için bizimle iletişime geçebilirsiniz.";

    $headers = 'From: noreply@aquatr.com' . "\r\n" .
        'Reply-To: support@aquatr.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($carwash['email'], $subject, $message, $headers);

    // Log the action
    $stmt = $conn->prepare("
        INSERT INTO admin_logs (
            admin_id,
            action,
            target_type,
            target_id,
            details,
            created_at
        ) VALUES (?, ?, 'carwash', ?, ?, NOW())
    ");
    $details = json_encode([
        'action' => $action,
        'carwash_name' => $carwash['business_name'],
        'owner_name' => $carwash['owner_name']
    ]);
    $stmt->bind_param("isss", $_SESSION['user_id'], $action, $carwash_id, $details);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Carwash status updated successfully',
        'new_status' => $new_status
    ]);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
