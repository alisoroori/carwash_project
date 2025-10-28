<?php
/**
 * اسکریپت مهاجرت رمزهای عبور
 * 
 * این اسکریپت رمزهای عبور قدیمی (MD5/SHA1) را به هش‌های امن تبدیل می‌کند.
 * برای اجرا، یک فایل با رمزهای عبور واقعی نیاز است.
 * فقط توسط مدیر سیستم باید اجرا شود.
 */

// مسیر فایل رمزهای عبور واقعی (باید در جای امن نگهداری شود)
define('PASSWORD_FILE', __DIR__ . '/real_passwords.json');

// تایید دسترسی: این اسکریپت باید فقط توسط مدیر سیستم اجرا شود
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// بررسی دسترسی مدیر
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "دسترسی غیرمجاز!";
    exit;
}

use App\Classes\Database;
use App\Models\User_Model;

// بررسی وجود فایل رمزهای عبور
if (!file_exists(PASSWORD_FILE)) {
    echo "فایل رمزهای عبور یافت نشد. لطفاً یک فایل JSON با ساختار مناسب ایجاد کنید.";
    exit;
}

// خواندن فایل رمزهای عبور
$passwords = json_decode(file_get_contents(PASSWORD_FILE), true);

if (!is_array($passwords)) {
    echo "خطا در خواندن فایل رمزهای عبور.";
    exit;
}

$db = Database::getInstance();
$userModel = new User_Model();

// آمار مهاجرت
$stats = [
    'total' => 0,
    'md5' => 0,
    'sha1' => 0,
    'plaintext' => 0,
    'updated' => 0,
    'failed' => 0,
    'already_secure' => 0
];

// دریافت همه کاربران
$users = $db->fetchAll("SELECT id, email, password FROM users");
$stats['total'] = count($users);

foreach ($users as $user) {
    $userId = $user['id'];
    $email = $user['email'];
    $currentHash = $user['password'];
    
    // بررسی اینکه آیا رمز عبور قبلاً با الگوریتم امن هش شده است
    if (password_get_info($currentHash)['algo'] !== 0) {
        $stats['already_secure']++;
        echo "کاربر {$email} از قبل هش امن دارد.<br>";
        continue;
    }
    
    // بررسی نوع هش فعلی
    $hashType = determineHashType($currentHash);
    $stats[$hashType]++;
    
    // جستجوی رمز عبور اصلی برای این کاربر
    if (!isset($passwords[$email])) {
        echo "رمز عبور برای کاربر {$email} یافت نشد.<br>";
        $stats['failed']++;
        continue;
    }
    
    $realPassword = $passwords[$email];
    
    // هش رمز عبور با الگوریتم امن
    $newHash = $userModel->hashPassword($realPassword);
    
    // به‌روزرسانی رمز عبور در دیتابیس
    $result = $db->update('users', ['password' => $newHash], ['id' => $userId]);
    
    if ($result) {
        $stats['updated']++;
        echo "رمز عبور کاربر {$email} با موفقیت به‌روزرسانی شد.<br>";
    } else {
        $stats['failed']++;
        echo "خطا در به‌روزرسانی رمز عبور کاربر {$email}.<br>";
    }
}

// نمایش آمار نهایی
echo "<h2>آمار مهاجرت</h2>";
echo "کل کاربران: {$stats['total']}<br>";
echo "هش MD5: {$stats['md5']}<br>";
echo "هش SHA1: {$stats['sha1']}<br>";
echo "بدون هش (متن ساده): {$stats['plaintext']}<br>";
echo "از قبل امن: {$stats['already_secure']}<br>";
echo "به‌روزرسانی موفق: {$stats['updated']}<br>";
echo "به‌روزرسانی ناموفق: {$stats['failed']}<br>";

// حذف فایل رمزهای عبور بعد از اتمام مهاجرت
if ($stats['updated'] > 0 && $stats['failed'] === 0) {
    //unlink(PASSWORD_FILE); // با احتیاط استفاده کنید
    echo "<p>مهاجرت با موفقیت انجام شد.</p>";
}

/**
 * تشخیص نوع هش
 * 
 * @param string $hash هش رمز عبور
 * @return string نوع هش (md5, sha1, plaintext)
 */
function determineHashType(string $hash): string
{
    // بررسی هش MD5
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        return 'md5';
    }
    
    // بررسی هش SHA1
    if (strlen($hash) === 40 && ctype_xdigit($hash)) {
        return 'sha1';
    }
    
    // در غیر این صورت، متن ساده در نظر گرفته می‌شود
    return 'plaintext';
}
