<?php
// Simple round-trip test for business info persistence (DEV only)
// Usage (dry-run): php .\tools\roundtrip_test.php --user=14 --name="New Name" --mobile="0550000000"
// To apply changes: add --apply

require_once __DIR__ . '/../backend/includes/bootstrap.php';
use App\Classes\Database;

$opts = getopt('', ['user:', 'name::', 'mobile::', 'apply']);
$userId = isset($opts['user']) ? (int)$opts['user'] : 0;
if (!$userId) {
    echo "Usage: php tools/roundtrip_test.php --user=14 [--name='Name'] [--mobile='055'] [--apply]\n";
    exit(1);
}

$name = $opts['name'] ?? null;
$mobile = $opts['mobile'] ?? null;
$apply = isset($opts['apply']);

$db = Database::getInstance();
$pdo = $db->getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Round-trip test for user_id={$userId}\n";

// detect business_profiles
$tbl = null;
$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tbl");
$stmt->execute(['tbl' => 'business_profiles']);
if ((int)$stmt->fetchColumn() > 0) {
    $tbl = 'business_profiles';
}
else {
    $stmt->execute(['tbl' => 'carwashes']);
    if ((int)$stmt->fetchColumn() > 0) {
        $tbl = 'carwashes';
    }
}

if (!$tbl) {
    echo "No business/profile table found (checked business_profiles and carwash_profiles)\n";
    exit(1);
}

echo "Detected table: {$tbl}\n";

// Build dry-run report
$report = [
    'user_id' => $userId,
    'table' => $tbl,
    'intended' => ['business_name' => $name, 'mobile_phone' => $mobile],
];

if ($apply) {
    echo "Applying changes to DB...\n";
    if ($tbl === 'business_profiles') {
        // upsert simple fields
        $sql = "SELECT id FROM business_profiles WHERE user_id = :uid LIMIT 1";
        $f = $pdo->prepare($sql);
        $f->execute(['uid' => $userId]);
        $row = $f->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $updates = [];
            $params = ['uid' => $userId];
            if ($name !== null) { $updates[] = 'business_name = :bn'; $params['bn'] = $name; }
            if ($mobile !== null) { $updates[] = 'mobile_phone = :mp'; $params['mp'] = $mobile; }
            if (!empty($updates)) {
                $pdo->prepare('UPDATE business_profiles SET ' . implode(',', $updates) . ' WHERE user_id = :uid')->execute($params);
            }
        } else {
            $pdo->prepare('INSERT INTO business_profiles (user_id, business_name, mobile_phone, created_at) VALUES (:uid, :bn, :mp, NOW())')
                ->execute(['uid' => $userId, 'bn' => $name ?? '', 'mp' => $mobile ?? '']);
        }
    } else {
        // Persist into canonical `carwashes` table
        $sql = "SELECT id, social_media FROM carwashes WHERE user_id = :uid LIMIT 1";
        $f = $pdo->prepare($sql);
        $f->execute(['uid' => $userId]);
        $row = $f->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $sm = [];
            if (!empty($row['social_media'])) {
                $sm = json_decode($row['social_media'], true) ?: [];
            }
            if ($mobile !== null) $sm['mobile_phone'] = $mobile;
            if ($name !== null) {
                $pdo->prepare('UPDATE carwashes SET business_name = :bn, social_media = :sm WHERE id = :id')
                    ->execute(['bn' => $name, 'sm' => json_encode($sm, JSON_UNESCAPED_UNICODE), 'id' => $row['id']]);
            } else {
                $pdo->prepare('UPDATE carwashes SET social_media = :sm WHERE id = :id')
                    ->execute(['sm' => json_encode($sm, JSON_UNESCAPED_UNICODE), 'id' => $row['id']]);
            }
        } else {
            $sm = [];
            if ($mobile !== null) $sm['mobile_phone'] = $mobile;
            $pdo->prepare('INSERT INTO carwashes (user_id, business_name, social_media, created_at) VALUES (:uid, :bn, :sm, NOW())')
                ->execute(['uid' => $userId, 'bn' => $name ?? '', 'sm' => json_encode($sm, JSON_UNESCAPED_UNICODE)]);
        }
    }
}

// Read back normalized record
if ($tbl === 'business_profiles') {
    $r = $pdo->prepare('SELECT id,user_id,business_name,address,postal_code,phone AS phone,mobile_phone AS mobile_phone,email AS email,working_hours AS working_hours,logo_path,created_at,updated_at FROM business_profiles WHERE user_id = :uid LIMIT 1');
    $r->execute(['uid' => $userId]);
    $out = $r->fetch(PDO::FETCH_ASSOC) ?: [];
} else {
    $r = $pdo->prepare('SELECT id,user_id, COALESCE(name,business_name) AS business_name, address, postal_code, COALESCE(phone,contact_phone) AS phone, COALESCE(mobile_phone,NULL) AS mobile_phone, COALESCE(email,contact_email) AS email, COALESCE(working_hours,opening_hours) AS working_hours, COALESCE(logo_path,featured_image) AS logo_path, social_media, created_at, updated_at FROM carwashes WHERE user_id = :uid LIMIT 1');
    $r->execute(['uid' => $userId]);
    $out = $r->fetch(PDO::FETCH_ASSOC) ?: [];
    if (!empty($out['social_media'])) {
        $sm = json_decode($out['social_media'], true) ?: [];
        foreach (['mobile_phone','mobile','phone','telephone','tel'] as $k) {
            if (!empty($sm[$k])) { $out['mobile_phone'] = $sm[$k]; break; }
        }
        if (empty($out['mobile_phone']) && isset($sm['whatsapp'])) {
            if (is_array($sm['whatsapp'])) $out['mobile_phone'] = $sm['whatsapp']['number'] ?? $sm['whatsapp']['phone'] ?? $out['mobile_phone'];
            elseif (is_string($sm['whatsapp'])) $out['mobile_phone'] = $sm['whatsapp'];
        }
        unset($out['social_media']);
    }
}

echo "DRY RUN: " . ($apply ? 'APPLIED' : 'NO-OP') . "\n";
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

exit(0);
