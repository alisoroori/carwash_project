<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\auth\register_process.php

/**
 * Customer Registration Processing Script for CarWash Web Application
 * Following project conventions: file-based routing, modular structure
 * Handles form submission from Customer_Registration.php
 */

// Start session following project patterns
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection following project structure
require_once __DIR__ . '/../includes/db.php';

// Only process POST requests - following project routing patterns
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Customer_Registration.php');
    exit();
}

try {
    // Get database connection using project's DB pattern
    $conn = getDBConnection();

    // Sanitize and validate form inputs following project conventions
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'customer';

    // Address information
    $city = $_POST['city'] ?? '';
    $address = trim($_POST['address'] ?? '');

    // Car information
    $car_brand = $_POST['car_brand'] ?? '';
    $car_model = trim($_POST['car_model'] ?? '');
    $car_year = $_POST['car_year'] ?? '';
    $car_color = trim($_POST['car_color'] ?? '');
    $license_plate = trim($_POST['license_plate'] ?? '');

    // Preferences
    $notifications = $_POST['notifications'] ?? [];
    $services = $_POST['services'] ?? [];

    // Validation following project error handling patterns
    $errors = [];

    if (empty($full_name)) {
        $errors[] = 'Ad Soyad alanı zorunludur';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin';
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır';
    }

    if (empty($phone)) {
        $errors[] = 'Telefon numarası zorunludur';
    }

    if (empty($city)) {
        $errors[] = 'Şehir seçimi zorunludur';
    }

    if (empty($car_brand)) {
        $errors[] = 'Araç markası seçimi zorunludur';
    }

    if (empty($car_model)) {
        $errors[] = 'Araç modeli zorunludur';
    }

    if (empty($car_year)) {
        $errors[] = 'Araç yılı seçimi zorunludur';
    }

    if (!isset($_POST['terms'])) {
        $errors[] = 'Kullanım şartlarını kabul etmelisiniz';
    }

    // Check for validation errors
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode('<br>', $errors);
        header('Location: Customer_Registration.php');
        exit();
    }

    // Check if email already exists following project DB patterns
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $_SESSION['error_message'] = 'Bu e-posta adresi zaten kayıtlı';
        header('Location: Customer_Registration.php');
        exit();
    }

    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

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

    // Convert arrays to JSON for storage
    $notifications_json = json_encode($notifications);
    $services_json = json_encode($services);

    // Insert new user following project DB conventions - only use existing columns
    $insert_sql = "INSERT INTO users (
        username,
        full_name, 
        email, 
        password, 
        phone, 
        role
    ) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insert_sql);
    $result = $stmt->execute([
        $username,
        $full_name,
        $email,
        $hashed_password,
        $phone,
        $role
    ]);

    if (!$result) {
        throw new Exception('Kayıt sırasında bir hata oluştu');
    }

    // Get the new user ID
    $user_id = $conn->lastInsertId();

    // Insert customer profile data into separate table
    $profile_sql = "INSERT INTO customer_profiles (
        user_id,
        city, 
        address, 
        car_brand, 
        car_model, 
        car_year, 
        car_color, 
        license_plate,
        notifications,
        preferred_services
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $profile_stmt = $conn->prepare($profile_sql);
    $profile_result = $profile_stmt->execute([
        $user_id,
        $city,
        $address,
        $car_brand,
        $car_model,
        $car_year,
        $car_color,
        $license_plate,
        $notifications_json,
        $services_json
    ]);

    if (!$profile_result) {
        // If profile insert fails, rollback user creation
        $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        throw new Exception('Profil bilgileri kaydedilirken hata oluştu');
    }

    // Set session variables for auto-login following project session patterns
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_email'] = $email;
    $_SESSION['role'] = $role;

    // Set success message and welcome flag following project messaging patterns
    $_SESSION['registration_success'] = true;
    $_SESSION['success_message'] = 'Kayıt işlemi başarıyla tamamlandı! Hoş geldiniz ' . htmlspecialchars($full_name);

    // Log successful registration for admin tracking
    error_log("CarWash new customer registration: " . $email . " (ID: " . $user_id . ")");

    // Redirect to welcome page for first-time experience
    header('Location: welcome.php');
    exit();
} catch (PDOException $e) {
    // Database-specific error handling following project patterns
    error_log("CarWash database error in customer registration: " . $e->getMessage());
    $_SESSION['error_message'] = 'Veritabanı hatası oluştu. Lütfen tekrar deneyin.';
    header('Location: Customer_Registration.php');
    exit();
} catch (Exception $e) {
    // General error handling following project patterns
    error_log("CarWash customer registration error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Kayıt sırasında bir hata oluştu: ' . $e->getMessage();
    header('Location: Customer_Registration.php');
    exit();
}

// Fallback redirect (should never reach here)
header('Location: Customer_Registration.php');
exit();
