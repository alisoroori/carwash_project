<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Session;

Session::start();
Auth::requireAuth();
// allow carwash or admin roles
if (!Auth::hasRole('carwash') && !Auth::hasRole('admin')) {
    Response::unauthorized();
}

$dir = __DIR__ . '/../../uploads/business_logo/';
$files = [];
if (is_dir($dir)) {
    $dh = opendir($dir);
    while (($f = readdir($dh)) !== false) {
        if ($f === '.' || $f === '..') continue;
        $path = $dir . $f;
        if (!is_file($path)) continue;
        $files[] = [
            'name' => $f,
            'size' => filesize($path),
            'mtime' => date('c', filemtime($path)),
            'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project/backend/uploads/business_logo/' . rawurlencode($f),
        ];
    }
    closedir($dh);
}

Response::success('Business logo files', ['files' => $files]);
