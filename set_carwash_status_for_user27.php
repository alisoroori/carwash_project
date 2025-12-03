<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
$db = App\Classes\Database::getInstance();
try {
    $pdo = $db->getPdo();
    $stmt = $pdo->prepare('UPDATE carwashes SET status = :s WHERE user_id = :uid');
    $stmt->execute(['s' => 'Kapalı', 'uid' => 27]);
    echo "Updated carwashes for user 27 to 'Kapalı'\n";
    $r = $db->fetchAll("SELECT id, name, status, is_active FROM carwashes WHERE user_id = :uid", ['uid' => 27]);
    echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
