# 🚗 CarWash Web Application

## English

A modern web application for managing car wash businesses, customer reservations, and service management.
Built with **PHP (Backend)**, **MySQL (Database)**, and **Composer PSR-4 Autoloading**, designed to run on **XAMPP/LAMP** stack.

---

## 🚀 Features

- **User Authentication System**
  - Customer and CarWash registration
  - Secure login with password hashing
  - Role-based access control (Admin, CarWash, Customer)
  
- **Booking Management**
  - Online appointment scheduling
  - Real-time availability checking
  - Booking history and tracking
  
- **CarWash Management**
  - Business profile setup (Logo, contact, hours)
  - Service catalog management
  - Location and zone mapping
  
- **Payment Integration**
  - Multiple payment methods
  - Transaction tracking
  - Webhook handling
  
- **Modern Architecture**
  - PSR-4 autoloading with Composer
  - Namespaced classes (`App\Classes`, `App\Models`)
  - Prepared statements for SQL injection prevention
  - Singleton pattern for database connections

---

## 📂 Project Structure (Modernized)

```
carwash_project/
├── composer.json                          # Composer configuration & PSR-4 autoloading
├── test_autoload.php                      # Autoloading verification script
├── vendor/                                # Composer dependencies (auto-generated)
│
├── backend/
│   ├── classes/                          # ⭐ NEW: Core business logic classes
│   │   ├── Database.php                 # PDO database wrapper (Singleton)
│   │   ├── Session.php                  # Session management
│   │   ├── Auth.php                     # Authentication logic
│   │   ├── Validator.php                # Input validation
│   │   └── Response.php                 # JSON API responses
│   │
│   ├── models/                           # ⭐ NEW: Database model classes
│   │   ├── User_Model.php               # User CRUD operations
│   │   ├── Booking_Model.php            # Booking management
│   │   ├── Service_Model.php            # Service catalog
│   │   └── Payment_Model.php            # Payment processing
│   │
│   ├── includes/                         # Helper files & configuration
│   │   ├── bootstrap.php                # ⭐ NEW: Autoloader initializer
│   │   ├── config.php                   # ⭐ NEW: Application constants
│   │   ├── db.php                       # Legacy DB (backward compatibility)
│   │   ├── functions.php                # Legacy helpers (backward compatibility)
│   │   ├── availability_checker.php
│   │   ├── image_handler.php
│   │   ├── location_manager.php
│   │   ├── notification_channels.php
│   │   ├── payment_gateway.php
│   │   ├── payment_tracker.php
│   │   └── profile_manager.php
│   │
│   ├── auth/                             # Authentication endpoints
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── Customer_Registration.php
│   │   ├── Car_Wash_Registration.php
│   │   ├── reset_password.php
│   │   └── uploads/
│   │       └── profiles/
│   │
│   ├── dashboard/                        # Role-based dashboards
│   │   ├── admin/
│   │   │   ├── analytics.php
│   │   │   ├── index.php
│   │   │   ├── users.php
│   │   │   └── zone_mapper.php
│   │   ├── carwash/
│   │   │   ├── bookings.php
│   │   │   ├── index.php
│   │   │   └── services.php
│   │   └── customer/
│   │       ├── bookings.php
│   │       ├── index.php
│   │       └── profile.php
│   │
│   └── api/                              # RESTful API endpoints
│       ├── bookings/
│       │   ├── create.php
│       │   └── list.php
│       ├── locations/
│       │   ├── search.php
│       │   └── update.php
│       ├── payment/
│       │   ├── process.php
│       │   └── webhook.php
│       └── services/
│           └── manage.php
│
├── frontend/                             # Client-side assets
│   ├── css/
│   │   ├── dashboard.css
│   │   ├── main.css
│   │   └── style.css
│   ├── js/
│   │   ├── maps/
│   │   │   └── service-areas.js
│   │   ├── payment/
│   │   │   └── checkout.js
│   │   └── websocket/
│   │       ├── connection-manager.js
│   │       └── event-handler.js
│   ├── booking.html
│   ├── index.html
│   └── services.html
│
├── database/
│   └── carwash.sql                       # MySQL schema
│
├── uploads/                              # User-uploaded content
│   ├── profiles/
│   └── services/
│
├── .gitignore
├── README.md
└── project_navigator.html
```

---

## 🏗️ Architecture Overview

### **Backend Structure**

#### **Classes (Namespace: `App\Classes`)**
Core business logic with PSR-4 autoloading:

- **Database.php**: PDO wrapper with prepared statements
  - Singleton pattern for connection management
  - Methods: `query()`, `fetchOne()`, `fetchAll()`, `insert()`, `update()`, `delete()`
  - Transaction support: `beginTransaction()`, `commit()`, `rollback()`

- **Auth.php**: User authentication
  - `register()`: User registration with password hashing
  - `login()`: Secure login with session management
  - `requireAuth()`: Middleware for protected routes
  - `hasRole()`: Role-based access control

- **Session.php**: Session wrapper
  - `start()`, `set()`, `get()`, `has()`, `remove()`, `destroy()`
  - Flash messages: `setFlash()`, `getFlash()`
  - Security: `regenerate()` for session fixation prevention

- **Validator.php**: Input validation
  - Chainable validation: `required()`, `email()`, `minLength()`, `maxLength()`
  - Sanitization: `sanitizeString()`, `sanitizeEmail()`
  - Error handling: `passes()`, `fails()`, `getErrors()`

- **Response.php**: JSON API responses
  - `success()`: Send success response
  - `error()`: Send error response
  - `notFound()`, `unauthorized()`, `forbidden()`

#### **Models (Namespace: `App\Models`)**
Database access layer with business logic:

- **User_Model.php**: User operations
- **Booking_Model.php**: Booking CRUD
- **Service_Model.php**: Service management
- **Payment_Model.php**: Payment processing

#### **Includes**
Helper files and configuration:

- **bootstrap.php**: Initializes Composer autoloader
- **config.php**: Application-wide constants (DB, paths, URLs)
- **Legacy files** (db.php, functions.php): Kept for backward compatibility

---

## 🛠️ Installation & Setup

### **1. Prerequisites**
- PHP >= 7.4.0
- MySQL/MariaDB
- XAMPP or LAMP stack
- Composer (for dependency management)

### **2. Installation Steps**

```bash
# Clone or download the project
cd c:\xampp\htdocs\carwash_project

# Install Composer dependencies
composer install

# Verify autoloading works
php test_autoload.php
```

### **3. Database Setup**

```sql
-- Import database schema
mysql -u root -p < database/carwash.sql

-- Or use phpMyAdmin:
-- 1. Open http://localhost/phpmyadmin
-- 2. Create database 'carwash'
-- 3. Import database/carwash.sql
```

### **4. Configuration**

Edit `backend/includes/config.php`:

```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application URLs
define('BASE_URL', 'http://localhost/carwash_project');
define('UPLOAD_PATH', BASE_URL . '/backend/auth/uploads/');
```

### **5. Access the Application**

```
Frontend:  http://localhost/carwash_project/frontend/index.html
Login:     http://localhost/carwash_project/backend/auth/login.php
Register:  http://localhost/carwash_project/backend/auth/Customer_Registration.php
```

---

## 🔌 API Endpoints

### **Bookings API**

#### **Create Booking**
```http
POST /backend/api/bookings/create.php
Content-Type: application/json

{
  "customer_id": 123,
  "service_id": 45,
  "carwash_id": 67,
  "date": "2025-10-25",
  "time": "14:00"
}

Response:
{
  "success": true,
  "message": "Booking created successfully",
  "data": {
    "booking_id": 89,
    "status": "pending"
  }
}
```

#### **List Bookings**
```http
GET /backend/api/bookings/list.php?status=confirmed&date_from=2025-10-20

Response:
{
  "success": true,
  "data": [
    {
      "id": 89,
      "customer_name": "John Doe",
      "service": "Full Wash",
      "date": "2025-10-25",
      "time": "14:00",
      "status": "confirmed"
    }
  ]
}
```

### **Locations API**

#### **Search Nearby CarWashes**
```http
GET /backend/api/locations/search.php?latitude=41.0082&longitude=28.9784&radius=5

Response:
{
  "success": true,
  "data": [
    {
      "id": 12,
      "name": "Premium Car Wash",
      "distance": 2.3,
      "address": "Istanbul, Turkey",
      "rating": 4.5
    }
  ]
}
```

### **Payment API**

#### **Process Payment**
```http
POST /backend/api/payment/process.php
Content-Type: application/json

{
  "booking_id": 89,
  "payment_method": "credit_card",
  "amount": 150.00
}

Response:
{
  "success": true,
  "message": "Payment processed successfully",
  "data": {
    "transaction_id": "TXN_12345",
    "status": "completed"
  }
}
```

---

## 👨‍💻 Usage Examples

### **Using Classes in PHP Files**

```php
<?php
// Load autoloader
require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;
use App\Classes\Auth;
use App\Classes\Validator;

// Initialize classes
$auth = new Auth();

// Validate user input
$validator = new Validator();
$validator
    ->required($_POST['email'], 'Email')
    ->email($_POST['email'], 'Email')
    ->required($_POST['password'], 'Password')
    ->minLength($_POST['password'], 6, 'Password');

if ($validator->fails()) {
    echo json_encode(['errors' => $validator->getErrors()]);
    exit;
}

// Login user
$result = $auth->login($_POST['email'], $_POST['password']);

if ($result['success']) {
    header('Location: /carwash_project/backend/dashboard/customer/index.php');
} else {
    echo $result['message'];
}
?>
```

### **Database Operations**

```php
<?php
use App\Classes\Database;

$db = Database::getInstance();

// Insert
$userId = $db->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'customer'
]);

// Update
$db->update('users', 
    ['status' => 'active'], 
    ['id' => $userId]
);

// Fetch
$user = $db->fetchOne(
    "SELECT * FROM users WHERE email = :email",
    ['email' => 'john@example.com']
);

// Check existence
if ($db->exists('users', ['email' => 'test@example.com'])) {
    echo "User exists!";
}
?>
```

---

## 🔐 Security Features

- **Password Hashing**: Uses `password_hash()` with bcrypt
- **Prepared Statements**: All SQL queries use PDO prepared statements
- **Session Security**: 
  - HTTP-only cookies
  - Session regeneration on login
  - CSRF protection (planned)
- **Input Validation**: Server-side validation for all inputs
- **XSS Prevention**: HTML escaping with `htmlspecialchars()`

---

## 📋 Developer Workflows

### **Local Development**
1. Place project in XAMPP's `htdocs` directory
2. Start Apache and MySQL via XAMPP Control Panel
3. Access via `http://localhost/carwash_project/`

### **Adding New Features**

#### **Create New Class**
```php
<?php
// File: backend/classes/My_Class.php
namespace App\Classes;

class My_Class {
    public function myMethod() {
        // Your code
    }
}
?>
```

#### **Create New Model**
```php
<?php
// File: backend/models/My_Model.php
namespace App\Models;

use App\Classes\Database;

class My_Model {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll() {
        return $this->db->fetchAll("SELECT * FROM my_table");
    }
}
?>
```

---

## 🧪 Testing

```bash
# Test autoloading
php test_autoload.php

# Expected output:
# ✅ Autoloader loaded successfully
# ✅ Database class loaded
# ✅ Database connected successfully
# ✅ All tests passed!
```

---

## 📦 Composer Configuration

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "backend/"
    }
  },
  "require": {
    "php": ">=7.4.0",
    "ext-pdo": "*",
    "ext-mysqli": "*",
    "ext-json": "*",
    "ext-session": "*"
  }
}
```

---

## 🗂️ Project-Specific Conventions

### **File Naming**
- PHP files: Use underscores (e.g., `Car_Wash_Registration.php`)
- Classes: PascalCase (e.g., `Database.php`, `User_Model.php`)
- HTML/CSS/JS: Lowercase with dashes (e.g., `booking.html`, `style.css`)

### **Namespaces**
- Classes: `App\Classes\`
- Models: `App\Models\`

### **Database Access**
- **New code**: Use `App\Classes\Database`
- **Legacy code**: Can still use `includes/db.php` (backward compatible)

### **Uploads**
- Profile images: `backend/auth/uploads/profiles/`
- Service images: `uploads/services/`

---

## 🚀 Migration from Legacy Code

### **Before (Old Style)**
```php
<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$email = $_POST['email'];
$sql = "SELECT * FROM users WHERE email = '$email'"; // ❌ SQL Injection risk
$result = mysqli_query($conn, $sql);
?>
```

### **After (Modern PSR-4)**
```php
<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';

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

---

## 📖 Documentation

- **Main Documentation**: This README
- **API Documentation**: See `API Endpoints` section above
- **Coding Standards**: See `.github/copilot-instructions.md`
- **Database Schema**: See `database/carwash.sql`

---

## 🤝 Contributing

1. Follow PSR-4 autoloading standards
2. Use namespaces for all classes
3. Write prepared statements for all SQL queries
4. Add comments to complex logic
5. Test changes with `test_autoload.php`

---

## 📄 License

MIT License - Feel free to use this project for learning and commercial purposes.

---

## 👥 Credits

**CarWash Development Team**
- Modern PHP architecture with Composer
- PSR-4 autoloading implementation
- Secure authentication system
- RESTful API design

---

## 📞 Support

For issues or questions:
- Check existing documentation
- Review similar files in the codebase
- Contact: dev@carwash.local

---

**Last Updated**: October 20, 2025
**Version**: 2.0.0 (Modernized with Composer + PSR-4)
