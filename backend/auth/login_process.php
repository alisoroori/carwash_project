<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    try {
        // Check user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['last_activity'] = time();

                    // Set remember me cookie if checked
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), "/"); // 30 days

                        // Store token in database
                        $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $stmt->bind_param("si", $token, $user['id']);
                        $stmt->execute();
                    }

                    // Log login
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();

                    // Redirect based on user type
                    switch ($user['user_type']) {
                        case 'admin':
                            header('Location: ../dashboard/admin/index.php');
                            break;
                        case 'carwash':
                            header('Location: ../dashboard/carwash/index.php');
                            break;
                        case 'customer':
                            header('Location: ../dashboard/customer/index.php');
                            break;
                    }
                    exit();
                } else {
                    $_SESSION['error'] = "Hesabınız aktif değil. Lütfen yönetici ile iletişime geçin.";
                }
            } else {
                $_SESSION['error'] = "Hatalı email veya şifre!";
            }
        } else {
            $_SESSION['error'] = "Hatalı email veya şifre!";
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "Giriş yapılırken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
    }

    header('Location: login.php');
    exit();
}
header('Location: login.php');
exit();
$name = trim($_POST['name']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$phone = trim($_POST['phone']);
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];
$address = trim($_POST['address']);

// Validation checks
if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($address)) {
    $_SESSION['error'] = "Tüm alanları doldurunuz.";
    header('Location: Customer_Registration.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Geçerli bir email adresi giriniz.";
    header('Location: Customer_Registration.php');
    exit();
}

if ($password !== $password_confirm) {
    $_SESSION['error'] = "Şifreler eşleşmiyor.";
    header('Location: Customer_Registration.php');
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['error'] = "Şifre en az 6 karakter olmalıdır.";
    header('Location: Customer_Registration.php');
    exit();
}

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Bu email adresi zaten kayıtlı.";
        header('Location: Customer_Registration.php');
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, address, user_type) VALUES (?, ?, ?, ?, ?, 'customer')");
    $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $address);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
        header('Location: login.php');
        exit();
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Kayıt işlemi başarısız oldu. Lütfen daha sonra tekrar deneyiniz.";
    header('Location: Customer_Registration.php');
    exit();
}
