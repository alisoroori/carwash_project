# Forms Audit — carwash_project

Date: 2025-11-10

Summary
-------
I scanned the repository for all occurrences of `<form` and performed a lightweight analysis of each file for: method attribute, action attribute, presence of a CSRF hidden field (`name="csrf_token"`), and whether server-side CSRF checks exist in related handlers.

Results (high-level)
--------------------
- Total `<form` matches found: 125 (scan capped at 200). Many forms are JavaScript-driven (Alpine/@submit.prevent) and therefore rely on JS to include CSRF tokens via meta tag or programmatic append.
- Files that already include server-side CSRF generation / verification are present (examples: `backend/includes/index-header.php` has `session_start()`; `backend/classes/Security.php`, `backend/includes/security.php`, and many API endpoints check CSRF). Several pages already generate `$_SESSION['csrf_token']`.

What I checked per form
-----------------------
For each file containing `<form`, I recorded:
- file path
- snippet of the form tag (if available)
- basic flags: has `method=`, has `action=`, contains `name="csrf_token"` somewhere in the file

Partial findings (selected / prioritized)
----------------------------------------
- `backend/dashboard/Customer_Dashboard.php` (multiple forms)
  - `#vehicleForm` line ~1133: form uses `@submit.prevent="saveVehicle()"` (AJAX). The file already contains server-side CSRF generation at top (`if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }`) and includes `<input type="hidden" name="csrf_token" :value="csrfToken">` in the template (Alpine binding). JS appends the token when building FormData. Status: OK but recommended to ensure a meta CSRF token is present for JS fallback.

- `backend/dashboard/Customer_Dashboard_Fixed.php` — similar to above; has CSRF generation and hidden input. Status: OK.

- `frontend/customer/booking.html` — `<form id="custBookingForm" action="/carwash_project/backend/api/bookings/create.php" method="post" ...>`: explicit action and method, so OK; ensure a hidden CSRF input exists or API accepts X-CSRF-Token header.

- `backend/booking/new_booking.php` — `method="post" action="/carwash_project/backend/api/bookings/create.php"`: OK; includes hidden csrf input in file.

- Admin forms (e.g. `backend/dashboard/admin/*` such as `carwashForm`, `serviceForm`, `userForm`, etc.) — many are `id`ed but some don't include explicit `method` or `action` on the form tag. Some admin pages rely on JS to submit data via AJAX; ensure they read CSRF token from meta tag or include hidden input.

- `frontend/payment.html` includes a hidden `<input name="csrf_token" id="csrf_token">` but the value is populated by JS; ensure the meta CSRF or session token is available for JS to read.

- Many legacy pages (e.g. `backend/auth/login.php`, `Customer_Registration.php`, `create_user.php`, etc.) already generate `$_SESSION['csrf_token']` and insert hidden input fields. Status: OK.

Files lacking explicit CSRF or missing method/action (representative)
-----------------------------------------------------------------
- Several frontend templates contain generic `<form class="space-y-6">` without method/action. These are often used for JS/AJAX flows — they should either:
  1) Have server-facing method/action and include a hidden CSRF input (simpler), or
  2) Keep JS submission but ensure JS reads CSRF from `meta[name="csrf-token"]` and includes it in POST/XHR header.

Recommended next steps (explicit)
---------------------------------
1. Add a central CSRF meta tag inside `backend/includes/header.php` (it already contains one when Session exists; verify `header.php` is included in all pages). Example:

   `<meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">`

   This ensures JS-driven forms (Alpine/fetch) can read the token.

2. For JS/AJAX forms (pages using `@submit.prevent` or `onsubmit="event.preventDefault(); ...`):
   - Either add a hidden input inside the form: `<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '')?>">`
   - Or ensure the page's JS uses `const csrf = document.querySelector('meta[name="csrf-token"]').content || ''` and appends the token to FormData or sets `X-CSRF-Token` header.

3. For forms that do full POSTs (server-submitted), ensure `method` and `action` exist. If they are omitted, propose adding `method="post" action="<handler>"`.

4. For each backend API handler (`backend/api/**`), ensure they validate CSRF tokens by reading `$_POST['csrf_token']` or `$_SERVER['HTTP_X_CSRF_TOKEN']` and comparing with `$_SESSION['csrf_token']` using `hash_equals()`. If missing, add a small helper at the top:

```php
if (session_status() === PHP_SESSION_NONE) session_start();
$csrfSent = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (empty($_SESSION['csrf_token']) || !$csrfSent || !hash_equals($_SESSION['csrf_token'], $csrfSent)) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Invalid CSRF token']);
    exit;
}
```

5. Create a small audit PR for the highest-impact pages first:
   - `backend/dashboard/Customer_Dashboard.php` and `_Fixed.php`: ensure meta CSRF exists, ensure AJAX code appends token (already present) — add fallback meta read.
   - `backend/includes/header.php`: verify meta tag present and session token generated somewhere globally (index-header had session_start). If not present, add meta generation and session CSRF generator.
   - Admin dashboard forms: add hidden CSRF inputs where forms are server-posted; for AJAX forms, ensure JS reads meta CSRF.

6. Optionally add a repo-wide automated check script (`scripts/check_forms_csrf.php`) to scan all forms and fail CI if forms lacking CSRF token or missing input names are detected.

Deliverables I can create now
----------------------------
- `FORMS_AUDIT.md` (this file) — created.
- A patch to add a meta CSRF token to `backend/includes/header.php` and ensure session CSRF token is generated at app bootstrap (`backend/includes/bootstrap.php` or `index-header.php`) — I can implement this change now.
- A small PHP helper include (`backend/includes/csrf_protect.php`) that provides `generate_csrf_token()` and `verify_csrf_token()` to standardize checks in API handlers; I can add it and update a couple of API handlers as examples.

Next step options (pick one)
---------------------------
- I will implement the meta CSRF tag in `backend/includes/header.php` and add a `backend/includes/csrf_protect.php` helper and update `backend/api/bookings/create.php` (and one other API) to use it. Then run `php -l` on modified files and present the patch. (Recommended incremental approach.)

- OR I will attempt automatic edits across all forms to insert hidden CSRF inputs and add name attributes to un-named inputs. This is higher-risk; I recommend manual PRs for groups of pages instead.

Tell me which option you prefer. If you want the safer incremental change, I will proceed to add the meta tag and helper and update a couple of API endpoints as a proof-of-concept.
