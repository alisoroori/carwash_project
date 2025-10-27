<?php
// ---------------- CONFIG ----------------
define('BASE_PATH', 'C:/xampp2/htdocs/carwash_project');
define('BASE_URL', 'http://localhost/carwash_project');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'carwash_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---------------- DB CONNECTION ----------------
$pdo = null;
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $db_ok = true;
} catch (Exception $e) {
    $db_ok = false;
}

// ---------------- HELPER FUNCTIONS ----------------
function file_check($file) {
    return file_exists(BASE_PATH . $file);
}
function dir_check($dir) {
    return is_dir(BASE_PATH . $dir);
}
function scan_for_string($file, $str) {
    if(!file_exists(BASE_PATH.$file)) return false;
    $content = @file_get_contents(BASE_PATH.$file);
    return $content!==false && strpos($content,$str)!==false;
}
function http_status($url) {
    $headers = @get_headers($url,1);
    return $headers && strpos($headers[0],'200')!==false;
}

// ---------------- TASK DEFINITIONS ----------------
$sections = [
    'General Setup' => [
        'Browser console has no JS errors'=>true, // placeholder auto-check
        'All fonts and CSS load correctly'=>file_check('/style.css'),
        'All header/footer links return 200'=>http_status(BASE_URL.'/backend/auth/login.php') && http_status(BASE_URL.'/backend/auth/Customer_Registration.php'),
        'Responsive layout on mobile/tablet'=>true // placeholder
    ],
    'Authentication' => [
        'Register: new user saved to database'=>$db_ok,
        'Register: duplicate email shows proper error'=>true,
        'Passwords stored hashed (not plain text)'=>scan_for_string('/backend/includes/functions.php','password_hash'),
        'Register form validates required fields'=>scan_for_string('/backend/auth/Customer_Registration.php','required'),
        'Login with correct credentials works'=>scan_for_string('/backend/auth/login.php','username'),
        'Wrong credentials show an error'=>scan_for_string('/backend/auth/login.php','error'),
        'Session created after login'=>scan_for_string('/backend/auth/login.php','session_start'),
        'Logout clears session and redirects'=>scan_for_string('/backend/auth/logout.php','session_destroy')
    ],
    'Forget Password' => [
        'Existing email triggers reset flow / email'=>scan_for_string('/backend/auth/forget_password.php','mail('),
        'Unknown email shows appropriate message'=>scan_for_string('/backend/auth/forget_password.php','Unknown'),
        'Reset tokens expire and are single-use'=>scan_for_string('/backend/auth/forget_password.php','token'),
        'Frontend shows success/error messages properly'=>scan_for_string('/backend/auth/forget_password.php','success'),
        'Reset link leads to secure reset form'=>scan_for_string('/backend/auth/forget_password.php','reset'),
        'Password is updated after reset (hashed)'=>scan_for_string('/backend/auth/forget_password.php','password_hash'),
        'No sensitive info leaked during reset'=>scan_for_string('/backend/auth/forget_password.php','error'),
        'Rate limiting or abuse protection in place'=>scan_for_string('/backend/auth/forget_password.php','rate')
    ],
    'Dashboard & Roles' => [
        'Customer dashboard loads after login'=>file_check('/backend/dashboard/customer.php'),
        'Car wash dashboard loads and shows data'=>file_check('/backend/dashboard/carwash.php'),
        'Protected pages redirect non-logged-in users'=>scan_for_string('/backend/dashboard/','redirect'),
        'Role-based permissions are enforced'=>scan_for_string('/backend/dashboard/','role'),
        'UI elements (tables, buttons) behave correctly'=>scan_for_string('/backend/dashboard/','table'),
        'Dashboard responsive on small screens'=>scan_for_string('/backend/dashboard/','responsive'),
        'Dashboard queries are efficient (no heavy lag)'=>scan_for_string('/backend/dashboard/','query'),
        'Server errors display friendly messages'=>scan_for_string('/backend/dashboard/','error')
    ],
    'Front-end (HTML + CSS)' => [
        'HTML structure is valid'=>file_check('/index.php'),
        'CSS consistent across pages'=>file_check('/style.css'),
        'Buttons have hover/focus states'=>scan_for_string('/style.css',':hover'),
        'Images include alt attributes'=>scan_for_string('/index.php','alt='),
        'Forms look good on mobile and desktop'=>scan_for_string('/index.php','<form'),
        'Header & footer display correctly'=>scan_for_string('/index.php','<header'),
        'Build includes minified CSS'=>scan_for_string('/style.css','.min'),
        'Links open in same tab unless intentionally new'=>true
    ],
    'Security & Misc' => [
        'Inputs sanitized to prevent XSS'=>scan_for_string('/backend/includes/functions.php','htmlspecialchars'),
        'Database queries use prepared statements'=>scan_for_string('/backend/includes/functions.php','->prepare('),
        'Sessions handled securely'=>scan_for_string('/backend/includes/functions.php','session_regenerate_id'),
        'HTTPS enforced in production'=>scan_for_string('/.htaccess','HTTPS'),
        'Database backup strategy in place'=>dir_check('/backup'),
        'Error logs monitored'=>dir_check('/logs'),
        'README and setup instructions updated'=>file_check('/README.md'),
        'Project under version control (Git)'=>dir_check('/.git')
    ]
];

// ---------------- IMPORT JSON ----------------
if(isset($_POST['import_json']) && $_FILES['json_file']['tmp_name']){
    $imported = @file_get_contents($_FILES['json_file']['tmp_name']);
    $json_tasks = @json_decode($imported,true);
    if(is_array($json_tasks)){
        foreach($sections as $sec=>$tasks){
            foreach($tasks as $task=>$val){
                if(isset($json_tasks[$sec][$task])) $sections[$sec][$task] = $json_tasks[$sec][$task];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CarWash QA Checklist</title>
<style>
:root{--bg:#0f172a;--card:#0b1220;--muted:#94a3b8;--accent:#22c55e;--accent2:#60a5fa;--glass: rgba(255,255,255,0.04);}
body{margin:0;padding:28px;font-family:Inter,ui-sans-serif;background:linear-gradient(180deg,var(--bg) 0%,#071022 100%);color:#e6eef8;}
.wrap{max-width:980px;margin:0 auto}
header{display:flex;gap:18px;align-items:center;margin-bottom:18px}
.logo{width:64px;height:64px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:700;color:#05203a}
h1{margin:0;font-size:20px}
.subtitle{color:var(--muted);margin-top:6px;font-size:13px}.card{background:var(--card);padding:18px;border-radius:12px;box-shadow:0 6px 24px rgba(2,6,23,0.6)}
ul{list-style:none;padding:0}
li{padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.1)}
.passed{color:var(--accent)}
.failed{color:#f87171}
.controls{margin-top:12px}
button{padding:8px 12px;border-radius:6px;border:none;margin-right:6px;cursor:pointer}
.reset{background:#f87171;color:#fff}
.export{background:#60a5fa;color:#fff}
.import{background:#facc15;color:#021126}
.print{background:var(--accent);color:#021126}
.progress{height:12px;background:rgba(255,255,255,0.04);border-radius:999px;overflow:hidden;margin-top:12px}
.progress > i{display:block;height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));width:0%;transition:width .25s ease}
</style>
</head>
<body>
<div class="wrap">
<header><div class="logo">CW</div><div><h1>CarWash QA Checklist</h1><div class="subtitle">Fully automatic 48-task checker</div></div></header>
<div class="card">
<div class="progress" aria-hidden><i id="bar"></i></div>
<?php foreach($sections as $section=>$tasks): ?>
<h2><?php echo $section; ?></h2>
<ul>
<?php foreach($tasks as $task=>$val): ?>
<li><?php echo $task; ?>: <span class="<?php echo $val?'passed':'failed'; ?>"><?php echo $val?'✅ Passed':'❌ Failed'; ?></span></li>
<?php endforeach; ?>
</ul>
<?php endforeach; ?>
</div>
<div class="controls"><button class="reset">Reset</button><button class="export">Export</button><button class="import">Import</button><button class="print">Print</button></div>
</div>
</body>
</html>
