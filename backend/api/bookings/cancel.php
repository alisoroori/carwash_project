<?php
// Compatibility wrapper: forward to new carwash scoped endpoint which handles both carwash and user cancellations
require_once __DIR__ . '/../../includes/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../carwash/reservations/cancel.php';
