<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
$db = App\Classes\Database::getInstance();
try {
    // Use direct PDO to surface detailed error messages for debugging
    $pdo = $db->getPdo();

    // Select only existing user columns (workplace_status may not exist in this schema)
    $stmt = $pdo->prepare('SELECT id, name, profile_image FROM users WHERE id = :id');
    $stmt->execute(['id' => 27]);
    $u = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    $stmt = $pdo->prepare('SELECT id, name, status, is_active FROM carwashes WHERE user_id = :id');
    $stmt->execute(['id' => 27]);
    $cw = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    echo "User:\n" . json_encode($u, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
    echo "Carwashes:\n" . json_encode($cw, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (\PDOException $e) {
    echo 'PDO ERR: '. $e->getMessage() . "\n" . $e->getTraceAsString();
} catch (Exception $e) {
    echo 'ERR: '. $e->getMessage() . "\n" . $e->getTraceAsString();
}
