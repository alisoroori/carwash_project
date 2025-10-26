<?php
declare(strict_types=1);

namespace App\Classes;

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Session;
use App\Classes\Response;
use App\Classes\Logger;

/**
 * Authentication and Authorization Class
 * 
 * Handles user authentication and role-based access control (RBAC)
 */
class Auth {
    /**
     * Get the current authenticated user
     * 
     * @return array|null User data or null if not authenticated
     */
    public function getUser() {
        Session::start();
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $db = Database::getInstance();
        return $db->fetchOne("SELECT * FROM users WHERE id = :id", [
            'id' => $_SESSION['user_id']
        ]);
    }
    
    /**
     * Check if a user is authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    public static function isAuthenticated(): bool
    {
        Session::start();
        $user = method_exists(Session::class, 'get') ? Session::get('user') : ($_SESSION['user'] ?? null);
        return !empty($user);
    }
    
    /**
     * Ensure session is started and user is logged in.
     * If not authenticated, redirect to login page.
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $uri = $_SERVER['REQUEST_URI'] ?? '';

            if (stripos($accept, 'application/json') !== false || stripos($uri, '/api/') !== false) {
                Response::unauthorized(); // sends 401 JSON and exits
            }

            header('Location: /carwash_project/backend/auth/login.php');
            exit;
        }
    }
    
    /**
     * Check if current user has the given role.
     * Accepts string role or array of allowed roles.
     */
    public static function hasRole($role): bool
    {
        Session::start();
        $user = method_exists(Session::class, 'get') ? Session::get('user') : ($_SESSION['user'] ?? null);
        if (empty($user) || empty($user['role'])) {
            return false;
        }
        $userRole = (string) $user['role'];
        if (is_array($role)) {
            return in_array($userRole, $role, true);
        }
        return $userRole === (string) $role;
    }

    /**
     * Require a specific role (or roles).
     * If missing, returns 403 for API calls or shows a 403 / redirects for pages.
     */
    public static function requireRole($role): void
    {
        // ensure authenticated
        self::requireAuth();

        if (!self::hasRole($role)) {
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $uri = $_SERVER['REQUEST_URI'] ?? '';

            if (stripos($accept, 'application/json') !== false || stripos($uri, '/api/') !== false) {
                Response::error('Forbidden', 403); // sends JSON 403 and exits
            }

            // Page request: try redirect to project 403 page, otherwise send 403 header and simple message
            $forbiddenPage = '/carwash_project/403.php';
            if (file_exists(__DIR__ . '/../../403.php') || file_exists(__DIR__ . '/../../backend/403.php')) {
                header('Location: ' . $forbiddenPage);
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
                echo '403 Forbidden - You do not have permission to access this page.';
            }
            exit;
        }
    }
    
    /**
     * Register a new user
     * 
     * @param array $data User data
     * @return array Result of registration
     */
    public function register($data) {
        $validator = new Validator();
        
        // Validate input data
        $validator
            ->required($data['email'] ?? null, 'Email')
            ->email($data['email'] ?? null, 'Email')
            ->required($data['password'] ?? null, 'Password')
            ->minLength($data['password'] ?? null, 8, 'Password')
            ->required($data['full_name'] ?? null, 'Full Name');
            
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->getErrors()
            ];
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'customer';
        }
        
        $db = Database::getInstance();
        
        // Check if email already exists
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = :email", [
            'email' => $data['email']
        ]);
        
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }
        
        // Insert user
        $userId = $db->insert('users', $data);
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $userId
        ];
    }
    
    /**
     * Login user
     *
     * @param string $email User email
     * @param string $password User password
     * @param bool $remember Remember login
     * @return array Result array with success status and message
     */
    public function login($email, $password, $remember = false) {
        // Sanitize inputs
        $email = Validator::sanitizeEmail($email);
        
        // Get database instance
        $db = Database::getInstance();
        
        // Get user from database
        $user = $db->fetchOne("SELECT * FROM users WHERE email = :email", [
            'email' => $email
        ]);
        
        // Debug: Log login attempt (remove in production)
        error_log("Login attempt for: $email - User found: " . ($user ? 'Yes' : 'No'));
        
        // Check if user exists and password is valid
        if (!$user || !password_verify($password, $user['password'])) {
            return [
                'success' => false, 
                'message' => 'Invalid email or password'
            ];
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            return [
                'success' => false,
                'message' => 'Account is inactive'
            ];
        }
        
        // Start session
        Session::start();
        
        // Regenerate session ID to prevent session fixation
        Session::regenerate();
        
        // Store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Handle remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 30 * 86400); // 30 days
            
            $db->update('users', [
                'remember_token' => $token,
                'token_expires' => $expires
            ], [
                'id' => $user['id']
            ]);
            
            // Set remember cookie
            setcookie('remember_token', $token, time() + 30 * 86400, '/', '', false, true);
        }
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }
    
    /**
     * Log out the current user
     * 
     * @return bool True on success
     */
    public function logout() {
        Session::start();
        
        // Clear remember me token if exists
        if (isset($_SESSION['user_id'])) {
            $this->clearRememberMeToken($_SESSION['user_id']);
        }
        
        // Clear session data
        $_SESSION = [];
        
        // Clear session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        return true;
    }
    
    /**
     * Create and store remember me token
     * 
     * @param int $userId User ID
     * @return bool Success
     */
    private function setRememberMeToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days
        
        $db = Database::getInstance();
        $db->update('users', [
            'remember_token' => $token,
            'token_expires' => $expires
        ], [
            'id' => $userId
        ]);
        
        setcookie(
            'remember_token',
            $token,
            time() + (86400 * 30),
            '/',
            '',
            false,
            true
        );
        
        return true;
    }
    
    /**
     * Clear remember me token
     * 
     * @param int $userId User ID
     * @return bool Success
     */
    private function clearRememberMeToken($userId) {
        $db = Database::getInstance();
        $db->update('users', [
            'remember_token' => null,
            'token_expires' => null
        ], [
            'id' => $userId
        ]);
        
        setcookie('remember_token', '', time() - 3600, '/');
        
        return true;
    }
    
    /**
     * Check and authenticate using remember me token
     * 
     * @return bool True if authenticated
     */
    public function authenticateFromRememberToken() {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        $db = Database::getInstance();
        
        $user = $db->fetchOne("
            SELECT * FROM users 
            WHERE remember_token = :token 
            AND token_expires > NOW()
        ", [
            'token' => $token
        ]);
        
        if (!$user) {
            setcookie('remember_token', '', time() - 3600, '/');
            return false;
        }
        
        // Start session
        Session::start();
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Regenerate session ID
        Session::regenerate();
        
        return true;
    }
}
