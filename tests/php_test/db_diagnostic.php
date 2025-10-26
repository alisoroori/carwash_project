<?php
// filepath: c:\xampp\htdocs\carwash_project\db_diagnostic.php

require_once __DIR__ . '/vendor/autoload.php';
use App\Classes\Database;

echo "<h2>عیب‌یابی پایگاه داده سیستم کارواش</h2>";

try {
    // تست اتصال به پایگاه داده
    $db = Database::getInstance();
    echo "<p style='color:green'>✓ اتصال به پایگاه داده با موفقیت برقرار شد</p>";
    
    // بررسی ساختار جدول users
    try {
        $tableInfo = $db->fetchAll("DESCRIBE users");
        echo "<h3>ساختار جدول کاربران:</h3>";
        echo "<table border='1' cellpadding='5' dir='ltr'>";
        echo "<tr><th>فیلد</th><th>نوع</th><th>Null</th><th>کلید</th><th>پیش‌فرض</th><th>ویژگی‌ها</th></tr>";
        
        foreach ($tableInfo as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color:red'>خطا در دریافت ساختار جدول: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // تست درج با قابلیت اشکال‌زدایی
    echo "<h3>تست درج کاربر:</h3>";
    
    try {
        // فعال کردن ثبت کوئری
        $db->beginTransaction();
        
        $userData = [
            'full_name' => 'علی تست',
            'email' => 'ali_test@customer.com',
            'password' => password_hash('12345678', PASSWORD_DEFAULT),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s')
        ];
        
        echo "<p>تلاش برای درج کاربر با اطلاعات زیر:</p>";
        echo "<pre dir='ltr'>" . print_r($userData, true) . "</pre>";
        
        $userId = $db->insert('users', $userData);
        
        if ($userId) {
            echo "<p style='color:green'>✓ تست درج موفقیت‌آمیز بود! شناسه کاربر: $userId</p>";
            // برگرداندن تراکنش برای جلوگیری از ایجاد کاربر تست
            $db->rollback();
            echo "<p>تراکنش برگردانده شد (فقط تست)</p>";
        } else {
            echo "<p style='color:red'>✗ تست درج ناموفق بود</p>";
            $db->rollback();
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>خطای درج: " . htmlspecialchars($e->getMessage()) . "</p>";
        $db->rollback();
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>خطای اصلی: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// نمایش اطلاعات PHP و پایگاه داده
echo "<h3>اطلاعات سیستم:</h3>";
echo "<p>نسخه PHP: " . phpversion() . "</p>";
echo "<p>مسیر ریشه XAMPP: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";