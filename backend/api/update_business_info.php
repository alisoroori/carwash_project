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
    $postal_code = Validator::sanitizeString($_POST['postal_code'] ?? '');
    
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

    // Validate required fields (ensure address and phone are provided)
    if (empty($address) || empty($phone)) {
        Response::validationError([
            'address' => empty($address) ? 'Adres gereklidir' : null,
            'phone' => empty($phone) ? 'Telefon numarası gereklidir' : null
        ]);
    }

    // Use direct PDO with exceptions so SQL errors are returned to caller
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        $pdo->beginTransaction();

        $affectedRows = 0;
        $insertedId = null;

        // Decide which table to use: prefer `business_profiles` if it exists, else use `carwash_profiles`
        $tableCheck = $pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tbl");
        $tableCheck->execute(['tbl' => 'business_profiles']);
        $hasBusinessProfiles = (int)$tableCheck->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

        if ($hasBusinessProfiles) {
            // Use business_profiles table (existing logic)
            $stmt = $pdo->prepare("SELECT id FROM business_profiles WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $existingBusiness = $stmt->fetch(PDO::FETCH_ASSOC);

            // Prepare common params
            $params = [
                'business_name' => $businessName,
                'address' => $address,
                'phone' => $phone,
                'mobile_phone' => $mobilePhone,
                'postal_code' => $postal_code,
                'email' => $email,
                'working_hours' => json_encode($workingHours),
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $userId
            ];

            if ($existingBusiness) {
                $set = "business_name = :business_name, address = :address, phone = :phone, mobile_phone = :mobile_phone, email = :email, working_hours = :working_hours, updated_at = :updated_at";
                $set .= ", postal_code = :postal_code";
                if ($logoPath) {
                    $set .= ", logo_path = :logo_path";
                    $params['logo_path'] = $logoPath;
                }
                $sql = "UPDATE business_profiles SET {$set} WHERE user_id = :user_id";
                $upd = $pdo->prepare($sql);
                $upd->execute($params);
                $affectedRows = $upd->rowCount();
                if ($affectedRows === 0) {
                    // No rows updated - treat as failure (do not commit)
                    $pdo->rollBack();
                    Response::error('Veritabanına yazılamadı (güncelleme etkilenen satır yok).', 500);
                }
            } else {
                // Insert new business profile
                $insertParams = [
                    'user_id' => $userId,
                    'business_name' => $businessName,
                    'address' => $address,
                    'phone' => $phone,
                    'mobile_phone' => $mobilePhone,
                    'postal_code' => $postal_code,
                    'email' => $email,
                    'working_hours' => json_encode($workingHours),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($logoPath) {
                    $insertSql = 'INSERT INTO business_profiles (user_id,business_name,address,phone,mobile_phone,postal_code,email,working_hours,logo_path,created_at,updated_at) VALUES (:user_id,:business_name,:address,:phone,:mobile_phone,:postal_code,:email,:working_hours,:logo_path,:created_at,:updated_at)';
                    $insertParams['logo_path'] = $logoPath;
                } else {
                    $insertSql = 'INSERT INTO business_profiles (user_id,business_name,address,phone,mobile_phone,postal_code,email,working_hours,created_at,updated_at) VALUES (:user_id,:business_name,:address,:phone,:mobile_phone,:postal_code,:email,:working_hours,:created_at,:updated_at)';
                }

                $ins = $pdo->prepare($insertSql);
                $ins->execute($insertParams);
                $insertedId = $pdo->lastInsertId();
                $affectedRows = $ins->rowCount() ?: ($insertedId ? 1 : 0);
            }

        } else {
            // business_profiles table not present. Fall back to carwash_profiles and map fields.
            $stmt = $pdo->prepare("SELECT id FROM carwash_profiles WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            // Map fields: business_name -> business_name, address -> address,
            // phone -> contact_phone, email -> contact_email, working_hours -> opening_hours, logo -> featured_image
            $params = [
                'business_name' => $businessName,
                'address' => $address,
                'contact_phone' => $phone,
                'contact_email' => $email,
                'postal_code' => $postal_code,
                'opening_hours' => json_encode($workingHours),
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $userId
            ];

            if ($existing) {
                // Preserve or merge social_media so we can store the mobile phone even when schema lacks mobile_phone
                $smStmt = $pdo->prepare("SELECT social_media FROM carwash_profiles WHERE user_id = :user_id LIMIT 1");
                $smStmt->execute(['user_id' => $userId]);
                $smRow = $smStmt->fetch(PDO::FETCH_ASSOC);
                $socialMedia = [];
                if ($smRow && !empty($smRow['social_media'])) {
                    $decodedSM = json_decode($smRow['social_media'], true);
                    if (is_array($decodedSM)) $socialMedia = $decodedSM;
                }

                if (!empty($mobilePhone)) {
                    $socialMedia['mobile_phone'] = $mobilePhone;
                }

                $params['social_media'] = json_encode($socialMedia);

                $set = "business_name = :business_name, address = :address, contact_phone = :contact_phone, contact_email = :contact_email, opening_hours = :opening_hours, social_media = :social_media, updated_at = :updated_at";
                $set .= ", postal_code = :postal_code";
                if ($logoPath) {
                    $set .= ", featured_image = :featured_image";
                    $params['featured_image'] = $logoPath;
                }
                $sql = "UPDATE carwash_profiles SET {$set} WHERE user_id = :user_id";
                $upd = $pdo->prepare($sql);
                $upd->execute($params);
                $affectedRows = $upd->rowCount();
                if ($affectedRows === 0) {
                    $pdo->rollBack();
                    Response::error('Veritabanına yazılamadı (güncelleme etkilenen satır yok).', 500);
                }
            } else {
                // Insert minimal carwash_profiles row (city is NOT NULL in schema, provide empty string)
                $socialMedia = [];
                if (!empty($mobilePhone)) $socialMedia['mobile_phone'] = $mobilePhone;

                $insertParams = [
                    'user_id' => $userId,
                    'business_name' => $businessName,
                    'address' => $address,
                    'city' => '',
                    'postal_code' => $postal_code,
                    'contact_phone' => $phone,
                    'contact_email' => $email,
                    'opening_hours' => json_encode($workingHours),
                    'social_media' => json_encode($socialMedia),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($logoPath) {
                    $insertSql = 'INSERT INTO carwash_profiles (user_id,business_name,address,city,postal_code,contact_phone,contact_email,opening_hours,social_media,featured_image,created_at,updated_at) VALUES (:user_id,:business_name,:address,:city,:postal_code,:contact_phone,:contact_email,:opening_hours,:social_media,:featured_image,:created_at,:updated_at)';
                    $insertParams['featured_image'] = $logoPath;
                } else {
                    $insertSql = 'INSERT INTO carwash_profiles (user_id,business_name,address,city,postal_code,contact_phone,contact_email,opening_hours,social_media,created_at,updated_at) VALUES (:user_id,:business_name,:address,:city,:postal_code,:contact_phone,:contact_email,:opening_hours,:social_media,:created_at,:updated_at)';
                }

                $ins = $pdo->prepare($insertSql);
                $ins->execute($insertParams);
                $insertedId = $pdo->lastInsertId();
                $affectedRows = $ins->rowCount() ?: ($insertedId ? 1 : 0);
            }
        }

        // If we get here, at least one row was affected or inserted
        $pdo->commit();

        // Fetch the up-to-date record to return to the client
        if ($hasBusinessProfiles) {
            $fetch = $pdo->prepare("SELECT id, user_id, business_name, address, phone, mobile_phone, email, working_hours, logo_path, created_at, updated_at FROM business_profiles WHERE user_id = :user_id LIMIT 1");
            $fetch->execute(['user_id' => $userId]);
            $row = $fetch->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['working_hours'])) {
                $decoded = json_decode($row['working_hours'], true);
                $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
            }
        } else {
            $fetch = $pdo->prepare("SELECT id, user_id, business_name, address, postal_code, city, contact_phone AS phone, contact_email AS email, opening_hours AS working_hours, social_media, featured_image AS logo_path, created_at, updated_at FROM carwash_profiles WHERE user_id = :user_id LIMIT 1");
            $fetch->execute(['user_id' => $userId]);
            $row = $fetch->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['working_hours'])) {
                $decoded = json_decode($row['working_hours'], true);
                $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
            }
            // If mobile phone is not a dedicated column, try to extract from social_media JSON
            if ($row && empty($row['phone']) && !empty($row['social_media'])) {
                $sm = json_decode($row['social_media'], true);
                if (is_array($sm) && !empty($sm['mobile_phone'])) {
                    $row['mobile_phone'] = $sm['mobile_phone'];
                }
            } elseif ($row && !empty($row['social_media'])) {
                $sm = json_decode($row['social_media'], true);
                if (is_array($sm) && !empty($sm['mobile_phone'])) {
                    $row['mobile_phone'] = $sm['mobile_phone'];
                }
            }
        }

        // If we couldn't fetch the row, return an error
        if (!$row) {
            Response::error('Güncellenen kayıt veritabanından alınamadı.', 500);
        }

        // Return the updated record
        Response::success('İşletme bilgileri başarıyla güncellendi', ['data' => $row]);

    } catch (\PDOException $pdoEx) {
        // Rollback and return real SQL error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Business info DB error: ' . $pdoEx->getMessage());
        error_log('Business info DB trace: ' . $pdoEx->getTraceAsString());
        // Return error to client (do not expose sensitive SQL details in production)
        Response::error('Veritabanı hatası: ' . $pdoEx->getMessage(), 500);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Business info error: ' . $e->getMessage());
        Response::error('İşletme bilgileri güncellenirken bir hata oluştu: ' . $e->getMessage(), 500);
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

