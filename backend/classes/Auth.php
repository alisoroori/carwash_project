<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Authentication Manager
 * Handles secure user authentication, registration, and access control
 */
class Auth 
{
    private $db;
    private $validator;
    
    public function __construct() 
    {
        $this->db = Database::getInstance();
        $this->validator = new Validator();
    }
    
    /**
     * Register a new user with secure password hashing
     * 
     * @param array $userData User data (name, email, password, role)
     * @return array Response with success/error status
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
                'errors' => ['email' => 'این ایمیل قبلا ثبت شده است']
            ];
        }
        
        // Hash password with modern algorithm and strong options
        $passwordHash = password_hash(
            $userData['password'], 
            PASSWORD_DEFAULT,
            ['cost' => 12]
        );
        
        // Insert user with secure prepared statement
        $userId = $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ]);
        
        if (!$userId) {
            return [
                'success' => false,
                'errors' => ['general' => 'خطا در ثبت نام. لطفا دوباره تلاش کنید']
            ];
        }
        
        return [
            'success' => true,
            'message' => 'ثبت نام با موفقیت انجام شد',
            'user_id' => $userId
        ];
    }
    
    /**
     * Secure login with brute force protection
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array Response with success/error status
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
        
        $email = Validator::sanitizeEmail($email);
        
        // Get user by email with secure query
        $user = $this->db->fetchOne(
            "SELECT id, name, email, password, role, status, login_attempts, 
                    last_login_attempt FROM users WHERE email = :email",
            ['email' => $email]
        );
        
        // Check for brute force attempts
        if ($user && $user['login_attempts'] >= 5) {
            $timeElapsed = time() - strtotime($user['last_login_attempt']);
            
            if ($timeElapsed < 900) { // 15 minutes lockout
                return [
                    'success' => false,
                    'errors' => ['general' => 'حساب کاربری شما موقتاً قفل شده است. لطفاً 15 دقیقه دیگر تلاش کنید']
                ];
            }
            
            // Reset attempts after lockout period
            $this->db->update('users', 
                ['login_attempts' => 0], 
                ['id' => $user['id']]
            );
        }
        
        // Verify user exists and is active
        if (!$user || $user['status'] !== 'active') {
            return [
                'success' => false,
                'errors' => ['general' => 'ایمیل یا رمز عبور اشتباه است']
            ];
        }
        
        // Verify password with timing attack safe comparison
        if (!password_verify($password, $user['password'])) {
            // Increment failed login attempts
            $this->db->update('users', [
                'login_attempts' => $user['login_attempts'] + 1,
                'last_login_attempt' => date('Y-m-d H:i:s')
            ], ['id' => $user['id']]);
            
            return [
                'success' => false,
                'errors' => ['general' => 'ایمیل یا رمز عبور اشتباه است']
            ];
        }
        
        // Reset login attempts on successful login
        $this->db->update('users', [
            'login_attempts' => 0,
            'last_login' => date('Y-m-d H:i:s')
        ], ['id' => $user['id']]);
        
        // Create session data
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
     * Secure logout
     */
    public function logout(): void 
    {
        Session::start();
        Session::destroy();
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if user is authenticated
     */
    public function isAuthenticated(): bool 
    {
        Session::start();
        
        // Basic authentication check
        if (!Session::has('logged_in') || !Session::get('logged_in')) {
            return false;
        }
        
        // Session timeout (default: 30 minutes)
        $timeout = 1800; // 30 minutes
        $loginTime = Session::get('login_time', 0);
        
        if (time() - $loginTime > $timeout) {
            $this->logout();
            return false;
        }
        
        // Refresh login time
        Session::set('login_time', time());
        return true;
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
     * @param string $redirectUrl URL to redirect if role doesn't match
     */
    public function requireRole($roles, string $redirectUrl = '/carwash_project/backend/auth/login.php'): void 
    {
        $this->requireAuth($redirectUrl);
        
        $userRole = Session::get('user_role');
        $allowedRoles = is_array($roles) ? $roles : [$roles];
        
        if (!in_array($userRole, $allowedRoles)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Update password with secure hashing
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return array Response with success/error status
     */
    public function updatePassword(int $userId, string $currentPassword, string $newPassword): array 
    {
        // Validate inputs
        $this->validator
            ->required($currentPassword, 'رمز عبور فعلی')
            ->required($newPassword, 'رمز عبور جدید')
            ->minLength($newPassword, 8, 'رمز عبور جدید');
            
        if ($this->validator->fails()) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors()
            ];
        }
        
        // Get current password hash
        $user = $this->db->fetchOne(
            "SELECT password FROM users WHERE id = :id",
            ['id' => $userId]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'errors' => ['general' => 'کاربر یافت نشد']
            ];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return [
                'success' => false,
                'errors' => ['current_password' => 'رمز عبور فعلی اشتباه است']
            ];
        }
        
        // Hash new password
        $passwordHash = password_hash(
            $newPassword, 
            PASSWORD_DEFAULT,
            ['cost' => 12]
        );
        
        // Update password
        $this->db->update('users', 
            ['password' => $passwordHash], 
            ['id' => $userId]
        );
        
        return [
            'success' => true,
            'message' => 'رمز عبور با موفقیت تغییر یافت'
        ];
    }
}
