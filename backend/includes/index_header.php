<?php
/**
 * Index Header
 * Public/homepage header — thin wrapper over existing public header implementation
 */
// Prefer existing global header implementation
if (file_exists(__DIR__ . '/header.php')) {
    include_once __DIR__ . '/header.php';
} else {
    // Fallback: include dashboard header if public header missing
    if (file_exists(__DIR__ . '/dashboard_header.php')) include_once __DIR__ . '/dashboard_header.php';
}
