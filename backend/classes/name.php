<?php
declare(strict_types=1);

namespace App\Classes;

class name to avoid 'use' inside blocks
    if (class_exists('\\App\\Classes\\Session')) {
        \App\Classes\Session::start();
    } else {
        // Session class هنوز لود نشده — احتمالاً autoload مشکل دارد
        // نادیده می‌گیریم تا بررسی‌های بعدی انجام شود
    }
}

// =============================================================================
// READY TO USE
// =============================================================================

// At this point, you can use:
// - All PSR-4 autoloaded classes (App\Classes\*, App\Models\*)
// - All configuration constants (BASE_URL, DB_HOST, etc.)
// - Session management via App\Classes\Session
// - Helper functions (app_path(), app_url(), redirect(), etc.)
?>
