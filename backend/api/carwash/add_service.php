<?php


require_once '../includes/api_bootstrap.php';


if (session_status() === PHP_SESSION_NONE) session_start();
if (file_exists($csrf_helper)) {
    require_once $csrf_helper;
    if (function_exists('require_valid_csrf')) {
        require_valid_csrf();
    }
} else {
    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (empty($_SESSION['csrf_token']) || empty($csrfToken) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrfToken)) {
        error_log('CSRF: missing or invalid token in carwash/add_service.php');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    // Handle image upload if present
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageUrl = uploadServiceImage($_FILES['image']);
    }

    $stmt = $conn->prepare("
        INSERT INTO services (
            carwash_id, 
            name, 
            description, 
            price, 
            duration, 
            image_url,
            category_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'issdisi',
        $_SESSION['carwash_id'],
        $_POST['name'],
        $_POST['description'],
        $_POST['price'],
        $_POST['duration'],
        $imageUrl,
        $_POST['category_id']
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Service added successfully'
        ]);
    } else {
        throw new Exception('Failed to add service');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
