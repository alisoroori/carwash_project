<?php
define('BASE_PATH', 'C:/xampp/htdocs/carwash_project');

// Validate base path
if (!is_dir(BASE_PATH)) {
    die('Error: Invalid BASE_PATH configuration. Directory does not exist: ' . BASE_PATH);
}

// ...existing code...