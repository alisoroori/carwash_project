<?php
/**
 * Profile Image Upload Handler
 * 
 * Secure file upload for user profile images
 */

// Load autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\FileUpload;
use App\Classes\Response;
use App\Classes\Validator;

// Start session and check authentication
Session::start();
$auth = new Auth();
$auth->requireAuth();

// CSRF protection
if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    Response::error('توکن امنیتی نامعتبر است', 403);
}

// Create upload handler with path to profiles directory
$uploadDir = __DIR__ . '/uploads/profiles';
$uploader = new FileUpload($uploadDir, 'image');

// Set upload requirements
$uploader->setMaxSize(2 * 1024 * 1024); // 2MB max
$uploader->setAllowedExtensions(['jpg', 'jpeg', 'png']);
$uploader->setAllowedTypes(['image/jpeg', 'image/png']);

// Use user ID in subdirectory for organization
$userId = Session::get('user_id');
$uploader->setSubDirectory('user_' . $userId);

// Process file upload
if (isset($_FILES['profile_image'])) {
    // Upload with sanitized user ID as filename base
    $result = $uploader->upload($_FILES['profile_image'], 'profile_' . Validator::sanitizeInt($userId));
    
    if ($result['success']) {
        // Save file path to database
        $db = \App\Classes\Database::getInstance();
        $updated = $db->update('users', 
            ['profile_image' => $result['file']['relative_path']], 
            ['id' => $userId]
        );
        
        if ($updated) {
            // Set in session for immediate use
            Session::set('profile_image', $result['file']['url']);
            
            // Return success response
            Response::success('تصویر پروفایل با موفقیت به‌روز شد', [
                'image_url' => $result['file']['url']
            ]);
        } else {
            Response::error('خطا در ذخیره مسیر فایل در پایگاه داده');
        }
    } else {
        Response::error($result['errors'][0]);
    }
} else {
    Response::error('فایلی برای آپلود انتخاب نشده است');
}
