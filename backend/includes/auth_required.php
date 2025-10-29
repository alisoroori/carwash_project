<?php
/**
 * Lightweight include that ensures authentication helper functions are available.
 *
 * Other pages should include this file when they intend to call requireLogin()
 * or other auth helper functions defined in `auth_check.php`.
 */

// Load session/auth helpers
if (!file_exists(__DIR__ . '/auth_check.php')) {
    // If the helper file is missing log and throw a clear exception
    error_log('auth_required.php: auth_check.php missing');
    throw new \RuntimeException('Authentication subsystem not available');
}

require_once __DIR__ . '/auth_check.php';

// Now auth functions (requireLogin, isLoggedIn, etc.) are available
// Do not auto-call requireLogin here; callers should explicitly requireLogin() when appropriate.

