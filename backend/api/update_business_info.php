<?php
/**
 * Update Business Information API
 * Handles business profile updates including logo upload
 */

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;
use App\Classes\FileUploader;
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
        $uploader = new FileUploader();
        $uploadResult = $uploader->upload($_FILES['logo'], 'logos');
        
        if ($uploadResult['success']) {
            $logoPath = $uploadResult['path'];
            
            // Delete old logo if exists
            $oldLogoPath = $_SESSION['logo_path'] ?? null;
            if ($oldLogoPath && file_exists($_SERVER['DOCUMENT_ROOT'] . $oldLogoPath)) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $oldLogoPath);
            }
        } else {
            Response::error($uploadResult['message'] ?? 'Logo yüklenirken bir hata oluştu', 400);
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
    
    // Update or insert business information
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
    
    // Update session variables
    $_SESSION['business_name'] = $businessName;
    if ($logoPath) {
        $_SESSION['logo_path'] = $logoPath;
    }
    
    Response::success('İşletme bilgileri başarıyla güncellendi', [
        'business_name' => $businessName,
        'logo_path' => $logoPath ?? ($_SESSION['logo_path'] ?? null)
    ]);
    
} catch (Exception $e) {
    error_log('Business info update error: ' . $e->getMessage());
    Response::error('İşletme bilgileri güncellenirken bir hata oluştu', 500);
}
