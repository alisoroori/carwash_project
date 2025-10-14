<?php
function requireLogin()
{
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /carwash_project/frontend/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}
