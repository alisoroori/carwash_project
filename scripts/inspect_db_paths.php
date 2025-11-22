<?php
require_once __DIR__ . '/../backend/includes/bootstrap.php';
use App\Classes\Database;

$r = new ReflectionClass('App\\Classes\\Database');
echo "Database class file: " . $r->getFileName() . PHP_EOL;
echo "Database class dir: " . dirname($r->getFileName()) . PHP_EOL;
echo "Computed debug path: " . realpath(dirname($r->getFileName()) . '/../../logs/db_insert_debug.log') . PHP_EOL;

// Also show current working dir and __DIR__ for this script
echo "Script __DIR__: " . __DIR__ . PHP_EOL;
echo "CWD: " . getcwd() . PHP_EOL;
