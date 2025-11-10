<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
// Ensure session is started for CSRF/session management
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    // Merge JSON body into $_POST so tokens sent in JSON are validated
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) foreach ($data as $k => $v) if (!isset($_POST[$k])) $_POST[$k] = $v;

    // CSRF protection: prefer centralized helper; keep inline fallback during rollout
    $csrf_helper = __DIR__ . '/../../includes/csrf_protect.php';
    if (file_exists($csrf_helper)) {
        require_once $csrf_helper;
        if (function_exists('require_valid_csrf')) {
            // require_valid_csrf() will emit 403 JSON and exit if invalid
            require_valid_csrf();
        }
    } else {
        $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (empty($_SESSION['csrf_token']) || empty($csrfToken) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrfToken)) {
            error_log('CSRF: missing or invalid token in auth/login.php');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
    }

    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('Email and password are required');
    }

    $auth = new Auth($conn);
    $result = $auth->login($data['email'], $data['password']);

    echo json_encode([
        'success' => true,
        'user' => $result['user'],
        'token' => $result['token']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
