<?php
// Secure session cookie params must be set BEFORE session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once "db.php"; // مسیر را طبق پروژه‌ات تغییر بده در صورت نیاز

// --- Simple rate limiting (adjust limits as desired) ---
$maxAttempts = 6;
$lockoutSeconds = 300; // 5 minutes

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}
// Purge old attempts
foreach ($_SESSION['login_attempts'] as $key => $attempt) {
    if ($attempt['time'] + $lockoutSeconds < time()) {
        unset($_SESSION['login_attempts'][$key]);
    }
}
// Count attempts for this IP
$attemptsForIp = 0;
foreach ($_SESSION['login_attempts'] as $attempt) {
    if ($attempt['ip'] === $ip) $attemptsForIp++;
}
if ($attemptsForIp >= $maxAttempts) {
    $_SESSION['error'] = "Çok fazla giriş denemesi. Lütfen 5 dakika sonra tekrar deneyin.";
    header("Location: ../login.php");
    exit;
}

// Validate POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'], $_POST['password'])) {
    header("Location: ../login.php");
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

// Basic email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Geçersiz e-posta adresi.";
    header("Location: ../login.php");
    exit;
}

try {
    // Prepared statement for safety
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    if ($stmt === false) {
        // DB prepare error
        error_log("DB prepare failed: " . $conn->error);
        $_SESSION['error'] = "Sunucu hatası. Daha sonra tekrar deneyin.";
        header("Location: ../login.php");
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Successful login: regenerate session id
            session_regenerate_id(true);

            // Store minimal info in session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'] ?? 'customer';
            $_SESSION['logged_in'] = true;

            // Clear attempts for this IP on success
            foreach ($_SESSION['login_attempts'] as $k => $attempt) {
                if ($attempt['ip'] === $ip) unset($_SESSION['login_attempts'][$k]);
            }

            // Redirect based on role (adjust paths as your project structure)
            switch ($_SESSION['role']) {
                case 'customer':
                    header("Location: ../dashboard/customer.php");
                    break;
                case 'carwash':
                    header("Location: ../dashboard/carwash.php");
                    break;
                case 'courier':
                case 'delivery':
                    header("Location: ../dashboard/courier.php");
                    break;
                default:
                    header("Location: ../index.php");
            }
            exit;
        } else {
            // Wrong password
            $_SESSION['error'] = "Şifre yanlış!";

            // record failed attempt
            $_SESSION['login_attempts'][] = ['ip' => $ip, 'time' => time()];

            header("Location: ../login.php");
            exit;
        }
    } else {
        // No user found
        $_SESSION['error'] = "Kullanıcı bulunamadı!";
        $_SESSION['login_attempts'][] = ['ip' => $ip, 'time' => time()];
        header("Location: ../login.php");
        exit;
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['error'] = "Sunucu hatası. Daha sonra tekrar deneyin.";
    header("Location: ../login.php");
    exit;
}
