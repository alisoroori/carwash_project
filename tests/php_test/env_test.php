<?php
/**
 * Test environment variables loading
 */

// Load bootstrap
require_once __DIR__ . '/backend/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست متغیرهای محیطی</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>تست متغیرهای محیطی</h1>
        
        <?php if (function_exists('env')): ?>
            <p class="success">✅ تابع env() با موفقیت بارگذاری شده است.</p>
        <?php else: ?>
            <p class="error">❌ خطا در بارگذاری تابع env()</p>
        <?php endif; ?>
        
        <?php if (class_exists('Dotenv\Dotenv')): ?>
            <p class="success">✅ کتابخانه Dotenv با موفقیت بارگذاری شده است.</p>
        <?php else: ?>
            <p class="error">❌ خطا در بارگذاری کتابخانه Dotenv. آیا دستور composer require vlucas/phpdotenv را اجرا کرده‌اید؟</p>
        <?php endif; ?>
        
        <h2>مقادیر متغیرهای محیطی:</h2>
        <table>
            <tr>
                <th>نام متغیر</th>
                <th>مقدار</th>
                <th>نوع</th>
            </tr>
            <tr>
                <td>DB_HOST</td>
                <td><?= DB_HOST ?></td>
                <td><?= gettype(DB_HOST) ?></td>
            </tr>
            <tr>
                <td>DB_NAME</td>
                <td><?= DB_NAME ?></td>
                <td><?= gettype(DB_NAME) ?></td>
            </tr>
            <tr>
                <td>DB_USER</td>
                <td><?= DB_USER ?></td>
                <td><?= gettype(DB_USER) ?></td>
            </tr>
            <tr>
                <td>DB_PASS</td>
                <td><?= str_repeat('*', strlen(DB_PASS)) ?></td>
                <td><?= gettype(DB_PASS) ?></td>
            </tr>
            <tr>
                <td>APP_NAME</td>
                <td><?= APP_NAME ?></td>
                <td><?= gettype(APP_NAME) ?></td>
            </tr>
            <tr>
                <td>APP_URL</td>
                <td><?= APP_URL ?></td>
                <td><?= gettype(APP_URL) ?></td>
            </tr>
            <tr>
                <td>APP_DEBUG</td>
                <td><?= APP_DEBUG ? 'true' : 'false' ?></td>
                <td><?= gettype(APP_DEBUG) ?></td>
            </tr>
            <tr>
                <td>SESSION_LIFETIME</td>
                <td><?= SESSION_LIFETIME ?></td>
                <td><?= gettype(SESSION_LIFETIME) ?></td>
            </tr>
            <tr>
                <td>APP_KEY</td>
                <td><?= substr(APP_KEY, 0, 10) . '...' ?></td>
                <td><?= gettype(APP_KEY) ?></td>
            </tr>
        </table>
        
        <h2>دایرکتوری‌ها:</h2>
        <table>
            <tr>
                <th>نام دایرکتوری</th>
                <th>مسیر</th>
            </tr>
            <tr>
                <td>ROOT_DIR</td>
                <td><?= ROOT_DIR ?></td>
            </tr>
            <tr>
                <td>UPLOAD_DIR</td>
                <td><?= UPLOAD_DIR ?></td>
            </tr>
            <tr>
                <td>PROFILE_UPLOAD_DIR</td>
                <td><?= PROFILE_UPLOAD_DIR ?></td>
            </tr>
        </table>
    </div>
</body>
</html>
