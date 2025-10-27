<?php
require_once '../includes/db.php';

class UserRegistration
{
    private $conn;
    private $uploadDir = '../uploads/profiles/';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initUserTable();
    }

    private function initUserTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('customer', 'carwash', 'admin') DEFAULT 'customer',
            name VARCHAR(100),
            phone VARCHAR(20),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
            INDEX (email),
            INDEX (role)
        )";

        $this->conn->query($sql);
    }

    public function registerUser($userData)
    {
        try {
            $this->validateUserData($userData);

            $stmt = $this->conn->prepare("
                INSERT INTO users 
                (email, password_hash, name, phone, role)
                VALUES (?, ?, ?, ?, ?)
            ");

            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            $stmt->bind_param(
                'sssss',
                $userData['email'],
                $passwordHash,
                $userData['name'],
                $userData['phone'],
                $userData['role']
            );

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'user_id' => $this->conn->insert_id
                ];
            }

            throw new Exception('Registration failed');
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
