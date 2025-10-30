<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/db.php';
require_once __DIR__ . '/backend/classes/Session.php';

Session::start();

// Simulate authenticated session
$_SESSION['user_id'] = 14;

// Simulate GET request for check_images action
$_GET['action'] = 'check_images';

include __DIR__ . '/backend/dashboard/vehicle_api.php';
?>