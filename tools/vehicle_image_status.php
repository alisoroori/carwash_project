<?php
/**
 * Vehicle Image Status Report
 * - Scans backend/uploads/vehicles/
 * - Reports server existence and HTTP accessibility
 *
 * Place this file under project/tools/ and open in browser:
 *  http://localhost/carwash_project/tools/vehicle_image_status.php
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

$projectRoot = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
// canonical server folder for vehicle uploads
$baseDir = $projectRoot . '/backend/uploads/vehicles/';
$baseUrl = (getenv('BASE_URL') ?: 'http://localhost/carwash_project') . '/backend/uploads/vehicles/';

if (php_sapi_name() === 'cli') {
    echo "Project root: $projectRoot\n";
    echo "Scanning: $baseDir\n\n";
}

if (!is_dir($baseDir)) {
    $msg = "❌ Directory not found: $baseDir";
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    } else {
        die("<pre>$msg</pre>");
    }
}

// helper: http status via curl or get_headers fallback
function http_status_code(string $url): int {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        curl_close($ch);
        return (int)$code;
    } else {
        $hdrs = @get_headers($url);
        if (!is_array($hdrs) || empty($hdrs[0])) return 0;
        if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $hdrs[0], $m)) return (int)$m[1];
        return 0;
    }
}

$files = array_values(array_filter(scandir($baseDir), function($f) use ($baseDir) {
    return $f !== '.' && $f !== '..' && is_file($baseDir . $f);
}));

// CLI summary
if (php_sapi_name() === 'cli') {
    $ok = 0; $missing = 0; $inaccessible = 0;
    foreach ($files as $f) {
        $path = $baseDir . $f;
        $exists = is_file($path);
        $url = $baseUrl . rawurlencode($f);
        $code = $exists ? http_status_code($url) : 0;
        $accessible = ($code >= 200 && $code < 400);
        printf("%-40s server:%s http:%s %s\n",
            $f,
            $exists ? 'OK' : 'MISSING',
            $code ?: 'N/A',
            $accessible ? '' : '(inaccessible)'
        );
        if ($exists) $ok++; else $missing++;
        if (!$accessible) $inaccessible++;
    }
    echo "\nSummary: total=" . count($files) . " present=" . ($ok) . " missing=" . ($missing) . " inaccessible=" . ($inaccessible) . "\n";
    exit(0);
}

// Web output (simple HTML)
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Vehicle Image Status</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:20px;background:#f7fafc;color:#0f172a}
table{border-collapse:collapse;width:100%;max-width:1100px;background:#fff;border:1px solid #e2e8f0}
th,td{padding:8px;border-bottom:1px solid #edf2f7;text-align:left;font-size:13px}
th{background:#eef2ff;font-weight:700}
.ok{color:#065f46;font-weight:600}
.miss{color:#b91c1c;font-weight:700}
.bad{color:#b45309}
.small{font-size:12px;color:#64748b}
</style>
</head>
<body>
<h2>🧩 Vehicle Image Status Report</h2>
<p class="small">Scanned folder: <code><?php echo htmlspecialchars($baseDir); ?></code></p>
<table>
  <tr><th>File Name</th><th>Exists (server)</th><th>Accessible (HTTP)</th><th>Preview / Link</th></tr>
<?php
foreach ($files as $img):
    $filePath = $baseDir . $img;
    $url = $baseUrl . rawurlencode($img);
    $exists = is_file($filePath);
    $status = $exists ? '<span class="ok">✅</span>' : '<span class="miss">❌</span>';
    $code = $exists ? http_status_code($url) : 0;
    $accessible = ($code >= 200 && $code < 400);
    $http = $code ? $code : 'N/A';
?>
  <tr>
    <td><?php echo htmlspecialchars($img); ?></td>
    <td><?php echo $status; ?></td>
    <td><?php echo $accessible ? '<span class="ok">✅ (HTTP ' . $http . ')</span>' : '<span class="bad">❌ (' . htmlspecialchars((string)$http) . ')</span>'; ?></td>
    <td>
      <?php if ($accessible): ?>
        <a href="<?php echo htmlspecialchars($url); ?>" target="_blank">Open</a>
        &nbsp;|&nbsp;
        <a href="<?php echo htmlspecialchars($url); ?>" target="_blank"><img src="<?php echo htmlspecialchars($url); ?>" alt="" style="height:44px;vertical-align:middle;border-radius:4px;border:1px solid #e6eef8"></a>
      <?php else: ?>
        <a href="<?php echo htmlspecialchars($url); ?>" target="_blank">Try</a>
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
</table>

<p class="small">Tip: if files exist on disk but are HTTP-inaccessible, confirm Apache permissions and that <code>backend/uploads/vehicles/</code> is readable by web server and not blocked by .htaccess.</p>

</body>
</html>
