<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\auth\login_process.php

/**
 * Login Processing Script for CarWash Web Application
 * Following project conventions with database compatibility checks
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

try {
    $conn = getDBConnection();

    // Sanitize inputs following CarWash project patterns
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Validation
    $errors = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin';
    }

    if (empty($password)) {
        $errors[] = 'Şifre alanı zorunludur';
    }

    if (empty($user_type)) {
        $errors[] = 'Hesap türünü seçin';
    }

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode('<br>', $errors);
        header('Location: login.php');
        exit();
    }

    // Check if role column exists in users table
    $check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    $has_role_column = $check_columns->rowCount() > 0;

    // Prepare query based on available columns
    if ($has_role_column) {
        // Use role column if it exists
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $user_type]);
    } else {
        // Fallback: query without role column for backward compatibility
        $stmt = $conn->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
    }

    $user = $stmt->fetch();

    if (!$user) {
        if ($has_role_column) {
            $_SESSION['error_message'] = 'Bu e-posta adresi ve hesap türü ile kayıtlı kullanıcı bulunamadı';
        } else {
            $_SESSION['error_message'] = 'Bu e-posta adresi ile kayıtlı kullanıcı bulunamadı';
        }
        header('Location: login.php');
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error_message'] = 'Şifre yanlış! Lütfen tekrar deneyin.';
        header('Location: login.php');
        exit();
    }

    // If no role column exists, assign role based on user_type
    $user_role = $has_role_column ? $user['role'] : $user_type;

    // For backward compatibility, allow login even if role doesn't match perfectly
    if ($has_role_column && $user['role'] !== $user_type) {
        $_SESSION['error_message'] = 'Seçilen hesap türü ile kullanıcı rolü uyuşmuyor';
        header('Location: login.php');
        exit();
    }

    // Successful login - Set session variables following CarWash project patterns
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user_role;
    $_SESSION['login_time'] = time();

    // Handle remember me functionality
    if ($remember_me) {
        $remember_token = bin2hex(random_bytes(32));
        setcookie('carwash_remember', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', false, true);

        // Update remember token if column exists
        try {
            if ($has_role_column) {
                $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$remember_token, $user['id']]);
            }
        } catch (PDOException $e) {
            // Continue without error if column doesn't exist
            error_log("Remember token update failed: " . $e->getMessage());
        }
    }

    // Update last login if column exists
    try {
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (PDOException $e) {
        // Continue without error if column doesn't exist
        error_log("Last login update failed: " . $e->getMessage());
    }

    // Log successful login
    error_log("CarWash successful login: " . $user['email'] . " (Role: " . $user_role . ", ID: " . $user['id'] . ")");

    // Redirect to appropriate dashboard following CarWash project structure
    switch ($user_role) {
        case 'admin':
            header('Location: ../dashboard/admin_panel.php');
            break;
        case 'carwash':
        case 'car_wash':
            header('Location: ../dashboard/Car_Wash_Dashboard.php');
            break;
        case 'customer':
        default:
            header('Location: ../dashboard/Customer_Dashboard.php');
    }
    exit();
} catch (PDOException $e) {
    error_log("CarWash database error in login: " . $e->getMessage());
    $_SESSION['error_message'] = 'Veritabanı bağlantı hatası oluştu. Lütfen daha sonra tekrar deneyin.';
    header('Location: login.php');
    exit();
} catch (Exception $e) {
    error_log("CarWash login error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Giriş işlemi sırasında beklenmeyen bir hata oluştu. Lütfen tekrar deneyin.';
    header('Location: login.php');
    exit();
}

header('Location: login.php');
exit();
