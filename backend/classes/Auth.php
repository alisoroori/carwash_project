<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Authentication and Authorization Manager
 */
class Auth
{
    private $db;
    private $validator;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->validator = new Validator();
    }
    
    /**
     * Register a new user
     * 
     * @param array $userData User data
     * @return array Response with status and messages
     */
    public function register(array $userData): array
    {
        // Validate required fields
        $this->validator
            ->required($userData['email'] ?? null, 'ایمیل')
            ->email($userData['email'] ?? null, 'ایمیل')
            ->required($userData['password'] ?? null, 'رمز عبور')
            ->minLength($userData['password'] ?? null, 8, 'رمز عبور')
            ->required($userData['name'] ?? null, 'نام')
            ->required($userData['role'] ?? null, 'نقش');
        
        if ($this->validator->fails()) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors()
            ];
        }
        
        // Sanitize inputs
        $email = Validator::sanitizeEmail($userData['email']);
        $name = Validator::sanitizeString($userData['name']);
        $role = Validator::sanitizeString($userData['role']);
        
        // Check if email already exists
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $email]
        );
        
        if ($existingUser) {
            return [
                'success' => false,
                'errors' => ['email' => 'این ایمیل قبلاً ثبت شده است']
            ];
        }
        
        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $userId = $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if (!$userId) {
            return [
                'success' => false,
                'errors' => ['general' => 'خطا در ثبت نام. لطفاً دوباره تلاش کنید']
            ];
        }
        
        return [
            'success' => true,
            'message' => 'ثبت نام با موفقیت انجام شد',
            'user_id' => $userId
        ];
    }
    
    /**
     * Login a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array Response with status and messages
     */
    public function login(string $email, string $password): array
    {
        // Validate inputs
        $this->validator
            ->required($email, 'ایمیل')
            ->email($email, 'ایمیل')
            ->required($password, 'رمز عبور');
        
        if ($this->validator->fails()) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors()
            ];
        }
        
        // Sanitize email
        $email = Validator::sanitizeEmail($email);
        
        // Get user by email
        $user = $this->db->fetchOne(
            "SELECT id, name, email, password, role FROM users WHERE email = :email",
            ['email' => $email]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'errors' => ['general' => 'ایمیل یا رمز عبور اشتباه است']
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'errors' => ['general' => 'ایمیل یا رمز عبور اشتباه است']
            ];
        }
        
        // Set up session
        Session::start();
        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', $user['role']);
        Session::set('logged_in', true);
        Session::set('login_time', time());
        
        // Regenerate session ID to prevent session fixation
        Session::regenerate();
        
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
    }
    
    /**
     * Logout the current user
     */
    public function logout(): void
    {
        Session::destroy();
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        Session::start();
        
        if (!Session::has('logged_in') || !Session::get('logged_in')) {
            return false;
        }
        
        // Session timeout check
        $timeout = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800; // 30 minutes
        $loginTime = Session::get('login_time', 0);
        
        if (time() - $loginTime > $timeout) {
            $this->logout();
            return false;
        }
        
        // Update login time to extend session
        Session::set('login_time', time());
        return true;
    }
    
    /**
     * Check if user has specific role
     * 
     * @param string|array $roles Role(s) to check
     * @return bool True if has role
     */
    public function hasRole($roles): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $userRole = Session::get('user_role');
        $requiredRoles = is_array($roles) ? $roles : [$roles];
        
        return in_array($userRole, $requiredRoles);
    }
    
    /**
     * Require authentication or redirect
     * 
     * @param string $redirectUrl URL to redirect if not authenticated
     */
    public function requireAuth(string $redirectUrl = '/carwash_project/backend/auth/login.php'): void
    {
        if (!$this->isAuthenticated()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require specific role or redirect
     * 
     * @param string|array $roles Required role(s)
     * @param string $redirectUrl URL to redirect if not authorized
     */
    public function requireRole($roles, string $redirectUrl = '/carwash_project/backend/auth/login.php'): void
    {
        $this->requireAuth($redirectUrl);
        
        if (!$this->hasRole($roles)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Get current user data
     * 
     * @return array|null User data or null if not logged in
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $userId = Session::get('user_id');
        
        return $this->db->fetchOne(
            "SELECT id, name, email, role, created_at FROM users WHERE id = :id",
            ['id' => $userId]
        );
    }
}
