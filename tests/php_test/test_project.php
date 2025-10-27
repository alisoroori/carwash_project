<?php
// test_project.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- 1. PHP Test ---
$phpTest = ['status' => 'success', 'message' => '✅ PHP is working!'];

// --- 2. Database Connection Test ---
$dbTest = ['status' => 'failure', 'message' => '❌ Database connection failed!'];
$dbFile = __DIR__ . '/backend/includes/db.php';
$pdo = null;

if (file_exists($dbFile)) {
    require_once $dbFile;
    if (function_exists('getDBConnection')) {
        $pdo = getDBConnection();
        if ($pdo) {
            $dbTest = ['status' => 'success', 'message' => '✅ Database connection successful!'];
        }
    } else {
        $dbTest = ['status' => 'failure', 'message' => '❌ getDBConnection function not found in db.php.'];
    }
} else {
    $dbTest = ['status' => 'failure', 'message' => '❌ db.php file not found.'];
}

// --- 3. PHP Files Check ---
$scanDirs = [
    __DIR__ . '/backend/auth',
    __DIR__ . '/backend/includes',
    __DIR__ . '/frontend',
];

$phpFiles = [];
foreach ($scanDirs as $dir) {
    if (is_dir($dir)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Project Test Page</title>
<style>
body{font-family:sans-serif;background:#f7f7f7;color:#333;padding:20px;}
h1,h2{color:#2c3e50;}
table{width:100%;border-collapse:collapse;margin-bottom:20px;}
th,td{padding:8px;border:1px solid #ccc;}
th{background:#2980b9;color:#fff;}
tr.success td{background:#d4edda;color:#155724;}
tr.failure td{background:#f8d7da;color:#721c24;}
</style>
</head>
<body>
<h1>Project Test Page</h1>

<h2>1. PHP Test</h2>
<table>
<tr class="<?= $phpTest['status'] ?>">
<td><?= $phpTest['status'] ?></td>
<td><?= $phpTest['message'] ?></td>
</tr>
</table>

<h2>2. Database Connection Test</h2>
<table>
<tr class="<?= $dbTest['status'] ?>">
<td><?= $dbTest['status'] ?></td>
<td><?= $dbTest['message'] ?></td>
</tr>
</table>

<h2>3. PHP Files Check</h2>
<table>
<tr><th>File</th><th>Status</th></tr>
<?php
if (empty($phpFiles)) {
    echo '<tr><td colspan="2">No PHP files found.</td></tr>';
} else {
    foreach ($phpFiles as $file) {
        echo '<tr class="success"><td>' . htmlspecialchars($file) . '</td><td>exists</td></tr>';
    }
}
?>
</table>

<h2>4. Internal Links Check</h2>
<table>
<tr><th>Source File</th><th>Link</th><th>Status</th></tr>
<?php
$internalLinks = [];
$linkDirs = [__DIR__ . '/backend', __DIR__ . '/frontend'];

foreach ($linkDirs as $dir) {
    if (is_dir($dir)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php','html','htm'])) {
                $content = file_get_contents($file->getPathname());
                preg_match_all('/(?:href|src)\s*=\s*["\']([^"\':#][^"\']*)["\']/i',$content,$matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $link) {
                        if (preg_match('#^(https?:)?//#i',$link)) continue;
                        if (strpos($link,'mailto:')===0) continue;
                        if (strpos($link,'#')===0) continue;
                        $linkPath = preg_replace('/[\?#].*$/','',$link);
                        $baseDir = dirname($file->getPathname());
                        $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . $linkPath);
                        if (!$fullPath) $fullPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim($linkPath,'/\\'));
                        $internalLinks[] = ['source'=>$file->getPathname(),'link'=>$link,'exists'=>$fullPath && file_exists($fullPath)];
                    }
                }
            }
        }
    }
}

$uniqueLinks = [];
foreach ($internalLinks as $l) {
    $key = $l['source'] . '||' . $l['link'];
    $uniqueLinks[$key] = $l;
}
$internalLinks = array_values($uniqueLinks);

$brokenLinks = array_filter($internalLinks, fn($l)=>!$l['exists']);
if (empty($brokenLinks)) {
    echo '<tr><td colspan="3">No broken internal links found.</td></tr>';
} else {
    foreach ($brokenLinks as $l) {
        echo '<tr class="failure"><td>'.htmlspecialchars($l['source']).'</td><td>'.htmlspecialchars($l['link']).'</td><td>Broken</td></tr>';
    }
}
?>
</table>
</body>
</html>
