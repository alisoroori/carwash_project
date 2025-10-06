# Copilot Instructions for CarWash Web Application

## Project Overview
- **Purpose:** Manage car wash businesses, customer reservations, and service management.
- **Tech Stack:** PHP (backend), MySQL (database), HTML/CSS/JS (frontend). Runs on XAMPP/LAMP.
- **Structure:**
  - `backend/`: PHP API, authentication, dashboard logic, and includes (DB, functions).
  - `frontend/`: Static assets, CSS, JS, and UI HTML files.

## Key Architectural Patterns
- **Backend:**
  - Modular PHP scripts for authentication (`auth/`), dashboards (`dashboard/`), and shared logic (`includes/`).
  - Database access via `includes/db.php`.
  - No framework; routing and logic are file-based.
- **Frontend:**
  - Static HTML files for main pages (e.g., `homes.html`).
  - Custom CSS/JS in `frontend/css/` and `frontend/js/`.

## Developer Workflows
- **Local Development:**
  - Use XAMPP/LAMP. Place project in `htdocs` (Windows) or `www` (Linux).
  - Access via `http://localhost/carwash_project/`.
- **Database:**
  - Import schema from `database/carwash.sql` (if present).
  - DB connection config in `backend/includes/db.php`.
- **Authentication:**
  - Registration and login handled in `backend/auth/`.
  - Session management via PHP sessions.
- **Dashboards:**
  - Separate dashboards for admin, car wash, and customer in `backend/dashboard/`.

## Project-Specific Conventions
- **File Naming:**
  - Use underscores for PHP files (e.g., `Car_Wash_Registration.php`).
  - HTML/JS/CSS files use lowercase and dashes/underscores.
- **No Composer or npm:**
  - No package manager; dependencies are managed manually.
- **Uploads:**
  - User-uploaded images stored in `backend/auth/uploads/`.

## Integration Points
- **PHP <-> MySQL:**
  - All DB access via `includes/db.php`.
- **Frontend <-> Backend:**
  - Forms in HTML pages POST to PHP scripts in `backend/`.

## Examples
- To add a new dashboard, create a PHP file in `backend/dashboard/` and link from the UI.
- To add a new auth flow, add a PHP script in `backend/auth/` and update forms accordingly.

## References
- See `README.md` for a high-level overview and folder structure.
- Key files: `backend/includes/db.php`, `backend/auth/login.php`, `backend/dashboard/Car_Wash_Dashboard.php`, `frontend/css/style.css`.

---
**For AI agents:**
- Follow the file-based routing and modular structure.
- Use existing patterns for DB access and session management.
- When in doubt, check similar files in the relevant directory.
