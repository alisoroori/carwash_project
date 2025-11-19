# Copilot Instructions — CarWash Web App (concise)

This file tells AI coding agents how this repository is organized and what patterns/workflows to follow so you can be productive immediately.

- **Where to start**: open `backend/includes/bootstrap.php` (initializes Composer autoload, logger, .env) and `composer.json` (PSR-4 mapping: `App\\Classes` → `backend/classes`).
- **Primary directories**:
  - `backend/classes/` — PSR-4 service classes (Database, Auth, Response, Validator, Session, FileUploader).
  - `backend/models/` — DB model helpers.
  - `backend/includes/` — legacy helpers and `bootstrap.php` (still required on most pages).
  - `backend/api/` — JSON endpoints called from frontend `fetch()`.
  - `frontend/js/` — client-side utilities (e.g., `api-utils.js`, `csrf-helper.js`, `vehicleManager.js`).

- **Must-follow conventions**:
  - New PHP files should use Composer autoload and namespace `App\\Classes\\...` (require `vendor/autoload.php`).
  - API endpoints must return JSON via `Response` class — do NOT `echo json_encode()` directly. Use `Response::success()` / `Response::error()` / `Response::validationError()`.
  - Use `Database::getInstance()` prepared-statement helpers (`fetchOne`, `fetchAll`, `insert`, `update`, `delete`) — avoid raw `mysqli_*` queries.
  - Protect routes: pages use `Auth::requireRole('admin'|'customer'|'carwash')`; JSON APIs use `Auth::requireAuth()` then `Auth::hasRole()` as appropriate.

- **Frontend ↔ Backend patterns**:
  - Frontend JS calls `/carwash_project/backend/api/...` with `fetch()`; CSRF token is exposed as `<meta name="csrf-token">` and via `window.CONFIG.CSRF_TOKEN` — use `csrf-helper.js` for POSTs.
  - File uploads expect `FileUploader` server-side validation and store profiles under `backend/auth/uploads/profiles/`.

- **Common tasks / commands (Windows PowerShell)**:
  - Install PHP deps: `cd c:\\xampp\\htdocs\\carwash_project ; composer install`
  - Install frontend deps: `.\\setup.bat` or `npm install`
  - Start dev (Vite/Tailwind): `.\\dev.bat` or `npm run dev` (Vite proxies to PHP).
  - Build for production: `npm run build` and `npm run build-css` (Tailwind).
  - Import DB schema: `mysql -u root -p < database/carwash.sql` (XAMPP MySQL)
  - Run tests: `vendor/bin/phpunit --configuration phpunit.xml.dist`

- **API endpoint pattern** (example):
  - File: `backend/api/users/get_profile.php`
  - Top of file:
    ```php
    require_once __DIR__ . '/../../includes/bootstrap.php';
    use App\\Classes\\Auth; use App\\Classes\\Response;
    Auth::requireAuth();
    // then use Response::success()/error()
    ```

- **Error handling & logging**:
  - Use `Logger::exception($e)` for exceptions. Global error handlers are wired via `bootstrap.php`.
  - Avoid printing HTML or raw output in JSON endpoints; use output buffering / `send_json_response()` patterns when necessary.

- **When updating UI or adding JS modules**:
  - Add files under `frontend/js/`, ensure `api-utils.js` or `csrf-helper.js` are loaded if you rely on `window.apiCall` or CSRF behaviors.
  - Frontend uses Vite/Tailwind; during development prefer `dev.bat` so Tailwind watch and Vite dev server run with the expected proxy.

- **Quick code examples**:
  - DB query (safe):
    ```php
    $db = App\\Classes\\Database::getInstance();
    $user = $db->fetchOne('SELECT * FROM users WHERE id = :id', ['id' => $id]);
    ```
  - JSON response:
    ```php
    App\\Classes\\Response::success('OK', ['user' => $user]);
    ```

- **Notes for AI agents**:
  - Prefer PSR-4 `App\\Classes` implementations over editing legacy `backend/includes` files unless compatibility is required.
  - Be conservative with global changes: many pages include legacy helpers; avoid breaking backward-compatible includes.
  - When creating new API endpoints, mirror existing patterns in `backend/api/*` (Auth checks, Response usage, prepared statements).

If any area feels incomplete or you want more examples (e.g., webhook flow, payment handlers, or vehicle API specifics), tell me which part to expand.
# Copilot Instructions for CarWash Web Application

## Project Overview
- **Purpose:** Multi-role car wash management platform for admin, car wash businesses, and customers
- **Tech Stack:** PHP 7.4+/8.x, MySQL, Composer (PSR-4), Vite (frontend bundling), TailwindCSS, WebSocket (real-time)
- **Environment:** XAMPP/LAMP stack on `http://localhost/carwash_project/`
- **Roles:** Admin (analytics, user management), Car Wash (bookings, services), Customer (reservations, payments)

## Architecture Overview

### **Backend (Modern PSR-4 + Legacy Compatibility)**
- **Namespaces:**
  - `App\Classes\*` → `backend/classes/` (core business logic, 90+ utility classes)
  - `App\Models\*` → `backend/models/` (database models)
- **Bootstrap:** Always use `backend/includes/bootstrap.php` which:
  - Loads Composer autoloader (`vendor/autoload.php`)
  - Initializes `Logger` with global error/exception handlers
  - Loads `.env` via `vlucas/phpdotenv`
  - Sets `APP_ENV` (production disables `display_errors`)
- **Core Classes:**
  - `Database`: PDO Singleton with `fetchOne()`, `fetchAll()`, `insert()`, `update()`, `delete()`
  - `Auth`: `requireAuth()`, `requireRole(['admin'])`, `isAuthenticated()`, `hasRole()`
  - `Response`: `success()`, `error()`, `validationError()`, `unauthorized()`, `notFound()`
  - `Validator`: Fluent validation (`->required()`, `->email()`, `->minLength()`), `sanitize*()` methods
  - `Session`: Secure wrapper with `regenerate()` after login
  - `Logger`: Logs to `logs/app.log`, `Logger::exception($e)` for full traces
  - `FileUploader`: Validates file uploads (type, size), stores in `backend/auth/uploads/profiles/`

### **Frontend (Vite + TailwindCSS)**
- **Build Tools:**
  - `dev.bat` / `npm run dev` → Vite dev server on `localhost:3000` with PHP backend proxy
  - `npm run build` → Production build to `dist/`
  - `npm run build-css` → TailwindCSS watch mode (recommended for PHP dev)
- **API Integration:** All frontend JS uses `fetch()` to call `/carwash_project/backend/api/*` endpoints
- **Real-Time:** WebSocket client (`frontend/js/websocket-client.js`) connects to `ws://localhost:8080` for analytics/notifications

## Critical Developer Workflows

### **Quick Start (Windows PowerShell):**
```powershell
# 1. Install PHP dependencies
cd c:\xampp\htdocs\carwash_project
composer install

# 2. Install Node.js dependencies (Vite + TailwindCSS)
.\setup.bat  # or npm install

# 3. Import database schema
# MySQL CLI: mysql -u root -p < database/carwash.sql

# 4. Configure database
# Edit backend/includes/config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS, BASE_URL)

# 5. Start development
.\dev.bat  # Choose option 1 for TailwindCSS watch mode
# XAMPP must be running! Access: http://localhost/carwash_project/
```

### **Testing & CI:**
```bash
# Run PHPUnit tests
vendor/bin/phpunit --configuration phpunit.xml.dist

# CI runs on GitHub Actions (.github/workflows/ci.yml)
# Tests run with matrix strategy: empty DB_PASS and set DB_PASS
```

### **API Development Pattern:**
1. Create endpoint in `backend/api/{feature}/action.php`
2. Use `Response::success()` / `Response::error()` for JSON responses
3. **Auth check:** `Auth::requireRole(['admin', 'carwash'])` at file top
4. **CSRF:** Frontend JS calls `PaymentUtils.generateCSRFToken()` and includes in POST
5. **File uploads:** Use `FileUploader` class, validates type/size, stores with sanitized names

### **Database Migrations:**
- SQL files in `database/` (e.g., `vehicle_image_default_migration.sql`)
- PowerShell scripts: `migrate_add_district.ps1`, `auto_migrate_district.ps1`
- Manual execution: `mysql -u root -p carwash_db < database/migration.sql`

## Project-Specific Conventions & Patterns

### **API Response Format (CRITICAL):**
All API endpoints must return JSON via `Response` class:
```php
use App\Classes\Response;

// ✅ Success
Response::success('Operation successful', ['user_id' => 123]);
// Outputs: {"status":"success","message":"...","data":{...}}

// ✅ Error
Response::error('Validation failed', 400);
// Outputs: {"status":"error","message":"..."}

// ❌ NEVER do raw json_encode() - breaks error handling
echo json_encode(['success' => false]);  // Breaks global error handler
```

### **Vehicle API Pattern (Special Case):**
`backend/dashboard/vehicle_api.php` demonstrates robust JSON API:
- Health-check endpoint: GET returns `{"status":"ok"}`
- Output buffering to capture accidental HTML/warnings
- Unified `send_json_response()` normalizes shape and logs raw output
- Dev mode: X-Dev-User header simulates auth (REMOVE in production)

### **Authentication Flow:**
```php
// Dashboard pages (HTML output)
Auth::requireRole('admin');  // Redirects if not admin

// API endpoints (JSON output)
Auth::requireAuth();  // Returns 401 JSON if not authenticated
if (!Auth::hasRole('customer')) {
    Response::unauthorized();
}
```

### **File Naming & Structure:**
- **Classes:** PascalCase (e.g., `Database.php`, `PaymentGateway.php`)
- **API endpoints:** lowercase with underscores (e.g., `get_profile.php`, `vehicle_api.php`)
- **Frontend:** lowercase with dashes/underscores (e.g., `payment-result.js`, `checkout.html`)
- **District field:** Many queries include `district` column for location filtering (see `backend/api/locations/search.php`)

## Integration Points

### **PHP <-> MySQL:**
- **New Method (Recommended):**
  ```php
  use App\Classes\Database;
  
  $db = Database::getInstance();
  $users = $db->fetchAll("SELECT * FROM users WHERE role = :role", ['role' => 'customer']);
  ```
  
- **Old Method (Still Works):**
  ```php
  require_once 'includes/db.php';
  $result = mysqli_query($conn, "SELECT * FROM users");
  ```

### **Frontend <-> Backend API:**
- **CSRF Protection:** All POST requests include CSRF token:
  ```js
  const token = await PaymentUtils.generateCSRFToken();
  // Backend endpoint: /backend/auth/get_csrf_token.php
  ```
- **Payment Flow:** `frontend/js/payment.js` → `backend/api/process_payment.php` → webhook handlers
- **WebSocket:** Real-time analytics on `ws://localhost:8080` (see `frontend/js/websocket-client.js`)

### **Payment Webhooks (Critical for Production):**
- **Webhook handlers:** `backend/api/payment/webhook.php`, `webhook_handler.php`
- **Signature verification:** Always validate `HTTP_X_WEBHOOK_SIGNATURE` header
- **Status mapping:** Webhook updates `transactions` table with JSON response data
- **Security:** Use `getenv('PAYMENT_WEBHOOK_SECRET')` for signature validation

## Code Examples

### **Using New PSR-4 Classes:**

#### **Database Query:**
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Database;

$db = Database::getInstance();

// Fetch one
$user = $db->fetchOne("SELECT * FROM users WHERE email = :email", ['email' => 'test@example.com']);

// Fetch all
$bookings = $db->fetchAll("SELECT * FROM bookings WHERE status = :status", ['status' => 'confirmed']);

// Insert
$userId = $db->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('password123', PASSWORD_DEFAULT),
    'role' => 'customer'
]);

// Update
$db->update('users', ['status' => 'active'], ['id' => $userId]);

// Delete
$db->delete('bookings', ['id' => 123]);
?>
```

#### **Authentication:**
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Session;

Session::start();
$auth = new Auth();

// Register
$result = $auth->register([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123',
    'role' => 'customer'
]);

// Login
$result = $auth->login('john@example.com', 'password123');

if ($result['success']) {
    header('Location: /carwash_project/backend/dashboard/customer/index.php');
}

// Check if logged in
if (!$auth->isAuthenticated()) {
    header('Location: /carwash_project/backend/auth/login.php');
    exit;
}

// Role-based access
$auth->requireRole('admin'); // Redirect if not admin
?>
```

#### **Validation:**
```php
<?php
use App\Classes\Validator;
use App\Classes\Response;

$validator = new Validator();
$validator
    ->required($_POST['email'], 'ایمیل')
    ->email($_POST['email'], 'ایمیل')
    ->required($_POST['password'], 'رمز عبور')
    ->minLength($_POST['password'], 6, 'رمز عبور');

if ($validator->fails()) {
    Response::validationError($validator->getErrors());
}

// Sanitize
$email = Validator::sanitizeEmail($_POST['email']);
$name = Validator::sanitizeString($_POST['name']);
?>
```

#### **API Response:**
```php
<?php
use App\Classes\Response;

// Success
Response::success('عملیات موفق', ['user_id' => 123]);

// Error
Response::error('عملیات ناموفق', 400);

// Not Found
Response::notFound('کاربر یافت نشد');

// Unauthorized
Response::unauthorized();
?>
```

## Migration Guide (Old → New)

### **Before (Old Style):**
```php
<?php
require_once 'includes/db.php';

$email = $_POST['email'];
$sql = "SELECT * FROM users WHERE email = '$email'"; // ❌ SQL Injection risk
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);
?>
```

### **After (Modern PSR-4):**
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Database;
use App\Classes\Validator;

$db = Database::getInstance();

$email = Validator::sanitizeEmail($_POST['email']);
$user = $db->fetchOne(
    "SELECT * FROM users WHERE email = :email", // ✅ Prepared statement
    ['email' => $email]
);
?>
```

## File Structure Reference

```
carwash_project/
├── composer.json                          # Composer configuration
├── vendor/                                # Composer dependencies (auto-generated)
│
├── backend/
│   ├── classes/                          # ⭐ NEW: Core business logic
│   │   ├── Database.php
│   │   ├── Session.php
│   │   ├── Auth.php
│   │   ├── Validator.php
│   │   └── Response.php
│   │
│   ├── models/                           # ⭐ NEW: Database models
│   │   ├── User_Model.php
│   │   ├── Booking_Model.php
│   │   ├── Service_Model.php
│   │   └── Payment_Model.php
│   │
│   ├── includes/                         # Helper files (legacy + new)
│   │   ├── bootstrap.php                # ⭐ NEW: Autoloader initializer
│   │   ├── config.php                   # ⭐ NEW: Application constants
│   │   ├── db.php                       # Legacy (backward compatible)
│   │   └── functions.php                # Legacy (backward compatible)
│   │
│   ├── auth/                             # Authentication
│   ├── dashboard/                        # Role-based dashboards
│   └── api/                              # RESTful endpoints
│
├── frontend/
│   ├── css/
│   ├── js/
│   └── *.html
│
└── database/
    └── carwash.sql
```

## Best Practices

### **Security:**
- ✅ Always use prepared statements (`Database` class handles this)
- ✅ Hash passwords with `password_hash()` / `password_verify()`
- ✅ Sanitize inputs with `Validator::sanitize*()` methods
- ✅ Use `Session::regenerate()` after login to prevent session fixation
- ✅ Escape output with `htmlspecialchars()`

### **Code Organization:**
- ✅ New business logic → `backend/classes/`
- ✅ Database models → `backend/models/`
- ✅ Legacy code → Keep in `backend/includes/` for now
- ✅ API endpoints → Return JSON via `Response` class

### **Performance:**
- ✅ Use Singleton pattern for Database (already implemented)
- ✅ Enable Composer's optimized autoloader: `composer dump-autoload -o`

## References
- **README:** See `README.md` for project overview
- **Key Modern Files:**
  - `backend/classes/Database.php` - Database wrapper
  - `backend/classes/Auth.php` - Authentication
  - `backend/includes/bootstrap.php` - Autoloader initializer
  - `composer.json` - Dependencies and PSR-4 config
- **Key Legacy Files (Still Valid):**
  - `backend/includes/db.php` - Old DB connection
  - `backend/auth/login.php` - Login page
  - `frontend/css/style.css` - Main styles

---

## For AI Agents (Copilot):
- **Always prefer PSR-4 classes** for new code
- **Use `require_once __DIR__ . '/vendor/autoload.php';`** at the top of new files
- **Use namespaces:** `App\Classes\ClassName`
- **Backward compatible:** Don't break existing code that uses legacy includes
- **When in doubt:** Check similar modern files in `backend/classes/` or `backend/models/`
- **Security first:** Always use prepared statements, sanitize inputs, hash passwords
