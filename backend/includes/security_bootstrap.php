<?php
/**
 * Security Bootstrap for Legacy Files
 * 
 * Include this file at the top of legacy PHP files to add security features
 * 
 * Usage: require_once __DIR__ . '/../includes/security_bootstrap.php';
 */

// Start autoloading
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Session;
use App\Classes\Validator;

// Secure session initialization
Session::start();

// Set security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com");

/**
 * Generate CSRF token for forms
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    return Session::generateCsrfToken();
}

/**
 * Verify CSRF token from form submission
 * 
 * @param string $token Token from form
 * @return bool True if valid
 */
function verify_csrf_token($token) {
    return Session::verifyCsrfToken($token);
}

/**
 * Clean and validate input
 * 
 * @param string $data Input data
 * @return string Sanitized string
 */
function secure_input($data) {
    return Validator::sanitizeString($data);
}

/**
 * Secure database query (for legacy direct mysqli usage)
 * 
 * @param mysqli $conn MySQLi connection
 * @param string $sql SQL query
 * @param array $params Query parameters
 * @return mysqli_result|bool Query result
 */
function secure_query($conn, $sql, $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        error_log('MySQL prepare error: ' . mysqli_error($conn) . ' - SQL: ' . $sql);
        return false;
    }
    
    if (!empty($params)) {
        $types = '';
        $values = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$values);
    }
    
    $executed = mysqli_stmt_execute($stmt);
    
    if (!$executed) {
        error_log('MySQL execute error: ' . mysqli_stmt_error($stmt) . ' - SQL: ' . $sql);
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Add security layer to legacy files that don't have it
 * 
 * @return void
 */
function require_auth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
        header('Location: /carwash_project/backend/auth/login.php');
        exit;
    }
}

/**
 * Require specific user role
 * 
 * @param string|array $roles Required role(s)
 * @return void
 */
function require_role($roles) {
    require_auth();
    
    $allowed_roles = is_array($roles) ? $roles : [$roles];
    
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        header('Location: /carwash_project/backend/auth/login.php');
        exit;
    }
}

/**
 * Security check for direct access to files
 */
function prevent_direct_access() {
    if (!defined('APP_LOADED')) {
        die('دسترسی مستقیم به این فایل مجاز نیست.');
    }
}