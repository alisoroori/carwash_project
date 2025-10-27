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
     * If session doesn't contain role, attempt to load it from DB and set it.
     */
    public static function hasRole($role): bool
    {
		// Ensure session started
		if (method_exists(Session::class, 'start')) Session::start();

		// Try session wrapper first
		$user = [];
		if (class_exists(Session::class) && method_exists(Session::class, 'get')) {
			$user = Session::get('user') ?? [];
		} else {
			$user = $_SESSION['user'] ?? [];
		}

		// If role missing but user id exists, attempt to load role from DB
		if (empty($user['role']) && !empty($user['id'])) {
			$loadedRole = null;

			// Prefer PSR-4 Database helper
			if (class_exists(\App\Classes\Database::class)) {
				try {
					$db = \App\Classes\Database::getInstance();
					$row = $db->fetchOne("SELECT role FROM users WHERE id = :id LIMIT 1", ['id' => $user['id']]);
					$loadedRole = $row['role'] ?? null;
				} catch (\Throwable $e) {
					// ignore DB lookup failure
				}
			}

			// Fallback to legacy PDO if available
			if ($loadedRole === null && function_exists('getDBConnection')) {
				try {
					$pdo = getDBConnection();
					$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
					$stmt->execute([$user['id']]);
					$r = $stmt->fetch(\PDO::FETCH_ASSOC);
					$loadedRole = $r['role'] ?? null;
				} catch (\Throwable $e) {
					// ignore
				}
			}

			// If loaded, persist back to session
			if (!empty($loadedRole)) {
				if (class_exists(Session::class) && method_exists(Session::class, 'set')) {
					Session::set('user', array_merge($user, ['role' => $loadedRole]));
				} else {
					$_SESSION['user']['role'] = $loadedRole;
					$_SESSION['role'] = $loadedRole;
				}
				$user['role'] = $loadedRole;
			} else {
				// final fallback: default to 'customer' to avoid missing role errors
				if (class_exists(Session::class) && method_exists(Session::class, 'set')) {
					Session::set('user', array_merge($user, ['role' => 'customer']));
				} else {
					$_SESSION['user']['role'] = 'customer';
					$_SESSION['role'] = 'customer';
				}
				$user['role'] = 'customer';
			}
		}

		// Now perform role comparison
		$userRole = $user['role'] ?? null;
		if (empty($userRole)) return false;

		if (is_array($role)) {
			return in_array((string)$userRole, array_map('strval', $role), true);
		}

		return ((string)$userRole === (string)$role);
	}

    /**
     * Require a specific role (or roles).
     * Uses hasRole which will attempt to populate missing role from DB.
     */
    public static function requireRole($role): void
    {
    	// Ensure authenticated first (this will handle loop detection too)
    	self::requireAuth();
	
    	// Attempt to populate missing role info for current session user
		$userId = null;
		if (class_exists(\App\Classes\Session::class) && method_exists(\App\Classes\Session::class, 'get')) {
			$u = \App\Classes\Session::get('user') ?? [];
			$userId = $u['id'] ?? null;
		} else {
			$userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
		}
		if ($userId) {
			self::populateRoleFromDb((int)$userId);
		}

		// If auth didn't exit but user not have role -> handle forbidden
		if (!self::hasRole($role)) {
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

			// For API calls return JSON 403
			if ($isApi && class_exists('\App\Classes\Response')) {
				\App\Classes\Response::error('Forbidden', 403);
			}

			// For normal pages: send 403 or redirect to a friendly page (avoid redirect loops)
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
     * Log in a user by email and password.
     * - Sanitizes input
     * - Performs case-insensitive lookup
     * - Verifies hashed password (password_verify)
     * - Falls back to legacy plain-text check (and re-hashes safely)
     * - Starts session, regenerates id, stores minimal session user info
     */
    public function login(string $email, string $password, bool $remember = false): array
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
                // keep legacy shape for compatibility
                Session::set('user', $sessionUser);
                // also set shortcut keys expected by older scripts
                Session::set('role', $sessionUser['role']);
                Session::set('user_id', $user['id']);
                Session::set('email', $user['email']);
                Session::set('name', $sessionUser['name']);
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

        // If remember me requested, create token
        if ($remember) {
            try {
                $this->setRememberMeToken((int)$user['id']);
            } catch (\Throwable $e) {
                // non-fatal
            }
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

    // Ensure the session contains a role for the given user id; try PSR-4 Database then legacy PDO
	private static function populateRoleFromDb($userId)
	{
		if (empty($userId)) {
			return;
		}

		// ensure session started
		if (class_exists(\App\Classes\Session::class) && method_exists(\App\Classes\Session::class, 'start')) {
			\App\Classes\Session::start();
		} else {
			if (session_status() === PHP_SESSION_NONE) session_start();
		}

		// If role already present, nothing to do
		$rolePresent = false;
		if (class_exists(\App\Classes\Session::class) && method_exists(\App\Classes\Session::class, 'get')) {
			$u = \App\Classes\Session::get('user') ?? [];
			if (!empty($u['role'])) $rolePresent = true;
		} else {
			if (!empty($_SESSION['user']['role']) || !empty($_SESSION['role'])) $rolePresent = true;
		}
		if ($rolePresent) return;

		$loadedRole = null;

		// Try PSR-4 Database class
		if (class_exists(\App\Classes\Database::class)) {
			try {
				$db = \App\Classes\Database::getInstance();
				$row = $db->fetchOne("SELECT role FROM users WHERE id = :id LIMIT 1", ['id' => $userId]);
				$loadedRole = $row['role'] ?? null;
			} catch (\Throwable $e) {
				// ignore DB lookup failure
			}
		}

		// Fallback to legacy PDO connection if available
		if ($loadedRole === null && function_exists('getDBConnection')) {
			try {
				$pdo = getDBConnection();
				$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
				$stmt->execute([$userId]);
				$r = $stmt->fetch(\PDO::FETCH_ASSOC);
				$loadedRole = $r['role'] ?? null;
			} catch (\Throwable $e) {
				// ignore
			}
		}

		// Persist role into session if found; otherwise leave unset (caller may default)
		if (!empty($loadedRole)) {
			if (class_exists(\App\Classes\Session::class) && method_exists(\App\Classes\Session::class, 'set')) {
				$user = \App\Classes\Session::get('user') ?? [];
				$user['role'] = $loadedRole;
				\App\Classes\Session::set('user', $user);
			} else {
				$_SESSION['user']['role'] = $loadedRole;
				$_SESSION['role'] = $loadedRole;
			}
		}
	}
}
