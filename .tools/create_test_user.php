<?php
$ROOT = realpath(__DIR__ . '/..');
chdir($ROOT);
require_once 'backend/includes/db.php';
$email = 'e2e_test@example.com';
$pw = 'E2ePass123!';
try {
    // try PDO getDBConnection
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email'=>$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        echo "User exists: id=".($u['id'] ?? $u['ID'] ?? 'unknown')."\n";
        exit(0);
    }
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,status,created_at) VALUES (:n,:e,:p,'customer','active',NOW())");
    $stmt->execute(['n'=>'E2E Test','e'=>$email,'p'=>$hash]);
    echo "Inserted user id " . $pdo->lastInsertId() . "\n";
} catch (Exception $e) {
    echo "PDO error: " . $e->getMessage() . "\n";
}

if (isset($conn) && $conn instanceof mysqli) {
    echo "mysqli available - checking via mysqli...\n";
    $emailEsc = $conn->real_escape_string($email);
    $res = $conn->query("SELECT id FROM users WHERE email='{$emailEsc}' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        echo "Found user by mysqli id=".($row['id'] ?? 'unknown')."\n";
    } else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,status,created_at) VALUES (?, ?, ?, 'customer', 'active', NOW())");
        $name = 'E2E Test';
        $stmt->bind_param('sss', $name, $email, $hash);
        if ($stmt->execute()) {
            echo "Inserted user with mysqli id=" . $conn->insert_id . "\n";
        } else {
            echo "mysqli insert failed: " . $stmt->error . "\n";
        }
    }
}
