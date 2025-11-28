# Copilot Instructions — CarWash Web App
This file guides AI coding agents on the CarWash project's structure, patterns, and workflows for immediate productivity.
## Quick Start
- **Entry Points:** ackend/includes/bootstrap.php (autoload, config) and composer.json (PSR-4: App\Classes → ackend/classes/).
- **Directories:**
  - ackend/classes/ — PSR-4 classes (Database, Auth, Response, Validator).
  - ackend/models/ — DB models.
  - ackend/includes/ — Bootstrap and legacy helpers.
  - ackend/api/ — JSON endpoints.
  - rontend/js/ — Client utilities (api-utils.js, csrf-helper.js).
## Key Conventions
- **PHP Files:** Use equire_once __DIR__ . '/vendor/autoload.php'; and App\Classes\* namespace.
- **API Responses:** Always use Response::success() / Response::error() — never raw json_encode().
- **DB Queries:** Use Database::getInstance()->fetchOne/All/Insert/Update/Delete() with prepared statements.
- **Auth:** Pages: Auth::requireRole('admin'); APIs: Auth::requireAuth() + Auth::hasRole().
- **Frontend API Calls:** etch('/carwash_project/backend/api/...') with CSRF from csrf-helper.js.
## Commands (Windows PowerShell)
- Install PHP: composer install
- Install Node: 
pm install
- Dev: .\dev.bat (Vite + Tailwind)
- Build: 
pm run build && npm run build-css
- DB: mysql -u root -p < database/carwash.sql
- Test: endor/bin/phpunit
## API Pattern Example
`php
require_once __DIR__ . '/../../includes/bootstrap.php';
use App\Classes\{Auth, Response};
Auth::requireAuth();
 = Database::getInstance()->fetchOne('SELECT * FROM users WHERE id = :id', ['id' => ['user_id']]);
Response::success('Profile', ['user' => ]);
`
## Notes for AI Agents
- Prefer PSR-4 classes over legacy ackend/includes/ edits.
- Maintain backward compatibility.
- Mirror patterns in ackend/api/* for new endpoints.
- WebSocket for real-time: ws://localhost:8080 (shared/websocket-client.js).
- Payments: Webhook signature verification in ackend/api/payment/webhook.php.
