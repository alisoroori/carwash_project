<?php
class Auth {
  private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($email, $password) {
        // Sanitize inputs
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Prepare statement
        $stmt = $this->conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception('Database error');
        }

        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            throw new Exception('Database error during execution');
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Invalid email or password');
        }

        $user = $result->fetch_assoc();
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new Exception('Invalid email or password');
        }

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];

        // Remove sensitive data before returning
        unset($user['password']);
        
        return [
            'user' => $user,
            'token' => $this->generateToken($user['id'])
        ];
    }

    private function generateToken($userId) {
        $token = bin2hex(random_bytes(32));
        
        // Store token in database
        $stmt = $this->conn->prepare("INSERT INTO user_tokens (user_id, token, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param('is', $userId, $token);
        $stmt->execute();
        
        return $token;
    }

    public function register($data) {
        // Validate required fields
        $required = ['name', 'email', 'password', 'role'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $data['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email already registered');
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $this->conn->prepare("INSERT INTO users (username, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        
        // Generate unique username if not provided
        $username = $data['username'] ?? strtolower(explode('@', $data['email'])[0]);
        $base_username = $username;
        $counter = 1;
        
        // Check if username exists and modify if needed
        while (true) {
            $check_stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->bind_param('s', $username);
            $check_stmt->execute();
            if (!$check_stmt->get_result()->fetch_assoc()) {
                break; // Username is available
            }
            $username = $base_username . $counter;
            $counter++;
        }
        
        $stmt->bind_param('sssss', $username, $data['name'], $data['email'], $hashedPassword, $data['role']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create user');
        }

        return [
            'id' => $stmt->insert_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role']
        ];
    }

    public function logout() {
        session_start();
        session_destroy();
        return true;
    }
}