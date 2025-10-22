# راهنمای امنیت و مدیریت اسرار در CarWash

این سند روش‌های امنیتی و الگوهای مورد استفاده در پروژه CarWash را مستندسازی می‌کند.

## احراز هویت و مجوزدهی

### احراز هویت کاربر

تمام عملیات احراز هویت باید از طریق کلاس `App\Classes\Auth` انجام شود:

```php
use App\Classes\Auth;
use App\Classes\Session;

// شروع جلسه
Session::start();

// ایجاد نمونه Auth
$auth = new Auth();

// ورود کاربر
$result = $auth->login($email, $password);

// بررسی احراز هویت در صفحات محافظت شده
$auth->requireAuth();

// بررسی دسترسی بر اساس نقش
$auth->requireRole('admin');
```

### متغیرهای محیطی و اسرار

#### دستورالعمل راه‌اندازی
1. از `.env.example` یک کپی به نام `.env` ایجاد کنید: `cp .env.example .env`
2. فایل `.env` را با اطلاعات واقعی خود به‌روزرسانی کنید
3. هرگز فایل `.env` را در مخزن گیت کامیت نکنید
4. اطمینان حاصل کنید که `.env` در `.gitignore` محلی شما قرار دارد

#### دسترسی به متغیرهای محیطی در کد
```php
use App\Classes\Config;

// بارگیری محیط
Config::load();

// دریافت متغیر با مقدار پیش‌فرض
$dbHost = Config::get('DB_HOST', 'localhost');

// بررسی نوع محیط
if (Config::isProduction()) {
    // کد مخصوص محیط تولید
}
```