<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/payment_gateway.php';

header('Content-Type: application/json');
// Merge JSON body into $_POST so tokens sent in JSON are validated
$raw = file_get_contents('php://input');
$parsed = json_decode($raw, true);
if (is_array($parsed)) foreach ($parsed as $k => $v) if (!isset($_POST[$k])) $_POST[$k] = $v;

// CSRF protection: prefer centralized helper; fallback kept during rollout
$csrf_helper = __DIR__ . '/../includes/csrf_protect.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (file_exists($csrf_helper)) {
    require_once $csrf_helper;
    if (function_exists('require_valid_csrf')) {
        // require_valid_csrf() will emit 403 JSON and exit if invalid
        require_valid_csrf();
    }
} else {
    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (empty($_SESSION['csrf_token']) || empty($csrfToken) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrfToken)) {
        error_log('CSRF: missing or invalid token in initiate_payment.php');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }
    // Get order data from request (merged above)
    $orderData = is_array($parsed) ? $parsed : json_decode(file_get_contents('php://input'), true);
    if (!$orderData) {
        throw new Exception('Invalid order data');
    }

    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $orderData['user'] = $user;

    // Initialize payment gateway
    $gateway = new PaymentGateway($conn);
    $result = $gateway->createPayment($orderData);

    if ($result['status'] === 'success') {
        // Save payment attempt
        $stmt = $conn->prepare("
            INSERT INTO payment_attempts (
                order_id,
                user_id,
                amount,
                payment_id,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, 'pending', NOW())
        ");

        $paymentId = $result['payment_id'];
        $stmt->bind_param(
            'iids',
            $orderData['order_id'],
            $_SESSION['user_id'],
            $orderData['total'],
            $paymentId
        );
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'paymentUrl' => isset($result['payment_url']) ? $result['payment_url'] : '',
            'paymentId' => $paymentId
        ]);
    } else {
        throw new Exception(isset($result['error_message']) ? $result['error_message'] : 'Payment failed');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
