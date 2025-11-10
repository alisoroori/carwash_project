<?php
session_start();
require_once '../includes/db.php';

// NOTE: READ-ONLY GET endpoint
// This endpoint returns payment/order records via GET parameters and does
// not perform state mutation. It's excluded from CSRF checks by design.
// If you add mutating behavior later, call require_valid_csrf() from
// backend/includes/csrf_protect.php and remove this exemption.
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
    $offset = ($page - 1) * $perPage;

    // Build query
    $query = "SELECT * FROM orders WHERE user_id = ?";
    $params = [$_SESSION['user_id']];
    $types = "i";

    // Add filters
    if (isset($_GET['status']) && $_GET['status']) {
        $query .= " AND status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    if (isset($_GET['date']) && $_GET['date']) {
        $query .= " AND DATE(created_at) = ?";
        $params[] = $_GET['date'];
        $types .= "s";
    }

    // Get total count
    $countStmt = $conn->prepare(str_replace("*", "COUNT(*)", $query));
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_row()[0];

    // Get paginated results
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $row['items'] = json_decode($row['items'], true);
        $payments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
