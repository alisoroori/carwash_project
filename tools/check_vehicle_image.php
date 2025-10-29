<?php
// tools/check_vehicle_image.php
// Improved checker: maps DB image_path values to filesystem paths and reports clear existence/status.

// Try to load project's bootstrap if available (so DB credentials come from project). Fallback to manual PDO.
if (file_exists(__DIR__ . '/../backend/includes/bootstrap.php')) {
  require_once __DIR__ . '/../backend/includes/bootstrap.php';
}

// Create PDO if $pdo not already provided by bootstrap
if (!isset($pdo) || !$pdo instanceof PDO) {
  // Adjust these if your local DB credentials differ
  $pdo = new PDO('mysql:host=127.0.0.1;dbname=carwash_db;charset=utf8mb4','root','', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
}

$projectRoot = realpath(__DIR__ . '/..'); // c:/xampp/htdocs/carwash_project
if ($projectRoot === false) $projectRoot = __DIR__ . '/..';

$stmt = $pdo->query("SELECT id, user_id, image_path FROM user_vehicles ORDER BY id DESC LIMIT 50");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
  $id = $r['id'];
  $uid = $r['user_id'];
  $raw = trim((string)($r['image_path'] ?? ''));

  if ($raw === '') {
    echo "id={$id} user_id={$uid} image_path=EMPTY exists_on_disk=NO\n";
    continue;
  }

  $path = str_replace('\\', '/', $raw);

  $serverPath = null;

  // Absolute Windows path (e.g., C:/... or C:\...)
  if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
    $serverPath = $path;
  }
  // Starts with web project prefix
  elseif (strpos($path, '/carwash_project') === 0) {
    $rel = substr($path, strlen('/carwash_project'));
    $serverPath = rtrim($projectRoot, '\\/') . $rel;
  }
  // leading slash (assume relative to project root)
  elseif (strpos($path, '/') === 0) {
    $serverPath = rtrim($projectRoot, '\\/') . $path;
  }
  // starts with uploads/ -> likely returned by some helpers; map to backend/uploads/
  elseif (strpos($path, 'uploads/') === 0) {
    $serverPath = rtrim($projectRoot, '\\/') . '/backend/' . $path;
  }
  // starts with backend/ -> map under project root
  elseif (strpos($path, 'backend/') === 0) {
    $serverPath = rtrim($projectRoot, '\\/') . '/' . $path;
  }
  else {
    // fallback: assume filename stored; check backend/uploads/vehicles/
    $serverPath = rtrim($projectRoot, '\\/') . '/backend/uploads/vehicles/' . basename($path);
  }

  // Normalize slashes for PHP
  $serverPath = str_replace('/', DIRECTORY_SEPARATOR, $serverPath);

  $exists = is_file($serverPath) && is_readable($serverPath);
  $size = $exists ? filesize($serverPath) : 0;

  echo sprintf("id=%s user_id=%s image_path=%s exists_on_disk=%s server_path=%s size=%d\n",
    $id, $uid, $raw, $exists ? 'YES' : 'NO', $serverPath, $size
  );
}