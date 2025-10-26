<?php
/**
 * Test PSR-4 Classes
 */

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Import classes
use App\Classes\Database;
use App\Classes\Validator;
use App\Models\User_Model;

// Initialize HTML page
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست کلاس‌های PSR-4</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .section { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        h2 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>تست کلاس‌های PSR-4 پروژه CarWash</h1>
        
        <div class="section">
            <h2>۱. تست کلاس Database</h2>
            <?php
            try {
                $db = Database::getInstance();
                echo "<p class='success'>✅ اتصال به پایگاه داده با موفقیت برقرار شد!</p>";
                
                // Show tables
                $tables = $db->fetchAll("SHOW TABLES");
                
                echo "<h3>جداول پایگاه داده:</h3>";
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>" . reset($table) . "</li>";
                }
                echo "</ul>";
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ خطا: " . $e->getMessage() . "</p>";
                echo "<p>لطفاً اطمینان حاصل کنید که:</p>";
                echo "<ol>";
                echo "<li>فایل backend/includes/config.php وجود دارد و حاوی تنظیمات صحیح پایگاه داده است</li>";
                echo "<li>پایگاه داده carwash در MySQL وجود دارد</li>";
                echo "<li>نام کاربری و رمز عبور پایگاه داده صحیح است</li>";
                echo "</ol>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>۲. تست کلاس Validator</h2>
            <?php
            $validator = new Validator();
            
            $validator
                ->required('', 'نام')
                ->email('invalid-email', 'ایمیل')
                ->minLength('123', 5, 'رمز عبور');
                
            echo "<p>وضعیت اعتبارسنجی: ";
            if ($validator->fails()) {
                echo "<span class='error'>ناموفق</span> (با خطاهای زیر)</p>";
                echo "<pre>" . print_r($validator->getErrors(), true) . "</pre>";
            } else {
                echo "<span class='success'>موفق</span></p>";
            }
            
            // Test sanitization
            $email = Validator::sanitizeEmail(' test@example.com ');
            $string = Validator::sanitizeString('<script>alert("XSS")</script>');
            
            echo "<h3>تست توابع پاکسازی:</h3>";
            echo "<p>ایمیل اصلی: ' test@example.com ' → ایمیل پاکسازی شده: '$email'</p>";
            echo "<p>رشته اصلی: &lt;script&gt;alert(\"XSS\")&lt;/script&gt; → رشته پاکسازی شده: '$string'</p>";
            ?>
        </div>
        
        <div class="section">
            <h2>۳. تست مدل User_Model</h2>
            <?php
            try {
                $userModel = new User_Model();
                
                // Try to get the first 5 users
                $users = $userModel->findAll([], 'id', 'ASC');
                
                echo "<h3>کاربران:</h3>";
                
                if (empty($users)) {
                    echo "<p>هیچ کاربری در پایگاه داده یافت نشد. می‌توانید با استفاده از مدل کاربران جدید ایجاد کنید.</p>";
                } else {
                    echo "<table>";
                    echo "<tr><th>شناسه</th><th>نام</th><th>ایمیل</th><th>نقش</th><th>تاریخ ثبت</th></tr>";
                    
                    $max = min(count($users), 5); // Show up to 5 users
                    
                    for ($i = 0; $i < $max; $i++) {
                        $user = $users[$i];
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($user['id'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($user['name'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($user['email'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($user['role'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($user['created_at'] ?? '') . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                    
                    if (count($users) > 5) {
                        echo "<p>... و " . (count($users) - 5) . " کاربر دیگر</p>";
                    }
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ خطا: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>۴. نتیجه‌گیری</h2>
            <p>پیکربندی PSR-4 و کلاس‌های مدرن با موفقیت در پروژه پیاده‌سازی شده‌اند. اکنون می‌توانید با استفاده از این کلاس‌ها، به تدریج کدهای قدیمی را به کدهای مدرن منتقل کنید.</p>
            
            <h3>گام‌های بعدی:</h3>
            <ol>
                <li>ایجاد سایر مدل‌ها مانند Booking_Model، Service_Model و Payment_Model</li>
                <li>به‌روزرسانی فایل‌های احراز هویت برای استفاده از کلاس Auth</li>
                <li>استفاده از کلاس Response برای API‌های REST</li>
            </ol>
        </div>
    </div>
</body>
</html>

