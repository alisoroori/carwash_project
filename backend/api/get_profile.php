<?php

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;

/**
 * Helper: Normalize profile image path to absolute URL
 * @param string|null $path Relative or absolute path from DB
 * @return string Absolute URL or empty string
 */
function normalizeProfileImageUrl($path) {
    if (empty($path)) return '';
    
    // Already absolute URL
    if (preg_match('#^https?://#i', $path)) return $path;
    
    // Build base URL
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
              . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';
    
    // Root-relative path
    if ($path[0] === '/') return $base_url . $path;
    
    // Relative path - prepend base
    return $base_url . '/' . ltrim($path, '/');
}

Auth::requireAuth();

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];

    // Fetch merged profile data from users and user_profiles
    $user = $db->fetchOne("
        SELECT 
            u.id, u.full_name, u.email, u.phone, u.profile_image, u.address, u.username,
            up.city, up.state, up.postal_code, up.country, up.birth_date, up.gender, 
            up.notification_settings, up.preferences, up.profile_image AS profile_img_extended,
            up.phone AS phone_extended, up.home_phone, up.national_id, up.driver_license,
            up.address AS profile_address
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        WHERE u.id = :id
    ", ['id' => $userId]);

    if (!$user || !is_array($user)) {
        Response::notFound('User not found');
    }

    // Merge fields: prefer user_profiles for extended fields, fallback to users
    // Normalize profile_image to absolute URL
    $rawProfileImage = $user['profile_img_extended'] ?? $user['profile_image'] ?? '';
    $absoluteProfileImage = normalizeProfileImageUrl($rawProfileImage);
    
    $profile = [
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'] ?? '',
        'email' => $user['email'],
        'phone' => $user['phone_extended'] ?? $user['phone'],
        'home_phone' => $user['home_phone'],
        'national_id' => $user['national_id'],
        'driver_license' => $user['driver_license'],
        'profile_image' => $absoluteProfileImage,
        'address' => $user['profile_address'] ?? $user['address'],
        'city' => $user['city'],
        'state' => $user['state'],
        'postal_code' => $user['postal_code'],
        'country' => $user['country'],
        'birth_date' => $user['birth_date'],
        'gender' => $user['gender'],
        'notification_settings' => $user['notification_settings'] ? json_decode($user['notification_settings'], true) : null,
        'preferences' => $user['preferences'] ? json_decode($user['preferences'], true) : null,
    ];

    Response::success('Profile retrieved successfully', ['user' => $profile]);

} catch (Exception $e) {
    Response::error('Failed to retrieve profile: ' . $e->getMessage());
}
