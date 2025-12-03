<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Classes\Database;

$db = Database::getInstance();

$names = [
    'Özil Oto Yıkama',
    'Express Auto Spa',
];

// Query both names exactly (case-sensitive as stored) using IN
$placeholders = implode(',', array_fill(0, count($names), '?'));
$sql = 'SELECT id, name, status, is_active FROM carwashes WHERE name IN (' . $placeholders . ')';
$stmt = $db->getPdo()->prepare($sql);
$stmt->execute($names);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No rows found for the provided names.\n";
    exit(0);
}

foreach ($rows as $r) {
    $status = isset($r['status']) ? $r['status'] : '';
    $raw = $status;
    $len = mb_strlen($raw, '8bit');
    $hex = bin2hex($raw);
    $trimmed = trim($raw);
    $lower = mb_strtolower($trimmed, 'UTF-8');
    $utf8_ok = mb_check_encoding($raw, 'UTF-8') ? 'yes' : 'no';
    $is_active = isset($r['is_active']) ? $r['is_active'] : 'NULL';

    echo sprintf("ID: %d | Name: %s\n", $r['id'], $r['name']);
    echo sprintf("Status (raw): '%s'\n", $raw);
    echo sprintf("Status (trimmed): '%s'\n", $trimmed);
    echo sprintf("Status (lower): '%s'\n", $lower);
    echo sprintf("Length bytes: %d\n", $len);
    echo sprintf("Hex: %s\n", $hex);
    echo sprintf("UTF-8 valid: %s\n", $utf8_ok);
    echo sprintf("is_active: %s\n", $is_active);
    echo "-----\n";
}
