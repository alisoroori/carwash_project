# TailwindCSS + Vite Setup Guide برای پروژه CarWash

## پیش‌نیازها (Prerequisites)

### 1. نصب Node.js
برای استفاده از Vite و TailwindCSS باید Node.js نصب باشد:

1. از سایت https://nodejs.org/fa برو و نسخه LTS رو دانلود کن
2. Node.js رو نصب کن
3. PowerShell یا Command Prompt رو باز کن و این دستورات رو اجرا کن:

```bash
node --version
npm --version
```

### 2. نصب Dependencies

```bash
# رفتن به مسیر پروژه
cd c:\xampp\htdocs\carwash_project

# نصب تمام dependencies
npm install
```

## کامندهای دسترسی (Available Commands)

### Development Mode
```bash
# اجرای Vite dev server
npm run dev

# اجرای TailwindCSS در حالت watch
npm run build-css
```

### Production Build
```bash
# ساخت نسخه production
npm run build

# ساخت CSS بهینه شده
npm run build-css-prod
```

## ساختار پروژه با Vite

```
carwash_project/
├── frontend/           # Static assets
│   ├── css/
│   │   └── tailwind.css    # Built CSS
│   ├── js/
│   │   └── main.js         # Main JavaScript
│   └── ...
├── src/
│   └── input.css          # TailwindCSS source
├── backend/               # PHP files
├── vite.config.js         # Vite configuration
├── tailwind.config.js     # TailwindCSS configuration
├── postcss.config.js      # PostCSS configuration
└── package.json           # Dependencies
```

## محیط Development

### با Vite:
```bash
npm run dev
```
- سرور development روی پورت 3000 اجرا میشه
- Hot Module Replacement (HMR) فعال میشه
- CSS تغییرات بلافاصله اعمال میشن

### بدون Vite (روش فعلی):
```bash
npm run build-css
```
- فقط TailwindCSS ساخته میشه
- نیاز به refresh دستی صفحه
- سرور XAMPP روی localhost اجرا میشه

## تنظیمات خاص

### 1. PHP Integration
فایل `backend/index.php` برای استفاده از assets ساخته شده:

```php
<!-- Development -->
<link rel="stylesheet" href="../frontend/css/tailwind.css">
<script src="../frontend/js/main.js"></script>

<!-- Production (با Vite build) -->
<link rel="stylesheet" href="../dist/assets/style.css">
<script src="../dist/assets/main.js"></script>
```

### 2. XAMPP Integration
- XAMPP همچنان برای PHP backend استفاده میشه
- Vite فقط برای CSS/JS development
- Production assets در مسیر `dist/` قرار میگیرن

## مزایای این Setup

### ✅ Development Experience
- **Hot Reload**: تغییرات بلافاصله دیده میشن
- **Fast Build**: کامپایل سریع CSS/JS
- **Error Reporting**: خطاها در browser نمایش داده میشن

### ✅ Production Optimization
- **Minification**: فایل‌های کوچک‌تر
- **Tree Shaking**: کد غیرضروری حذف میشه
- **Vendor Prefixes**: سازگاری با مرورگرهای قدیمی

### ✅ PHP Compatibility
- **No Changes**: کد PHP تغییری نمیکنه
- **Static Assets**: فقط CSS/JS بهینه میشن
- **XAMPP**: همچنان کار میکنه

## دستورالعمل‌های مرحله به مرحله

### مرحله 1: نصب Node.js
```bash
# دانلود از https://nodejs.org
# نصب و restart terminal
node --version  # باید version نمایش بده
```

### مرحله 2: نصب Dependencies
```bash
cd c:\xampp\htdocs\carwash_project
npm install
```

### مرحله 3: اجرای Development
```bash
# گزینه 1: فقط TailwindCSS
npm run build-css

# گزینه 2: کامل با Vite
npm run dev
```

### مرحله 4: Build برای Production
```bash
npm run build
npm run build-css-prod
```

## عیب‌یابی (Troubleshooting)

### خطای "npm command not found"
- Node.js نصب نشده
- PATH environment variable تنظیم نشده

### خطای "Cannot resolve dependency"
- `npm install` دوباره اجرا کن
- `node_modules` پاک کن و دوباره install کن

### خطای "Port already in use"
- پورت 3000 در حال استفاده
- پورت رو در `vite.config.js` تغییر بده

## نتیجه‌گیری

این setup بهترین تعادل بین:
- **سادگی**: کد PHP تغییری نمیکنه
- **کارایی**: Development experience بهتر میشه
- **Performance**: Production assets بهینه میشن
- **Compatibility**: با XAMPP کار میکنه

هر دو روش (با و بدون Vite) در دسترس هستن.