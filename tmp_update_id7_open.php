<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
$db = App\Classes\Database::getInstance();
$pdo = $db->getPdo();
$upd = $pdo->prepare("UPDATE carwashes SET status = :s, is_active = :ia, updated_at = NOW() WHERE id = :id");
$upd->execute(['s' => 'Açık', 'ia' => 1, 'id' => 7]);
$row = $db->fetchOne('SELECT id, name, status, is_active FROM carwashes WHERE id = :id', ['id' => 7]);
echo "Updated:\n";
echo sprintf("ID: %d\nName: %s\nstatus: '%s'\nis_active: %s\n", $row['id'], $row['name'], $row['status'], $row['is_active']);
