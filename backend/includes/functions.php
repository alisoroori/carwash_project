<?php
/**
 * Farsça: این فایل شامل توابع کمکی و عمومی برای سیستم CarWash است.
 * Türkçe: Bu dosya, CarWash sisteminin yardımcı ve genel fonksiyonlarını içerir.
 * English: This file contains helper and general functions for the CarWash system.
 * 
 * Farsça: این فایل را در ابتدای صفحات PHP خود با require_once('functions.php'); شامل کنید.
 * Türkçe: Bu dosyayı PHP sayfalarınızın başında require_once('functions.php'); ile dahil edin.
 * English: Include this file at the top of your PHP pages with require_once('functions.php');
 */

// Farsça: شروع جلسه (Session) در صورت عدم وجود.
 // Türkçe: Oturum başlatma (eğer yoksa).
 // English: Start session if not already started.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Farsça: تابع اتصال به دیتابیس.
 * Türkçe: Veritabanına bağlantı fonksiyonu.
 * English: Database connection function.
 * 
 * Farsça: این تابع اتصال امن به دیتابیس MySQL را برقرار می‌کند.
 * Türkçe: Bu fonksiyon MySQL veritabanına güvenli bağlantı kurar.
 * English: This function establishes a secure connection to MySQL database.
 * 
 * @return PDO|false اتصال PDO یا false در صورت خطا.
 * @return PDO|false PDO connection or false on error.
 * @return PDO|false PDO bağlantısı veya hata durumunda false.
 */
function getDatabaseConnection() {
    // Farsça: تنظیمات دیتابیس (این مقادیر را با اطلاعات واقعی خود جایگزین کنید).
    // Türkçe: Veritabanı ayarları (gerçek bilgilerinizle değiştirin).
    // English: Database settings (replace with your actual credentials).
    $host = 'localhost';
    $dbname = 'carwash_db'; // نام دیتابیس CarWash
    $username = 'root'; // نام کاربری دیتابیس
    $password = ''; // رمز عبور دیتابیس

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        // Farsça: لاگ خطا (در محیط تولید، لاگ کنید نه نمایش).
        // Türkçe: Hata logu (üretim ortamında loglayın, göstermeyin).
        // English: Error logging (in production, log don't display).
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Farsça: تابع پاکسازی ورودی برای جلوگیری از SQL Injection و XSS.
 * Türkçe: SQL Injection ve XSS'i önlemek için girdi temizleme fonksiyonu.
 * English: Input sanitization function to prevent SQL Injection and XSS.
 * 
 * @param string $input ورودی کاربر.
 * @param string $type نوع پاکسازی (sql, html, email).
 * @return string ورودی پاکسازی شده.
 * @return string $input Kullanıcı girdisi.
 * @return string $type Temizleme türü (sql, html, email).
 * @return string Temizlenmiş girdi.
 * @return string Sanitized input.
 * @return string Temizlenmiş girdi.
 */
function sanitizeInput($input, $type = 'html') {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

    switch ($type) {
        case 'sql':
            // Farsça: برای PDO، از Prepared Statements استفاده کنید. این فقط برای رشته‌های ساده است.
            // Türkçe: PDO için Prepared Statements kullanın. Bu sadece basit stringler içindir.
            // English: For PDO, use Prepared Statements. This is for simple strings only.
            return $input;
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'html':
        default:
            return $input;
    }
}

/**
 * Farsça: تابع بررسی ورود کاربر (Authentication Check).
 * Türkçe: Kullanıcı giriş kontrolü fonksiyonu.
 * English: User authentication check function.
 * 
 * @param string $role نقش مورد نظر (customer, provider, admin).
 * @return bool true اگر کاربر لاگین کرده و نقش مناسب داشته باشد.
 * @return bool true Kullanıcı giriş yapmış ve uygun role sahipse.
 * @return bool true if user is logged in and has appropriate role.
 * @return bool true اگر کاربر لاگین کرده و نقش مناسب داشته باشد.
 */
function isLoggedIn($role = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    if ($role && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role)) {
        return false;
    }

    return true;
}

/**
 * Farsça: تابع ورود کاربر (User  Login).
 * Türkçe: Kullanıcı giriş fonksiyonu.
 * English: User login function.
 * 
 * @param string $username_email نام کاربری یا ایمیل.
 * @param string $password رمز عبور.
 * @return bool|array true در صورت موفقیت، یا آرایه خطا.
 * @return bool|array true Başarılıysa, yoksa hata dizisi.
 * @return bool|array true on success, or error array.
 * @return bool|array true در صورت موفقیت، یا آرایه خطا.
 */
function userLogin($username_email, $password) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['error' => 'Veritabanı bağlantı hatası.'];
    }

    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username_email, $username_email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Farsça: به‌روزرسانی آخرین ورود.
        // Türkçe: Son girişi güncelle.
        // English: Update last login.
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        return true;
    }

    return ['error' => 'Kullanıcı adı/e-posta veya şifre hatalı.'];
}

/**
 * Farsça: تابع خروج کاربر (User  Logout).
 * Türkçe: Kullanıcı çıkış fonksiyonu.
 * English: User logout function.
 */
function userLogout() {
    session_unset();
    session_destroy();
    // Farsça: حذف کوکی‌های "Beni Hatırla" در صورت وجود.
    // Türkçe: "Beni Hatırla" çerezlerini sil (varsa).
    // English: Delete "Remember Me" cookies if exist.
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

/**
 * Farsça: تابع ثبت نام کاربر (User  Registration).
 * Türkçe: Kullanıcı kayıt fonksiyonu.
 * English: User registration function.
 * 
 * @param array $data آرایه داده‌های کاربر (name, email, password, role).
 * @return bool|array true در صورت موفقیت، یا آرایه خطا.
 * @return array $data Kullanıcı verileri dizisi (name, email, password, role).
 * @return bool|array true Başarılıysa, yoksa hata dizisi.
 * @return bool|array true on success, or error array.
 * @return array $data کاربر verileri dizisi (name, email, password, role).
 * @return bool|array true در صورت موفقیت، یا آرایه خطا.
 */
function userRegister($data) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['error' => 'Veritabanı bağlantı hatası.'];
    }

    // Farsça: بررسی وجود ایمیل.
    // Türkçe: E-posta kontrolü.
    // English: Check if email exists.
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$data['email']]);
    if ($checkStmt->fetch()) {
        return ['error' => 'Bu e-posta adresi zaten kayıtlı.'];
    }

    // Farsça: هش کردن رمز عبور.
    // Türkçe: Şifreyi hash'le.
    // English: Hash the password.
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Generate unique username from email
    $username = strtolower(explode('@', $data['email'])[0]);
    $base_username = $username;
    $counter = 1;
    
    // Check if username exists and modify if needed
    while (true) {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        if (!$check_stmt->fetch()) {
            break; // Username is available
        }
        $username = $base_username . $counter;
        $counter++;
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$username, $data['name'], $data['email'], $hashedPassword, $data['role']]);

    if ($result) {
        return true;
    }

    return ['error' => 'Kayıt sırasında bir hata oluştu.'];
}

/**
 * Farsça: تابع دریافت اطلاعات کاربر.
 * Türkçe: Kullanıcı bilgilerini alma fonksiyonu.
 * English: Get user information function.
 * 
 * @param int $userId شناسه کاربر.
 * @return array|false اطلاعات کاربر یا false.
 * @param int $userId Kullanıcı ID'si.
 * @return array|false Kullanıcı bilgileri veya false.
 * @return array|false User info or false.
 * @param int $userId شناسه کاربر.
 * @return array|false اطلاعات کاربر یا false.
 */
function getUserInfo($userId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Farsça: تابع ایجاد رزرو (Create Reservation).
 * Türkçe: Rezervasyon oluşturma fonksiyonu.
 * English: Create reservation function.
 * 
 * @param array $reservationData داده‌های رزرو (user_id, service_id, date, time, etc.).
 * @return bool|array true در صورت موفقیت، یا آرایه خطا.
 * @param array $reservationData Rezervasyon verileri (user_id, service_id, date, time, vb.).
 * @return bool|array true Başarılıysa, yoksa hata dizisi.
 * @return bool|array true on success, or error array.
 * @param array $reservationData داده‌های رزرو (user_id, service_id, date, time, etc.).
 * @return bool|array true در صورت موفقیت، یا آرایه خطا.
 */
function createReservation($reservationData) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['error' => 'Veritabanı bağlantı hatası.'];
    }

    try {
        $pdo->beginTransaction();

        // Farsça: درج رزرو.
        // Türkçe: Rezervasyonu ekle.
        // English: Insert reservation.
        $stmt = $pdo->prepare("INSERT INTO reservations (user_id, service_id, date, time, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $result = $stmt->execute([
            $reservationData['user_id'],
            $reservationData['service_id'],
            $reservationData['date'],
            $reservationData['time']
        ]);

        if (!$result) {
            throw new Exception('Rezervasyon eklenemedi.');
        }

        $reservationId = $pdo->lastInsertId();

        // Farsça: درج جزئیات رزرو (اگر لازم باشد).
        // Türkçe: Rezervasyon detaylarını ekle (gerekirse).
        // English: Insert reservation details (if needed).
        // ... (اضافه کردن جزئیات بیشتر اگر لازم باشد)

        $pdo->commit();
        return ['success' => true, 'reservation_id' => $reservationId];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['error' => 'Rezervasyon oluşturulamadı: ' . $e->getMessage()];
    }
}

/**
 * Farsça: تابع دریافت رزروهای کاربر.
 * Türkçe: Kullanıcının rezervasyonlarını alma fonksiyonu.
 * English: Get user's reservations function.
 * 
 * @param int $userId شناسه کاربر.
 * @return array لیست رزروها.
 * @param int $userId Kullanıcı ID'si.
 * @return array Rezervasyon listesi.
 * @return array List of reservations.
 * @param int $userId شناسه کاربر.
 * @return array لیست رزروها.
 */
function getUserReservations($userId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT r.*, s.name as service_name 
        FROM reservations r 
        JOIN services s ON r.service_id = s.id 
        WHERE r.user_id = ? 
        ORDER BY r.date DESC, r.time DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Farsça: تابع ارسال ایمیل (Email Sending).
 * Türkçe: E-posta gönderme fonksiyonu.
 * English: Email sending function.
 * 
 * @param string $to گیرنده.
 * @param string $subject موضوع.
 * @param string $message پیام.
 * @param string $from فرستنده (پیش‌فرض).
 * @return bool true در صورت موفقیت.
 * @param string $to Alıcı.
 * @param string $subject Konu.
 * @param string $message Mesaj.
 * @param string $from Gönderen (varsayılan).
 * @return bool true Başarılıysa.
 * @return bool true on success.
 * @param string $to گیرنده.
 * @param string $subject موضوع.
 * @param string $message پیام.
 * @param string $from فرستنده (پیش‌فرض).
 * @return bool true در صورت موفقیت.
 */
function sendEmail($to, $subject, $message, $from = 'noreply@carwash.com') {
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $message = "
    <html>
    <head>
        <title>$subject</title>
    </head>
    <body>
        $message
        <br><br>
        <p>Bu e-posta CarWash tarafından gönderilmiştir.</p>
    </body>
    </html>
    ";

    return mail($to, $subject, $message, $headers);
}

/**
 * Farsça: تابع تولید توکن تصادفی (Random Token Generation).
 * Türkçe: Rastgele token oluşturma fonksiyonu.
 * English: Random token generation function.
 * 
 * @param int $length طول توکن (پیش‌فرض 32).
 * @return string توکن تصادفی.
 * @param int $length Token uzunluğu (varsayılan 32).
 * @return string Rastgele token.
 * @return string Random token.
 * @param int $length طول توکن (پیش‌فرض 32).
 * @return string توکن تصادفی.
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Farsça: تابع اعتبارسنجی ایمیل.
 * Türkçe: E-posta doğrulama fonksiyonu.
 * English: Email validation function.
 * 
 * @param string $email ایمیل.
 * @return bool true اگر معتبر باشد.
 * @param string $email E-posta.
 * @return bool true Geçerliyse.
 * @return bool true if valid.
 * @param string $email ایمیل.
 * @return bool true اگر معتبر باشد.
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Farsça: تابع اعتبارسنجی رمز عبور (حداقل 8 کاراکتر، حروف و اعداد).
 * Türkçe: Şifre doğrulama fonksiyonu (en az 8 karakter, harf ve rakam).
 * English: Password validation function (min 8 chars, letters and numbers).
 * 
 * @param string $password رمز عبور.
 * @return bool true اگر معتبر باشد.
 * @param string $password Şifre.
 * @return bool true Geçerliyse.
 * @return bool true if valid.
 * @param string $password رمز عبور.
 * @return bool true اگر معتبر باشد.
 */
function validatePassword($password) {
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

/**
 * Farsça: تابع لاگ خطاها (Error Logging).
 * Türkçe: Hata loglama fonksiyonu.
 * English: Error logging function.
 * 
 * @param string $message پیام خطا.
 * @param string $level سطح خطا (error, warning, info).
 * @param string $message Hata mesajı.
 * @param string $level Hata seviyesi (error, warning, info).
 * @param string $message Error message.
 * @param string $level Error level (error, warning, info).
 * @param string $message پیام خطا.
 * @param string $level سطح خطا (error, warning, info).
 */
function logError($message, $level = 'error') {
    $logEntry = date('Y-m-d H:i:s') . " [$level] $message" . PHP_EOL;
    file_put_contents('logs/error.log', $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Farsça: تابع ریدایرکت با پیام (Redirect with Message).
 * Türkçe: Mesaj ile yönlendirme fonksiyonu.
 * English: Redirect with message function.
 * 
 * @param string $url آدرس مقصد.
 * @param string $message پیام (اختیاری).
 * @param string $type نوع پیام (success, error).
 * @param string $url Hedef URL.
 * @param string $message Mesaj (isteğe bağlı).
 * @param string $type Mesaj türü (success, error).
 * @param string $url Destination URL.
 * @param string $message Message (optional).
 * @param string $type Message type (success, error).
 * @param string $url آدرس مقصد.
 * @param string $message پیام (اختیاری).
 * @param string $type نوع پیام (success, error).
 */
function redirectWithMessage($url, $message = '', $type = 'success') {
    
}
