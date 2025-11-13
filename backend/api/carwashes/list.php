<?php

require_once '../includes/api_bootstrap.php';


try {
    require_once __DIR__ . '/../../../vendor/autoload.php';
} catch (Throwable $e) {
    // ignore — we'll try PDO fallback below
}

$carwashes = [];

// Primary: use App\Classes\Database if available
if (class_exists('\App\Classes\Database')) {
    try {
        $db = \App\Classes\Database::getInstance();
    $rows = $db->fetchAll("SELECT id, business_name AS name, city, district FROM carwash_profiles ORDER BY business_name ASC");
        foreach ($rows as $r) {
            $carwashes[] = [ 'id' => (int)$r['id'], 'name' => $r['name'], 'city' => $r['city'] ?? '', 'district' => $r['district'] ?? '' ];
        }
    } catch (Throwable $e) {
        error_log('carwashes/list.php: DB query failed: ' . $e->getMessage());
    }
}

// Secondary: try a minimal PDO connection using env/constants if Database class isn't available
if (empty($carwashes)) {
    try {
        $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : '127.0.0.1');
        $name = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'carwash_db');
        $user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root');
        $pass = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $stmt = $pdo->query("SELECT id, business_name AS name, city, district FROM carwash_profiles ORDER BY business_name ASC LIMIT 1000");
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $carwashes[] = [ 'id' => (int)$r['id'], 'name' => $r['name'], 'city' => $r['city'] ?? '', 'district' => $r['district'] ?? '' ];
        }
    } catch (Throwable $e) {
        error_log('carwashes/list.php: PDO fallback failed: ' . $e->getMessage());
    }
}

// Final fallback: static embedded list
if (empty($carwashes)) {
    $carwashes = [
        [ 'id' => 1, 'name' => 'CarWash Merkez', 'city' => 'İstanbul', 'district' => 'Kadıköy' ],
        [ 'id' => 2, 'name' => 'CarWash Premium', 'city' => 'İstanbul', 'district' => 'Beşiktaş' ],
        [ 'id' => 3, 'name' => 'CarWash Express', 'city' => 'İstanbul', 'district' => 'Üsküdar' ],
        [ 'id' => 4, 'name' => 'Ankara Oto Yıkama', 'city' => 'Ankara', 'district' => 'Çankaya' ],
        [ 'id' => 5, 'name' => 'İzmir Hızlı Yıkama', 'city' => 'İzmir', 'district' => 'Konak' ],
        [ 'id' => 6, 'name' => 'Bursa Hızlı Yıkama', 'city' => 'Bursa', 'district' => 'Nilüfer' ],
        [ 'id' => 7, 'name' => 'Antalya Premium', 'city' => 'Antalya', 'district' => 'Muratpaşa' ],
    ];
}

echo json_encode($carwashes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
