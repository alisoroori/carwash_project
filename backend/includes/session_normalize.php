<?php
/**
 * Session normalization helper
 *
 * Ensures a single canonical session key is used for role: $_SESSION['role']
 * Migrates legacy keys ($_SESSION['user_role'], $_SESSION['user_type'], and user['role'])
 * to the canonical key when missing.
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// If canonical key already present, nothing to do
if (empty($_SESSION['role'])) {
    // prefer explicit legacy user_role
    if (!empty($_SESSION['user_role'])) {
        $_SESSION['role'] = $_SESSION['user_role'];
    }

    // else try user_type (might be capitalized or different format)
    if (empty($_SESSION['role']) && !empty($_SESSION['user_type'])) {
        $_SESSION['role'] = is_string($_SESSION['user_type']) ? strtolower($_SESSION['user_type']) : $_SESSION['user_type'];
    }

    // else try nested user array shape
    if (empty($_SESSION['role']) && !empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['role'])) {
        $_SESSION['role'] = $_SESSION['user']['role'];
    }
}

// For short transitional compatibility, ensure legacy key also exists
if (!empty($_SESSION['role']) && empty($_SESSION['user_role'])) {
    $_SESSION['user_role'] = $_SESSION['role'];
}

// End of session_normalize.php
