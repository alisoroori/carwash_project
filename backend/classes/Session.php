<?php
/**
 * Session Management Class (PSR-4 Autoloaded)
 * Secure session handling wrapper
 * 
 * @package App\Classes
 * @namespace App\Classes
 */

namespace App\Classes;

class Session {
    
    /**
     * Session started flag
     */
    private static $started = false;
    
    /**
     * Session configuration
     */
    private static $config = [
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => false, // Set to true if using HTTPS
        'use_strict_mode' => true,
        'use_only_cookies' => true
    ];
    
    /**
     * Start session with security settings
     */
    public static function start() {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Apply security settings
        ini_set('session.cookie_httponly', self::$config['cookie_httponly']);
        ini_set('session.use_strict_mode', self::$config['use_strict_mode']);
        ini_set('session.use_only_cookies', self::$config['use_only_cookies']);
        
        session_start();
        self::$started = true;
    }
    
    /**
     * Set session value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session value
     */
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Destroy entire session
     */
    public static function destroy() {
        self::start();
        
        // Unset all session variables
        $_SESSION = [];
        
        // Delete session cookie
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
        self::$started = false;
    }
    
    /**
     * Regenerate session ID (security)
     */
    public static function regenerate($deleteOldSession = true) {
        self::start();
        session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Get user ID from session
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return self::has('user_id') && self::get('user_id') > 0;
    }
    
    /**
     * Get user role
     */
    public static function getUserRole() {
        return self::get('role');
    }
    
    /**
     * Get user name
     */
    public static function getUserName() {
        return self::get('name');
    }
    
    /**
     * Set flash message
     */
    public static function setFlash($type, $message) {
        self::set('flash_message', [
            'type' => $type,
            'message' => $message
        ]);
    }
    
    /**
     * Get and clear flash message
     */
    public static function getFlash() {
        $flash = self::get('flash_message');
        self::remove('flash_message');
        return $flash;
    }
    
    /**
     * Check if flash message exists
     */
    public static function hasFlash() {
        return self::has('flash_message');
    }
}
?>