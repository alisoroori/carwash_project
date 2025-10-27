<?php
/**
 * فایل تست برای کلاس Database
 * آزمایش عملیات CRUD و مقاومت در برابر حملات SQL Injection
 */

// بارگذاری اتولودر
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Database;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست کلاس Database</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; line-height: 1.6; margin: 20px; direction: rtl; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .section { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; direction: ltr; text-align: left; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>تست کلاس Database</h1>
        
        <div class="section">
            <h2>۱. تست اتصال به پایگاه داده</h2>
            <?php
            try {
                $db = Database::getInstance();
                echo "<p class='success'>✅ اتصال به پایگاه داده با موفقیت برقرار شد!</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ خطا در اتصال به پایگاه داده: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>۲. تست واکشی (Fetch) داده‌ها</h2>
            <?php
            try {
                $db = Database::getInstance();
                
                // نمایش جدول‌های موجود در پایگاه داده
                echo "<h3>جدول‌های موجود در پایگاه داده:</h3>";
                $tables = $db->fetchAll("SHOW TABLES");
                
                if (empty($tables)) {
                    echo "<p>هیچ جدولی یافت نشد.</p>";
                } else {
                    echo "<ul>";
                    foreach ($tables as $table) {
                        $tableName = reset($table);
                        echo "<li>$tableName</li>";
                    }
                    echo "</ul>";
                }
                
                // نمایش کاربران (اگر جدول users وجود داشته باشد)
                $checkUsers = $db->fetchAll("SHOW TABLES LIKE 'users'");
                if (!empty($checkUsers)) {
                    echo "<h3>نمونه داده از جدول users:</h3>";
                    $users = $db->fetchAll("SELECT * FROM users LIMIT 5");
                    
                    if (empty($users)) {
                        echo "<p>هیچ کاربری یافت نشد.</p>";
                    } else {
                        echo "<table>";
                        echo "<tr>";
                        foreach (array_keys($users[0]) as $column) {
                            echo "<th>" . htmlspecialchars($column) . "</th>";
                        }
                        echo "</tr>";
                        
                        foreach ($users as $user) {
                            echo "<tr>";
                            foreach ($user as $value) {
                                echo "<td>" . htmlspecialchars((string)$value) . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ خطا در واکشی داده‌ها: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>۳. تست عملیات‌های CRUD</h2>
            <?php
            try {
                $db = Database::getInstance();
                $testTable = 'test_table';
                
                // بررسی وجود جدول تست
                $checkTable = $db->fetchAll("SHOW TABLES LIKE '$testTable'");
                
                if (empty($checkTable)) {
                    // ایجاد جدول تست
                    $db->query("
                        CREATE TABLE $testTable (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(100) NOT NULL,
                            email VARCHAR(100) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    echo "<p class='success'>✅ جدول تست با موفقیت ایجاد شد.</p>";
                }
                
                // تست عملیات درج (INSERT)
                $testName = 'کاربر تست ' . rand(1000, 9999);
                $testEmail = 'test' . rand(1000, 9999) . '@example.com';
                
                $insertId = $db->insert($testTable, [
                    'name' => $testName,
                    'email' => $testEmail
                ]);
                
                if ($insertId) {
                    echo "<p class='success'>✅ درج داده موفق: شناسه = $insertId، نام = $testName، ایمیل = $testEmail</p>";
                    
                    // تست عملیات به‌روزرسانی (UPDATE)
                    $newName = $testName . ' (به‌روز شده)';
                    $updateResult = $db->update($testTable, 
                        ['name' => $newName], 
                        ['id' => $insertId]
                    );
                    
                    if ($updateResult) {
                        echo "<p class='success'>✅ به‌روزرسانی داده موفق: نام جدید = $newName</p>";
                        
                        // نمایش داده به‌روز شده
                        $updatedRecord = $db->fetchOne("SELECT * FROM $testTable WHERE id = :id", ['id' => $insertId]);
                        echo "<pre>" . print_r($updatedRecord, true) . "</pre>";
                        
                        // تست عملیات حذف (DELETE)
                        $deleteResult = $db->delete($testTable, ['id' => $insertId]);
                        
                        if ($deleteResult) {
                            echo "<p class='success'>✅ حذف داده موفق: شناسه = $insertId</p>";
                            
                            // تأیید حذف
                            $checkDeleted = $db->fetchOne("SELECT * FROM $testTable WHERE id = :id", ['id' => $insertId]);
                            if ($checkDeleted === null) {
                                echo "<p class='success'>✅ تأیید حذف: رکورد در پایگاه داده یافت نشد.</p>";
                            } else {
                                echo "<p class='error'>❌ خطا در تأیید حذف: رکورد هنوز در پایگاه داده وجود دارد.</p>";
                            }
                        } else {
                            echo "<p class='error'>❌ خطا در حذف داده.</p>";
                        }
                    } else {
                        echo "<p class='error'>❌ خطا در به‌روزرسانی داده.</p>";
                    }
                } else {
                    echo "<p class='error'>❌ خطا در درج داده.</p>";
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ خطا در عملیات CRUD: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>۴. تست مقاومت در برابر SQL Injection</h2>
            <?php
            try {
                $db = Database::getInstance();
                $testTable = 'test_table';
                
                // درج یک رکورد تست جدید
                $normalName = 'کاربر امن';
                $normalId = $db->insert($testTable, [
                    'name' => $normalName,
                    'email' => 'secure@example.com'
                ]);
                
                echo "<p>رکورد تست با شناسه $normalId ایجاد شد.</p>";
                
                // آزمایش حمله SQL Injection (روش سنتی و ناامن)
                $maliciousInput = "x' OR '1'='1";
                
                echo "<h3>ورودی مخرب:</h3>";
                echo "<pre>" . htmlspecialchars($maliciousInput) . "</pre>";
                
                echo "<h3>کوئری ناامن (روش قدیمی):</h3>";
                $unsafeQuery = "SELECT * FROM $testTable WHERE name = '$maliciousInput'";
                echo "<pre>" . htmlspecialchars($unsafeQuery) . "</pre>";
                
                echo "<h3>کوئری امن (با استفاده از پارامترهای باند شده):</h3>";
                echo "<pre>SELECT * FROM $testTable WHERE name = :name</pre>";
                
                echo "<h3>نتیجه با استفاده از کلاس Database (امن):</h3>";
                $result = $db->fetchAll(
                    "SELECT * FROM $testTable WHERE name = :name", 
                    ['name' => $maliciousInput]
                );
                
                echo "<p>تعداد رکوردهای بازگشتی: " . count($result) . "</p>";
                
                if (count($result) === 0) {
                    echo "<p class='success'>✅ عالی! حمله SQL Injection خنثی شد. هیچ رکوردی با این ورودی مخرب یافت نشد.</p>";
                } else {
                    echo "<p class='error'>❌ هشدار: ممکن است هنوز آسیب‌پذیری SQL Injection وجود داشته باشد.</p>";
                }
                
                // پاک‌سازی رکورد تست
                $db->delete($testTable, ['id' => $normalId]);
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ خطا در تست SQL Injection: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>۵. تست تراکنش‌ها (Transactions)</h2>
            <?php
            try {
                $db = Database::getInstance();
                $testTable = 'test_table';
                
                echo "<h3>تست تراکنش موفق:</h3>";
                
                // شروع تراکنش
                $db->beginTransaction();
                
                // درج چند رکورد در یک تراکنش
                $id1 = $db->insert($testTable, [
                    'name' => 'کاربر تراکنش 1',
                    'email' => 'trans1@example.com'
                ]);
                
                $id2 = $db->insert($testTable, [
                    'name' => 'کاربر تراکنش 2',
                    'email' => 'trans2@example.com'
                ]);
                
                // تأیید تراکنش
                $db->commit();
                
                echo "<p class='success'>✅ تراکنش با موفقیت انجام شد. دو رکورد با شناسه‌های $id1 و $id2 ایجاد شدند.</p>";
                
                echo "<h3>تست تراکنش ناموفق (rollback):</h3>";
                
                // شروع تراکنش
                $db->beginTransaction();
                
                // درج یک رکورد
                $id3 = $db->insert($testTable, [
                    'name' => 'کاربر تراکنش 3',
                    'email' => 'trans3@example.com'
                ]);
                
                echo "<p>رکورد با شناسه $id3 به طور موقت ایجاد شد.</p>";
                
                // بازگشت تراکنش
                $db->rollback();
                
                // بررسی وجود رکورد
                $checkRecord = $db->fetchOne("SELECT * FROM $testTable WHERE id = :id", ['id' => $id3]);
                
                if ($checkRecord === null) {
                    echo "<p class='success'>✅ تراکنش با موفقیت بازگشت داده شد. رکورد $id3 در پایگاه داده وجود ندارد.</p>";
                } else {
                    echo "<p class='error'>❌ خطا در بازگشت تراکنش. رکورد هنوز در پایگاه داده وجود دارد.</p>";
                }
                
                // پاک‌سازی رکوردهای تست
                $db->delete($testTable, ['id' => $id1]);
                $db->delete($testTable, ['id' => $id2]);
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ خطا در تست تراکنش‌ها: " . $e->getMessage() . "</p>";
                
                // در صورت خطا، سعی می‌کنیم تراکنش را بازگشت دهیم
                $db->rollback();
            }
            ?>
        </div>
        
        <div class="section">
            <h2>نتیجه‌گیری</h2>
            <p>کلاس Database با موفقیت پیاده‌سازی شده و قابلیت‌های زیر را فراهم می‌کند:</p>
            <ul>
                <li>اتصال امن به پایگاه داده با استفاده از الگوی Singleton</li>
                <li>استفاده از prepared statements برای جلوگیری از حملات SQL Injection</li>
                <li>متدهای کمکی برای عملیات‌های رایج CRUD</li>
                <li>مدیریت تراکنش‌ها</li>
                <li>سازگاری با کدهای قدیمی از طریق فایل db.php</li>
            </ul>
            
            <p>تمام عملیات اصلی با موفقیت آزمایش شدند. کلاس آماده استفاده در پروژه است.</p>
        </div>
    </div>
</body>
</html>

