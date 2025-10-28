<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\auth\login_process.php

/**
 * Login Processing Script for CarWash Web Application
 * Handles authentication and redirects based on user role
 */

// Load composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Validator;
use App\Classes\Response;
use App\Classes\Database;

// Initialize session
Session::start();

// Redirect if not a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// CSRF protection
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error_message'] = 'Security validation failed. Please try again.';
    header('Location: login.php');
    exit();
}

// Validate inputs
$validator = new Validator();
$validator
    ->required($_POST['email'] ?? null, 'Email')
    ->email($_POST['email'] ?? null, 'Email')
    ->required($_POST['password'] ?? null, 'Password');

// Validate user_type if provided
if (isset($_POST['user_type'])) {
    $validator->required($_POST['user_type'] ?? null, 'Account type');
    $validUserTypes = ['admin', 'carwash', 'customer'];
    if (!in_array($_POST['user_type'], $validUserTypes, true)) {
        $validator->addError('Account type', 'Invalid account type selected.');
    }
}

if ($validator->fails()) {
    $_SESSION['error_message'] = implode('<br>', $validator->getErrors());
    header('Location: login.php');
    exit();
}

// Sanitize inputs
$email = Validator::sanitizeEmail($_POST['email']);
$password = $_POST['password']; // keep raw for verification
$remember_me = isset($_POST['remember_me']);

try {
    // Test database connection before proceeding
    $db = Database::getInstance();
    
    // Use Auth class for login
    $auth = new Auth();
    
    // According to documentation, Auth::login expects (email, password, remember)
    $result = $auth->login($email, $password, $remember_me);
    
    if (!isset($result['success']) || !$result['success']) {
        throw new Exception($result['message'] ?? 'Invalid email or password.');
    }
    
    // Regenerate session ID to prevent session fixation
    Session::regenerate();
    
    // Ensure role is set in session
    if (!isset($_SESSION['role'])) {
        throw new Exception('Authentication error: User role not set.');
    }
    
    // Redirect based on role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../dashboard/admin_panel.php');
            break;
        case 'carwash':
            header('Location: ../dashboard/Car_Wash_Dashboard.php');
            break;
        case 'customer':
        default:
            header('Location: ../dashboard/Customer_Dashboard.php');
            break;
    }
    exit();
} catch (\PDOException $e) {
    // Handle database connection errors
    error_log('Database error during login: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database connection error. Please try again later.';
    header('Location: login.php');
    exit();
} catch (Exception $e) {
    // Handle general errors
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: login.php');
    exit();
}
