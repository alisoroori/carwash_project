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
    
    // helper: read current user info from Session or legacy $_SESSION keys
    private static function getCurrentUserData(): array
    {
    	// try Session wrapper
    	if (class_exists(\App\Classes\Session::class) && method_exists(\App\Classes\Session::class, 'get')) {
    		$user = \App\Classes\Session::get('user');
    		if (is_array($user)) {
    			return $user;
    		}
    	}
	
    	// fallback to legacy $_SESSION shapes
    	$u = [];
    	if (!empty($_SESSION['user'])) {
    		$u = is_array($_SESSION['user']) ? $_SESSION['user'] : $u;
    	}
    	// common legacy keys
    	if (empty($u['id']) && !empty($_SESSION['user_id'])) $u['id'] = $_SESSION['user_id'];
    	if (empty($u['role']) && !empty($_SESSION['role'])) $u['role'] = $_SESSION['role'];
    	if (empty($u['email']) && !empty($_SESSION['email'])) $u['email'] = $_SESSION['email'];
    	if (empty($u['name']) && !empty($_SESSION['name'])) $u['name'] = $_SESSION['name'];
	
    	return $u;
    }

    /**
     * Ensure session is started and user is logged in.
     * Prevents redirect loops by detecting login page and counting redirects.
     */
    public static function requireAuth(): void
    {
        Session::start();

        $user = self::getCurrentUserData();

        if (!empty($user) && (!empty($user['id']) || !empty($user['email']))) {
            // authenticated -> reset any redirect counter
            if (isset($_SESSION['redirect_count'])) {
                unset($_SESSION['redirect_count']);
            }
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isApi = stripos($accept, 'application/json') !== false || stripos($uri, '/api/') !== false;

        // If request is for the login page, do not redirect (avoid loop)
        $loginScriptNames = ['/backend/auth/login.php', '/auth/login.php', 'login.php'];
        foreach ($loginScriptNames as $name) {
            if (stripos($uri, $name) !== false || basename($_SERVER['PHP_SELF']) === basename($name)) {
                // On login page already — do not redirect again
                return;
            }
        }

        // Track redirect attempts in session to detect loops
        if (!isset($_SESSION['redirect_count'])) {
            $_SESSION['redirect_count'] = 0;
        }
        $_SESSION['redirect_count']++;

        // If we have redirected too many times, stop and show friendly message (and log)
        if ($_SESSION['redirect_count'] > 5) {
            if (class_exists('\App\Classes\Logger')) {
                \App\Classes\Logger::warning('Potential redirect loop detected in requireAuth', ['uri' => $uri, 'count' => $_SESSION['redirect_count']]);
            }
            unset($_SESSION['redirect_count']);

            if ($isApi && class_exists('\App\Classes\Response')) {
                \App\Classes\Response::error('Too many redirects or invalid session. Please authenticate using the login endpoint.', 403);
            }

            http_response_code(403);
            echo 'Access denied. Please login using the login page.';
            exit;
        }

        // Normal behavior: API -> JSON 401, Page -> redirect to login with return_to param
        if ($isApi && class_exists('\App\Classes\Response')) {
            \App\Classes\Response::unauthorized();
        }

        $returnTo = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        $loginUrl = '/carwash_project/backend/auth/login.php?return_to=' . $returnTo;

        header('Location: ' . $loginUrl);
        exit;
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
     * Prevents redirect loops similarly to requireAuth().
     */
    public static function requireRole($role): void
    {
    	// Ensure authenticated first (this will handle loop detection too)
    	self::requireAuth();
	
    	$user = self::getCurrentUserData();
    	$userRole = $user['role'] ?? null;
	
    	// If no role information, treat as forbidden (but avoid redirect loops)
    	if (empty($userRole)) {
    		$uri = $_SERVER['REQUEST_URI'] ?? '/';
    		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    		$isApi = stripos($accept, 'application/json') !== false || stripos($uri, '/api/') !== false;
	
    		// If we're on login page, do not redirect to avoid loop
    		if (stripos($uri, '/backend/auth/login.php') !== false || basename($_SERVER['PHP_SELF']) === 'login.php') {
    			if ($isApi && class_exists('\App\Classes\Response')) \App\Classes\Response::error('Forbidden', 403);
    			http_response_code(403);
    			echo '403 Forbidden - insufficient permissions.';
    			exit;
    		}
	
    		if ($isApi && class_exists('\App\Classes\Response')) \App\Classes\Response::error('Forbidden', 403);
	
    		$forbiddenPage = '/carwash_project/403.php';
    		if (file_exists(__DIR__ . '/../../403.php') || file_exists(__DIR__ . '/../../backend/403.php')) {
    			header('Location: ' . $forbiddenPage);
    		} else {
    			header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    			echo '403 Forbidden - You do not have permission to access this page.';
    		}
    		exit;
    	}
	
    	// Check role match
    	if (is_array($role)) {
    		if (!in_array($userRole, $role, true)) {
    			// forbidden handling same as above
    			if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false && class_exists('\App\Classes\Response')) {
    				\App\Classes\Response::error('Forbidden', 403);
    			}
    			header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    			echo '403 Forbidden - You do not have permission to access this page.';
    			exit;
    		}
    	} else {
    		if ((string)$userRole !== (string)$role) {
    			if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false && class_exists('\App\Classes\Response')) {
    				\App\Classes\Response::error('Forbidden', 403);
    			}
    			header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    			echo '403 Forbidden - You do not have permission to access this page.';
    			exit;
    		}
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
     * Log in a user by email and password.
     * - Sanitizes input
     * - Performs case-insensitive lookup
     * - Verifies hashed password (password_verify)
     * - Falls back to legacy plain-text check (and re-hashes safely)
     * - Starts session, regenerates id, stores minimal session user info
     */
    public function login(string $email, string $password): array
    {
        // sanitize
        $email = strtolower(filter_var(trim($email), FILTER_SANITIZE_EMAIL));
        $password = (string)$password;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Use PSR-4 Database class
        $db = null;
        try {
            $db = Database::getInstance();
        } catch (\Throwable $e) {
            // fallback / unexpected
            return ['success' => false, 'message' => 'Service unavailable'];
        }

        // fetch user case-insensitively
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE LOWER(email) = :email LIMIT 1",
            ['email' => $email]
        );

        if (empty($user) || !isset($user['password'])) {
            // do not reveal which part failed
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $storedHash = (string)$user['password'];
        $verified = false;

        // Preferred: verify hashed password
        if (!empty($storedHash) && password_verify($password, $storedHash)) {
            $verified = true;
            // rehash if algorithm needs update
            if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                try {
                    $db->update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)], ['id' => $user['id']]);
                } catch (\Throwable $e) {
                    // non-fatal; continue
                }
            }
        } else {
            // Legacy fallback: stored value was plaintext (unsafe). If matches, rehash and update.
            if ($storedHash === $password) {
                $verified = true;
                try {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->update('users', ['password' => $newHash], ['id' => $user['id']]);
                } catch (\Throwable $e) {
                    // ignore update failure
                }
            }
        }

        if (!$verified) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Optional: check status/active flag if your schema uses it
        if (isset($user['status']) && in_array($user['status'], ['inactive','banned','suspended'], true)) {
            return ['success' => false, 'message' => 'Account not active'];
        }

        // Start session and store minimal user info
        if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
            Session::start();
            if (method_exists(Session::class, 'regenerate')) {
                Session::regenerate();
            } else {
                @session_regenerate_id(true);
            }
            // store minimal user info (avoid storing password)
            $sessionUser = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'] ?? ($user['full_name'] ?? null),
                'role' => $user['role'] ?? 'customer',
            ];
            if (method_exists(Session::class, 'set')) {
                Session::set('user', $sessionUser);
            } else {
                $_SESSION['user'] = $sessionUser;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $sessionUser['name'];
                $_SESSION['role'] = $sessionUser['role'];
            }
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'] ?? ($user['full_name'] ?? null),
                'role' => $user['role'] ?? 'customer',
            ];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $_SESSION['user']['name'];
            $_SESSION['role'] = $_SESSION['user']['role'];
        }

        return ['success' => true, 'message' => 'Login successful', 'user' => $_SESSION['user']];
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
