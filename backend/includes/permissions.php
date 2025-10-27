<?php
class Permissions
{
    private $conn;
    private $user_id;
    private $user_role;

    public function __construct($conn, $user_id, $user_role)
    {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->user_role = $user_role;
    }

    public function can($action)
    {
        $permissions = $this->getRolePermissions();
        return in_array($action, $permissions);
    }

    private function getRolePermissions()
    {
        // Define role-based permissions
        $permissions = [
            'admin' => [
                'view_admin_dashboard',
                'manage_carwash',
                'manage_users',
                'view_reports',
                'manage_settings',
                'manage_content',
                'manage_reviews'
            ],
            'carwash' => [
                'view_carwash_dashboard',
                'manage_services',
                'manage_appointments',
                'view_carwash_reports',
                'respond_reviews',
                'manage_schedule'
            ],
            'user' => [
                'view_user_dashboard',
                'book_appointment',
                'manage_profile',
                'write_review',
                'view_history'
            ]
        ];

        return $permissions[$this->user_role] ?? [];
    }

    public function checkPermission($action)
    {
        if (!$this->can($action)) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'error' => 'Permission denied'
            ]));
        }
    }
}

// Usage Example:
/*
session_start();
require_once 'db.php';
require_once 'permissions.php';

$permissions = new Permissions($conn, $_SESSION['user_id'], $_SESSION['user_role']);

// Check permission before action
$permissions->checkPermission('view_admin_dashboard');
*/
