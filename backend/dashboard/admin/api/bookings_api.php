<?php
// bookings api stub
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../Auth.php';
Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status'=>'success','data'=>[]]);
