<?php
// Clean single-page implementation for create_user_direct.php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Database;
use App\Classes\Session;
use App\Classes\Validator;
use App\Classes\Auth;

Session::start();

if (class_exists('\App\Classes\Auth')) {
    Auth::requireRole('admin');
}

// Localhost-only guard
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
$generated = null;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

function generate_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $pw = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $pw .= $chars[random_int(0, $max)];
    }
    return $pw;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Invalid request (CSRF).';
        $messageType = 'error';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $password = $_POST['password'] ?? '';

        if ($password === '') {
            $password = generate_password(12);
        }

        $validator = new Validator();
        $validator->required($full_name, 'Full name')
                  ->required($email, 'Email')
                  ->email($email, 'Email')
                  ->required($password, 'Password')
                  ->minLength($password, 6, 'Password')
                  ->in($role, $roles, 'Role');

        if ($validator->fails()) {
            $message = implode('<br>', $validator->getErrors());
            $messageType = 'error';
        } else {
            try {
                $db = Database::getInstance();
                $exists = $db->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => $email]);
                if ($exists) {
                    $message = 'A user with this email already exists.';
                    $messageType = 'error';
                } else {
                    $insertData = [
                        'full_name' => $full_name,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                        'status' => 'active',
                        'email_verified_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $db->beginTransaction();
                    $newId = $db->insert('users', $insertData);
                    if ($newId) {
                        $db->commit();
                        $message = 'User created successfully.';
                        $messageType = 'success';
                        $generated = [
                            'id' => $newId,
                            'full_name' => $full_name,
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
                if (isset($db) && method_exists($db, 'rollback')) { $db->rollback(); }
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create User - CarWash</title>
    <style>
        body{font-family:Inter,Segoe UI,Arial;background:#f3f6fb;margin:0;padding:24px}
        .card{max-width:720px;margin:20px auto;background:#fff;border-radius:10px;padding:24px;box-shadow:0 6px 24px rgba(32,40,60,.08)}
        h1{margin:0 0 8px;color:#1f2937;font-size:20px}
        p.lead{color:#6b7280;margin:0 0 18px}
        .form-group{margin-bottom:16px}
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
        <h1>Create New User</h1>
        <p class="lead">Add a new user to the CarWash system. Use only in trusted/local environment.</p>

        <?php if ($message): ?>
            <div class="msg <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" type="text" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Leave blank to auto-generate">
            </div>

            <div class="form-group">
                <label for="role">User Role</label>
                <select id="role" name="role">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r; ?>" <?php echo (($_POST['role'] ?? 'customer') === $r) ? 'selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-top:16px">
                <button class="btn" type="submit">Create User</button>
                <span class="small" style="margin-left:12px">Password will be generated automatically if left blank.</span>
            </div>
        </form>

        <?php if ($generated): ?>
            <div class="creds">
                <strong>New user created</strong>
                <div>ID: <?php echo (int)$generated['id']; ?></div>
                <div>Name: <?php echo htmlspecialchars($generated['full_name']); ?></div>
                <div>Email: <?php echo htmlspecialchars($generated['email']); ?></div>
                <div>Password: <code><?php echo htmlspecialchars($generated['password']); ?></code></div>
                <div>Role: <?php echo htmlspecialchars($generated['role']); ?></div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
