# Copilot Instructions for CarWash Web Application

## Project Overview
- **Purpose:** Manage car wash businesses, customer reservations, and service management.
- **Tech Stack:** PHP (backend), MySQL (database), HTML/CSS/JS (frontend), Composer (PSR-4 autoloading). Runs on XAMPP/LAMP.
- **Structure:**
  - `backend/`: PHP API, authentication, dashboard logic, includes (DB, functions), **classes/** (PSR-4), **models/** (database models).
  - `frontend/`: Static assets, CSS, JS, and UI HTML files.
  - `vendor/`: Composer dependencies (auto-generated).

## Key Architectural Patterns

### **Backend (Modernized with PSR-4):**
- **New Architecture (Composer + PSR-4):**
  - Namespace: `App\Classes` → `backend/classes/`
  - Namespace: `App\Models` → `backend/models/`
  - Autoloading via Composer: `require_once __DIR__ . '/vendor/autoload.php';`
  
- **Core Classes (`backend/classes/`):**
  - `Database.php`: PDO wrapper with Singleton pattern, prepared statements
  - `Session.php`: Secure session management wrapper
  - `Auth.php`: User authentication (register, login, logout, role-based access)
  - `Validator.php`: Input validation and sanitization
  - `Response.php`: JSON API response handler
  
- **Models (`backend/models/`):**
  - Database access layer for entities (User, Booking, Service, Payment)
  - Each model extends base functionality from `Database` class
  
- **Legacy Support:**
  - Old files in `backend/includes/` (db.php, functions.php) remain for backward compatibility
  - New code should use PSR-4 classes; old code can still use legacy includes

### **Frontend:**
- Static HTML files for main pages (e.g., `index.php`, `booking.html`).
- Custom CSS/JS in `frontend/css/` and `frontend/js/`.

## Developer Workflows

### **Local Development:**
- Use XAMPP/LAMP. Place project in `htdocs` (Windows) or `www` (Linux).
- Access via `http://localhost/carwash_project/`.

### **Composer Setup:**
```bash
# Install dependencies (first time only)
cd c:\xampp\htdocs\carwash_project
composer install

# Update dependencies
composer update

# Regenerate autoloader
composer dump-autoload
```

### **Database:**
- Import schema from `database/carwash.sql`.
- DB connection config in `backend/includes/config.php`.
- **New Code:** Use `App\Classes\Database` for queries.
- **Old Code:** Can still use `backend/includes/db.php`.

### **Authentication:**
- Registration and login handled in `backend/auth/`.
- **New Code:** Use `App\Classes\Auth` and `App\Classes\Session`.
- **Old Code:** Direct `$_SESSION` manipulation still works.

### **Dashboards:**
- Separate dashboards for admin, car wash, and customer in `backend/dashboard/`.
- Add authentication checks using `Auth::requireAuth()` and `Auth::requireRole()`.

## Project-Specific Conventions

### **File Naming:**
- PHP class files: PascalCase (e.g., `Database.php`, `User_Model.php`)
- Other PHP files: Use underscores (e.g., `Car_Wash_Registration.php`)
- HTML/JS/CSS files: lowercase with dashes/underscores

### **Namespace Convention:**
- Classes: `App\Classes\ClassName`
- Models: `App\Models\ModelName`

### **Composer + PSR-4:**
- Dependencies managed via `composer.json`
- Autoloading: `require_once __DIR__ . '/vendor/autoload.php';`
- Do NOT commit `vendor/` folder to git (use `.gitignore`)

### **Uploads:**
- User-uploaded images stored in `backend/auth/uploads/profiles/`.
- Service images in `uploads/services/`.

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

### **Frontend <-> Backend:**
- Forms in HTML pages POST to PHP scripts in `backend/`.
- API endpoints return JSON via `App\Classes\Response`.

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
