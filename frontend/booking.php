<?php
include $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/backend/includes/header.php';
$html = file_get_contents(__DIR__ . '/booking.html');
if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $m)) { echo $m[1]; } else { echo $html; }
include $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/backend/includes/footer.php';
?>
