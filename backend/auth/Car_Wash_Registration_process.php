<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\auth\Car_Wash_Registration_process.php

/**
 * Car Wash Registration Processing Script for CarWash Web Application
 * Following project conventions: file-based routing, modular structure
 * Handles form submission from Car_Wash_Registration.php
 */

// Start session following project patterns
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection following project structure
require_once __DIR__ . '/../includes/db.php';

// Only process POST requests - following project routing patterns
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Car_Wash_Registration.php');
    exit();
}

// CSRF protection: verify token exists and matches session
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['error_message'] = 'Geçersiz CSRF tokeni. Lütfen sayfayı yenileyin ve tekrar deneyin.';
    header('Location: Car_Wash_Registration.php');
    exit();
}

try {
    // Get database connection using project's DB pattern
    $conn = getDBConnection();
    
    // Sanitize and validate form inputs following project conventions
    $business_name = trim($_POST['business_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $tax_number = trim($_POST['tax_number'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    
    // Owner information
    $owner_name = trim($_POST['owner_name'] ?? '');
    $owner_id = trim($_POST['owner_id'] ?? '');
    $owner_phone = trim($_POST['owner_phone'] ?? '');
    $birth_date = $_POST['birth_date'] ?? null;
    
    // Location information
    $city = $_POST['city'] ?? '';
    $district = trim($_POST['district'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Services and pricing
    $services = $_POST['services'] ?? [];
    $exterior_price = (float)($_POST['exterior_price'] ?? 0);
    $interior_price = (float)($_POST['interior_price'] ?? 0);
    $detailing_price = (float)($_POST['detailing_price'] ?? 0);
    
    // Business details
    $opening_time = $_POST['opening_time'] ?? '';
    $closing_time = $_POST['closing_time'] ?? '';
    $capacity = (int)($_POST['capacity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    // Validation following project error handling patterns
    $errors = [];
    
    if (empty($business_name)) {
        $errors[] = 'İşletme adı zorunludur';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır';
    }
    
    if (empty($phone)) {
        $errors[] = 'İşletme telefonu zorunludur';
    }
    
    if (empty($tax_number)) {
        $errors[] = 'Vergi numarası zorunludur';
    }
    
    if (empty($license_number)) {
        $errors[] = 'Ruhsat numarası zorunludur';
    }
    
    if (empty($owner_name)) {
        $errors[] = 'İşletme sahibi adı zorunludur';
    }
    
    if (empty($owner_id) || strlen($owner_id) !== 11) {
        $errors[] = 'Geçerli bir TC kimlik numarası girin (11 hane)';
    }
    
    if (empty($city)) {
        $errors[] = 'Şehir seçimi zorunludur';
    }
    
    if (empty($district)) {
        $errors[] = 'İlçe bilgisi zorunludur';
    }
    
    if (empty($address)) {
        $errors[] = 'Adres detayları zorunludur';
    }
    
    if (!isset($_POST['terms'])) {
        $errors[] = 'Kullanım şartlarını kabul etmelisiniz';
    }
    
    // Check for validation errors
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode('<br>', $errors);
        header('Location: Car_Wash_Registration.php');
        exit();
    }
    
    // Check if email already exists following project DB patterns
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $_SESSION['error_message'] = 'Bu e-posta adresi zaten kayıtlı';
        header('Location: Car_Wash_Registration.php');
        exit();
    }
    
    // Check if business name already exists in canonical `carwashes` table
    $stmt = $conn->prepare("SELECT id FROM carwashes WHERE name = ?");
    $stmt->execute([$business_name]);

    if ($stmt->fetch()) {
        $_SESSION['error_message'] = 'Bu işletme adı zaten kayıtlı';
        header('Location: Car_Wash_Registration.php');
        exit();
    }
    
    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Handle file uploads following project upload patterns
    $profile_image = null;
    $logo_image = null;
    $upload_dir = __DIR__ . '/uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $profile_image = 'profile_' . uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $profile_image);
        }
    }
    
    // Handle logo image upload
    if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $logo_image = 'logo_' . uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['logo_image']['tmp_name'], $upload_dir . $logo_image);
        }
    }
    
    // Begin transaction for data integrity
    $conn->beginTransaction();
    
    try {
        // Generate unique username from email
        $username = strtolower(explode('@', $email)[0]);
        $base_username = $username;
        $counter = 1;
        
        // Check if username exists and modify if needed
        while (true) {
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->execute([$username]);
            if (!$check_stmt->fetch()) {
                break; // Username is available
            }
            $username = $base_username . $counter;
            $counter++;
        }

        // Insert user record following project DB conventions
        $user_sql = "INSERT INTO users (
            username,
            full_name, 
            email, 
            password, 
            phone, 
            role, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'carwash', NOW())";
        
        $stmt = $conn->prepare($user_sql);
        $stmt->execute([$username, $business_name, $email, $hashed_password, $phone]);
        $user_id = $conn->lastInsertId();
        
        // Insert carwash business record into canonical `carwashes` table
        // Map the most common fields; optional/extra fields can be added to the target schema via migration
        $carwash_sql = "INSERT INTO carwashes (
            user_id,
            name,
            email,
            phone,
            city,
            district,
            address,
            logo_path,
            created_at,
            updated_at,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'pending')";

        $stmt = $conn->prepare($carwash_sql);
        $stmt->execute([
            $user_id,
            $business_name,
            $email,
            $phone,
            $city,
            $district,
            $address,
            $logo_image ? '/carwash_project/backend/uploads/' . $logo_image : null
        ]);

        $carwash_id = $conn->lastInsertId();
        
        // Insert services if services table exists
        if (!empty($services)) {
            try {
                foreach ($services as $service) {
                    $price = 0;
                    switch ($service) {
                        case 'exterior':
                            $price = $exterior_price;
                            break;
                        case 'interior':
                            $price = $interior_price;
                            break;
                        case 'detailing':
                            $price = $detailing_price;
                            break;
                    }
                    
                    $service_sql = "INSERT INTO services (carwash_id, name, price, status) VALUES (?, ?, ?, 'active')";
                    $stmt = $conn->prepare($service_sql);
                    $stmt->execute([$carwash_id, $service, $price]);
                }
            } catch (PDOException $e) {
                // Services table might not exist, continue without error
                error_log("Services insertion failed: " . $e->getMessage());
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set session variables for auto-login following project session patterns
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $business_name;
        $_SESSION['user_email'] = $email;
        $_SESSION['role'] = 'carwash';
        $_SESSION['carwash_id'] = $carwash_id;
        // If a logo was uploaded, expose its web path in the session so headers/sidebars can use it
        if (!empty($logo_image)) {
            // Store web-accessible path (keeps behaviour consistent with other handlers)
            $_SESSION['logo_path'] = '/carwash_project/backend/uploads/' . $logo_image;
        }
        
        // Set success message following project messaging patterns
        $_SESSION['registration_success'] = true;
        $_SESSION['success_message'] = 'İşletme kaydınız başarıyla tamamlandı! Onay sürecinde olan başvurunuz değerlendirmeye alınmıştır.';
        
        // Log successful registration
        error_log("CarWash new business registration: " . $email . " (Business: " . $business_name . ", ID: " . $carwash_id . ")");
        
        // Redirect to welcome page for first-time experience
        header('Location: welcome.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (PDOException $e) {
    // Database-specific error handling following project patterns
    error_log("CarWash database error in car wash registration: " . $e->getMessage());
    
    // Show more specific error messages for debugging
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $_SESSION['error_message'] = 'Veritabanı şeması uyumsuzluğu: ' . $e->getMessage() . '<br><a href="apply_schema_fix.php">Şemayı düzelt</a>';
    } elseif (strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
        $_SESSION['error_message'] = 'Veritabanı tablosu bulunamadı: ' . $e->getMessage();
    } else {
        $_SESSION['error_message'] = 'Veritabanı hatası: ' . $e->getMessage();
    }
    
    header('Location: Car_Wash_Registration.php');
    exit();
    
} catch (Exception $e) {
    // General error handling following project patterns
    error_log("CarWash car wash registration error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Kayıt sırasında bir hata oluştu: ' . $e->getMessage();
    header('Location: Car_Wash_Registration.php');
    exit();
}

// Fallback redirect (should never reach here)
header('Location: Car_Wash_Registration.php');
exit();
?>
