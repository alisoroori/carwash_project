<?php
session_start();
$_SESSION['user_id'] = 1;
$ch = curl_init('http://localhost/carwash_project/backend/dashboard/vehicle_api.php?action=check_images');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
$response = curl_exec($ch);
curl_close($ch);
echo $response;
?>