<?php
// Admin dashboard entry — minimal componentized loader
require_once __DIR__ . '/../../Auth.php';
require_once __DIR__ . '/../../includes/bootstrap.php';

// Ensure the user is an admin
Auth::requireRole('admin');

// Basic page variables
$page_title = 'Admin Dashboard';

// Whitelisted sections
$allowed = ['users','bookings','reports','settings'];
$section = isset($_GET['section']) ? strtolower(preg_replace('/[^a-zA-Z0-9_\-]/','', $_GET['section'])) : 'users';
if (!in_array($section, $allowed, true)) {
    $section = 'users';
}

// Include shared components (these render markup)
include_once __DIR__ . '/components/header.php';
include_once __DIR__ . '/components/sidebar.php';

// Render main area
echo "<main class=\"main-content\">";
include_once __DIR__ . '/components/stats_cards.php';
echo "<div class=\"content-area\">";
include_once __DIR__ . '/sections/' . $section . '.php';
echo "</div></main>'";

include_once __DIR__ . '/components/footer.php';
