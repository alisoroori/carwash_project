<?php
/**
 * Test script: Business Info (Car Wash) controller + update flow
 * - Finds one carwash (or business_profiles) row
 * - Reads authoritative fields
 * - Begins a transaction, applies test updates, reads back, then rolls back
 * - Prints JSON summary for automated parsing
 */

require_once __DIR__ . '/../../backend/includes/bootstrap.php';
use App\Classes\Database;

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();
$pdo = $db->getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fields = [
    'business_name' => 'İşletme Adı',
    'address' => 'Adres',
    'phone' => 'Telefon',
    'mobile_phone' => 'Cep Telefonu',
    'email' => 'Email',
    'logo_path' => 'İşletme Logosu',
    'working_hours' => 'Çalışma Saatleri',
    'postal_code' => 'Posta Kodu',
    'license_number' => 'Ruhsat Numarası',
    'tax_number' => 'Vergi Numarası',
    'city' => 'Şehir',
    'district' => 'İlçe',
    'certificate_path' => 'Sertifika Yükle'
];

$result = ['success' => false, 'errors' => [], 'report' => []];

try {
    // Find a sample profile: prefer carwashes
    $check = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('carwashes','business_profiles')");
    $check->execute();
    $found = $check->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasCarwashes = in_array('carwashes', $found, true);
    $hasBusinessProfiles = in_array('business_profiles', $found, true);

    if ($hasCarwashes) {
        $table = 'carwashes';
        $fetch = $pdo->query('SELECT * FROM carwashes LIMIT 1');
        $row = $fetch->fetch(PDO::FETCH_ASSOC) ?: null;
    } elseif ($hasBusinessProfiles) {
        $table = 'business_profiles';
        $fetch = $pdo->query('SELECT * FROM business_profiles LIMIT 1');
        $row = $fetch->fetch(PDO::FETCH_ASSOC) ?: null;
    } else {
        throw new \Exception('No carwashes or business_profiles table found in DB');
    }

    if (empty($row)) {
        throw new \Exception('No profile rows found to test against');
    }

    $userId = $row['user_id'] ?? $row['id'] ?? null;
    if (!$userId) $userId = $row['user_id'] ?? null; // best effort

    // Collect original values for fields of interest
    $original = [];
    foreach ($fields as $col => $label) {
        if (array_key_exists($col, $row)) {
            $original[$col] = $row[$col];
        } else {
            // map some legacy names
            if ($col === 'business_name' && isset($row['name'])) $original[$col] = $row['name'];
            elseif ($col === 'logo_path' && isset($row['featured_image'])) $original[$col] = $row['featured_image'];
            else $original[$col] = $row[$col] ?? null;
        }
    }

    // Prepare test update values (distinct so we can detect changes)
    $nowTag = date('YmdHis');
    $testValues = [
        'business_name' => ($original['business_name'] ?? 'Test Business') . ' [test ' . $nowTag . ']',
        'address' => ($original['address'] ?? 'Test Address') . ' [test ' . $nowTag . ']',
        'phone' => '0212' . substr($nowTag, -6),
        'mobile_phone' => '05' . substr($nowTag, -10),
        'email' => 'test+' . $nowTag . '@example.com',
        'logo_path' => '/carwash_project/backend/auth/uploads/logos/test_logo_' . $nowTag . '.png',
        'working_hours' => json_encode([
            'monday' => ['start' => '09:00','end' => '17:00'],
            'tuesday' => ['start' => '09:00','end' => '17:00'],
            'wednesday' => ['start' => '09:00','end' => '17:00'],
            'thursday' => ['start' => '09:00','end' => '17:00'],
            'friday' => ['start' => '09:00','end' => '17:00'],
            'saturday' => ['start' => '10:00','end' => '14:00'],
            'sunday' => ['start' => '11:00','end' => '15:00']
        ]),
        'postal_code' => '0000' . substr($nowTag, -4),
        'license_number' => 'LIC-' . $nowTag,
        'tax_number' => 'TAX-' . $nowTag,
        'city' => 'TestCity',
        'district' => 'TestDistrict',
        'certificate_path' => '/carwash_project/backend/auth/uploads/certs/test_cert_' . $nowTag . '.pdf'
    ];

    // Begin transaction and attempt update (rolled back later)
    $pdo->beginTransaction();

    // Build update statement for the table
    $upCols = [];
    $params = [];
    foreach ($testValues as $col => $val) {
        // Only update if column actually exists in the fetched row (avoid unknown column errors)
        if ($table === 'carwashes') {
            // map business_name -> name
            $dbCol = $col === 'business_name' ? 'name' : $col;
            // don't attempt certificate if it isn't present
            if (!array_key_exists($dbCol, $row)) continue;
            $upCols[] = "$dbCol = :$dbCol";
            $params[$dbCol] = $val;
        } else {
            // business_profiles uses business_name column
            $dbCol = $col === 'business_name' ? 'business_name' : $col;
            if (!array_key_exists($dbCol, $row)) continue;
            $upCols[] = "$dbCol = :$dbCol";
            $params[$dbCol] = $val;
        }
    }

    if (empty($upCols)) {
        throw new \Exception('No updatable columns found on table ' . $table);
    }

    // Determine appropriate WHERE column: prefer user_id when present, otherwise use primary id
    if (!empty($row['user_id'])) {
        $whereCol = 'user_id';
        $whereVal = $row['user_id'];
    } else {
        $whereCol = 'id';
        $whereVal = $row['id'];
    }

    $params[$whereCol] = $whereVal;
    $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $upCols) . ' WHERE `'. $whereCol . '` = :' . $whereCol;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Fetch back
    $fetch = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE `'. $whereCol . '` = :' . $whereCol . ' LIMIT 1');
    $fetch->execute([$whereCol => $whereVal]);
    $after = $fetch->fetch(PDO::FETCH_ASSOC) ?: [];

    $report = [];
    foreach ($fields as $col => $label) {
        // map to DB column
        $dbCol = $col;
        if ($col === 'business_name') $dbCol = ($table === 'carwashes') ? 'name' : 'business_name';
        if (!array_key_exists($dbCol, $after)) { $report[$col] = ['label'=>$label,'original'=>$original[$col] ?? null,'updated'=>null,'status'=>'missing_column']; continue; }
        $report[$col] = [
            'label' => $label,
            'original' => $original[$col] ?? null,
            'updated' => $after[$dbCol] ?? null,
            'status' => ((string)($after[$dbCol] ?? '') !== (string)($original[$col] ?? '')) ? 'changed' : 'unchanged'
        ];
    }

    // Check that unrelated important columns were not modified
    $integrityCols = ['rating','total_reviews','is_active','owner_id','created_at'];
    $integrity = [];
    foreach ($integrityCols as $icol) {
        $origVal = array_key_exists($icol, $row) ? (string)$row[$icol] : null;
        $afterVal = array_key_exists($icol, $after) ? (string)$after[$icol] : null;
        $integrity[$icol] = [
            'original' => $origVal,
            'after' => $afterVal,
            'status' => ($origVal === $afterVal) ? 'unchanged' : 'changed'
        ];
    }

    // Rollback to keep DB unchanged
    $pdo->rollBack();

    $result['success'] = true;
    $result['table'] = $table;
    $result['where'] = [$whereCol => $whereVal];
    $result['report'] = $report;
    $result['integrity'] = $integrity;

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        @$pdo->rollBack();
    }
    $result['success'] = false;
    $result['errors'][] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// EOF
