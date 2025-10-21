<?php
/**
 * Authentication Class (PSR-4 Autoloaded)
 * User authentication and authorization logic
 * 
 * @package App\Classes
 * @namespace App\Classes
 */

namespace App\Classes;

use App\Classes\Database;
use App\Classes\Session;

class Auth {
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        Session::start();
    }
    
    /**
     * Register new user
     * 
     * @param array $data User registration data
     * @return array ['success' => bool, 'message' => string, 'user_id' => int]
     */
    public function register($data) {
        try {
            // Validate required fields
            $required = ['name', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => ucfirst($field) . ' الزامی است'
                    ];
                }
            }
            
            // Check if email already exists
            if ($this->db->exists('users', ['email' => $data['email']])) {
                return [
                    'success' => false,
                    'message' => 'این ایمیل قبلاً ثبت شده است'
                ];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Prepare user data
            $userData = [
                'name' => htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8'),
                'email' => filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL),
                'phone' => !empty($data['phone']) ? htmlspecialchars(trim($data['phone']), ENT_QUOTES, 'UTF-8') : null,
                'password' => $hashedPassword,
                'role' => $data['role'] ?? 'customer',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert user
            $userId = $this->db->insert('users', $userData);
            
            return [
                'success' => true,
                'message' => 'ثبت‌نام با موفقیت انجام شد! اکنون می‌توانید وارد شوید.',
                'user_id' => $userId
            ];
            
        } catch (\Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'ثبت‌نام ناموفق بود. لطفاً بعداً تلاش کنید.'
            ];
        }
    }
    
    /**
     * Login user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array ['success' => bool, 'message' => string, 'user' => array]
     */
    public function login($email, $password) {
        try {
            // Validate inputs
            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'ایمیل و رمز عبور الزامی است'
                ];
            }
            
            // Fetch user by email
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE email = :email AND status = 'active'",
                ['email' => $email]
            );
            
            // Check if user exists
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'ایمیل یا رمز عبور نادرست است'
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'ایمیل یا رمز عبور نادرست است'
                ];
            }
            
            // Set session data
            Session::set('user_id', $user['id']);
            Session::set('name', $user['name']);
            Session::set('email', $user['email']);
            Session::set('role', $user['role']);
            Session::regenerate();
            
            // Update last login timestamp
            $this->db->update(
                'users',
                ['last_login' => date('Y-m-d H:i:s')],
                ['id' => $user['id']]
            );
            
            return [
                'success' => true,
                'message' => 'ورود موفقیت‌آمیز',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
            
        } catch (\Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'ورود ناموفق بود. لطفاً بعداً تلاش کنید.'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        Session::destroy();
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public function isAuthenticated() {
        return Session::isLoggedIn();
    }
    
    /**
     * Check if user has specific role
     * 
     * @param string|array $roles Role(s) to check
     * @return bool
     */
    public function hasRole($roles) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $userRole = Session::getUserRole();
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        
        return $userRole === $roles;
    }
    
    /**
     * Require authentication (redirect if not logged in)
     * 
     * @param string $redirectUrl Redirect URL
     */
    public function requireAuth($redirectUrl = '/carwash_project/backend/auth/login.php') {
        if (!$this->isAuthenticated()) {
            header("Location: {$redirectUrl}");
            exit;
        }
    }
    
    /**
     * Require specific role (redirect if unauthorized)
     * 
     * @param string|array $roles Required role(s)
     * @param string $redirectUrl Redirect URL
     */
    public function requireRole($roles, $redirectUrl = '/carwash_project/frontend/index.php') {
        if (!$this->hasRole($roles)) {
            Session::setFlash('error', 'شما دسترسی به این صفحه را ندارید');
            header("Location: {$redirectUrl}");
            exit;
        }
    }
    
    /**
     * Get current user data
     * 
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $userId = Session::getUserId();
        
        return $this->db->fetchOne(
            "SELECT id, name, email, phone, role, status, created_at FROM users WHERE id = :id",
            ['id' => $userId]
        );
    }
}
?>