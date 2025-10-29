<?php
$ROOT = realpath(__DIR__ . '/..');
chdir($ROOT);
require_once 'backend/includes/db.php';
$testEmail = 'e2e_test@example.com';
$testPassword = 'E2ePass123!';

// Detect DB object similar to E2E script
$db = null;
$useDbClass = false;
if (class_exists(\App\Classes\Database::class)) {
    try { $db = \App\Classes\Database::getInstance(); $useDbClass = true; } catch (Throwable $e) { $db = null; }
}
if (!$db && file_exists($ROOT . '/backend/includes/db.php')) {
    require_once $ROOT . '/backend/includes/db.php';
    if (isset($conn) && $conn instanceof \mysqli) {
        $db = $conn;
    } elseif (function_exists('getDBConnection')) {
        try { $maybe = getDBConnection(); if ($maybe instanceof \PDO || $maybe instanceof \mysqli) $db = $maybe; } catch (Throwable $e) { echo "getDBConnection failed: " . $e->getMessage() . "\n"; }
    }
}

var_dump(['db_type' => is_object($db)? get_class($db) : gettype($db), 'useDbClass' => $useDbClass]);

// Now try to ensure the user using the same logic as updated ensureTestUser
function ensureUserManual($db, $email, $password) {
    if ($db === null) return ['ok'=>false,'msg'=>'No DB'];
    $columns = [];
    try {
        if ($db instanceof \App\Classes\Database || (method_exists($db, 'fetchOne') && method_exists($db, 'fetchAll'))) {
            $rows = $db->fetchAll("SHOW COLUMNS FROM users");
            foreach ($rows as $r) $columns[] = $r['Field'] ?? $r['field'] ?? null;
        } elseif ($db instanceof \PDO) {
            $stmt = $db->query("SHOW COLUMNS FROM users");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) $columns[] = $r['Field'];
        } elseif ($db instanceof \mysqli) {
            $res = $db->query("SHOW COLUMNS FROM users");
            while ($r = $res->fetch_assoc()) $columns[] = $r['Field'];
        }
    } catch (Throwable $e) {
        return ['ok'=>false,'msg'=>'SHOW COLUMNS failed: '.$e->getMessage()];
    }
    $columns = array_filter(array_map('strval', $columns));
    var_dump(['detected_columns'=>$columns]);

    try {
        if ($db instanceof \App\Classes\Database || (method_exists($db, 'fetchOne') && method_exists($db, 'fetchAll'))) {
            $user = $db->fetchOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
            if ($user) return ['ok' => true, 'user' => $user];
            $data = [];
            if (in_array('name', $columns)) $data['name'] = 'E2E Test';
            if (in_array('email', $columns)) $data['email'] = $email;
            if (in_array('password', $columns)) $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            if (in_array('role', $columns)) $data['role'] = 'customer';
            if (in_array('status', $columns)) $data['status'] = 'active';
            if (in_array('created_at', $columns)) $data['created_at'] = date('Y-m-d H:i:s');
            $id = $db->insert('users', $data);
            return ['ok' => true, 'id' => $id];
        }
        if ($db instanceof \PDO) {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user) return ['ok' => true, 'user' => $user];
            $cols = [];
            $placeholders = [];
            $params = [];
            if (in_array('name', $columns)) { $cols[] = 'name'; $placeholders[] = ':n'; $params[':n'] = 'E2E Test'; }
            if (in_array('email', $columns)) { $cols[] = 'email'; $placeholders[] = ':e'; $params[':e'] = $email; }
            if (in_array('password', $columns)) { $cols[] = 'password'; $placeholders[] = ':p'; $params[':p'] = password_hash($password, PASSWORD_DEFAULT); }
            if (in_array('role', $columns)) { $cols[] = 'role'; $placeholders[] = ':r'; $params[':r'] = 'customer'; }
            if (in_array('status', $columns)) { $cols[] = 'status'; $placeholders[] = ':s'; $params[':s'] = 'active'; }
            if (in_array('created_at', $columns)) { $cols[] = 'created_at'; $placeholders[] = 'NOW()'; }
            if (empty($cols)) { $cols = ['email','password']; $placeholders = [':e', ':p']; $params = [':e'=>$email,':p'=>password_hash($password, PASSWORD_DEFAULT)]; }
            $colsStr = implode(',', $cols);
            $placeholdersStr = implode(',', array_map(function($p){ return $p==='NOW()'? 'NOW()' : $p; }, $placeholders));
            $query = "INSERT INTO users ($colsStr) VALUES ($placeholdersStr)";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $id = (int)$db->lastInsertId();
            return ['ok'=>true,'id'=>$id];
        }
        if ($db instanceof \mysqli) {
            $stmt = $db->prepare("SELECT id,email,name FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            if ($user) return ['ok'=>true,'user'=>$user];
            $cols = [];
            $placeholders = [];
            $values = [];
            if (in_array('name', $columns)) { $cols[] = 'name'; $placeholders[] = '?'; $values[] = 'E2E Test'; }
            if (in_array('email', $columns)) { $cols[] = 'email'; $placeholders[] = '?'; $values[] = $email; }
            if (in_array('password', $columns)) { $cols[] = 'password'; $placeholders[] = '?'; $values[] = password_hash($password, PASSWORD_DEFAULT); }
            if (in_array('role', $columns)) { $cols[] = 'role'; $placeholders[] = '?'; $values[] = 'customer'; }
            if (in_array('status', $columns)) { $cols[] = 'status'; $placeholders[] = '?'; $values[] = 'active'; }
            if (in_array('created_at', $columns)) { $cols[] = 'created_at'; $placeholders[] = 'NOW()'; }
            if (empty($cols)) { $cols = ['email','password']; $placeholders = ['?','?']; $values = [$email, password_hash($password, PASSWORD_DEFAULT)]; }
            $colsStr = implode(',', $cols);
            $placeholdersStr = implode(',', $placeholders);
            $query = "INSERT INTO users ($colsStr) VALUES ($placeholdersStr)";
            $prep = $db->prepare($query);
            $bindValues = array_filter($values, function($v) { return true; });
            if (!empty($bindValues)) { $types = str_repeat('s', count($bindValues)); $prep->bind_param($types, ...$bindValues); }
            $prep->execute();
            return ['ok'=>true,'id'=>$db->insert_id];
        }
    } catch (Throwable $e) {
        return ['ok'=>false,'msg'=>'DB error: ' . $e->getMessage()];
    }
    return ['ok'=>false,'msg'=>'Unsupported DB object'];
}

$result = ensureUserManual($db, $testEmail, $testPassword);
var_dump(['ensure_result'=>$result]);

