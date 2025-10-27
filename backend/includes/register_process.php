<?php
session_start();
include "db.php";

if(isset($_POST['username'], $_POST['email'], $_POST['password'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // بررسی ایمیل تکراری
    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if($check->num_rows > 0){
        $_SESSION['error'] = "Bu e-posta zaten kayıtlı.";
        header("Location: ../register.php");
        exit;
    }

    // درج کاربر جدید
    $sql = "INSERT INTO users (username, email, password) VALUES ('$username','$email','$password')";
    if($conn->query($sql)){
        $_SESSION['success'] = "Kayıt başarılı! Giriş yapabilirsiniz.";
        header("Location: ../login.php");
    } else {
        $_SESSION['error'] = "Bir hata oluştu: " . $conn->error;
        header("Location: ../register.php");
    }
} else {
    header("Location: ../register.php");
}
?>
