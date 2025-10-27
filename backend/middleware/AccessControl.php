<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\middleware\AccessControl.php

namespace App\Middleware;

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Response;

/**
 * Access Control Middleware
 * 
 * Handles role-based access control for API endpoints
 */
class AccessControl {
    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated
     */
    public static function isAuthenticated() {
        if (!(new Auth())->isAuthenticated()) {
            Response::unauthorized('Authentication required');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user has required role
     * 
     * @param string|array $role Required role(s)
     * @return bool True if user has required role
     */
    public static function hasRole($role) {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        if (!Auth::hasRole($role)) {
            Response::forbidden('You do not have permission to access this resource');
            return false;
        }
        
        return true;
    }
    
    /**
     * Allow only specific roles to access a resource
     * 
     * @param string|array $allowedRoles Role or roles allowed to access
     * @return bool True if access is allowed
     */
    public static function allowRoles($allowedRoles) {
        return self::hasRole($allowedRoles);
    }
    
    /**
     * Restrict access to admin users only
     * 
     * @return bool True if user is admin
     */
    public static function adminOnly() {
        return self::hasRole('admin');
    }
    
    /**
     * Restrict access to car wash business users only
     * 
     * @return bool True if user is car wash business
     */
    public static function carwashOnly() {
        return self::hasRole('carwash');
    }
    
    /**
     * Restrict access to customers only
     * 
     * @return bool True if user is customer
     */
    public static function customerOnly() {
        return self::hasRole('customer');
    }
    
    /**
     * Check if user owns a resource
     * 
     * @param string $table Database table
     * @param int $resourceId Resource ID
     * @param string $userIdField Field name for user ID (default: user_id)
     * @return bool True if user owns resource
     */
    public static function ownsResource($table, $resourceId, $userIdField = 'user_id') {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        // Get current user ID
        $userId = $_SESSION['user_id'];
        
        // Check if user is admin (admins can access all resources)
        if (Auth::hasRole('admin')) {
            return true;
        }
        
        // Query database to check ownership
        $db = \App\Classes\Database::getInstance();
        $result = $db->fetchOne(
            "SELECT id FROM $table WHERE id = :id AND $userIdField = :user_id",
            ['id' => $resourceId, 'user_id' => $userId]
        );
        
        if (!$result) {
            Response::forbidden('You do not have permission to access this resource');
            return false;
        }
        
        return true;
    }

    /**
     * Enforce authentication
     */
    public static function requireAuth(): void
    {
        Auth::requireAuth();
    }

    /**
     * Enforce role(s)
     * $role can be string or array of strings
     */
    public static function requireRole($role): void
    {
        Auth::requireRole($role);
    }
}
