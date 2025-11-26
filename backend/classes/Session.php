<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Secure Session Management
 */
class Session
{
    /**
     * Start secure session
     * 
     * @param array $options Session options
     * @return bool True if session started
     */
    public static function start(array $options = []): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        // If headers were already sent, we cannot (safely) call session_start().
        // Return false to avoid a fatal/exception and let callers handle the lack of an active session.
        if (headers_sent()) {
            error_log('[Session::start] Headers already sent; skipping session_start() to avoid fatal.');
            return false;
        }

        // Secure session settings
        $defaultOptions = [
            'cookie_httponly' => true,     // Prevent JavaScript access to session cookie
            'cookie_secure' => false,      // Allow HTTP for development
            'use_strict_mode' => true,     // Reject uninitialized session ID
            'cookie_samesite' => 'Lax',    // CSRF protection
            'gc_maxlifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800, // 30 minutes lifetime
            'cookie_path' => '/',          // Allow cookie for entire domain
            'cookie_domain' => '',         // Allow for localhost
        ];
        
        $sessionOptions = array_merge($defaultOptions, $options);
        
        return session_start($sessionOptions);
    }
    
    /**
     * Regenerate session ID to prevent session fixation
     * 
     * @param bool $deleteOldSession Whether to delete old session data
     * @return bool True on success
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Set session value
     * 
     * @param string $key Session key
     * @param mixed $value Session value
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     * 
     * @param string $key Session key
     * @param mixed $default Default value if key not found
     * @return mixed Session value or default
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     * 
     * @param string $key Session key
     * @return bool True if key exists
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     * 
     * @param string $key Session key
     */
    public static function remove(string $key): void
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Destroy session
     * 
     * @return bool True on success
     */
    public static function destroy(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear session array
            $_SESSION = [];
            
            // Clear session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destroy session
            return session_destroy();
        }
        
        return true;
    }
    
    /**
     * Set flash message (available only for one request)
     * 
     * @param string $key Flash key
     * @param mixed $value Flash value
     */
    public static function setFlash(string $key, $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get flash message and remove it
     * 
     * @param string $key Flash key
     * @param mixed $default Default value if key not found
     * @return mixed Flash value or default
     */
    public static function getFlash(string $key, $default = null)
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        
        if (isset($_SESSION['_flash'][$key])) {
            unset($_SESSION['_flash'][$key]);
        }
        
        return $value;
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCsrfToken(): string
    {
        self::start();
        $token = bin2hex(random_bytes(32));
        self::set('csrf_token', $token);
        return $token;
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if valid
     */
    public static function verifyCsrfToken(string $token): bool
    {
        self::start();
        $storedToken = self::get('csrf_token');
        
        if (empty($storedToken)) {
            return false;
        }
        
        return hash_equals($storedToken, $token);
    }
}
