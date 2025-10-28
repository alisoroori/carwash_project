<?php
/**
 * Service Image Upload Handler
 * 
 * Secure file upload for car wash service images
 */

// Load autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\FileUpload;
use App\Classes\Response;
use App\Classes\Validator;

// Start session and check authentication
Session::start();
$auth = new Auth();
$auth->requireRole(['admin', 'car_wash_manager']);

// CSRF protection
if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    Response::error('توکن امنیتی نامعتبر است', 403);
}

// Validate service ID
if (empty($_POST['service_id'])) {
    Response::error('شناسه خدمت الزامی است');
}

$serviceId = Validator::sanitizeInt($_POST['service_id']);

// Create upload handler with path to services directory
$uploadDir = __DIR__ . '/../../../uploads/services';
$uploader = new FileUpload($uploadDir, 'image');

// Set upload requirements
$uploader->setMaxSize(5 * 1024 * 1024); // 5MB max
$uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'webp']);
$uploader->setAllowedTypes(['image/jpeg', 'image/png', 'image/webp']);

// Use service category in subdirectory for organization
if (!empty($_POST['category'])) {
    $category = Validator::sanitizeString($_POST['category']);
    $uploader->setSubDirectory($category);
}

// Process file upload
if (isset($_FILES['service_image'])) {
    // Upload with service ID as filename base
    $result = $uploader->upload($_FILES['service_image'], 'service_' . $serviceId);
    
    if ($result['success']) {
        // Save file path to database
        $db = \App\Classes\Database::getInstance();
        $updated = $db->update('services', 
            ['image_path' => $result['file']['relative_path']], 
            ['id' => $serviceId]
        );
        
        if ($updated) {
            // Return success response
            Response::success('تصویر خدمت با موفقیت آپلود شد', [
                'image_url' => $result['file']['url'],
                'service_id' => $serviceId
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
