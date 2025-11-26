# Deployment & Permissions Checklist (CarWash)

This quick checklist helps prepare the profile upload flow and app for production.

1. PHP & Webserver
- PHP 7.4+ or 8.x recommended.
- Ensure `mod_rewrite` and standard `uploads` settings enabled.

2. Composer
- Run `composer install --no-dev --optimize-autoloader`.

3. Uploads directory
- Ensure `backend/auth/uploads/` and `backend/auth/uploads/profiles/` exist.
- Set ownership to the webserver user (example for Debian/Ubuntu):
  sudo chown -R www-data:www-data backend/auth/uploads
- Set permissions:
  sudo find backend/auth/uploads -type d -exec chmod 0755 {} \;
  sudo find backend/auth/uploads -type f -exec chmod 0644 {} \;

4. Logs
- Ensure `logs/` is writable by webserver and rotated externally (logrotate).

5. Environment
- Configure `.env` with secure DB credentials and `APP_ENV=production`.
- Disable `display_errors` in production.

6. Security
- Use HTTPS and force redirects to HTTPS via webserver config.
- Secure upload handling: consider additional checks (finfo, getimagesize), virus scanning, and limiting accepted file sizes.

7. Backups
- Set up scheduled backups for uploaded files and the database.

8. Migration
- If the `user_profiles` table may not exist, ensure migration scripts run prior to deploying this change.

9. Post-deploy checks
- Test profile upload manually, confirm header/sidebar updates and persistence.
- Check `logs/app.log` for uncaught exceptions.

