<?php
// Quick user-creation page styled like login page
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Session;
use App\Classes\Database as AppDatabase;
use App\Classes\Validator;

// Start session (used for messages)
Session::start();

// Restrict access to localhost only for safety (admin-only access)
$allowed = ['127.0.0.1', '::1', 'localhost'];
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, $allowed, true)) {
    // Optionally allow by IP override key via GET token (not recommended for production)
    header('HTTP/1.1 403 Forbidden');
    echo "<h2>Access denied</h2><p>This admin page is accessible only from the server (localhost).</p>";
    exit;
}

$roles = ['admin', 'carwash', 'customer'];
$message = '';
$messageType = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Invalid request (CSRF).';
        $messageType = 'error';
    } else {
        $role = $_POST['role'] ?? 'customer';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate
        $validator = new Validator();
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
                $db = AppDatabase::getInstance();
                $exists = $db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
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

// Render page (login-styled)
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
  <style>
    /* keep styles consistent with login page */
    .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .login-container { background: rgba(255,255,255,0.98); backdrop-filter: blur(12px); border:1px solid rgba(255,255,255,0.2); }
    .input-field { transition: all .25s; border:2px solid #e5e7eb; }
    .input-field:focus { border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.08); }
    .btn-primary { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); border:none; color:#fff; }
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
          <div class="msg <?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo $messageType === 'success' ? htmlspecialchars($message) : htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
          <label for="auto_label_37" class="sr-only">Csrf token</label><label for="auto_label_37" class="sr-only">Csrf token</label><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ? id="auto_label_37">">

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
            <label for="auto_label_36" class="sr-only">Role</label><label for="auto_label_36" class="sr-only">Role</label><select name="role" class="input-field w-full px-4 py-3 rounded-lg" id="auto_label_36">
              <?php foreach ($roles as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo (isset($_POST['role']) && $_POST['role']===$r) ? 'selected' : ($r==='customer' ? 'selected' : ''); ?>><?php echo ucfirst($r); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <label for="auto_label_35" class="sr-only">Email</label><label for="auto_label_35" class="sr-only">Email</label><input type="email" name="email" class="input-field w-full px-4 py-3 rounded-lg" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ? id="auto_label_35" placeholder="Email">">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <label for="auto_label_34" class="sr-only">Password</label><label for="auto_label_34" class="sr-only">Password</label><input type="password" name="password" class="input-field w-full px-4 py-3 rounded-lg" required id="auto_label_34" placeholder="Password">
          </div>

          <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold">Create User</button>
        </form>

      </div>
    </div>
  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


