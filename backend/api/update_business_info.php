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

        // Decide which table to use: prefer `carwashes` -> `business_profiles` (legacy `carwash_profiles` removed)
        $tableCheck = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('carwashes','business_profiles')");
        $tableCheck->execute();
        $found = $tableCheck->fetchAll(PDO::FETCH_COLUMN, 0);
        $hasCarwashes = in_array('carwashes', $found, true);
        $hasBusinessProfiles = in_array('business_profiles', $found, true);

        if ($hasCarwashes) {
            // Use new canonical `carwashes` table
            $stmt = $pdo->prepare("SELECT id FROM carwashes WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            $params = [
                'name' => $businessName,
                'address' => $address,
                'phone' => $phone,
                'mobile_phone' => $mobilePhone,
                'postal_code' => $postal_code,
                'email' => $email,
                'working_hours' => json_encode($workingHours),
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $userId
            ];

            if ($existing) {
                $set = "name = :name, address = :address, phone = :phone, mobile_phone = :mobile_phone, email = :email, working_hours = :working_hours, updated_at = :updated_at, postal_code = :postal_code";
                if ($logoPath) {
                    $set .= ", logo_path = :logo_path";
                    $params['logo_path'] = $logoPath;
                }
                $sql = "UPDATE carwashes SET {$set} WHERE user_id = :user_id";
                $upd = $pdo->prepare($sql);
                $upd->execute($params);
                $affectedRows = $upd->rowCount();
                if ($affectedRows === 0) {
                    $pdo->rollBack();
                    Response::error('Veritabanına yazılamadı (güncelleme etkilenen satır yok).', 500);
                }
            } else {
                $insertParams = [
                    'user_id' => $userId,
                    'name' => $businessName,
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
                    $insertSql = 'INSERT INTO carwashes (user_id,name,address,phone,mobile_phone,postal_code,email,working_hours,logo_path,created_at,updated_at) VALUES (:user_id,:name,:address,:phone,:mobile_phone,:postal_code,:email,:working_hours,:logo_path,:created_at,:updated_at)';
                    $insertParams['logo_path'] = $logoPath;
                } else {
                    $insertSql = 'INSERT INTO carwashes (user_id,name,address,phone,mobile_phone,postal_code,email,working_hours,created_at,updated_at) VALUES (:user_id,:name,:address,:phone,:mobile_phone,:postal_code,:email,:working_hours,:created_at,:updated_at)';
                }

                $ins = $pdo->prepare($insertSql);
                $ins->execute($insertParams);
                $insertedId = $pdo->lastInsertId();
                $affectedRows = $ins->rowCount() ?: ($insertedId ? 1 : 0);
            }

        } elseif ($hasBusinessProfiles) {
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
            // No known profile table exists in this database
            $pdo->rollBack();
            Response::error('Veritabanında uygun işletme tablosu bulunamadı (carwashes veya business_profiles).', 500);
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
            // Fetch latest from carwashes (preferred) or business_profiles
            if ($hasCarwashes) {
                $fetch = $pdo->prepare("SELECT id, user_id, COALESCE(name,business_name) AS business_name, address, postal_code, COALESCE(phone,contact_phone) AS phone, COALESCE(mobile_phone,NULL) AS mobile_phone, COALESCE(email,contact_email) AS email, COALESCE(working_hours,opening_hours) AS working_hours, COALESCE(logo_path,featured_image) AS logo_path, social_media, created_at, updated_at FROM carwashes WHERE user_id = :user_id LIMIT 1");
                $fetch->execute(['user_id' => $userId]);
                $row = $fetch->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['working_hours'])) {
                    $decoded = json_decode($row['working_hours'], true);
                    $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
                }
            } elseif ($hasBusinessProfiles) {
                $fetch = $pdo->prepare("SELECT id, user_id, business_name, address, postal_code, phone, mobile_phone, email, working_hours, logo_path, created_at, updated_at FROM business_profiles WHERE user_id = :user_id LIMIT 1");
                $fetch->execute(['user_id' => $userId]);
                $row = $fetch->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['working_hours'])) {
                    $decoded = json_decode($row['working_hours'], true);
                    $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
                }
            } else {
                $row = null;
            }
            // If mobile phone is missing, try to extract from social_media JSON if available
            if ($row && empty($row['mobile_phone']) && !empty($row['social_media'])) {
                $sm = json_decode($row['social_media'], true);
                if (is_array($sm)) {
                    foreach (['mobile_phone','mobile','phone','telephone','tel'] as $k) {
                        if (!empty($sm[$k])) { $row['mobile_phone'] = $sm[$k]; break; }
                    }
                    if (empty($row['mobile_phone']) && isset($sm['whatsapp'])) {
                        if (is_array($sm['whatsapp'])) {
                            $row['mobile_phone'] = $sm['whatsapp']['number'] ?? $sm['whatsapp']['phone'] ?? $row['mobile_phone'];
                        } elseif (is_string($sm['whatsapp'])) {
                            $row['mobile_phone'] = $sm['whatsapp'];
                        }
                    }
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

