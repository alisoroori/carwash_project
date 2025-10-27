<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\auth\Car_Wash_Registration_process_debug.php

/**
 * DEBUG VERSION - Car Wash Registration Processing Script for CarWash Web Application
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/registration_debug.log');

// Start session following project patterns
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>🔍 Car Wash Registration Debug</h2>";
echo "<p><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Include database connection following project structure
require_once __DIR__ . '/../includes/db.php';

// Only process POST requests - following project routing patterns
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p>❌ <strong>Not a POST request - redirecting to registration form</strong></p>";
    echo "<p>This is why the form just refreshes!</p>";
    echo '<a href="Car_Wash_Registration.php">Go to Registration Form</a>';
    exit();
}

echo "<p>✅ <strong>POST request received</strong></p>";

try {
    // Get database connection using project's DB pattern
    $conn = getDBConnection();
    echo "<p>✅ <strong>Database connection successful</strong></p>";
    
    // Show all POST data
    echo "<h3>📝 POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Show FILES data
    echo "<h3>📁 FILES Data:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    // Sanitize and validate form inputs following project conventions
    $business_name = trim($_POST['business_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $tax_number = trim($_POST['tax_number'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    
    echo "<h3>🔍 Field Validation:</h3>";
    echo "<ul>";
    echo "<li>Business Name: " . (!empty($business_name) ? "✅ '$business_name'" : "❌ Empty") . "</li>";
    echo "<li>Email: " . (!empty($email) ? "✅ '$email'" : "❌ Empty") . "</li>";
    echo "<li>Password: " . (!empty($password) ? "✅ Set (" . strlen($password) . " chars)" : "❌ Empty") . "</li>";
    echo "<li>Phone: " . (!empty($phone) ? "✅ '$phone'" : "❌ Empty") . "</li>";
    echo "<li>Tax Number: " . (!empty($tax_number) ? "✅ '$tax_number'" : "❌ Empty") . "</li>";
    echo "<li>License Number: " . (!empty($license_number) ? "✅ '$license_number'" : "❌ Empty") . "</li>";
    echo "</ul>";
    
    // Owner information
    $owner_name = trim($_POST['owner_name'] ?? '');
    $owner_id = trim($_POST['owner_id'] ?? '');
    $owner_phone = trim($_POST['owner_phone'] ?? '');
    $birth_date = $_POST['birth_date'] ?? null;
    
    echo "<h3>👤 Owner Information:</h3>";
    echo "<ul>";
    echo "<li>Owner Name: " . (!empty($owner_name) ? "✅ '$owner_name'" : "❌ Empty") . "</li>";
    echo "<li>Owner ID: " . (!empty($owner_id) ? "✅ '$owner_id'" : "❌ Empty") . "</li>";
    echo "<li>Owner Phone: " . (!empty($owner_phone) ? "✅ '$owner_phone'" : "❌ Empty") . "</li>";
    echo "<li>Birth Date: " . (!empty($birth_date) ? "✅ '$birth_date'" : "❌ Empty") . "</li>";
    echo "</ul>";
    
    // Location information
    $city = $_POST['city'] ?? '';
    $district = trim($_POST['district'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    echo "<h3>📍 Location Information:</h3>";
    echo "<ul>";
    echo "<li>City: " . (!empty($city) ? "✅ '$city'" : "❌ Empty") . "</li>";
    echo "<li>District: " . (!empty($district) ? "✅ '$district'" : "❌ Empty") . "</li>";
    echo "<li>Address: " . (!empty($address) ? "✅ '$address'" : "❌ Empty") . "</li>";
    echo "</ul>";
    
    // Terms checkbox
    $terms = isset($_POST['terms']) ? 'checked' : 'not checked';
    echo "<h3>📋 Terms:</h3>";
    echo "<p>Terms checkbox: " . (isset($_POST['terms']) ? "✅ Accepted" : "❌ Not accepted") . "</p>";
    
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
    
    echo "<h3>🚨 Validation Results:</h3>";
    if (!empty($errors)) {
        echo "<div style='color: red;'>";
        echo "<p><strong>❌ Validation failed with " . count($errors) . " errors:</strong></p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        echo "<h3>🔄 This would redirect back to registration form</h3>";
        echo "<p>Errors would be stored in session and displayed on the form.</p>";
        
    } else {
        echo "<p style='color: green;'><strong>✅ All validation passed! Registration would proceed.</strong></p>";
        
        // Check if email already exists
        echo "<h3>📧 Email Check:</h3>";
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            echo "<p style='color: red;'>❌ Email already exists in database</p>";
        } else {
            echo "<p style='color: green;'>✅ Email is available</p>";
        }
        
        echo "<h3>🏪 Business Name Check:</h3>";
    $stmt = $conn->prepare("SELECT id FROM carwash_profiles WHERE business_name = ?");
        $stmt->execute([$business_name]);
        
        if ($stmt->fetch()) {
            echo "<p style='color: red;'>❌ Business name already exists</p>";
        } else {
            echo "<p style='color: green;'>✅ Business name is available</p>";
        }
        
        echo "<h3>✅ Registration Process Complete</h3>";
        echo "<p>In normal operation, this would:</p>";
        echo "<ul>";
        echo "<li>1. Hash the password</li>";
        echo "<li>2. Generate username from email</li>";
        echo "<li>3. Insert user record</li>";
        echo "<li>4. Insert carwash business record</li>";
        echo "<li>5. Set session variables</li>";
        echo "<li>6. Set registration_success flag</li>";
        echo "<li>7. Redirect to welcome.php</li>";
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>❌ Database error:</strong> " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ General error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🔗 Navigation</h3>";
echo '<a href="Car_Wash_Registration.php">Back to Registration Form</a> | ';
echo '<a href="debug_registration.php">Simple Debug Form</a>';
?>
