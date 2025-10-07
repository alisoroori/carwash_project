<?php
// db.php
require_once 'config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database connection failed");
}

// تست اتصال
if ($conn && !$conn->connect_error) {
    echo "✅ Database connected successfully!";
} else {
    echo "❌ Database connection failed!";
}
