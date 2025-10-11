<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\auth\register_process.php

require_once '../includes/db.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database connection using the function from db.php
        $conn = getDBConnection();

        // Get and sanitize form data
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');

        // Validate required fields
        if (empty($full_name) || empty($email) || empty($password) || empty($phone)) {
            throw new Exception('لطفاً تمام فیلدهای اجباری را پر کنید.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('فرمت ایمیل نامعتبر است.');
        }

        if (strlen($password) < 6) {
            throw new Exception('رمز عبور باید حداقل ۶ کاراکتر باشد.');
        }

        // Check if email already exists (using PDO syntax from your db.php)
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            throw new Exception('این ایمیل قبلاً ثبت شده است.');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user (adjust columns based on your actual users table structure)
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");

        if (!$stmt->execute([$full_name, $email, $hashedPassword, $phone])) {
            throw new Exception('خطا در ثبت اطلاعات');
        }

        $userId = $conn->lastInsertId();

        // Set session variables for auto-login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_type'] = 'customer';

        // Success message
        $_SESSION['success_message'] = 'ثبت‌نام با موفقیت انجام شد!';

        // Redirect to customer dashboard following your project structure
        header('Location: ../dashboard/customer_dashboard.php');
        exit();
    } catch (Exception $e) {
        // Store error message in session and redirect back to registration form
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: Customer_Registration.php');
        exit();
    }
} else {
    // If accessed directly, redirect to registration form
    header('Location: Customer_Registration.php');
    exit();
}
