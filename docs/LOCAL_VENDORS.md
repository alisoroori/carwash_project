Local vendor installation

This repository uses local copies of some vendor files (Font Awesome, Alpine.js) to avoid CDN tracking/prevention issues during development.

What this document provides
- A PowerShell script to download official vendor files into `frontend/vendor/*`.
- Verification steps to ensure `backend/includes/header.php` will load the local files.

How to run (Windows PowerShell)

1. Open an elevated or regular PowerShell terminal in the repository root (C:\xampp\htdocs\carwash_project).
2. Run the installer script (this will download files from CDNs):

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\install-local-vendors.ps1
```

Notes
- The script downloads Font Awesome 6.4.0 CSS and common webfonts from cdnjs, and Alpine.js 3.12.0 cdn build from jsDelivr. You can edit `scripts/install-local-vendors.ps1` to change versions/URLs.
- If your environment blocks web downloads, download the files manually and place them at:
  - `frontend/vendor/fontawesome/css/all.min.css`
  - `frontend/vendor/fontawesome/webfonts/*.woff2` and `*.woff`
  - `frontend/vendor/alpinejs/cdn.min.js`

Why local files
- Some browsers block or heavily restrict third-party CDN resources because of tracking protection, which can break icon fonts or JS initialization. Local copies avoid that while you develop locally.

Header behavior
- `backend/includes/header.php` already prefers local vendor files if they exist. After installing, it will include local files automatically.

Security
- When using local vendor files, keep them updated and verify checksums for production use. For development this script is convenient, but for production it's preferred to use a proper package manager, pinned versions, and an asset pipeline.

Troubleshooting
- If the script fails due to ExecutionPolicy, you can temporarily relax the policy:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
# then run the script
```

- If web requests fail, check network or proxy settings.

Next steps (optional)
- Add the vendor files to your build pipeline (npm, yarn) and keep them updated via package.json.
- Replace the CDN fallback strategy with a static site hosting strategy for production.
