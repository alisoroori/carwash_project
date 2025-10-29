<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
}

$carwashId = isset($_GET['carwash_id']) ? (int)$_GET['carwash_id'] : null;

$services = [];

if (!$carwashId) {
    echo json_encode($services);
    exit;
}

try {
    if (class_exists('\App\\Classes\\Database')) {
        $db = \App\Classes\Database::getInstance();
        $rows = $db->fetchAll('SELECT id, name, price FROM services WHERE carwash_id = :cw AND status = "active" ORDER BY name ASC', ['cw' => $carwashId]);
        foreach ($rows as $r) {
            $services[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'price' => $r['price']];
        }
    } else {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'carwash_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $stmt = $pdo->prepare('SELECT id, name, price FROM services WHERE carwash_id = :cw AND status = "active" ORDER BY name ASC');
        $stmt->execute(['cw' => $carwashId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $services[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'price' => $r['price']];
        }
    }
} catch (Throwable $e) {
    error_log('services/list.php error: ' . $e->getMessage());
}

echo json_encode($services, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
