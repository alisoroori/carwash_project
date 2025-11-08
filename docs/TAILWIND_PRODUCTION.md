Tailwind CSS — Production setup for CarWash Project

This document explains how Tailwind is built for production in this repository and how to reproduce the setup locally.

What I changed

- Removed runtime CDN loads of Tailwind (`https://cdn.tailwindcss.com`) across the repo and replaced them with a link to the compiled CSS at `/carwash_project/frontend/css/tailwind.css`.
- Added a small PowerShell script at `scripts/fix-tailwind-cdn.ps1` that can be re-run to replace remaining CDN references if needed.
- Wrapped any `tailwind.config = {...}` runtime assignments with a safety guard (`if (typeof tailwind !== 'undefined') { ... }`) so pages don't throw reference errors when the CDN runtime is not present.
- Compiled the production CSS with the Tailwind CLI (`npm run build-css-prod`) to generate a minified `frontend/css/tailwind.css`.

Files of interest

- `package.json` — contains build scripts:
  - `npm run build-css` — development (watch)
  - `npm run build-css-prod` — production (minify)
- `frontend/css/input.css` — Tailwind input file with `@tailwind base/components/utilities`.
- `frontend/css/tailwind.css` — compiled output (generated).
- `tailwind.config.js` — Tailwind config (content paths include `backend/**/*.php` and `frontend/**/*.{html,js}`).
- `scripts/fix-tailwind-cdn.ps1` — PowerShell script to replace CDN tags with local CSS links across the repo.

How to build locally (Windows PowerShell, served under XAMPP)

1. Install node dependencies (if not already):

```powershell
cd C:\xampp\htdocs\carwash_project
npm install
```

2. Build production CSS once:

```powershell
npm run build-css-prod
```

3. For active development (watch mode):

```powershell
npm run build-css
# or (if you want both Vite dev server + Tailwind watch):
# npm run dev (vite)  # front-end dev server
# npm run build-css   # tailwind watch (separate terminal)
```

Notes & caveats

- All pages now reference the compiled CSS at `/carwash_project/frontend/css/tailwind.css` (absolute path). This works when you access the site via your local web server at `http://localhost/carwash_project/`.
- I wrapped runtime `tailwind.config` assignments with a guard so pages won't attempt to set `tailwind.config` when the CDN is removed. The recommended approach is to put any configuration in `tailwind.config.js` and regenerate the CSS rather than rely on runtime config.
- If you have any pages served statically via `file://` (not through the PHP server), the absolute `/carwash_project/` root path may not resolve correctly — serve the site through XAMPP or adjust links accordingly for static testing.
- The repository still contains some test/report artifacts referencing the CDN (e.g., `.reports/*.json`) — these are not served to users and can be ignored or updated if desired.

Re-run the automated replacement script (if you want to re-apply the CDN→local conversion):

```powershell
cd C:\xampp\htdocs\carwash_project
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\fix-tailwind-cdn.ps1
```

If you'd like, I can:
- Update remaining docs/tests that still mention the CDN.
- Convert absolute `/carwash_project/...` links to use `<?php echo $base_url; ?>` in PHP templates for portability.
- Add a small cache-busting hash to the compiled `tailwind.css` include in production.

If you want me to proceed with any of these follow-ups, tell me which one to do next.