<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['reportId']) || !isset($data['status'])) {
        throw new Exception('Missing required fields');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update report status
        $stmt = $conn->prepare("
            UPDATE review_reports 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param('si', $data['status'], $data['reportId']);
        $stmt->execute();

        // If resolved, hide the review
        if ($data['status'] === 'resolved') {
            $stmt = $conn->prepare("
                UPDATE reviews r
                JOIN review_reports rr ON r.id = rr.review_id
                SET r.status = 'hidden'
                WHERE rr.id = ?
            ");

            $stmt->bind_param('i', $data['reportId']);
            $stmt->execute();
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Report status updated successfully'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
