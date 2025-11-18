<?php
/**
 * Admin Header
 * Wrapper for admin pages. Sets dashboard context to 'admin' and includes the dashboard header.
 */
if (!isset($dashboard_type)) $dashboard_type = 'admin';
if (!isset($page_title)) $page_title = 'Yönetici Paneli - CarWash';
if (!isset($current_page)) $current_page = 'dashboard';

if (file_exists(__DIR__ . '/dashboard_header.php')) {
    include_once __DIR__ . '/dashboard_header.php';
} else {
    if (file_exists(__DIR__ . '/header.php')) include_once __DIR__ . '/header.php';
}
