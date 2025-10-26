<?php
// filepath: c:\xampp\htdocs\carwash_project\setup_database.php

/**
 * CarWash Project - Database and Admin User Setup Script
 * 
 * This script:
 * 1. Creates the 'carwash' database if it doesn't exist
 * 2. Creates necessary tables if they don't exist
 * 3. Creates a superadmin user
 */

// Try to create database first without loading autoloader
try {
    // Connect without selecting a database
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS carwash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'carwash' created or verified successfully.<br>";
    
    // Select the database
    $pdo->exec("USE carwash");
    
    // Create users table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'carwash', 'customer') NOT NULL DEFAULT 'customer',
            status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
            email_verified_at DATETIME NULL,
            remember_token VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Users table created or verified successfully.<br>";
    
    // Now load the autoloader for the project classes
    require_once __DIR__ . '/vendor/autoload.php';
        
    // 'use' declarations must be at file scope; we'll reference the class with its fully-qualified name below.
    
    // Check if admin user exists
        $db = \App\Classes\Database::getInstance();
        $adminExists = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = :role AND email = :email", [
            'role' => 'admin',
            'email' => 'admin@carwash.com'
        ]);
    
    if (!$adminExists || $adminExists['count'] == 0) {
        // Create admin user with secure password hash
        $userId = $db->insert('users', [
            'full_name' => 'Super Administrator',
            'email' => 'admin@carwash.com',
            'password' => password_hash('Admin@123456', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($userId) {
            echo "✅ Super Admin user created successfully!<br>";
            echo "<strong>Email:</strong> admin@carwash.com<br>";
            echo "<strong>Password:</strong> Admin@123456<br>";
            echo "<strong>Role:</strong> admin<br>";
        } else {
            echo "❌ Failed to create admin user.<br>";
        }
    } else {
        echo "ℹ️ Admin user already exists.<br>";
    }
    
    echo "<br><strong>Setup Complete!</strong> You can now <a href='backend/auth/login.php'>login to the admin panel</a>.";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Please make sure your MySQL server is running and accessible.";
}
?>