<?php
$ROOT = realpath(__DIR__ . '/..');
chdir($ROOT);
$report = [];
$report[] = 'CWD: '.getcwd();
$report[] = 'Checking vendor/autoload.php: ' . (file_exists('vendor/autoload.php') ? 'exists' : 'missing');
$report[] = 'class_exists App\\Classes\\Database before autoload: ' . (class_exists('App\\Classes\\Database') ? 'yes' : 'no');
if (file_exists('vendor/autoload.php')) require_once 'vendor/autoload.php';
$report[] = 'class_exists App\\Classes\\Database after autoload: ' . (class_exists('App\\Classes\\Database') ? 'yes' : 'no');
$report[] = 'Checking backend/includes/db.php: ' . (file_exists('backend/includes/db.php') ? 'exists' : 'missing');
if (file_exists('backend/includes/db.php')) require_once 'backend/includes/db.php';
$report[] = 'isset($conn): ' . (isset($conn) ? 'yes' : 'no');
$report[] = 'function getDBConnection exists: ' . (function_exists('getDBConnection') ? 'yes' : 'no');
if (isset($conn)) $report[] = '\$conn is type: ' . gettype($conn) . (is_object($conn)?(' class '.get_class($conn)):'');
echo implode("\n", $report) . "\n";
