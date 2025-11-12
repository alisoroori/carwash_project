<?php
// Quick admin-only create user page (login-styled)
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Database as DBClass;

// Start session for messages and CSRF
\App\Classes\Session::start();

// Restrict access to localhost only (admin access)
$allowed = ['127.0.0.1', '::1'];
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, $allowed, true)) {
    header('HTTP/1.1 403 Forbidden');
    echo '<h2>Access denied</h2><p>This admin page is accessible only from the server (localhost).</p>';
    exit;
}

$roles = ['admin', 'carwash', 'customer'];
$message = '';
$messageType = '';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Invalid request (CSRF).';
        $messageType = 'error';
    } else {
        $role = $_POST['role'] ?? 'customer';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input
        $validator = new \App\Classes\Validator();
        $validator->required($role, 'Role')
                  ->in($role, $roles, 'Role')
                  ->required($email, 'Email')
                  ->email($email, 'Email')
                  ->required($password, 'Password')
                  ->minLength($password, 6, 'Password');

        if ($validator->fails()) {
            $message = implode('<br>', $validator->getErrors());
            $messageType = 'error';
        } else {
            try {
                $db = DBClass::getInstance();

                // check existing
                $exists = $db->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => $email]);
                if ($exists) {
                    $message = 'A user with this email already exists.';
                    $messageType = 'error';
                } else {
                    $userId = $db->insert('users', [
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                        'status' => 'active',
                        'email_verified_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    if ($userId) {
                        $message = 'Registration approved. The user can now log in.';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to create user.';
                        $messageType = 'error';
                    }
                }
            } catch (Exception $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Render login-styled page
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
    <style>
        .gradient-bg { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); }
        .login-container { background: rgba(255,255,255,0.98); backdrop-filter: blur(12px); border:1px solid rgba(255,255,255,0.2); }
        .input-field { transition: all .25s; border:2px solid #e5e7eb; padding:10px 12px; border-radius:8px }
        .input-field:focus { border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.08); }
        .btn-primary { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); border:none; color:#fff; padding:12px; border-radius:8px }
        .msg { padding:12px;border-radius:8px;margin-bottom:12px }
        .msg.success { background:#ecfdf5;color:#065f46 }
        .msg.error { background:#ffebe9;color:#9b1c1c }
    </style>

    <div class="flex items-center justify-center min-h-screen p-4 pt-20">
        <div class="w-full max-w-md mx-auto">
            <div class="login-container rounded-2xl shadow-2xl p-6 sm:p-8">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-shield text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold">Create User (Admin)</h1>
                    <p class="text-sm text-gray-600">Select role first, then provide email and password.</p>
                </div>

                <?php if ($message): ?>
                    <div class="msg <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <label for="auto_label_7" class="sr-only">Csrf token</label><label for="auto_label_7" class="sr-only">Csrf token</label><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ? id="auto_label_7">">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <label for="auto_label_6" class="sr-only">Role</label><label for="auto_label_6" class="sr-only">Role</label><select name="role" class="input-field w-full" id="auto_label_6">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r; ?>" <?php echo (isset($_POST['role']) && $_POST['role']===$r) ? 'selected' : ($r==='customer' ? 'selected' : ''); ?>><?php echo ucfirst($r); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <label for="auto_label_5" class="sr-only">Email</label><label for="auto_label_5" class="sr-only">Email</label><input type="email" name="email" class="input-field w-full" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ? id="auto_label_5" placeholder="Email">">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <label for="auto_label_4" class="sr-only">Password</label><label for="auto_label_4" class="sr-only">Password</label><input type="password" name="password" class="input-field w-full" required id="auto_label_4" placeholder="Password">
                    </div>

                    <button type="submit" class="btn-primary w-full font-bold">Create User</button>
                </form>

            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\admin\create_user_direct.php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Database as AppDatabase;

// This page intentionally does NOT require authentication.
// It is meant to be used by an admin opening the page locally.

$roles = ['admin', 'carwash', 'customer'];
$message = '';
$messageType = '';
$generated = null;

// Helper: generate a secure random password
function generate_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $max = strlen($chars) - 1;
    $pw = '';
    for ($i = 0; $i < $length; $i++) {
        $pw .= $chars[random_int(0, $max)];
    }
    return $pw;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'customer';

    // Basic validation
    if ($username === '' || $email === '') {
        $message = 'Username and email are required.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
        $messageType = 'error';
    } elseif (!in_array($role, $roles, true)) {
        $message = 'Invalid role.';
        $messageType = 'error';
    } else {
            try {
            $db = AppDatabase::getInstance();

            // Check if users table has a username column
            $cols = $db->fetchAll("SHOW COLUMNS FROM users");
            $hasUsername = false;
            foreach ($cols as $c) {
                if (isset($c['Field']) && $c['Field'] === 'username') {
                    $hasUsername = true;
                    break;
                }
            }

            // Check duplicate email
            $exists = $db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
            if ($exists) {
                $message = 'A user with this email already exists.';
                $messageType = 'error';
            } else {
                $password = generate_password(12);
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $insertData = [
                    'email' => $email,
                    'password' => $passwordHash,
                    'role' => $role,
                    'status' => 'active',
                    'email_verified_at' => date('Y-m-d H:i:s')
                ];

                if ($hasUsername) {
                    $insertData['username'] = $username;
                    // also try to fill full_name if exists
                    $insertData['full_name'] = $username;
                } else {
                    $insertData['full_name'] = $username;
                }

                // Ensure created_at/updated_at if columns exist and not null
                foreach ($cols as $c) {
                    if (isset($c['Field'])) {
                        if (($c['Field'] === 'created_at' || $c['Field'] === 'updated_at') && strtoupper($c['Null']) === 'NO' && $c['Default'] === null) {
                            $insertData[$c['Field']] = date('Y-m-d H:i:s');
                        }
                    }
                }

                $db->beginTransaction();
                $newId = $db->insert('users', $insertData);
                if ($newId) {
                    $db->commit();
                    $message = 'User created successfully.';
                    $messageType = 'success';
                    $generated = [
                        'id' => $newId,
                        'username' => $username,
                        'email' => $email,
                        'password' => $password,
                        'role' => $role
                    ];
                } else {
                    $db->rollback();
                    $message = 'Failed to insert user.';
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            if (isset($db) && method_exists($db, 'rollback')) {
                $db->rollback();
            }
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create User Direct - CarWash</title>
    <style>
        body{font-family:Inter,Segoe UI,Arial;background:#f3f6fb;margin:0;padding:24px}
        .card{max-width:720px;margin:20px auto;background:#fff;border-radius:10px;padding:24px;box-shadow:0 6px 24px rgba(32,40,60,.08)}
        h1{margin:0 0 8px;color:#1f2937;font-size:20px}
        p.lead{color:#6b7280;margin:0 0 18px}
        .row{display:flex;gap:12px}
        .col{flex:1}
        label{display:block;font-size:13px;color:#374151;margin-bottom:6px}
        input,select{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px}
        .btn{display:inline-block;background:#2563eb;color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer}
        .msg{padding:12px;border-radius:8px;margin-bottom:12px}
        .msg.success{background:#ecfdf5;color:#065f46}
        .msg.error{background:#ffebe9;color:#9b1c1c}
        .creds{background:#f8fafc;padding:12px;border-radius:8px;border:1px dashed #e6eef8;margin-top:12px}
        .small{font-size:13px;color:#6b7280}
    </style>
</head>
<body>
    <div class="card">
        <h1>Create User (Direct)</h1>
        <p class="lead">Fill username, email and select role. This page does not require login. Use only in trusted environment.</p>

        <?php if ($message): ?>
            <div class="msg <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?php
            // Idempotent ensure session and CSRF token for this form
            if (session_status() !== PHP_SESSION_ACTIVE) {
                \App\Classes\Session::start();
            }
            if (empty($_SESSION['csrf_token'])) {
                $csrf_helper = __DIR__ . '/../includes/csrf_protect.php';
                if (file_exists($csrf_helper)) {
                    require_once $csrf_helper;
                    if (function_exists('generate_csrf_token')) {
                        generate_csrf_token();
                    } else {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                    }
                } else {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                }
            }
            ?>
            <label for="auto_label_3" class="sr-only">Csrf token</label><label for="auto_label_3" class="sr-only">Csrf token</label><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ? id="auto_label_3">">
            <div class="row">
                <div class="col">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                <div class="col">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>

            <div style="margin-top:12px">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r; ?>" <?php echo (isset($_POST['role']) && $_POST['role']=== $r) ? 'selected' : ($r==='customer' ? 'selected':'' ); ?>><?php echo ucfirst($r); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-top:16px">
                <button class="btn" type="submit">Create User</button>
                <span class="small" style="margin-left:12px">Password will be generated automatically and shown after creation.</span>
            </div>
        </form>

        <?php if ($generated): ?>
            <div class="creds">
                <strong>New user created</strong>
                <div>ID: <?php echo (int)$generated['id']; ?></div>
                <div>Username: <?php echo htmlspecialchars($generated['username']); ?></div>
                <div>Email: <?php echo htmlspecialchars($generated['email']); ?></div>
                <div>Password: <code><?php echo htmlspecialchars($generated['password']); ?></code></div>
                <div>Role: <?php echo htmlspecialchars($generated['role']); ?></div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\admin\create_user_direct.php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Database;
use App\Classes\Session;
use App\Classes\Validator;

// Start session
Session::start();

// Configuration
$admin_username = "System Administrator"; // Set the admin username to display
$roles = ['admin', 'carwash', 'customer'];
$message = '';
$messageType = '';
$formData = [
    'full_name' => '',
    'email' => '',
    'role' => 'customer'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $validator = new Validator();
    $validator
        ->required($_POST['full_name'] ?? null, 'Full name')
        ->required($_POST['email'] ?? null, 'Email')
        ->email($_POST['email'] ?? null, 'Email')
        ->required($_POST['password'] ?? null, 'Password')
        ->minLength($_POST['password'] ?? null, 6, 'Password')
        ->required($_POST['role'] ?? null, 'Role')
        ->in($_POST['role'] ?? null, $roles, 'Role');
    
    // If validation passes
    if (!$validator->fails()) {
        try {
            $db = Database::getInstance();
            
            // Check if email already exists
            $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = :email", [
                'email' => $_POST['email']
            ]);
            
            if ($existingUser) {
                $message = "A user with this email already exists.";
                $messageType = 'error';
            } else {
                // Create new user
                $userId = $db->insert('users', [
                    'full_name' => Validator::sanitizeString($_POST['full_name']),
                    'email' => Validator::sanitizeEmail($_POST['email']),
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'role' => $_POST['role'],
                    'status' => 'active',
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($userId) {
                    $message = "User created successfully!";
                    $messageType = 'success';
                    
                    // Clear form data
                    $formData = [
                        'full_name' => '',
                        'email' => '',
                        'role' => 'customer'
                    ];
                } else {
                    $message = "Failed to create user. Please try again.";
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $validator->getErrors());
        $messageType = 'error';
        
        // Keep form data
        $formData = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'role' => $_POST['role'] ?? 'customer'
        ];
    }
}

// Page title
$page_title = 'Create User - CarWash Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #34495e;
            --light-color: #ecf0f1;
            --border-radius: 6px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #444;
            background-color: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-color);
        }
        
        h1 {
            color: var(--dark-color);
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
        }
        
        .admin-badge {
            display: inline-block;
            background-color: var(--dark-color);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }
        
        .message.success {
            background-color: rgba(46, 204, 113, 0.15);
            border-left: 4px solid var(--success-color);
            color: #27ae60;
        }
        
        .message.error {
            background-color: rgba(231, 76, 60, 0.15);
            border-left: 4px solid var(--danger-color);
            color: #c0392b;
        }
        
        form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2334495e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .admin-submit {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            margin-right: 12px;
        }
        
        .admin-info-text {
            display: flex;
            flex-direction: column;
        }
        
        .admin-label {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .admin-name {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            padding: 12px 24px;
            font-size: 16px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s, transform 0.15s;
            margin-top: 20px;
        }
        
        .btn:active {
            transform: translateY(1px);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .form-info {
            margin-top: 30px;
            padding: 15px;
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: var(--border-radius);
        }
        
        .form-info h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .role-description {
            margin-top: 5px;
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create New User</h1>
            <p class="subtitle">Add a new user to the CarWash system</p>
            <div class="admin-info">
                <span>Created by:</span>
                <span class="admin-badge"><?php echo htmlspecialchars($admin_username); ?></span>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php
            // Idempotent ensure session and CSRF token for this form
            if (session_status() !== PHP_SESSION_ACTIVE) {
                \App\Classes\Session::start();
            }
            if (empty($_SESSION['csrf_token'])) {
                $csrf_helper = __DIR__ . '/../includes/csrf_protect.php';
                if (file_exists($csrf_helper)) {
                    require_once $csrf_helper;
                    if (function_exists('generate_csrf_token')) {
                        generate_csrf_token();
                    } else {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                    }
                } else {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                }
            }
            ?>
            <label for="auto_label_2" class="sr-only">Csrf token</label><label for="auto_label_2" class="sr-only">Csrf token</label><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ? id="auto_label_2">">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($formData['full_name']); ?>" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($formData['email']); ?>" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="role">User Role</label>
                <select id="role" name="role" class="form-control form-select">
                    <?php foreach ($roles as $role): ?>
                        <option 
                            value="<?php echo $role; ?>" 
                            <?php echo $formData['role'] === $role ? 'selected' : ''; ?>
                        >
                            <?php echo ucfirst($role); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="role-description" id="role-description">
                    Customer accounts can book services and view their history.
                </div>
            </div>
            
            <div class="admin-submit">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                </div>
                <div class="admin-info-text">
                    <span class="admin-label">Creating as</span>
                    <span class="admin-name"><?php echo htmlspecialchars($admin_username); ?></span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Create User</button>
            
            <div class="form-info">
                <h3>Important Information</h3>
                <p>All users are created with an 'active' status and will be able to log in immediately.</p>
                <p>Make sure to provide a secure password that meets the minimum length requirement (6 characters).</p>
            </div>
        </form>
        
        <a href="../dashboard/admin_panel.php" class="back-link">&larr; Back to Admin Dashboard</a>
    </div>
    
    <script>
        // Update role description when selection changes
        document.getElementById('role').addEventListener('change', function() {
            const descriptions = {
                'admin': 'Administrator accounts have full access to the system.',
                'carwash': 'Car wash accounts can manage their services and bookings.',
                'customer': 'Customer accounts can book services and view their history.'
            };
            
            document.getElementById('role-description').textContent = 
                descriptions[this.value] || '';
        });
    </script>
</body>
</html>


