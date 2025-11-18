<?php
/**
 * Universal Header
 * Wrapper for site-wide header to be used on general/non-authenticated pages
 */
// Reuse existing public header implementation
if (file_exists(__DIR__ . '/header.php')) {
    include_once __DIR__ . '/header.php';
} else {
    // If header.php missing, fall back to dashboard header
    if (file_exists(__DIR__ . '/dashboard_header.php')) include_once __DIR__ . '/dashboard_header.php';
}
