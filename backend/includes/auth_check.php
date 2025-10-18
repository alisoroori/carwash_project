<?php
/**
 * Authentication Check File
 * This file checks if a user is logged in and has proper session
 * Used across the application to protect pages that require authentication
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has a specific role
 * @param string $role The role to check for
 * @return bool True if user has the role, false otherwise
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Require login - redirect to login page if not logged in
 * @param string $redirect_url URL to redirect to after login
 */
function requireLogin($redirect_url = null) {
    if (!isLoggedIn()) {
        // Store the current page URL for redirect after login
        if ($redirect_url) {
            $_SESSION['redirect_after_login'] = $redirect_url;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        
        // Redirect to login page
        header('Location: /carwash_project/backend/auth/login.php');
        exit();
    }
}

/**
 * Require specific role - redirect if user doesn't have the role
 * @param string $required_role The role required to access the page
 * @param string $redirect_url URL to redirect to if access denied
 */
function requireRole($required_role, $redirect_url = null) {
    // First check if logged in
    requireLogin();
    
    // Then check if user has the required role
    if (!hasRole($required_role)) {
        // Redirect based on role or to login
        if ($redirect_url) {
            header('Location: ' . $redirect_url);
        } else {
            // Redirect to appropriate dashboard based on current role
            if (hasRole('admin')) {
                header('Location: /carwash_project/backend/dashboard/admin_panel.php');
            } elseif (hasRole('car_wash')) {
                header('Location: /carwash_project/backend/dashboard/Car_Wash_Dashboard.php');
            } elseif (hasRole('customer')) {
                header('Location: /carwash_project/backend/dashboard/Customer_Dashboard.php');
            } else {
                header('Location: /carwash_project/backend/auth/login.php');
            }
        }
        exit();
    }
}

/**
 * Get current user information
 * @return array|null User information or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_name' => $_SESSION['user_name'] ?? null,
        'user_email' => $_SESSION['user_email'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null
    ];
}

/**
 * Check if session is expired
 * @param int $timeout Session timeout in seconds (default: 3600 = 1 hour)
 * @return bool True if session is expired, false otherwise
 */
function isSessionExpired($timeout = 3600) {
    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity'];
        if ($elapsed_time > $timeout) {
            return true;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return false;
}

/**
 * Logout user - destroy session and redirect
 * @param string $redirect_url URL to redirect to after logout
 */
function logout($redirect_url = '/carwash_project/backend/auth/login.php') {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: ' . $redirect_url);
    exit();
}

/**
 * Check if user has permission to access admin features
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is a car wash business
 * @return bool True if user is car wash, false otherwise
 */
function isCarWash() {
    return hasRole('car_wash');
}

/**
 * Check if user is a customer
 * @return bool True if user is customer, false otherwise
 */
function isCustomer() {
    return hasRole('customer');
}

// Auto-check for session expiration
if (isLoggedIn() && isSessionExpired()) {
    logout('/carwash_project/backend/auth/login.php?expired=1');
}
?>
