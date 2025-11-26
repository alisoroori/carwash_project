# Profile Upload - Addendum

These notes document the changes made by the automated fixes and the CLI test helper added to the project.

Files added:
- `backend/includes/profile_upload_helper.php` - Reusable helper to copy an image into the uploads directory and upsert the `user_profiles.profile_image` value. Also updates session keys when run in a web/CLI context.
- `tools/test_profile_upload_cli.php` - CLI test script that selects a random existing profile image and simulates an upload for a given `user_id` (defaults to `14`).

Frontend changes summary:
- Immediate preview via FileReader bound to `#profileImageInput`, updates `#profileImagePreview`.
- AJAX submission uses `profileForm.getAttribute('action')`, `FormData`, and checks `response.ok` before JSON parsing.
- `refreshProfileImages()` now falls back to `window.CARWASH.profile.canonical` when no URL is provided.
- `backend/includes/customer_header.php` now exposes `window.CARWASH.profile.canonical` and `window.getCanonicalProfileImage()`.

Server-side changes summary:
- `backend/dashboard/Customer_Dashboard.php` will insert into `user_profiles` if the row is missing before updating `profile_image`.
- Diagnostic `error_log()` calls in header and dashboard were consolidated to `cw_log_debug()` which prefers `\App\Classes\Logger::debug()` when available.

Testing notes:
- The CLI test attempts to include bootstrap and run. If your environment is missing webserver-specific state (certain $_SERVER vars), you may need to run it via the webserver or set expected $_SERVER values in the CLI script.

If you want, I can merge the helper usage into the web upload path so the web handler calls `handleProfileUploadFromPath()` when appropriate, and then remove the inline upload code in `Customer_Dashboard.php` to avoid duplication.
