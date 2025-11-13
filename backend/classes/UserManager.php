<?php
namespace App\Classes;

use App\Classes\Database;
use App\Classes\Validator;

class UserManager
{
    /**
     * Create a new user.
     * Returns an array: ['success' => bool, 'message' => string, 'id' => int|null, 'errors' => array]
     */
    public static function create(array $data): array
    {
        $validator = new Validator();
        $validator
            ->required($data['full_name'] ?? null, 'Full Name')
            ->required($data['role'] ?? null, 'Role')
            ->required($data['email'] ?? null, 'Email')
            ->email($data['email'] ?? null, 'Email')
            ->required($data['password'] ?? null, 'Password')
            ->minLength($data['password'] ?? null, 6, 'Password');

        $validRoles = ['admin', 'customer', 'staff', 'carwash'];
        if (!in_array($data['role'] ?? '', $validRoles, true)) {
            $validator->addError('Role', 'Invalid role selected.');
        }

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'id' => null,
                'errors' => $validator->getErrors(),
            ];
        }

        try {
            $db = Database::getInstance();

            // check existing email
            $existing = $db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $data['email']]);
            if ($existing) {
                return ['success' => false, 'message' => 'Email already exists.', 'id' => null, 'errors' => ['Email already exists']];
            }

            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $username = $data['email'];

            $userId = $db->insert('users', [
                'username' => $username,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'role' => $data['role'],
                'is_active' => 1,
                'email_verified' => 0,
            ]);

            if ($userId) {
                return ['success' => true, 'message' => 'New user has been created successfully.', 'id' => $userId, 'errors' => []];
            }

            return ['success' => false, 'message' => 'Failed to create user. Please check database connection and table structure.', 'id' => null, 'errors' => []];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'id' => null, 'errors' => [$e->getMessage()]];
        }
    }
}
