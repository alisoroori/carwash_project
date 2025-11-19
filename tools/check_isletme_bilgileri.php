<?php
// check_isletme_bilgileri.php
// Usage: edit DB config below, then run via CLI: php check_isletme_bilgileri.php
// or open in browser (dev env). Produces tools/reports/isletme_check_report.json

set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ----------------- CONFIG -----------------
$dbConfig = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'your_database_name',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];

// If user didn't edit the DB name, attempt to auto-detect from the app config
if (in_array($dbConfig['dbname'], ['your_database_name', '', null], true)) {
    $appConfig = __DIR__ . '/../backend/includes/config.php';
    if (file_exists($appConfig)) {
        // include in isolated scope to avoid polluting globals
        try {
            /** @noinspection PhpIncludeInspection */
            require_once $appConfig;
            // Use defined constants if available
            if (defined('DB_HOST')) $dbConfig['host'] = constant('DB_HOST');
            if (defined('DB_NAME')) $dbConfig['dbname'] = constant('DB_NAME');
            if (defined('DB_USER')) $dbConfig['user'] = constant('DB_USER');
            if (defined('DB_PASS')) $dbConfig['pass'] = constant('DB_PASS');
            if (defined('DB_CHARSET')) $dbConfig['charset'] = constant('DB_CHARSET');
        } catch (Throwable $e) {
            // ignore and use manual config
        }
    }
}

$carwashId = 1; // <-- change to the carwash id you want to test
$projectBaseUrl = '/carwash_project'; // used to build public logo URL
$uploadsDirRelative = __DIR__ . '/../backend/uploads/business_logo/'; // adjust if needed
$reportPath = __DIR__ . '/reports/isletme_check_report.json';
// -------------------------------------------

// Fields to check (from your list)
$fields = [
    'business_name' => 'İşletme Adı',
    'address' => 'Adres',
    'phone' => 'Telefon',
    'mobile_phone' => 'Cep Telefonu',
    'email' => 'Email',
    'logo_path' => 'İşletme Logosu',
    'working_hours' => 'Çalışma Saatleri', // assume JSON or structure
    'postal_code' => 'Posta Kodu',
    'ruhsat_no' => 'Ruhsat Numarası',
    'vergi_no' => 'Vergi Numarası',
    'city' => 'Şehir',
    'district' => 'İlçe',
    'certificate' => 'Sertifika Yükle (optional)'
];

// Helper: print for CLI and browser
function out($s = '') {
    if (php_sapi_name() === 'cli') {
        echo $s . PHP_EOL;
    } else {
        echo nl2br(htmlspecialchars($s)) . "<br>";
    }
}

$report = [
    'meta' => [
        'checked_at' => date('c'),
        'carwash_id' => $carwashId,
        'checked_by' => get_current_user(),
        'php_sapi' => php_sapi_name()
    ],
    'connection' => [],
    'schema' => [],
    'row' => null,
    'field_checks' => [],
    'logo_file_check' => [],
    'write_test' => [],
    'notes' => []
];

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $report['connection']['ok'] = true;
    out("DB connection: OK");
} catch (Exception $e) {
    $report['connection']['ok'] = false;
    $report['connection']['error'] = $e->getMessage();
    out("DB connection: FAILED - " . $e->getMessage());
    @mkdir(dirname($reportPath), 0775, true);
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    exit(1);
}

// Check that table exists
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'carwashes'");
    $stmt->execute();
    $exists = (bool)$stmt->fetchColumn();
    $report['schema']['carwashes_exists'] = $exists;
    out("Table carwashes exists: " . ($exists ? 'YES' : 'NO'));
    if (!$exists) {
        $report['notes'][] = "Table 'carwashes' does not exist in DB. Stop further checks.";
        @mkdir(dirname($reportPath), 0775, true);
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        exit(1);
    }
} catch (Exception $e) {
    $report['schema']['error'] = $e->getMessage();
    out("Error checking table existence: " . $e->getMessage());
    @mkdir(dirname($reportPath), 0775, true);
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    exit(1);
}

// Get table columns
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM `carwashes`");
    $columns = [];
    while ($row = $colsStmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    $report['schema']['columns'] = $columns;
    out("carwashes columns: " . implode(', ', $columns));
} catch (Exception $e) {
    $report['schema']['error'] = $e->getMessage();
    out("Error getting columns: " . $e->getMessage());
}

// Map expected fields to actual columns (best-effort)
$fieldMapping = [];
foreach ($fields as $colKey => $label) {
    // heuristics: direct match first, then common alternative names
    $candidates = [
        $colKey,
        $colKey,
        str_replace('_', '', $colKey),
        strtolower($colKey),
        'name',
        'title',
        'address',
        'phone',
        'mobile',
        'mobile_phone',
        'cep_telefonu',
        'logo',
        'logo_path',
        'business_logo',
        'working_hours',
        'schedule',
        'postal_code',
        'zip',
        'ruhsat_no',
        'vergi_no',
        'city',
        'district',
        'certificate'
    ];
    $found = null;
    foreach ($candidates as $c) {
        foreach ($columns as $actual) {
            if (strcasecmp($actual, $c) === 0) {
                $found = $actual;
                break 2;
            }
        }
    }
    $fieldMapping[$colKey] = $found; // null if not found
    $report['schema']['field_mapping'][$colKey] = $found;
    out("Field mapping: {$label} => " . ($found ?: '[MISSING]'));
}

// Fetch row
try {
    $selectCols = array_unique(array_filter(array_values($fieldMapping)));
    if (empty($selectCols)) $selectCols = ['*'];
    $selectSql = 'SELECT ' . implode(', ', array_map(function($c){ return "`$c`"; }, $selectCols)) . ' FROM `carwashes` WHERE id = :id LIMIT 1';
    $stmt = $pdo->prepare($selectSql);
    $stmt->execute([':id' => $carwashId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $report['row'] = $row ?: null;
    out("Fetched row for carwash id={$carwashId}: " . ($row ? 'FOUND' : 'NOT FOUND'));
    if (!$row) {
        $report['notes'][] = "No record found with id={$carwashId} in carwashes. Check the id or DB contents.";
    }
} catch (Exception $e) {
    $report['row_error'] = $e->getMessage();
    out("Error fetching row: " . $e->getMessage());
}

// Per-field checks: presence, type notes, JSON validation
foreach ($fields as $colKey => $label) {
    $actualCol = $fieldMapping[$colKey];
    $value = null;
    if ($actualCol && isset($row[$actualCol])) $value = $row[$actualCol];
    $check = [
        'label' => $label,
        'expected_key' => $colKey,
        'mapped_column' => $actualCol,
        'value_present' => $value !== null,
        'value_preview' => is_scalar($value) ? (string)mb_substr((string)$value, 0, 500) : null,
        'issues' => []
    ];

    // If working_hours check JSON
    if ($colKey === 'working_hours' && $value) {
        // try decode
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $check['json_valid'] = true;
                $check['json_sample'] = array_slice($decoded, 0, 5);
            } else {
                $check['json_valid'] = false;
                $check['issues'][] = 'working_hours JSON invalid: ' . json_last_error_msg();
            }
        } else {
            $check['json_valid'] = true;
            $check['json_sample'] = $value;
        }
    }

    // For logo_path, check file exists
    if ($colKey === 'logo_path') {
        if ($value) {
            $filename = basename($value);
            $diskPath = rtrim($uploadsDirRelative, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            $publicUrl = rtrim($projectBaseUrl, '/') . '/backend/uploads/business_logo/' . $filename;
            $exists = file_exists($diskPath);
            $check['file'] = [
                'filename' => $filename,
                'disk_path' => $diskPath,
                'public_url' => $publicUrl,
                'exists_on_disk' => $exists,
                'is_readable' => $exists ? is_readable($diskPath) : false,
                'filesize' => $exists ? filesize($diskPath) : null,
            ];
            if (!$exists) $check['issues'][] = 'Logo file not found on disk';
            // check folder perms
            $dirExists = is_dir(dirname($diskPath));
            $check['folder'] = [
                'folder_exists' => $dirExists,
                'folder_is_writable' => $dirExists ? is_writable(dirname($diskPath)) : false
            ];
            if (!$dirExists) $check['issues'][] = 'Uploads directory missing: ' . dirname($diskPath);
            if ($dirExists && !is_writable(dirname($diskPath))) $check['issues'][] = 'Uploads directory not writable';
        } else {
            $check['issues'][] = 'logo_path is empty';
        }
    }

    // Phone/email simple sanity
    if (in_array($colKey, ['email']) && $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) $check['issues'][] = 'Invalid email format';
    }
    if (in_array($colKey, ['phone','mobile_phone']) && $value) {
        $only = preg_replace('/\D+/', '', $value);
        if (strlen($only) < 7) $check['issues'][] = 'Phone number seems too short';
    }

    $report['field_checks'][$colKey] = $check;
    out("[Field] {$label}: " . ($value !== null ? 'PRESENT' : 'MISSING') . (count($check['issues']) ? ' | Issues: ' . implode('; ', $check['issues']) : ''));
}

// WRITE TEST: update attempt inside transaction then rollback
try {
    $pdo->beginTransaction();
    // prepare updates for columns that exist: set test values
    $updates = [];
    $params = [':id' => $carwashId];
    foreach ($fieldMapping as $colKey => $actualCol) {
        if (!$actualCol) continue;
        // skip logo_path (we don't move files here)
        if ($colKey === 'logo_path') continue;
        // set test value
        $testVal = "[TEST-" . date('YmdHis') . "]-" . substr($colKey,0,10);
        // for working_hours, write JSON example
        if ($colKey === 'working_hours') {
            $testVal = json_encode([
                'Mon' => ['start' => '08:00', 'end' => '18:00'],
                'Tue' => ['start' => '08:00', 'end' => '18:00']
            ]);
        }
        $updates[] = "`$actualCol` = :{$actualCol}";
        $params[":{$actualCol}"] = $testVal;
    }
    if (!empty($updates)) {
        $sql = "UPDATE `carwashes` SET " . implode(', ', $updates) . " WHERE id = :id";
        $s = $pdo->prepare($sql);
        $s->execute($params);
        $report['write_test']['attempt'] = 'UPDATE executed inside transaction (no commit)';
        $report['write_test']['rows_affected'] = $s->rowCount();
        out("Write test: executed UPDATE, rows affected: " . $s->rowCount());
    } else {
        $report['write_test']['note'] = 'No mapped columns to update';
        out("Write test: skipping, no mapped columns");
    }
    $pdo->rollBack();
    $report['write_test']['result'] = 'rolled_back';
    out("Write test: ROLLBACK - no DB changes applied");
} catch (Exception $e) {
    // if error, capture it
    $report['write_test']['error'] = $e->getMessage();
    out("Write test: ERROR - " . $e->getMessage());
    try { $pdo->rollBack(); } catch(Exception $ex){}
}

// Extra: check webserver access to uploads directory (simulate URL check via file existence)
$report['logo_uploads_dir'] = [
    'path' => $uploadsDirRelative,
    'exists' => is_dir($uploadsDirRelative),
    'is_readable' => is_dir($uploadsDirRelative) ? is_readable($uploadsDirRelative) : false,
    'is_writable' => is_dir($uploadsDirRelative) ? is_writable($uploadsDirRelative) : false,
];
out("Uploads dir check: exists=" . ($report['logo_uploads_dir']['exists'] ? 'YES':'NO') . 
    " readable=" . ($report['logo_uploads_dir']['is_readable'] ? 'YES':'NO') . 
    " writable=" . ($report['logo_uploads_dir']['is_writable'] ? 'YES':'NO'));

// Save report
@mkdir(dirname($reportPath), 0775, true);
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
out("Report written to: " . $reportPath);

// Final summary
out("");
out("SUMMARY:");
out(" - DB connection: " . ($report['connection']['ok'] ? 'OK' : 'FAIL'));
out(" - carwashes table: " . ($report['schema']['carwashes_exists'] ? 'present' : 'missing'));
if (isset($report['row']) && $report['row']) {
    out(" - Row found for id={$carwashId} - field checks: " . count($report['field_checks']));
} else {
    out(" - Row not found - cannot fully validate field values.");
}
out(" - Write test: " . (isset($report['write_test']['error']) ? 'ERROR: ' . $report['write_test']['error'] : ($report['write_test']['result'] ?? 'n/a')));
out(" - Logo uploads dir: " . ($report['logo_uploads_dir']['exists'] ? 'exists' : 'missing') . ", writable: " . ($report['logo_uploads_dir']['is_writable'] ? 'YES':'NO'));

exit(0);
