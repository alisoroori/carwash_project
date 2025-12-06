<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
$pdo = App\Classes\Database::getInstance()->getPdo();
$pdo->exec("UPDATE carwashes SET status='Açık', is_active=1, updated_at=NOW() WHERE user_id=27");
echo "Reset carwash for user 27 to Açık / is_active=1\n";

$row = App\Classes\Database::getInstance()->fetchOne('SELECT id, name, status, is_active FROM carwashes WHERE user_id=27');
print_r($row);
