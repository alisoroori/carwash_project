Puppeteer smoke test
====================

This repository contains a headless Puppeteer script to exercise key flows:
- Registration (Customer_Registration)
- Profile update (Customer_Dashboard_process)
- Password change (backend/api/update_password.php)

Quick start
-----------

1. Start PHP built-in server from the project root (PowerShell):

   php -S localhost:8000 -t "C:\xampp\htdocs\carwash_project";

2. Install puppeteer (optional: use the pinned quiet script in package.json):

   npm run smoke:install:quiet

3. Run the smoke test directly with node:

   node tools/puppeteer_smoke_test.cjs

Notes
-----
- The script calls tools/session_bootstrap.php to create a session and obtain a CSRF token. Ensure that file exists and is reachable at /tools/session_bootstrap.php when the PHP server is running.
- If your environment blocks network access or Chrome downloads, use the CLI runner tools/run_profile_update_cli.php to validate the server-side handlers without HTTP.
- The script is conservative: it will exit with code 0 on likely success, or >0 on failure. Inspect printed outputs for details.
Puppeteer smoke test (tools/puppeteer_smoke_test.js)

What it does
- Runs a headless browser to exercise three flows:
  1) Customer registration (visits `/backend/auth/Customer_Registration.php`, reads CSRF token, posts to `Customer_Registration_process.php`).
  2) Profile update (uses `/tools/session_bootstrap.php` to create a logged-in session + CSRF token, posts to `backend/dashboard/Customer_Dashboard_process.php`).
  3) Password change (posts to `backend/api/update_password.php` using the same session + token — expects a password-specific error but not a CSRF error).

Prerequisites (local)
- Node.js (v16+ recommended)
- npm
- A running local PHP dev server hosting the project, e.g. (run in project root):

  php -S localhost:8000 -t "C:\xampp\htdocs\carwash_project"

Install Puppeteer

  npm install puppeteer

Run the smoke test

  node tools/puppeteer_smoke_test.js

Environment variables
- BASE_URL: optionally override the default base URL (default http://localhost:8000)
  Example:
    BASE_URL=http://localhost:8000 node tools/puppeteer_smoke_test.js

Notes
- The script expects the project to be available at the BASE_URL path exactly as in development (backend and tools folders accessible).
- If your environment blocks localhost connections for the test runner, run this script locally on the machine that hosts the PHP dev server.
- The script exits with code 0 on success, non-zero on failure. It prints responses to the console for debugging.
