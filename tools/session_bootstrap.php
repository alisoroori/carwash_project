<?php
// Lightweight test helper: start a session, set a dummy logged-in user and CSRF token, and return the token
// Usage: curl -c cookies.txt http://localhost:8000/tools/session_bootstrap.php
session_start();
// Use a fixed user id for local testing (ensure this user id exists in your DB if DB checks run)
$_SESSION['user_id'] = 1;
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
header('Content-Type: text/plain');
echo $_SESSION['csrf_token'];
