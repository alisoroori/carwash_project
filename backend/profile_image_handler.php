<?php
// profile_image_handler.php
// Centralized handler to serve user profile images.
require_once __DIR__ . '/includes/bootstrap.php';
use App\Classes\Database;

// Start session if not already
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Determine user id: prefer GET, fallback to session
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null);

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', "\\/");
$defaultRel = '/carwash_project/frontend/images/default-avatar.svg';
$defaultFs = $docRoot . $defaultRel;

function output_default() {
    global $defaultFs;
    if (is_readable($defaultFs)) {
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=60');
        readfile($defaultFs);
        exit;
    }
    // Minimal 1x1 PNG fallback
    header('Content-Type: image/png');
    header('Cache-Control: no-cache');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=');
    exit;
}

if (empty($userId)) {
    output_default();
}

try {
    $db = Database::getInstance();
    $row = $db->fetchOne('SELECT COALESCE(up.profile_image, u.profile_image, NULL) AS img FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = :id LIMIT 1', ['id' => $userId]);
    $img = $row['img'] ?? null;
} catch (Exception $e) {
    error_log('[profile_image_handler] DB error: ' . $e->getMessage());
    output_default();
}

if (empty($img)) {
    output_default();
}

// Remove query string if present
$pathOnly = parse_url($img, PHP_URL_PATH) ?: $img;

// Normalize to filename
$basename = basename($pathOnly);

// Build expected uploads path (project root uploads/profiles/)
$uploadsFs = __DIR__ . '/../uploads/profiles/' . $basename;

// Additional legacy locations to try
$legacyCandidate = __DIR__ . '/auth/uploads/profiles/' . $basename;

$finalFs = null;
if (is_readable($uploadsFs)) {
    $finalFs = $uploadsFs;
} elseif (is_readable($legacyCandidate)) {
    $finalFs = $legacyCandidate;
} else {
    // If img looks like an absolute path under document root, try that
    $possible = $docRoot . $pathOnly;
    if (is_readable($possible)) $finalFs = $possible;
}

if (empty($finalFs) || !is_readable($finalFs)) {
    error_log('[profile_image_handler] File not found or unreadable for user ' . $userId . ': ' . $basename);
    output_default();
}

// Serve the file with proper headers
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $finalFs) ?: 'application/octet-stream';
finfo_close($finfo);

// Cache control: allow short caching; use ?ts= param to bust
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($finalFs));
header('Cache-Control: public, max-age=60');
// Optional: set ETag / Last-Modified
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($finalFs)) . ' GMT');

readfile($finalFs);
exit;

?>
