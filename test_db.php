<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
use App\Classes\Database;
try {
    $db = Database::getInstance();
    echo 'DB connected successfully';
} catch (Exception $e) {
    echo 'DB error: ' . $e->getMessage();
}
?>