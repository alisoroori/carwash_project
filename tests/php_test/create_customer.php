<?php
// filepath: c:\xampp\htdocs\carwash_project\create_customer.php

require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Database;

try {
    // Initialize database connection
    $db = Database::getInstance();
    
    // Check if user already exists
    $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = :email", [
        'email' => 'ali@customer.com'
    ]);
    
    if ($existingUser) {
        echo "<h3>User already exists!</h3>";
        echo "<p>You can login with:</p>";
        echo "<p><strong>Email:</strong> ali@customer.com<br>";
        echo "<strong>Password:</strong> 12345678</p>";
        echo "<p><a href='backend/auth/login.php'>Go to login page</a></p>";
    } else {
        // Create new customer user
        $userId = $db->insert('users', [
            'full_name' => 'Ali',
            'email' => 'ali@customer.com',
            'password' => password_hash('12345678', PASSWORD_DEFAULT),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($userId) {
            echo "<h3>✅ Customer account created successfully!</h3>";
            echo "<p><strong>Customer Login Details:</strong></p>";
            echo "<p><strong>Email:</strong> ali@customer.com<br>";
            echo "<strong>Password:</strong> 12345678<br>";
            echo "<strong>Role:</strong> customer</p>";
            echo "<p><a href='backend/auth/login.php'>Go to login page</a></p>";
        } else {
            echo "<h3>❌ Failed to create user account.</h3>";
            echo "<p>Please check the database connection and try again.</p>";
        }
    }
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure your database connection is working and the 'users' table exists.</p>";
}
