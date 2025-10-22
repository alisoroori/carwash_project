# راهنمای امنیت آپلود فایل در پروژه CarWash

این سند بهترین شیوه‌ها و استانداردهای امنیتی برای آپلود فایل در پروژه CarWash را توضیح می‌دهد.

## تهدیدات امنیتی آپلود فایل

1. **اجرای کد از راه دور (RCE):** آپلود و اجرای فایل‌های مخرب PHP یا دیگر کدهای قابل اجرا
2. **تزریق Cross-site (XSS):** آپلود فایل‌هایی که حاوی کدهای مخرب JavaScript هستند
3. **حملات تغییر مسیر (Path Traversal):** استفاده از نام‌های فایل حاوی کاراکترهای خاص برای دسترسی به فایل‌های خارج از مسیر مجاز
4. **حملات سرریز حافظه:** آپلود فایل‌های بزرگ برای اشغال منابع سرور

## کلاس FileUpload

کلاس `FileUpload` یک راهکار امنیتی جامع برای آپلود فایل‌ها در پروژه CarWash است. این کلاس ویژگی‌های زیر را ارائه می‌دهد:

- اعتبارسنجی نوع فایل (MIME Type)
- محدودیت اندازه فایل
- بررسی محتوای فایل‌های تصویری
- نام‌گذاری امن فایل‌ها
- ساختار پوشه امن با .htaccess
- جلوگیری از اجرای فایل‌های PHP در مسیر آپلود

### نحوه استفاده

```php
use App\Classes\FileUpload;

// ایجاد نمونه با مسیر پوشه آپلود و نوع فایل
$uploader = new FileUpload('/path/to/upload/dir', 'image');

// تنظیم محدودیت‌ها
$uploader->setMaxSize(2 * 1024 * 1024); // 2MB
$uploader->setAllowedExtensions(['jpg', 'png']);
$uploader->setSubDirectory('user_files');

// پردازش آپلود
$result = $uploader->upload($_FILES['uploaded_file']);

if ($result['success']) {
    // فایل با موفقیت آپلود شد
    $filePath = $result['file']['path'];
    $fileUrl = $result['file']['url'];
} else {
    // خطا در آپلود
    $errors = $result['errors'];
}