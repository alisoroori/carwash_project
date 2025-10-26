# CarWash Project — QA & Security Checklist (Pre-Deployment)

This checklist is intended to be followed before deploying to production. Mark items as Done/NA and keep evidence (screenshots/logs).

## Environment & Configuration
- [ ] APP_ENV set to `production` (or equivalent) on server.
  - Verify: environment variable or backend/includes/config.php
- [ ] display_errors = Off in php.ini / runtime
  - Command: php -r "echo ini_get('display_errors');"
- [ ] Composer installed with production deps only: `composer install --no-dev --optimize-autoloader`
- [ ] .gitignore present and contains /vendor, /.env, /uploads, /logs
- [ ] Secrets NOT present in repo. If they were, rotate immediately and remove from history.

## Authentication & Sessions
- [ ] Passwords hashed with password_hash() and verified with password_verify()
- [ ] Session regeneration after login: Session::regenerate() or session_regenerate_id(true)
- [ ] Session cookies set with Secure, HttpOnly and SameSite=strict/ lax
  - Verify via browser devtools cookies
- [ ] Rate limiting / brute-force protection on login endpoints (rate-limit or account lockout)
- [ ] Account recovery flows verified (email reset tokens expire, single-use)

## Input Validation & Output Encoding
- [ ] Use prepared statements / parameterized queries for all DB access
- [ ] Validate & sanitize server-side inputs (Validator class)
- [ ] HTML output escaped (htmlspecialchars) to prevent XSS
- [ ] Client-side validation present (nice-to-have), server-side authoritative

## CSRF, CORS & API Security
- [ ] CSRF protection enabled for state-changing forms (token tied to session)
- [ ] API endpoints return JSON with proper HTTP codes for auth/forbidden
- [ ] CORS configured only for allowed origins (if API used cross-origin)

## File Uploads & Storage
- [ ] Uploaded files stored outside web root or with safe access controls
- [ ] Validate file types, max size, and scan for malware if possible
- [ ] Filenames sanitized; do not trust user-supplied names

## Transport & Headers
- [ ] HTTPS enforced (redirect HTTP -> HTTPS)
- [ ] HSTS header set (Strict-Transport-Security)
- [ ] Content Security Policy (CSP) reviewed for app needs
- [ ] X-Content-Type-Options, X-Frame-Options set appropriately

## Logging, Monitoring & Error Handling
- [ ] Logger initialized (logs/ protected) and logging level appropriate
- [ ] Display of raw exception messages disabled; generic message shown to users
- [ ] Log rotation in place (logrotate or similar)
- [ ] Alerts configured for critical errors (optional integration with Sentry/monitoring)

## Backup & Recovery
- [ ] Database backups scheduled and tested
  - Example: mysqldump and test restore to staging
- [ ] File storage backups scheduled (uploads, attachments)
- [ ] Disaster recovery playbook documented (who to contact, restore steps)

## Dependencies & Vulnerability Management
- [ ] composer audit / composer outdated checked and critical updates applied
- [ ] Third-party libraries reviewed and pinned where possible
- [ ] Remove unused packages (composer remove)

## Permissions & Hosting
- [ ] File/folder permissions hardened (no world-writable code files, uploads writable only by web user)
- [ ] SSH keys and server credentials restricted, use key-based auth
- [ ] No development tools left on public servers (phpmyadmin, admin panels)

## CI / Tests / Release
- [ ] Automated tests (phpunit) pass on CI
- [ ] Static analysis (PHPStan/PHPCS) run in CI
- [ ] Deployment process documented (zero-downtime if required)
- [ ] Staging environment used for final acceptance testing

## Final Verification (Smoke Tests)
- [ ] Register / Login / Logout flows work
- [ ] Role-based access control (admin/carwash/customer) enforced
- [ ] Create booking, view booking, notifications flow verified
- [ ] Payments (or sandbox) verified end-to-end

## Incident & Secrets Handling
- [ ] Secrets rotation plan in place (DB credentials, API keys)
- [ ] If secrets leaked: rotate immediately, remove from git history (BFG or filter-branch), force-push and notify team

---

Notes / Quick commands
- Remove sensitive files from tracking:
  - git rm -r --cached vendor .env uploads logs || true
  - git commit -m "Stop tracking sensitive files" && git push
- Check for accidentally committed secrets:
  - git log --all --grep='.env' --name-only
- Use BFG Cleaner to purge large/sensitive files: https://rtyley.github.io/bfg-repo-cleaner/
