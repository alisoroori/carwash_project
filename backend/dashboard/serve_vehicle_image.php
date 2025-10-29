<?php
// Secure image serving endpoint for vehicle images
// Usage: /backend/dashboard/serve_vehicle_image.php?vehicle_id=123

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';

use App\Classes\Auth;
use App\Classes\Session;

// start session
if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// require authentication
if (class_exists(Auth::class) && method_exists(Auth::class, 'requireRole')) {
    Auth::requireRole('customer');
} else {
    if (empty($_SESSION['user_id'])) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

$vid = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
if ($vid <= 0) {
    http_response_code(400);
    echo 'Bad request';
    exit;
}

// get DB
$pdo = null;
if (class_exists(\App\Classes\Database::class)) {
    try {
        $dbw = \App\Classes\Database::getInstance();
        if (method_exists($dbw, 'getPdo')) $pdo = $dbw->getPdo();
        elseif ($dbw instanceof PDO) $pdo = $dbw;
    } catch (Throwable $e) {
        $pdo = null;
    }
}
if (!$pdo && function_exists('getDBConnection')) $pdo = getDBConnection();

if (!$pdo) {
    http_response_code(500);
    echo 'Server error';
    exit;
}

$stmt = $pdo->prepare('SELECT image_path, user_id FROM user_vehicles WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $vid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

// authorize: only owner or admins may see
if ((int)$row['user_id'] !== (int)($_SESSION['user_id'] ?? 0)) {
    // optionally allow admins; skip for now
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$imagePath = $row['image_path'] ?? '';
if (empty($imagePath)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

// Map web path to server path
$webPrefix = '/carwash_project';
$projectRoot = realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../';
$normalized = str_replace('\\', '/', $imagePath);
if (strpos($normalized, $webPrefix) === 0) {
    $rel = substr($normalized, strlen($webPrefix));
    $serverPath = rtrim($projectRoot, '\\/') . $rel;
} else {
    // fallback: assume image_path is relative to backend/uploads
    $serverPath = rtrim($projectRoot, '\\/') . '/backend/uploads/vehicles/' . basename($normalized);
}

$serverPath = str_replace('\\', DIRECTORY_SEPARATOR, $serverPath);

if (!is_file($serverPath) || !is_readable($serverPath)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$mime = mime_content_type($serverPath) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($serverPath));
// cache
header('Cache-Control: public, max-age=86400');
readfile($serverPath);
exit;
