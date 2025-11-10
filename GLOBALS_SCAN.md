# GLOBALS_SCAN.md

Repository global-scope scan, prioritized refactor plan, and smoke-test checklist

Date: 2025-11-10

## Goal
Find top-level JS globals (window.*, top-level var/let/const) that templates rely on (x-data usage), prioritize vehicle-related elements, and provide an explicit migration plan to Alpine.data factories with backward-compatible wrappers and smoke-tests.

## Quick commands (run locally)
- Ripgrep-style scans (Windows PowerShell / repo root):

  # find window.* globals
  rg --hidden --line-number --no-ignore-vcs "window\.[A-Za-z0-9_]+" .

  # find top-level var/let/const assignments
  rg --hidden --line-number --no-ignore-vcs "^\s*(var|let|const)\s+[A-Za-z0-9_]+\s*=" frontend backend assets || true

  # find x-data factory usages
  rg --hidden --line-number --no-ignore-vcs "x-data=(\"|')?[^\"'>]*\b[A-Za-z0-9_]+\(\)" .

## Summary of findings (high-level)
I scanned for the three patterns and produced the following prioritized candidates. This is not every match in the repo (there are many OK uses of window.* like window.addEventListener). Focus on global factories and window.* exposures that affect UI state and Alpine expectations.

### Top-priority (vehicle related)
- `backend/dashboard/Customer_Dashboard.php` — lines around 1011: `x-data="vehicleManager()"` (section for vehicles). Suggested action: refactor vehicle code into `Alpine.data('vehicleManager', ...)` and ensure the page includes the factory file deferred.
- `backend/dashboard/Customer_Dashboard_Fixed.php` — line ~356: `x-data="vehicleManager()"` (same change as above).
- `frontend/js/vehicleManager.js` — already present; update/replace with Alpine.data factory (or move into `frontend/js/alpine-factories/vehicleManager.js`) and leave a temporary `window.vehicleManager` wrapper for compatibility.

### High-priority (page-level globals that should be Alpine-scoped)
- `backend/includes/header.php` and `backend/includes/index-header.php` — many `window.*` assignments (e.g., `window.toggleMobileMenu`, `window.closeMobileMenu`, and top-level `let isMobile` variables). These are good candidates to convert the header behavior into an Alpine.data factory (e.g. `Alpine.data('siteHeader', ...)`) that exposes `mobileMenuOpen`, `toggleMobileMenu()`, computed breakpoints, and ARIA state.
- `backend/includes/dashboard_header.php`, `backend/includes/dashboard_header_improved.php` — similar header/dashboard global behavior to move into an Alpine factory used inside dashboard pages.

### Medium-priority
- `frontend/booking.html`, `frontend/profile.html`, `frontend/customer_profile.html` — uses `window.open`, `window.onclick` and `window.__carwash_debug` exposure. `__carwash_debug` is a debug helper; keep but consider namespacing under `window.__carwash_debug = { ... }` or convert to an Alpine factory for book/booking flows.
- `backend/includes/footer.php` — uses window scroll/resize events to adjust UI. Consider `Alpine.data('siteFooter', ...)` or keep event listeners but remove assignments to `window.*` where not necessary.

### Tests and tools
- `tools/tests/puppeteer/test_carwash_flow.js` — uses headless browser tests; a good place to wire an automated smoke test that checks console logs and missing references.

## Concrete action plan (explicit steps)
Follow these steps in sequence. For each refactor, do 1 file at a time, run lint and php -l, and smoke-test in browser.

1) Repo scan (you already ran; to re-run locally):
   - Commands above (ripgrep). Export to CSV/Markdown by piping rg results.

2) Create folder for Alpine factories:
   - `frontend/js/alpine-factories/`

3) Vehicle factory (first PR)
   - Create `frontend/js/alpine-factories/vehicleManager.js` with this exact pattern (copy into file):

```js
// frontend/js/alpine-factories/vehicleManager.js
document.addEventListener('alpine:init', () => {
  Alpine.data('vehicleManager', () => ({
    // state
    vehicles: [],
    formData: { brand: '', model: '', license_plate: '', year: '', color: '' },
    editingVehicle: null,
    showVehicleForm: false,
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || window.CONFIG?.CSRF_TOKEN || '',
    imagePreview: null,
    loading: false,
    message: '',
    messageType: '',

    // lifecycle
    init() {
      console.log('vehicleManager factory loaded');
      if (!this.csrfToken && window.CONFIG?.CSRF_TOKEN) this.csrfToken = window.CONFIG.CSRF_TOKEN;
      // optional: fetch initial vehicles
      this.fetchVehicles().catch(err => console.error('fetchVehicles error', err));
    },

    // methods
    async fetchVehicles() {
      try {
        const res = await fetch('/carwash_project/backend/api/get_vehicles.php', {
          headers: { 'X-CSRF-TOKEN': this.csrfToken }
        });
        if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
        const data = await res.json();
        this.vehicles = Array.isArray(data) ? data : [];
      } catch (err) {
        this.messageType = 'error';
        this.message = 'Could not load vehicles';
        console.error('fetchVehicles:', err);
      }
    },

    openVehicleForm(vehicle = null) {
      this.editingVehicle = vehicle;
      if (vehicle) this.formData = { ...vehicle };
      else this.formData = { brand: '', model: '', license_plate: '', year: '', color: '' };
      this.showVehicleForm = true;
    },

    closeVehicleForm() {
      this.showVehicleForm = false;
    },

    async saveVehicle() {
      this.loading = true;
      try {
        const action = this.editingVehicle ? 'update_vehicle.php' : 'add_vehicle.php';
        const res = await fetch(`/carwash_project/backend/api/${action}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken
          },
          body: JSON.stringify(this.formData)
        });
        if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
        await this.fetchVehicles();
        this.messageType = 'success';
        this.message = 'Saved';
        this.closeVehicleForm();
      } catch (err) {
        this.messageType = 'error';
        this.message = 'Save failed';
        console.error('saveVehicle:', err);
      } finally {
        this.loading = false;
      }
    },

    async deleteVehicle(id) {
      if (!confirm('Delete this vehicle?')) return;
      try {
        const res = await fetch(`/carwash_project/backend/api/delete_vehicle.php?id=${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': this.csrfToken }
        });
        if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
        await this.fetchVehicles();
      } catch (err) {
        this.messageType = 'error';
        this.message = 'Delete failed';
        console.error('deleteVehicle:', err);
      }
    }
  }));
});

// Backward compatibility wrapper (temporary)
window.vehicleManager = function() {
  // If Alpine is available, return the factory instance object used by x-data (Alpine.data returns a factory function)
  try {
    return (Alpine && Alpine.data && Alpine.data('vehicleManager') && Alpine.data('vehicleManager')()) || {};
  } catch (e) {
    return {};
  }
};
```

Notes:
- Use `document.addEventListener('alpine:init', ...)` which is the recommended way to register factories and ensures Alpine is available.
- The wrapper `window.vehicleManager` returns the factory object so templates using `x-data="vehicleManager()"` still work while we migrate.
- Adjust API paths (`/carwash_project/backend/api/...`) if your BASE_URL differs; prefer using `window.CONFIG.BASE_URL` if available.

4) Update template includes (header/footer)
   - Ensure `backend/includes/header.php` includes Alpine with `defer` and then the factories with `defer` (ordering matters):

```php
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="<?php echo $base_url; ?>/frontend/js/alpine-factories/vehicleManager.js" defer></script>
```

   - Ensure meta CSRF token exists in `header.php`:

```php
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
```

5) Replace or confirm x-data usage
   - If `x-data="vehicleManager()"` is used, the Alpine.data registration above will make it resolve. No change required to the templates except ensuring the factory file is included (deferred).
   - For header-level state (mobile menu, dropdowns), create `frontend/js/alpine-factories/siteHeader.js` implementing `Alpine.data('siteHeader', ...)` and convert inline `window.toggleMobileMenu` logic to `toggleMobileMenu()` inside that factory. Then change the header root to `x-data="siteHeader()"` (or wrap inner header area with the x-data).

6) Repeat pattern for other prioritized globals
   - Examples: `vehicleOperations.js` -> `Alpine.data('vehicleOps', ...)`, `customer-dashboard.js` -> `Alpine.data('customerDashboard', ...)`.
   - Add console.log statements in init(): `console.log('<name> factory loaded')` for quick smoke verification.

7) Lint and checks
   - PHP syntax checks for modified PHP files:
     php -l backend/dashboard/Customer_Dashboard.php || true
   - Quick JS lint if ESLint is available:
     npx eslint frontend/js/alpine-factories --ext .js || true

8) Smoke-test (manual)
   - Rebuild Tailwind if needed: `npm run build-css-prod`
   - Start XAMPP/Apache + PHP.
   - Open: `http://localhost/carwash_project/backend/dashboard/Customer_Dashboard.php`
   - DevTools Console expectations:
     - See: "vehicleManager factory loaded"
     - No: Uncaught ReferenceError: vehicleManager (or vehicles, formData, loadVehicles etc.)
   - Actions to test:
     - Open Vehicles section; click Add Vehicle, fill fields, Save; confirm vehicles list updates.
     - Delete a vehicle; confirm list updates.

9) Optional automated smoke test (Puppeteer)
   - Add a simple script under `tools/tests/puppeteer/` that opens the dashboard, listens to console messages, asserts the factory log message, and checks for console errors.

10) Cleanup
   - After verifying pages use Alpine.data factories and there are no dependencies on `window.*`, remove the temporary `window.*` wrappers and inline globals. Optionally bundle factories with your build system (Vite) and convert to ESM if desired.

## Findings table (selected matches)
| File | Location / lines | Match / notes | Suggested action |
|---|---:|---|---|
| `backend/dashboard/Customer_Dashboard.php` | ~line 1011 | `x-data="vehicleManager()"` | Ensure vehicle factory file is loaded deferred; migrate inline JS to factory. |
| `backend/dashboard/Customer_Dashboard_Fixed.php` | ~line 356 | `x-data="vehicleManager()"` | Same action as above. |
| `frontend/js/vehicleManager.js` | file | Contains older factory/wrapper code | Move to `frontend/js/alpine-factories/vehicleManager.js`, adopt `alpine:init` registration and wrapper. |
| `backend/includes/header.php` | multiple | `window.toggleMobileMenu`, top-level isMobile variables | Convert header logic to `Alpine.data('siteHeader', ...)` to avoid window.* exposures. |
| `backend/includes/index-header.php` | multiple | Many window.scroll/resize handlers and window.closeMobileMenu | Consider `siteHeader` factory; or namespace these event handlers behind a factory. |
| `frontend/booking.html` | multiple | `window.__carwash_debug = { ... }` and window.history.back usages | Leave as debug but consider namespacing. |
| `backend/includes/footer.php` | multiple | window scroll events | Consider `siteFooter` factory or keep but avoid polluting window. |

(There are many additional matches; above are the prioritized ones.)

## Recommended PR strategy
- PR 1 (small, high-impact): Add `frontend/js/alpine-factories/vehicleManager.js`, include it via `header.php` (defer), run php -l on updated files, manual smoke-test vehicles flows.
- PR 2: Add `frontend/js/alpine-factories/siteHeader.js` and convert header global functions to the factory; update header markup to use `x-data="siteHeader()"` or wrap the relevant DOM.
- PR 3+: Convert other dashboard globals (`vehicleOperations`, `customer-dashboard`, `admin_panel` helpers) iteratively. Each PR should be small and accompanied by smoke-test steps.

## Rollback plan
- If a refactor causes breakage, revert the PR and re-introduce the previous script include. Keep a short-lived `window.*` wrapper to reduce breakage risk.

## Next steps I can take for you
- I can create the `frontend/js/alpine-factories/vehicleManager.js` file and modify `backend/includes/header.php` to include it with `defer`, then run `php -l` on affected PHP files and produce the PR patch here.
- I can produce a Puppeteer smoke-test script under `tools/tests/puppeteer/` to verify console logs and lack of ReferenceErrors.

---

If you want, I will proceed to create the vehicle factory file and update `backend/includes/header.php` (small PR). Tell me if you prefer a new git branch name (default: `alpine-migrate/vehicleManager`) and whether to commit the Puppeteer test too.
