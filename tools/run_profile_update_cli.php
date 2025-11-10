<?php
// CLI smoke test: simulate a POST to Customer_Dashboard_process.php (action=update_profile)
// Run: php tools/run_profile_update_cli.php

// Ensure we run from project root
chdir(__DIR__ . '/..');

// Emulate server globals
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Start a named session so handler's session_start() will use same session
session_id('smoketest');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Set authenticated user and csrf token
$_SESSION['user_id'] = 1;
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

// Populate POST fields expected by update_profile action
$_POST = array_merge($_POST, [
    'action' => 'update_profile',
    'name' => 'Smoke Test',
    'surname' => 'Tester',
    'email' => 'smoke+test@example.test',
    'phone' => '5550001111',
    'home_phone' => '021234567',
    'national_id' => '12345678901',
    'address' => '123 Test St',
    'city' => 'istanbul',
    'csrf_token' => $_SESSION['csrf_token']
]);

// Run the handler - it will echo JSON and exit
require __DIR__ . '/../backend/dashboard/Customer_Dashboard_process.php';
