<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Classes\Database;
$db = Database::getInstance();

$name = "Özil Oto Yıkama";
$rows = $db->fetchAll('SELECT id, name, status, is_active FROM carwashes WHERE name LIKE :name', ['name' => "%" . $name . "%"]);
if (empty($rows)) {
    // Fallback: try known ID 7 (observed earlier) if present
    try {
        $rows = $db->fetchAll('SELECT id, name, status, is_active FROM carwashes WHERE id = :id', ['id' => 7]);
    } catch (Exception $_) {
        // ignore
    }
}
if (empty($rows)) {
    echo "No rows found for name like '{$name}'\n";
    exit(0);
}
foreach ($rows as $r) {
    $status = $r['status'] ?? '';
    $raw = $status;
    $len = mb_strlen($raw, '8bit');
    $hex = bin2hex($raw);
    // Show also normalized trimmed version and UTF-8 validation
    $trimmed = trim($raw);
    $utf8_ok = mb_check_encoding($raw, 'UTF-8') ? 'yes' : 'no';
    echo sprintf("ID: %d | Name: %s\n", $r['id'], $r['name']);
    echo sprintf("Status (raw): '%s'\n", $raw);
    echo sprintf("Status (trimmed): '%s'\n", $trimmed);
    echo sprintf("Length bytes: %d\n", $len);
    echo sprintf("Hex: %s\n", $hex);
    echo sprintf("UTF-8 valid: %s\n", $utf8_ok);
    echo "-----\n";
}
