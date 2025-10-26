# CarWash Web Application

Summary
-------
Modern PHP web application to manage car wash businesses, services, customer bookings and payments.
Designed for local development on XAMPP/LAMP and structured with Composer + PSR-4 autoloading.

Quick facts
- Tech: PHP (7.4+ / 8.x), MySQL, Composer, HTML/CSS/JS
- Namespaces: App\Classes (backend/classes), App\Models (backend/models)
- Root dev path used in examples: `c:\xampp\htdocs\carwash_project`
- Web root (browser): http://localhost/carwash_project/

Table of contents
- Getting started
- Project layout & important files
- Composer, autoload and bootstrap
- Database & config
- Authentication & RBAC (how to use)
- Logging & error handling
- Security: sessions, CSRF, uploads
- Frontend notes (validation, CSRF)
- Tests & CI
- Deployment checklist & troubleshooting
- API examples & Postman tip
- Support

1) Getting started (local)
--------------------------
Prerequisites
- XAMPP or LAMP stack running Apache + MySQL
- Composer installed
- PHP CLI available

Install and run
1. Put project in XAMPP htdocs:
   c:\xampp\htdocs\carwash_project
2. Install PHP dependencies:
   cd c:\xampp\htdocs\carwash_project
   composer install
3. Ensure `backend/includes/config.php` is set (DB constants, BASE_URL).
4. Import DB schema:
   mysql -u root -p < database/carwash.sql
5. Open in browser:
   http://localhost/carwash_project/frontend/index.html
   Login page: http://localhost/carwash_project/backend/auth/login.php

2) Project layout — (high level)
--------------------------------
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
Key locations:
- backend/classes/ — PSR-4 classes (Database, Session, Auth, Validator, Response, Logger)
- backend/models/ — models for User, Booking, Service, Payment
- backend/includes/bootstrap.php — autoloader + logger + global handlers
- backend/auth/ — login/register/logout handlers and profile uploads
- backend/dashboard/ — role dashboards (admin / carwash / customer)
- backend/api/ — JSON endpoints for bookings, services, payments
- frontend/ — static UI pages, JS/CSS
- database/carwash.sql — schema
- logs/ — application logs (created by Logger)
- .github/workflows/ci.yml — CI workflow for tests and static checks

3) Composer, PSR-4 & bootstrap
-----------------------------
Composer autoload:
- composer.json maps PSR-4: "App\\": "backend/"
- Use `require_once __DIR__ . '/vendor/autoload.php';` or `backend/includes/bootstrap.php` to bootstrap app.

Bootstrap responsibilities (backend/includes/bootstrap.php):
- require vendor/autoload.php
- read backend/includes/config.php
- initialize Logger::init()
- set error/exception handlers to log full traces and show friendly messages
- optionally set display_errors based on APP_ENV

4) Database & configuration
---------------------------
- Database config: backend/includes/config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- Use App\Classes\Database (Singleton PDO wrapper) for queries:
  $db = \App\Classes\Database::getInstance();
  $user = $db->fetchOne("SELECT * FROM users WHERE email = :email", ['email'=>$email]);

5) Authentication and RBAC (how to use)
---------------------------------------
Auth class location: backend/classes/Auth.php (namespace App\Classes)
Essential methods provided and recommended usage:
- Auth::isAuthenticated(): bool — check if a user is logged in
- Auth::requireAuth(): void — block anonymous requests (redirect or JSON 401)
- Auth::hasRole($roleOrArray): bool — check current user's role
- Auth::requireRole($roleOrArray): void — enforce role; returns JSON 403 for API or redirect/show 403 page for UI

Examples:
- At top of dashboard admin pages:
  require_once __DIR__ . '/../../../vendor/autoload.php';
  use App\Classes\Auth;
  Auth::requireRole('admin');

- In API endpoints:
  Auth::requireRole('admin'); // will send JSON 403 if not permitted

Session behavior:
- Use App\Classes\Session to start sessions and read/write user data.
- After successful login: Session::regenerate() or session_regenerate_id(true) to prevent session fixation.
- Session cookie flags: Secure, HttpOnly, SameSite should be set in production.

6) Logging & error handling
---------------------------
- Logger available at backend/classes/Logger.php — uses Monolog if present, otherwise falls back to PHP error_log to logs/app.log
- bootstrap.php registers:
  - set_error_handler: converts warnings/notices to ErrorException
  - set_exception_handler: logs full exception (stack trace) and returns generic message to user (JSON for API)
- Do NOT echo raw exception messages to users in production; rely on Logger::exception($e)

7) Security recommendations (important)
---------------------------------------
- APP_ENV=production: display_errors = Off
- Use prepared statements for ALL DB access (Database class helps)
- Passwords: use password_hash() and password_verify()
- CSRF:
  - Server should issue a per-session token (stored server-side) and validate incoming POSTs.
  - Frontend includes client-side CSRF helper; update server to validate `csrf_token` field against session.
- File uploads:
  - Store uploaded images outside web root or restrict direct execution
  - Sanitize filenames, validate MIME/type and size
- HTTPS: enforce redirect and enable HSTS
- Rate limit login attempts (simple throttle or lockout)
- Rotate secrets immediately if accidentally committed

8) Frontend notes
-----------------
- Client-side validation script: frontend/js/form-validation.js (mirrors server rules)
- Error partial: frontend/templates/error-block.html injected for consistent UI
- Forms include a hidden `csrf_token` input populated by JS — server must validate it to be effective

9) Tests & Continuous Integration
---------------------------------
- PHPUnit configured: phpunit.xml.dist, tests/ (DatabaseTest, AuthTest) included
- CI workflow: .github/workflows/ci.yml — runs on push/pull_request, sets up PHP, composer install and runs vendor/bin/phpunit. Also optionally runs phpstan/phpcs if present.
- To run locally:
  composer install
  vendor/bin/phpunit --configuration phpunit.xml.dist

10) Deployment checklist & QA (short)
-------------------------------------
See CAR_WASH_QA_CHECKLIST.md for the detailed checklist. Quick items:
- Set APP_ENV=production, display_errors=0
- Run composer install --no-dev --optimize-autoloader
- Ensure logs/ exists and is writable by web user but not world-readable
- Enforce HTTPS and set HSTS header
- Rotate any secrets found in git history
- Test backups and restore

11) Troubleshooting — login issues (common causes)
--------------------------------------------------
If login works via navigation but not in browser frame or form:
- Session cookie domain/path: ensure login form posts to the correct domain (http://localhost/carwash_project/backend/auth/login.php) and the response sets a session cookie that the browser can store. Using iframe can complicate cookies — test in a top-level window.
- Check browser dev tools -> Network & Cookies: after successful login response, a PHPSESSID cookie should be set (and later sent on subsequent requests).
- If using `127.0.0.1` vs `localhost`, cookies may be set differently. Use consistent hostnames.
- Verify bootstrap.php is included in login script so Session::start() runs and Logger/handlers are initialized.
- Confirm login handler expects POST field names `email` and `password`. Use correct content-type (form or JSON) depending on handler.
- Check logs/app.log for exceptions or DB errors (use Logger).
- Cross-site restrictions: if login is inside an iframe, some browsers block third-party cookies; test outside iframe.
- Tips:
  - Open backend/auth/login.php directly in browser tab and login — if it works, the issue is framing/cookie policy.
  - Use curl with `-c cookies.txt` and `-b cookies.txt` to debug programmatic login.

12) API examples (quick)
------------------------
Login (form):
curl -i -X POST -d "email=hasan@carwash.com&password=password123" http://localhost/carwash_project/backend/auth/login.php

Create booking (JSON API):
curl -i -H "Accept: application/json" -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d '{"service_id":1,"date":"2025-10-30","time":"10:00"}' \
  http://localhost/carwash_project/backend/api/bookings/create.php

13) Post-deployment & maintenance
---------------------------------
- Monitor logs/ for exceptions and increased error rates.
- Keep PHP and Composer deps updated; run `composer audit` periodically.
- Backup DB daily and test restores on staging.
- Use a centralized logging or APM solution for production if possible.

Support & contributors
----------------------
- Report issues to repository issue tracker
- For urgent production incidents, rotate credentials and follow checklist steps in CAR_WASH_QA_CHECKLIST.md

License
-------
MIT

Last updated: 2025-10-20
