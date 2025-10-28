<?php
/**
 * Role-Based Access Control (RBAC) System
 * Enterprise-level permission management
 * 
 * @package CarWash Admin
 * @author CarWash Team
 * @version 2.0
 * @created October 18, 2025
 */

class RBAC {
    private $pdo;
    private $currentUser;
    private $permissions = [];
    
    public function __construct($pdo, $userId = null) {
        $this->pdo = $pdo;
        
        if ($userId) {
            $this->loadUser($userId);
        } elseif (isset($_SESSION['user_id'])) {
            $this->loadUser($_SESSION['user_id']);
        }
    }
    
    /**
     * Load user and their permissions
     */
    private function loadUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, r.name as role_name, r.level as role_level, r.permissions as role_permissions
                FROM users u
                LEFT JOIN role_user ru ON u.id = ru.user_id
                LEFT JOIN roles r ON ru.role_id = r.id
                WHERE u.id = ? AND r.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($this->currentUser && $this->currentUser['role_permissions']) {
                $this->permissions = json_decode($this->currentUser['role_permissions'], true) ?? [];
            }
            
            return $this->currentUser !== false;
        } catch (PDOException $e) {
            error_log("RBAC Error loading user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has a specific permission
     * 
     * @param string $permission Permission string (e.g., 'users.edit')
     * @return bool
     */
    public function can($permission) {
        if (!$this->currentUser) {
            return false;
        }
        
        // SuperAdmin has all permissions
        if (in_array('*', $this->permissions)) {
            return true;
        }
        
        // Check exact permission
        if (in_array($permission, $this->permissions)) {
            return true;
        }
        
        // Check wildcard permissions (e.g., 'users.*' matches 'users.edit')
        foreach ($this->permissions as $perm) {
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has ANY of the given permissions
     * 
     * @param array $permissions Array of permission strings
     * @return bool
     */
    public function canAny(array $permissions) {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has ALL of the given permissions
     * 
     * @param array $permissions Array of permission strings
     * @return bool
     */
    public function canAll(array $permissions) {
        foreach ($permissions as $permission) {
            if (!$this->can($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check if user has a specific role
     * 
     * @param string $role Role name
     * @return bool
     */
    public function hasRole($role) {
        return $this->currentUser && $this->currentUser['role_name'] === $role;
    }
    
    /**
     * Check if user has ANY of the given roles
     */
    public function hasAnyRole(array $roles) {
        return $this->currentUser && in_array($this->currentUser['role_name'], $roles);
    }
    
    /**
     * Get user's role level (higher = more permissions)
     */
    public function getRoleLevel() {
        return $this->currentUser['role_level'] ?? 0;
    }
    
    /**
     * Check if user can manage another user (based on role level)
     */
    public function canManageUser($targetUserId) {
        if (!$this->can('users.edit')) {
            return false;
        }
        
        // Get target user's role level
        $stmt = $this->pdo->prepare("
            SELECT r.level
            FROM users u
            LEFT JOIN role_user ru ON u.id = ru.user_id
            LEFT JOIN roles r ON ru.role_id = r.id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([$targetUserId]);
        $targetLevel = $stmt->fetchColumn();
        
        // Can only manage users with lower role level
        return $this->getRoleLevel() > $targetLevel;
    }
    
    /**
     * Require a specific permission or throw exception
     */
    public function requirePermission($permission, $message = null) {
        if (!$this->can($permission)) {
            $message = $message ?? "You don't have permission to perform this action ($permission)";
            http_response_code(403);
            throw new Exception($message);
        }
    }
    
    /**
     * Require a specific role or throw exception
     */
    public function requireRole($role, $message = null) {
        if (!$this->hasRole($role)) {
            $message = $message ?? "You must be a $role to perform this action";
            http_response_code(403);
            throw new Exception($message);
        }
    }
    
    /**
     * Get all user permissions
     */
    public function getPermissions() {
        return $this->permissions;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    /**
     * Check if user is SuperAdmin
     */
    public function isSuperAdmin() {
        return $this->hasRole('superadmin');
    }
    
    /**
     * Check if user is Admin (SuperAdmin or Admin role)
     */
    public function isAdmin() {
        return $this->hasAnyRole(['superadmin', 'admin']);
    }
    
    /**
     * Get all available permissions from database
     */
    public function getAllPermissions() {
        $stmt = $this->pdo->query("
            SELECT name, category, description 
            FROM permissions 
            ORDER BY category, name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all roles
     */
    public function getAllRoles() {
        $stmt = $this->pdo->query("
            SELECT * FROM roles 
            WHERE is_active = 1 
            ORDER BY level DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Assign role to user
     */
    public function assignRole($userId, $roleId, $assignedBy = null) {
        $this->requirePermission('users.edit');
        
        $assignedBy = $assignedBy ?? $this->currentUser['id'];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO role_user (user_id, role_id, assigned_by) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE assigned_by = ?, assigned_at = NOW()
        ");
        
        return $stmt->execute([$userId, $roleId, $assignedBy, $assignedBy]);
    }
    
    /**
     * Remove role from user
     */
    public function removeRole($userId, $roleId) {
        $this->requirePermission('users.edit');
        
        $stmt = $this->pdo->prepare("DELETE FROM role_user WHERE user_id = ? AND role_id = ?");
        return $stmt->execute([$userId, $roleId]);
    }
    
    /**
     * Create new role
     */
    public function createRole($name, $displayName, $permissions, $level, $description = null) {
        $this->requirePermission('settings.edit');
        
        $stmt = $this->pdo->prepare("
            INSERT INTO roles (name, display_name, description, level, permissions) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $permissionsJson = json_encode($permissions);
        return $stmt->execute([$name, $displayName, $description, $level, $permissionsJson]);
    }
    
    /**
     * Update role permissions
     */
    public function updateRole($roleId, $permissions) {
        $this->requirePermission('settings.edit');
        
        $stmt = $this->pdo->prepare("
            UPDATE roles 
            SET permissions = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        $permissionsJson = json_encode($permissions);
        return $stmt->execute([$permissionsJson, $roleId]);
    }
}
