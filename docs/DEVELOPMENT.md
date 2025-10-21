# Development Environment — CarWash Project

این فایل نحوه راه‌اندازی محیط توسعه محلی را برای ویندوز (XAMPP) و ابزارهای رایج توضیح می‌دهد.

## پیش‌نیازها
- XAMPP (Apache + MySQL) — اجرا و MySQL فعال باشد  
- PHP 8.0+ (مطابق composer.json)  
- Composer (در PATH)  
- Node.js + npm (برای بخش frontend، در صورت نیاز)  
- Git  
- (اختیاری) Docker برای اجرای ابزارهای اسکن مانند gitleaks

## کلیات ساختار
- ریشه پروژه: `c:\xampp\htdocs\carwash_project`  
- فایل‌های پیکربندی مرکزی: `backend/includes/config.php` و `backend/includes/bootstrap.php`  
- کلاس‌های PSR-4: `backend/classes/` (namespace `App\Classes`)  
- مدل‌ها: `backend/models/` (namespace `App\Models`)  
- مستندات: `docs/`

---

## گام‌های نصب و راه‌اندازی (ویندوز / PowerShell)
1. کلون کردن مخزن:
```powershell
cd C:\xampp\htdocs
git clone https://github.com/alisoroori/carwash_project.git
cd carwash_project
```

2. نصب وابستگی‌های PHP:
```powershell
composer install
composer dump-autoload -o
```

3. نصب وابستگی‌های Node (اختیاری برای فرانت‌اند):
```powershell
if (Test-Path package.json) { npm ci }
```

4. پیکربندی محیط:
- یک نسخه از `.env.example` بسازید یا متغیرها را در `backend/includes/config.php` هماهنگ کنید.
- مثال `.env` (نمونه):
```text
APP_ENV=development
DB_HOST=127.0.0.1
DB_NAME=carwash
DB_USER=root
DB_PASS=
BASE_URL=http://localhost/carwash_project
```

5. ایجاد دیتابیس و ایمپورت اسکیمای SQL:
- phpMyAdmin: http://localhost/phpmyadmin → New → `carwash` → Create → Import `database/carwash.sql`
- یا CLI:
```powershell
cd C:\xampp\mysql\bin
.\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS carwash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
.\mysql.exe -u root carwash < C:\xampp\htdocs\carwash_project\database\carwash.sql
```

6. ساخت دایرکتوری‌های آپلود (در صورت نیاز):
```powershell
# اگر config.php اتوماتیک نمی‌سازد
New-Item -ItemType Directory -Force backend\auth\uploads\profiles
New-Item -ItemType Directory -Force uploads\services
```

7. اجرای اسکریپت‌های پروژه (شرح کوتاه)
- `setup.bat` — اسکریپت راه‌اندازی اولیه (اگر موجود است): اجرا برای کارهای bootstrap محلی  
- `dev.bat` — راه‌اندازی محیط توسعه محلی (مثلاً build watch، hot-reload)  
- `build.bat` — ساخت تولیدی فرانت‌اند (minify/bundle)

مثال اجرا (PowerShell):
```powershell
# اجرای اسکریپت‌ها (اگر bat موجودند)
.\setup.bat
.\dev.bat
.\build.bat
```

> نکته: محتوای داخلی این اسکریپت‌ها را بازبینی کنید و در صورت نیاز پارامترهای path/ENV را ویرایش نمایید.

8. اجرای تست‌ها:
```powershell
# بعد از composer install
vendor\bin\phpunit --configuration phpunit.xml
```

9. اجرای اسکریپت تست اتولود (local CLI check)
```powershell
php test_autoload.php
```

---

## نکات ایمنی و توسعه
- هرگز `vendor/` یا `node_modules/` را در گیت کامیت نکنید — از `.gitignore` استفاده شود.  
- از `App\Classes\Database` و prepared statements استفاده کنید؛ اطلاعات حساس را در `.env` نگه دارید.  
- پس از هر تغییر در composer.json اجرا کنید:
```powershell
composer dump-autoload -o
```

---

## چک‌لیست سریع پیش از ایجاد PR
- [ ] composer install اجرا شده و autoload سالم است  
- [ ] تست‌های مرتبط اجرا و سبز هستند  
- [ ] هیچ راز یا کلید در کد یا فایل‌های commit شده نیست (اگر تردید دارید از gitleaks استفاده کنید)  
- [ ] فایل‌های مستند مرتبط (در docs/) بروز شده‌اند  
- [ ] تغییرات ساختاری بزرگ در branch جدا انجام شده و PR ایجاد شده

---

## ابزار مفید (دستورات سریع)
- بازسازی autoload:
```powershell
composer dump-autoload -o
```
- اجرای phpunit:
```powershell
vendor\bin\phpunit
```
- اسکن سریع secrets با Docker + gitleaks:
```powershell
docker run --rm -v C:\xampp\htdocs\carwash_project:/repo zricethezav/gitleaks:latest detect --source /repo --report-path /repo/gitleaks-report.json
```

---

اگر می‌خواهید، من می‌توانم:
- 1) همین فایل را برای شما ایجاد/کامیت کنم،  
- 2) یا نسخه خلاصه را داخل README.md اضافه کنم،  
- 3) یا محتوای `setup.bat`/`dev.bat`/`build.bat` را بازبینی و مستندسازی کنم.  
کدام را ترجیح می‌دهید؟