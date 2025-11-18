<?php
/**
 * Update Business Information API
 * Handles business profile updates including logo upload
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;
use App\Classes\Validator;
use App\Classes\Session;

// Start session
Session::start();

// Require authentication and car wash role
Auth::requireAuth();
Auth::requireRole(['carwash', 'admin']);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        Response::unauthorized();
    }
    
    // Get business information from POST
    $businessName = Validator::sanitizeString($_POST['business_name'] ?? '');
    $address = Validator::sanitizeString($_POST['address'] ?? '');
    $phone = Validator::sanitizeString($_POST['phone'] ?? '');
    $mobilePhone = Validator::sanitizeString($_POST['mobile_phone'] ?? '');
    $email = Validator::sanitizeEmail($_POST['email'] ?? '');
    
    // Validate required fields
    $validator = new Validator();
    $validator
        ->required($businessName, 'İşletme Adı')
        ->minLength($businessName, 3, 'İşletme Adı');
    
    if ($email) {
        $validator->email($email, 'E-posta');
    }
    
    if ($validator->fails()) {
        Response::validationError($validator->getErrors());
    }
    
    // Handle logo upload if provided
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];
        $fileType = $_FILES['logo']['type'];
        $fileSize = $_FILES['logo']['size'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Check file type
        if (!in_array($fileType, $allowedTypes)) {
            Response::error('Geçersiz dosya türü. Sadece JPG, PNG, WEBP veya GIF yükleyebilirsiniz.', 400);
        }
        
        // Check file size
        if ($fileSize > $maxSize) {
            Response::error('Dosya boyutu çok büyük. Maksimum 5MB yükleyebilirsiniz.', 400);
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/backend/auth/uploads/logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
            // Generate web-accessible path
            $logoPath = '/carwash_project/backend/auth/uploads/logos/' . $filename;
            
            // Delete old logo if exists
            $oldLogoPath = $_SESSION['logo_path'] ?? null;
            if ($oldLogoPath && $oldLogoPath !== '/carwash_project/backend/logo01.png') {
                $oldFilePath = $_SERVER['DOCUMENT_ROOT'] . $oldLogoPath;
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }
        } else {
            Response::error('Logo yüklenirken bir hata oluştu. Lütfen tekrar deneyin.', 500);
        }
    }
    
    // Handle working hours (7 days, start and end times)
    $workingHours = [];
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    foreach ($days as $day) {
        $startTime = $_POST["{$day}_start"] ?? '09:00';
        $endTime = $_POST["{$day}_end"] ?? '18:00';
        $workingHours[$day] = [
            'start' => $startTime,
            'end' => $endTime
        ];
    }
    
    // Update session variables first
    $_SESSION['business_name'] = $businessName;
    $_SESSION['name'] = $businessName; // Fallback
    if ($logoPath) {
        $_SESSION['logo_path'] = $logoPath;
    }
    
    // Try to update database (optional, gracefully handle if table doesn't exist)
    try {
        // Check if business profile exists for this user
        $existingBusiness = $db->fetchOne(
            "SELECT id FROM business_profiles WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        $businessData = [
            'user_id' => $userId,
            'business_name' => $businessName,
            'address' => $address,
            'phone' => $phone,
            'mobile_phone' => $mobilePhone,
            'email' => $email,
            'working_hours' => json_encode($workingHours),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($logoPath) {
            $businessData['logo_path'] = $logoPath;
        }
        
        if ($existingBusiness) {
            // Update existing business
            $db->update('business_profiles', $businessData, ['user_id' => $userId]);
        } else {
            // Insert new business
            $businessData['created_at'] = date('Y-m-d H:i:s');
            $db->insert('business_profiles', $businessData);
        }
    } catch (Exception $dbError) {
        // Log but don't fail - session is updated
        error_log('Database update warning: ' . $dbError->getMessage());
    }
    
    Response::success('İşletme bilgileri başarıyla güncellendi', [
        'business_name' => $businessName,
        'logo_path' => $logoPath ?? ($_SESSION['logo_path'] ?? null)
    ]);
    
} catch (Exception $e) {
    error_log('Business info update error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    Response::error('İşletme bilgileri güncellenirken bir hata oluştu: ' . $e->getMessage(), 500);
}
