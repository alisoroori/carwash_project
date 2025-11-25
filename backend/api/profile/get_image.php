<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
use App\Classes\Response;
use App\Classes\Auth;

// Allow only authenticated users to get their profile image
Auth::requireAuth();

// Determine profile image from session keys (same logic as header)
$base_url = $base_url ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';
$default = $base_url . '/frontend/images/default-avatar.svg';
$profile = null;
if (!empty($_SESSION['user']) && !empty($_SESSION['user']['profile_image'])) {
    $profile = $_SESSION['user']['profile_image'];
} elseif (!empty($_SESSION['profile_image'])) {
    $profile = $_SESSION['profile_image'];
}

if (empty($profile)) {
    $image = $default;
} else {
    // If relative filename, make canonical uploads path
    if (!preg_match('#^(https?://)#i', $profile) && strpos($profile, '/') !== 0) {
        $image = $base_url . '/backend/auth/uploads/profiles/' . basename($profile);
    } else {
        // If path is absolute or URL, normalize to base if needed
        if (strpos($profile, 'http') !== 0 && strpos($profile, '/') === 0) {
            $image = rtrim($base_url, '/') . $profile;
        } else {
            $image = $profile;
        }
    }
}

// Append ts if set in session to force cache-bust
$ts = !empty($_SESSION['profile_image_ts']) ? intval($_SESSION['profile_image_ts']) : time();
if (strpos($image, '?') === false) $image .= '?ts=' . $ts; else $image .= '&ts=' . $ts;

Response::success('OK', ['image' => $image]);
