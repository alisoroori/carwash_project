<?php
// Compatibility wrapper: forward to new carwash scoped endpoint
require_once __DIR__ . '/../../includes/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// include the new implementation
require_once __DIR__ . '/../../carwash/reservations/approve.php';
