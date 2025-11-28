<?php
// Test the get_reservations API directly
session_start();
$_SESSION['user'] = ['id' => 1, 'email' => 'test@example.com'];
$_SESSION['user_id'] = 1;

require_once 'backend/api/get_reservations.php';
?>