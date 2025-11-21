<?php
/**
 * Universal Header Component for CarWash Website
 * Hybrid header that works for both website pages and customer dashboard
 * Automatically detects context and adapts accordingly
 * 
 * Features:
 * - Responsive design (mobile hamburger, tablet/desktop full nav)
 * - Modern, elegant, professional styling
 * - Works from any directory level
 * - Detects if user is logged in
 * - Shows appropriate navigation based on context
 * 
 * Usage: 
 * <?php include $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/backend/includes/header.php'; ?>
 * 
 * Optional variables to set before including:
 * - $page_title: Custom page title
 * - $current_page: Current page identifier for active states
 * - $is_dashboard: Boolean to indicate dashboard context
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect the base URL automatically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/carwash_project';

// Smart path detection - works from any directory level
$current_dir = dirname($_SERVER['PHP_SELF']);

// Determine if this is a dashboard page
$is_dashboard = isset($is_dashboard) ? $is_dashboard : (strpos($current_dir, '/dashboard') !== false);

// Ensure $is_logged_in is defined to avoid PHP notices when header is included
// Pages may set $is_logged_in before including this file; default to session check.
$is_logged_in = isset($is_logged_in) ? $is_logged_in : (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']));

// Build navigation URLs
$home_url = $base_url . '/backend/index.php';
$about_url = $base_url . '/backend/about.php';
$contact_url = $base_url . '/backend/contact.php';
$login_url = $base_url . '/backend/auth/login.php';
$register_url = $base_url . '/backend/auth/register.php';
$logout_url = $base_url . '/backend/includes/logout.php';

// Dashboard URL should point to the proper dashboard based on user role
$customer_dashboard = $base_url . '/backend/dashboard/Customer_Dashboard.php';
$carwash_dashboard = $base_url . '/backend/dashboard/Car_Wash_Dashboard.php';
$admin_dashboard = $base_url . '/backend/dashboard/admin_panel.php';
$dashboard_url = $customer_dashboard; // default
if ($is_logged_in) {
  $sessRole = $_SESSION['role'] ?? strtolower($_SESSION['user_type'] ?? '');
  // Prefer Auth::hasRole if available
  if (class_exists(\App\Classes\Auth::class) && method_exists(\App\Classes\Auth::class, 'hasRole')) {
    try {
      if (\App\Classes\Auth::hasRole('carwash')) {
        $dashboard_url = $carwash_dashboard;
      } elseif (\App\Classes\Auth::hasRole('admin')) {
        $dashboard_url = $admin_dashboard;
      } else {
        $dashboard_url = $customer_dashboard;
      }
    } catch (Throwable $e) {
      // fallback to session role below
      if ($sessRole === 'carwash') $dashboard_url = $carwash_dashboard;
      elseif ($sessRole === 'admin') $dashboard_url = $admin_dashboard;
    }
  } else {
    if ($sessRole === 'carwash') $dashboard_url = $carwash_dashboard;
    elseif ($sessRole === 'admin') $dashboard_url = $admin_dashboard;
  }
}

// Set defaults
$page_title = isset($page_title) ? $page_title : 'CarWash - Araç Yıkama Rezervasyon Sistemi';
$current_page = isset($current_page) ? $current_page : '';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = $is_logged_in ? ($_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User') : '';
$user_email = $is_logged_in ? ($_SESSION['email'] ?? '') : '';

// Determine site logo source (shared): prefer session value, then DB lookup by carwash_id/user_id, otherwise fallback
$default_logo = $base_url . '/backend/logo01.png';
$logo_src = $default_logo;
$raw_logo = $_SESSION['logo_path'] ?? null;
if (empty($raw_logo)) {
  // Try to read from DB if carwash_id or user_id present
  $cwId = $_SESSION['carwash_id'] ?? null;
  $uid = $_SESSION['user_id'] ?? null;
  if ($cwId || $uid) {
    try {
      if (class_exists(\App\Classes\Database::class)) {
        $db = \App\Classes\Database::getInstance();
        $pdo = method_exists($db, 'getPdo') ? $db->getPdo() : $db;
        // Prefer `carwashes` with `logo_path` column
        $stmt = $pdo->prepare('SELECT logo_path FROM carwashes WHERE id = :id OR user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $cwId ?: 0, ':uid' => $uid ?: 0]);
        $logoPath = $stmt->fetchColumn();
        if ($logoPath) {
          $raw_logo = $logoPath;
          // cache a filename-only value in session when possible
          if (preg_match('#(/|\\\\|https?://)#i', $raw_logo)) {
              $fname = basename($raw_logo);
              if (!empty($fname)) {
                  $_SESSION['logo_path'] = $fname;
              } else {
                  $_SESSION['logo_path'] = $raw_logo;
              }
          } else {
              $_SESSION['logo_path'] = $raw_logo;
          }
        }
      }
    } catch (Throwable $e) {
      // don't break the page; fall back to default logo
      error_log('Logo lookup failed: ' . $e->getMessage());
      $raw_logo = null;
    }
  }
}

// Normalize $raw_logo into a web-accessible $logo_src (prefer business_logo folder for filename-only values)
if (!empty($raw_logo)) {
  if (preg_match('#^(?:https?://|/)#i', $raw_logo)) {
    $candidate = $raw_logo;
  } else {
    // treat as filename and map to business_logo folder
    $candidate = $base_url . '/backend/uploads/business_logo/' . ltrim($raw_logo, '/');
  }
  // Verify file exists on disk
  $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\/');
  if (!preg_match('#^(?:https?://|/)#i', $raw_logo)) {
    $filePath = $docRoot . '/carwash_project/backend/uploads/business_logo/' . ltrim($raw_logo, '/');
  } else {
    $filePath = $docRoot . parse_url($candidate, PHP_URL_PATH);
  }
  if (file_exists($filePath)) {
    $logo_src = $candidate;
  } else {
    // fallback to default and clear session
    @file_put_contents(__DIR__ . '/../../logs/logo_missing.log', date('Y-m-d H:i:s') . " - header logo missing: {$filePath}\n", FILE_APPEND | LOCK_EX);
    $logo_src = $default_logo;
    unset($_SESSION['logo_path']);
  }
}
// Language detection: allow explicit session or GET override, else use Accept-Language
// and fall back to Turkish ('tr') as the site default.
$lang = 'tr';
$dir = 'ltr';
// Session override (set by auth or user preference)
if (!empty($_SESSION['lang'])) {
  $lang = $_SESSION['lang'];
} elseif (!empty($_GET['lang'])) {
  // allow simple language tag like 'tr' or 'en-US'
  $lang = preg_replace('/[^a-zA-Z\-]/', '', $_GET['lang']);
  // persist preference
  $_SESSION['lang'] = $lang;
} else {
  // Parse Accept-Language header if available
  if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $parts = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (!empty($parts[0])) {
      $lang = strtolower(substr(trim($parts[0]), 0, 5));
    }
  }
}
// Determine direction based on primary language subtag
$primary = substr($lang, 0, 2);
$rtl_languages = array('ar','fa','he','ur','ps','sd','ug');
$dir = in_array($primary, $rtl_languages, true) ? 'rtl' : 'ltr';

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>

  <!-- CSRF token for JS (server-side meta) -->
  <?php
  // Ensure CSRF helper is available and a token is generated for JavaScript
  if (file_exists(__DIR__ . '/csrf_protect.php')) {
      require_once __DIR__ . '/csrf_protect.php';
      $csrf_for_meta = generate_csrf_token();
      echo '<meta name="csrf-token" content="' . htmlspecialchars($csrf_for_meta, ENT_QUOTES, "UTF-8") . '">';
  } else {
      // Fallbacks: try Session helper or existing session value
      if (class_exists(\App\Classes\Session::class) && method_exists(\App\Classes\Session::class, 'get')) {
          echo '<meta name="csrf-token" content="' . htmlspecialchars(\App\Classes\Session::get('csrf_token'), ENT_QUOTES, 'UTF-8') . '">';
      } else {
          if (!empty($_SESSION['csrf_token'])) {
              echo '<meta name="csrf-token" content="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
          }
      }
  }
  ?>

  <!-- Tailwind CSS (CDN) - development convenience. -->
  <!-- Note: Using the CDN is convenient for development and quick demos.
    For production it's recommended to build Tailwind with the CLI/PostCSS
    and include a compiled, minified CSS file to enable purging of unused
    styles and better performance. -->
  <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/backdrop-fixes.css">
  <script>
    console.info("Tailwind CDN removed — serving compiled <?php echo $base_url; ?>/dist/output.css for production.");
  </script>
  
  <!-- Fixed Font Awesome font 404 errors -->
  <!-- Font Awesome: prefer local vendor copy, fall back to CDN. Use local only if CSS and essential webfonts exist. -->
  <?php
    $fa_css_local = __DIR__ . '/../../frontend/vendor/fontawesome/css/all.min.css';
    $fa_brands_local = __DIR__ . '/../../frontend/vendor/fontawesome/webfonts/fa-brands-400.woff2';
    if (file_exists($fa_css_local) && file_exists($fa_brands_local)):
  ?>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/frontend/vendor/fontawesome/css/all.min.css">
  <?php else: ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <?php endif; ?>

  <!-- Vehicle manager factory (load before Alpine so factories can register before Alpine initializes) -->
  <!-- Lightweight API utilities used by many frontend modules (defines window.apiCall) -->
  <script defer src="<?php echo $base_url; ?>/frontend/js/api-utils.js"></script>
  <!-- Vehicle manager factory (load before Alpine so factories can register before Alpine initializes) -->
  <script defer src="<?php echo $base_url; ?>/frontend/js/vehicleManager.js"></script>
  <!-- Small global shims to avoid ReferenceError when Alpine evaluates expressions that rely on page-scoped data
       These only set defaults when variables are not already defined (preserve real factories when present). -->
  <script>
    (function(){
      try {
        var metaCsrf = (document && document.querySelector && document.querySelector('meta[name="csrf-token"]')) ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
      } catch(e){ var metaCsrf = ''; }
      if (typeof window.csrfToken === 'undefined') {
        window.csrfToken = (window.CONFIG && window.CONFIG.CSRF_TOKEN) || metaCsrf || '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>' || '';
      }
      if (typeof window.editingVehicle === 'undefined') window.editingVehicle = null;
      if (typeof window.imagePreview === 'undefined') window.imagePreview = '';
      if (typeof window.formData === 'undefined') window.formData = { brand: '', model: '', license_plate: '', year: '', color: '' };
    })();
  </script>
  <!-- Alpine.js: prefer local vendor copy, fall back to CDN -->
  <?php if (file_exists(__DIR__ . '/../../frontend/vendor/alpinejs/cdn.min.js')): ?>
    <script defer src="<?php echo $base_url; ?>/frontend/vendor/alpinejs/cdn.min.js"></script>
  <?php else: ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <?php endif; ?>
  <!-- Local Alpine components (registers customerDashboard, etc.) -->
  <script defer src="<?php echo $base_url; ?>/frontend/js/alpine-components.js"></script>
  <!-- CSRF helper: reads meta token and appends to fetch/XHR and forms -->
  <script defer src="<?php echo $base_url; ?>/frontend/js/csrf-helper.js"></script>
  <!-- Accessibility: add titles/aria-labels to icon-only buttons at runtime -->
  <script defer src="<?php echo $base_url; ?>/frontend/js/accessible-button-labels.js"></script>
  <!-- Accessibility: add titles/aria-labels to selects missing accessible names -->
  <script defer src="<?php echo $base_url; ?>/frontend/js/accessible-select-labels.js"></script>
  
  <?php 
  // Include Universal CSS Styles for entire website
  include_once(__DIR__ . '/universal_styles.php');
  ?>
  
  <style>
    /* Custom CSS for enhanced styling */
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --primary-color: #667eea;
      --secondary-color: #764ba2;
      --shadow-elevation: 0 10px 30px rgba(0, 0, 0, 0.1);
      --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    html {
      scroll-behavior: smooth;
    }
    
    body {
      <?php if (!$is_dashboard): ?>
      /* Use CSS variable so header height is centralized in CSS */
      padding-top: var(--header-h);
      <?php endif; ?>
      /* Reserve bottom space for footer (uses CSS var) */
      padding-bottom: var(--footer-h);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    /* Elite Header Styling - Lighter Blue Theme with White Text */
    .header-elite {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      /* Safari support */
      -webkit-backdrop-filter: blur(10px);
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .logo-gradient {
      background: var(--primary-gradient);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .nav-link {
      position: relative;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .nav-link::before {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--primary-gradient);
      transition: width 0.3s ease;
    }
    
    .nav-link:hover::before,
    .nav-link.active::before {
      width: 100%;
    }
    
    .nav-link:hover {
      color: var(--primary-color);
      transform: translateY(-1px);
    }
    
    /* Button Styling */
    .btn-primary {
      background: var(--primary-gradient);
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-hover);
    }
    
    .btn-secondary {
      border: 2px solid transparent;
      background: linear-gradient(white, white) padding-box,
                  var(--primary-gradient) border-box;
      transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
      transform: translateY(-1px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
    }
    
    /* Enhanced Mobile Menu Animation */
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
        visibility: hidden;
      }
      to {
        opacity: 1;
        transform: translateY(0);
        visibility: visible;
      }
    }
    
    @keyframes slideUp {
      from {
        opacity: 1;
        transform: translateY(0);
        visibility: visible;
      }
      to {
        opacity: 0;
        transform: translateY(-20px);
        visibility: hidden;
      }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes fadeOut {
      from { opacity: 1; }
      to { opacity: 0; }
    }
    
    .mobile-menu {
      animation: slideDown 0.3s ease-out forwards;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
    }
    
    /* Hide mobile menu on desktop (md breakpoint and above) */
    @media (min-width: 768px) {
      .mobile-menu {
        display: none !important;
      }
      
      .mobile-menu-overlay {
        display: none !important;
      }
      
      /* Ensure desktop navigation is visible */
      nav.hidden.md\:flex {
        display: flex !important;
      }
    }
    
    .mobile-menu.hiding {
      animation: slideUp 0.3s ease-in forwards;
    }
    
    .mobile-menu-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.3);
      z-index: 40;
      animation: fadeIn 0.2s ease-out forwards;
    }
    
    .mobile-menu-overlay.hiding {
      animation: fadeOut 0.2s ease-in forwards;
    }
    
    /* Enhanced touch targets for mobile */
    .mobile-nav-item {
      min-height: 52px;
      display: flex;
      align-items: center;
      padding: 14px 18px;
      border-radius: 14px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(10px);
    }
    
    .mobile-nav-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
      transition: left 0.5s ease;
    }
    
    .mobile-nav-item:active::before {
      left: 100%;
    }
    
    .mobile-nav-item:hover,
    .mobile-nav-item:focus {
      transform: translateX(6px) scale(1.02);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    /* Swipe gesture support */
    .mobile-menu-container {
      touch-action: pan-y;
      overscroll-behavior: contain;
    }
    
    /* Enhanced mobile menu button */
    .mobile-menu-btn {
      position: relative;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: all 0.2s ease;
    }
    
    /* Hide mobile menu button on desktop (md breakpoint and above) */
    @media (min-width: 768px) {
      .mobile-menu-btn {
        display: none !important;
      }
    }
    
    .mobile-menu-btn:hover {
      background: rgba(59, 130, 246, 0.1);
    }
    
    .mobile-menu-btn:active {
      transform: scale(0.95);
    }
    
    .hamburger-icon,
    .close-icon {
      position: absolute;
      transition: all 0.3s ease;
    }
    
    .hamburger-icon.active {
      transform: rotate(180deg);
      opacity: 0;
    }
    
    .close-icon {
      transform: rotate(-180deg);
      opacity: 0;
    }
    
    .close-icon.active {
      transform: rotate(0deg);
      opacity: 1;
    }
    
    /* Dashboard Header Styling - Lighter Blue Theme with White Text */
    .dashboard-header {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      overflow: visible !important;
    }
    
    /* Header Elite Overflow */
    .header-elite {
      overflow: visible !important;
    }
    
    /* Ensure header containers allow dropdown overflow */
    .dashboard-header .container,
    .header-elite .container {
      overflow: visible !important;
    }
    
    /* Enhanced Dropdown Styling for Better Visibility */
    .dashboard-header .group,
    .header-elite .group {
      position: relative;
      z-index: 10000;
    }
    
    .dashboard-header .group > div[class*="absolute"],
    .header-elite .group > div[class*="absolute"] {
      z-index: 99999 !important;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.08) !important;
      border: 2px solid rgba(59, 130, 246, 0.15) !important;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      min-width: 220px !important;
    }
    
    /* Enhanced Dropdown Items */
    .dashboard-header .group a,
    .header-elite .group a {
      padding: 1rem 1.25rem !important;
      font-size: 0.9375rem !important;
      font-weight: 600 !important;
      color: #1f2937 !important;
      display: flex !important;
      align-items: center !important;
      gap: 0.75rem !important;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
      border-radius: 10px !important;
      margin: 0.25rem 0.5rem !important;
    }
    
    .dashboard-header .group a:hover,
    .header-elite .group a:hover {
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(37, 99, 235, 0.12) 100%) !important;
      transform: translateX(5px) !important;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15) !important;
    }
    
    .dashboard-header .group a i,
    .header-elite .group a i {
      width: 1.5rem !important;
      text-align: center !important;
      font-size: 1.125rem !important;
    }
    
    /* Enhanced Dropdown Header Section */
    .dashboard-header .group div[class*="border-b"],
    .header-elite .group div[class*="border-b"] {
      border-bottom-width: 2px !important;
      border-color: rgba(229, 231, 235, 0.8) !important;
      margin-bottom: 0.5rem !important;
      padding: 0.875rem 1.25rem !important;
    }
    
    .dashboard-header .group div[class*="border-b"] p:first-child,
    .header-elite .group div[class*="border-b"] p:first-child {
      font-weight: 700 !important;
      color: #111827 !important;
      font-size: 0.9375rem !important;
    }
    
    .dashboard-header .group div[class*="border-b"] p:last-child,
    .header-elite .group div[class*="border-b"] p:last-child {
      color: #6b7280 !important;
      font-weight: 500 !important;
    }
    
    /* Ensure dropdowns appear on hover with smooth transition */
    .dashboard-header .group:hover > div[class*="absolute"],
    .header-elite .group:hover > div[class*="absolute"] {
      opacity: 1 !important;
      visibility: visible !important;
      transform: translateY(0) !important;
    }
    
    /* Logout link special styling */
    .dashboard-header .group a[href*="logout"],
    .header-elite .group a[href*="logout"] {
      color: #dc2626 !important;
    }
    
    .dashboard-header .group a[href*="logout"]:hover,
    .header-elite .group a[href*="logout"]:hover {
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.1) 0%, rgba(185, 28, 28, 0.1) 100%) !important;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15) !important;
    }
    
    /* Comprehensive Responsive Design - Mobile First */
    
    /* Extra Small Mobile Devices: 320px - 479px */
    @media (max-width: 479px) {
      body {
        <?php if (!$is_dashboard): ?>
        padding-top: 56px;
        <?php endif; ?>

      .header-elite .py-4, .dashboard-header .py-4 {
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
      }
      
      .container {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
      }
      
      .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
      }
      
      .btn-primary, .btn-secondary {
        padding: 0.5rem 0.875rem;
        font-size: 0.875rem;
      }
      
      .mobile-hidden {
        display: none !important;
      }
    }
    
    /* Small Mobile Devices: 480px - 639px */
    @media (min-width: 480px) and (max-width: 639px) {
      body {
        <?php if (!$is_dashboard): ?>
        padding-top: 58px;
        <?php endif; ?>
      }
      
      .header-elite, .dashboard-header {
        padding: 0.5rem 0;
      }
      
      .header-elite .py-4, .dashboard-header .py-4 {
        padding-top: 0.875rem !important;
        padding-bottom: 0.875rem !important;
      }
      
      .nav-link {
        font-size: 0.875rem;
        padding: 0.625rem 0.875rem;
      }
      
      .btn-primary, .btn-secondary {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
      }
    }
    
    /* Large Mobile/Small Tablet: 640px - 767px */
    @media (min-width: 640px) and (max-width: 767px) {
      body {
        <?php if (!$is_dashboard): ?>
        padding-top: 60px;
        <?php endif; ?>
      }
      
      .header-elite, .dashboard-header {
        padding: 0.5rem 0;
      }
      
      .header-elite .py-4, .dashboard-header .py-4 {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
      }
      
      .nav-link {
        font-size: 0.9375rem;
        padding: 0.625rem 1rem;
      }
      
      .btn-primary, .btn-secondary {
        padding: 0.625rem 1.25rem;
        font-size: 0.9375rem;
      }
    }
    
    /* Tablet Portrait: 768px - 1023px */
    @media (min-width: 768px) and (max-width: 1023px) {
      body {
        <?php if (!$is_dashboard): ?>
        padding-top: 62px;
        <?php endif; ?>
      }
      
      .header-elite, .dashboard-header {
        padding: 0.5rem 0;
      }
      
      .header-elite .py-4, .dashboard-header .py-4 {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
      }
      
      .nav-link {
        font-size: 0.9375rem;
        padding: 0.625rem 1rem;
        margin: 0 0.25rem;
      }
      
      .btn-primary, .btn-secondary {
        padding: 0.625rem 1.25rem;
        font-size: 0.9375rem;
      }
      
      .mobile-hidden {
        display: block !important;
      }
    }
    
    /* Tablet Landscape/Small Desktop: 1024px - 1279px */
    @media (min-width: 1024px) and (max-width: 1279px) {
      body {
        <?php if (!$is_dashboard): ?>
        padding-top: 64px;
        <?php endif; ?>
      }
      
      .header-elite, .dashboard-header {
        padding: 0.5rem 0;
      }
      
      .header-elite .py-4, .dashboard-header .py-4 {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
      }
      
      .nav-link {
        font-size: 0.9375rem;
        padding: 0.625rem 1.125rem;
        margin: 0 0.25rem;
      }
      
      .btn-primary, .btn-secondary {
        padding: 0.625rem 1.5rem;
        font-size: 0.9375rem;
      }
      
      .desktop-hidden {
        display: none !important;
      }
    }
    
    /* Medium Desktop: 1280px - 1439px */
    @media (min-width: 1280px) and (max-width: 1439px) {
      body {
        <?php if (!$is_dashboard): ?>
        padding-top: 66px;
        <?php endif; ?>
      }
      
      .header-elite, .dashboard-header {
        padding: 0.5rem 0;
      }
      
      .header-elite .py-4, .dashboard-header .py-4 {
        padding-top: 1.125rem !important;
        padding-bottom: 1.125rem !important;
      }
      
      .nav-link {
        font-size: 1rem;
        padding: 0.75rem 1.25rem;
        margin: 0 0.375rem;
      }
      
      .btn-primary, .btn-secondary {
        padding: 0.75rem 1.75rem;
        font-size: 1rem;
      }
    }
    
    /* Large Desktop: 1440px+ */
    @media (min-width: 1440px) {
      body {
        <?php if (!$is_dashboard): ?>
        padding-top: 68px;
        <?php endif; ?>
      }
      
      .header-elite, .dashboard-header {
        padding: 0.5rem 0;
      }
      
      .header-elite .py-4, .dashboard-header .py-4 {
        padding-top: 1.125rem !important;
        padding-bottom: 1.125rem !important;
      }
      
      .nav-link {
        font-size: 1rem;
        padding: 0.75rem 1.5rem;
        margin: 0 0.5rem;
      }
      
      .btn-primary, .btn-secondary {
        padding: 0.75rem 2rem;
        font-size: 1rem;
      }
      
      .container {
        max-width: 1400px;
      }
    }
    
    /* Touch Device Optimizations */
    @media (hover: none) and (pointer: coarse) {
      .nav-link, .btn-primary, .btn-secondary {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      
      .nav-link:hover::before {
        width: 0;
      }
      
      .nav-link:active::before {
        width: 100%;
      }
    }
    
    /* Progressive Enhancement and Touch Optimizations */
    .touch-device .nav-link:hover::before {
      width: 0;
    }
    
    .touch-device .nav-link:active::before,
    .touch-device .nav-link.touch-active::before {
      width: 100%;
    }
    
    .touch-active {
      transform: scale(0.98);
      opacity: 0.8;
    }
    
    /* Skip link for accessibility */
    .skip-link {
      position: absolute;
      top: -40px;
      left: 6px;
      background: #000;
      color: #fff;
      padding: 8px 16px;
      text-decoration: none;
      border-radius: 4px;
      z-index: 1000;
      transition: top 0.2s;
    }
    
    .skip-link:focus {
      top: 6px;
    }
    
    /* Focus visible indicators */
    [data-focus-visible="true"] {
      outline: 2px solid #3b82f6;
      outline-offset: 2px;
    }
    
    /* Smooth scroll behavior */
    @media (prefers-reduced-motion: no-preference) {
      html {
        scroll-behavior: smooth;
      }
      
      .animate-in {
        animation: fadeInUp 0.6s ease-out forwards;
      }
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Reduced motion preferences */
    @media (prefers-reduced-motion: reduce) {
      *,
      *::before,
      *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
      .header-elite {
        background: rgba(17, 24, 39, 0.95);
        border-bottom-color: rgba(55, 65, 81, 0.3);
      }
      
      .mobile-menu {
        background: rgba(17, 24, 39, 0.95);
        border-color: rgba(55, 65, 81, 0.3);
      }
      
      .mobile-nav-item {
        color: #f9fafb;
      }
      
      .mobile-nav-item:hover {
        background: rgba(59, 130, 246, 0.2);
      }
    }
    
    /* High contrast mode support */
    @media (prefers-contrast: high) {
      .nav-link::before {
        height: 3px;
      }
      
      .mobile-nav-item {
        border: 1px solid transparent;
      }
      
      .mobile-nav-item:focus {
        border-color: currentColor;
      }
    }
    
    /* Custom scrollbar for mobile menu */
    .mobile-menu-container::-webkit-scrollbar {
      width: 4px;
    }
    
    .mobile-menu-container::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.1);
    }
    
    .mobile-menu-container::-webkit-scrollbar-thumb {
      background: rgba(59, 130, 246, 0.3);
      border-radius: 2px;
    }
    
    .mobile-menu-container::-webkit-scrollbar-thumb:hover {
      background: rgba(59, 130, 246, 0.5);
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php if ($is_dashboard): ?>
  <!-- Dashboard Header - Light Compact Theme -->
  <header class="dashboard-header sticky top-0 z-50">
    <div class="container mx-auto px-4">
      <div class="flex justify-between items-center py-3">
        <!-- Logo -->
        <div class="site-logo" style="display: flex; align-items: center;">
          <a href="<?php echo $home_url; ?>" style="display: flex; align-items: center; text-decoration: none;">
            <img src="<?php echo htmlspecialchars($logo_src, ENT_QUOTES, 'UTF-8'); ?>" alt="Site Logo" class="logo-image header-logo">
            <span style=" font-size: 2rem; font-weight: 400; font-family: 'momo-signature-regular'; color: #194bb6ff;">MYCAR</span>
          </a>
        </div>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center space-x-2 lg:space-x-4">
          <a href="<?php echo $home_url; ?>" class="nav-link text-white hover:text-blue-200 text-sm lg:text-base px-2 lg:px-3 py-1.5 rounded-lg hover:bg-white hover:bg-opacity-10 transition-all duration-200 flex items-center gap-2" title="Ana Sayfa" aria-label="Ana Sayfa">
            <i class="fas fa-home text-blue-300 text-xs lg:text-sm"></i>
            <span>Ana Sayfa</span>
          </a>
          <a href="<?php echo $about_url; ?>" class="nav-link text-white hover:text-blue-200 text-sm lg:text-base px-2 lg:px-3 py-1.5 rounded-lg hover:bg-white hover:bg-opacity-10 transition-all duration-200 flex items-center gap-2" title="Hakkımızda" aria-label="Hakkımızda">
            <i class="fas fa-info-circle text-green-300 text-xs lg:text-sm"></i>
            <span>Hakkımızda</span>
          </a>
          <a href="<?php echo $contact_url; ?>" class="nav-link text-white hover:text-blue-200 text-sm lg:text-base px-2 lg:px-3 py-1.5 rounded-lg hover:bg-white hover:bg-opacity-10 transition-all duration-200 flex items-center gap-2" title="İletişim" aria-label="İletişim">
            <i class="fas fa-envelope text-red-300 text-xs lg:text-sm"></i>
            <span>İletişim</span>
          </a>
        </nav>

        <!-- User Info & Actions -->
        <div class="flex items-center space-x-2">
          <?php if ($is_logged_in): ?>
            <!-- User Profile Dropdown -->
            <div class="relative group">
              <button class="flex items-center space-x-1.5 hover:bg-white hover:bg-opacity-10 px-2 py-1.5 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50" aria-expanded="false" aria-haspopup="true">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-white shadow-sm">
                  <i class="fas fa-user text-xs sm:text-sm"></i>
                </div>
                <span class="mobile-hidden text-sm font-medium text-white truncate max-w-24"><?php echo htmlspecialchars($user_name); ?></span>
                <i class="fas fa-chevron-down text-xs text-white mobile-hidden transition-transform duration-200 group-hover:rotate-180"></i>
              </button>
              
              <!-- Dropdown Menu -->
              <div class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 border border-gray-200 z-50">
                <div class="py-1.5">
                  <div class="px-3 py-2 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user_email); ?></p>
                  </div>
                  <a href="<?php echo $dashboard_url; ?>" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200" title="Dashboard" aria-label="Dashboard">
                    <i class="fas fa-tachometer-alt mr-2 text-blue-500"></i>Gösterge Paneli
                  </a>
                  <a href="<?php echo $logout_url; ?>" class="block px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200" title="Çıkış Yap" aria-label="Çıkış Yap">
                    <i class="fas fa-sign-out-alt mr-2 text-red-500"></i>Çıkış Yap
                  </a>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- Mobile Menu Button -->
          <button onclick="toggleMobileMenu()" class="mobile-menu-btn md:hidden text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 rounded-lg p-1.5" aria-label="Menüyü Aç/Kapat" aria-expanded="false">
            <svg class="hamburger-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg class="close-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenuOverlay" class="mobile-menu-overlay hidden"></div>
    <div id="mobileMenu" class="md:hidden hidden mobile-menu mobile-menu-container bg-gradient-to-b from-blue-600 to-blue-700 border-t border-white border-opacity-20 shadow-2xl relative z-50">
      <div class="container mx-auto px-4 py-5">
        <nav class="space-y-2">
          <a href="<?php echo $home_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-white hover:bg-opacity-20 font-medium rounded-xl transition-all duration-200">
            <i class="fas fa-home mr-3 text-blue-300 w-5 opacity-90"></i>
            <span>Ana Sayfa</span>
          </a>
          <a href="<?php echo $about_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-white hover:bg-opacity-20 font-medium rounded-xl transition-all duration-200">
            <i class="fas fa-info-circle mr-3 text-green-300 w-5 opacity-90"></i>
            <span>Hakkımızda</span>
          </a>
          <a href="<?php echo $contact_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-white hover:bg-opacity-20 font-medium rounded-xl transition-all duration-200">
            <i class="fas fa-envelope mr-3 text-red-300 w-5 opacity-90"></i>
            <span>İletişim</span>
          </a>
          <?php if ($is_logged_in): ?>
            <div class="border-t border-white border-opacity-30 pt-4 mt-4 space-y-2">
              <a href="<?php echo $dashboard_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-green-500 hover:bg-opacity-30 font-medium rounded-xl transition-all duration-200">
                <i class="fas fa-tachometer-alt mr-3 text-blue-300 w-5 opacity-90"></i>
                <span>Gösterge Paneli</span>
              </a>
              <a href="<?php echo $logout_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-red-500 hover:bg-opacity-30 font-medium rounded-xl transition-all duration-200">
                <i class="fas fa-sign-out-alt mr-3 text-red-300 w-5 opacity-90"></i>
                <span>Çıkış Yap</span>
              </a>
            </div>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>

<?php else: ?>
  <!-- Standard Website Header - Light Compact Theme -->
  <header class="header-elite fixed top-0 w-full z-50">
    <div class="container mx-auto px-4">
      <div class="flex justify-between items-center py-3">
      <!-- Logo Section -->
      <div class="site-logo" style="display: flex; align-items: center;">
        <a href="<?php echo $home_url; ?>" style="display: flex; align-items: center; text-decoration: none;">
          <img src="<?php echo $base_url; ?>/backend/logo01.png" alt="Site Logo" class="logo-image">
          <span style="font-size: 1.5rem; font-weight: 400; font-family: 'Momo Signature', cursive; color: #019be5;">MYCAR</span>
        </a>
      </div>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center space-x-2 lg:space-x-4">
          <a href="<?php echo $home_url; ?>" class="nav-link <?php echo $current_page === 'home' ? 'active' : ''; ?> text-white font-medium text-sm lg:text-base px-2 lg:px-3 py-1.5 rounded-lg hover:bg-white hover:bg-opacity-10 hover:text-blue-200 transition-all duration-200 flex items-center gap-2">
            <i class="fas fa-home text-blue-300 text-xs lg:text-sm"></i>
            <span>Ana Sayfa</span>
          </a>
          <a href="<?php echo $about_url; ?>" class="nav-link <?php echo $current_page === 'about' ? 'active' : ''; ?> text-white font-medium text-sm lg:text-base px-2 lg:px-3 py-1.5 rounded-lg hover:bg-white hover:bg-opacity-10 hover:text-blue-200 transition-all duration-200 flex items-center gap-2">
            <i class="fas fa-info-circle text-green-300 text-xs lg:text-sm"></i>
            <span>Hakkımızda</span>
          </a>
          <a href="<?php echo $contact_url; ?>" class="nav-link <?php echo $current_page === 'contact' ? 'active' : ''; ?> text-white font-medium text-sm lg:text-base px-2 lg:px-3 py-1.5 rounded-lg hover:bg-white hover:bg-opacity-10 hover:text-blue-200 transition-all duration-200 flex items-center gap-2">
            <i class="fas fa-envelope text-red-300 text-xs lg:text-sm"></i>
            <span>İletişim</span>
          </a>
          <?php if ($is_logged_in): ?>
            <a href="<?php echo $dashboard_url; ?>" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?> text-white font-medium text-sm lg:text-base px-2 lg:px-3 py-1.5 rounded-lg hover:bg-white hover:bg-opacity-10 hover:text-blue-200 transition-all duration-200 flex items-center gap-2">
              <i class="fas fa-tachometer-alt text-purple-300 text-xs lg:text-sm"></i>
              <span>Gösterge Paneli</span>
            </a>
          <?php endif; ?>
        </nav>

        <!-- Auth Buttons / User Menu -->
        <div class="flex items-center space-x-2">
          <?php if ($is_logged_in): ?>
            <!-- Logged In User Menu -->
            <div class="relative group">
              <button class="flex items-center space-x-1.5 hover:bg-white hover:bg-opacity-10 px-2 py-1.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50" aria-expanded="false" aria-haspopup="true">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-white shadow-sm">
                  <i class="fas fa-user text-xs sm:text-sm"></i>
                </div>
                <span class="mobile-hidden font-medium text-white text-sm truncate max-w-24"><?php echo htmlspecialchars($user_name); ?></span>
                <i class="fas fa-chevron-down text-xs text-white mobile-hidden transition-transform duration-200 group-hover:rotate-180"></i>
              </button>
              
              <!-- Dropdown Menu -->
              <div class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 border border-gray-200 z-50">
                <div class="py-1.5">
                  <div class="px-3 py-2 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user_email); ?></p>
                  </div>
                  <a href="<?php echo $dashboard_url; ?>" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                    <i class="fas fa-tachometer-alt mr-2 text-blue-500 w-4"></i>Gösterge Paneli
                  </a>
                  <a href="<?php echo $logout_url; ?>" class="flex items-center px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200">
                    <i class="fas fa-sign-out-alt mr-2 text-red-500 w-4"></i>Çıkış Yap
                  </a>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- Mobile Menu Button -->
          <button onclick="toggleMobileMenu()" class="mobile-menu-btn md:hidden text-white hover:text-blue-200 hover:bg-white hover:bg-opacity-10 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 rounded-lg p-1.5" aria-label="Menüyü Aç/Kapat" aria-expanded="false">
            <svg class="hamburger-icon w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg class="close-icon w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenuOverlay" class="mobile-menu-overlay hidden"></div>
    <div id="mobileMenu" class="md:hidden hidden mobile-menu mobile-menu-container bg-gradient-to-b from-blue-600 to-blue-700 border-t border-white border-opacity-20 shadow-2xl relative z-50">
      <div class="container mx-auto px-4 py-5">
        <nav class="space-y-2">
          <a href="<?php echo $home_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-white hover:bg-opacity-20 font-medium rounded-xl transition-all duration-200">
            <i class="fas fa-home mr-3 text-blue-300 w-5 opacity-90"></i>
            <span>Ana Sayfa</span>
          </a>
          <a href="<?php echo $about_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-white hover:bg-opacity-20 font-medium rounded-xl transition-all duration-200">
            <i class="fas fa-info-circle mr-3 text-green-300 w-5 opacity-90"></i>
            <span>Hakkımızda</span>
          </a>
          <a href="<?php echo $contact_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-white hover:bg-opacity-20 font-medium rounded-xl transition-all duration-200">
            <i class="fas fa-envelope mr-3 text-red-300 w-5 opacity-90"></i>
            <span>İletişim</span>
          </a>
          
          <?php if ($is_logged_in): ?>
            <div class="border-t border-white border-opacity-30 pt-4 mt-4 space-y-2">
              <a href="<?php echo $dashboard_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-green-500 hover:bg-opacity-30 font-medium rounded-xl transition-all duration-200">
                <i class="fas fa-tachometer-alt mr-3 text-purple-300 w-5 opacity-90"></i>
                <span>Gösterge Paneli</span>
              </a>
              <a href="<?php echo $logout_url; ?>" class="mobile-nav-item block text-white hover:text-white hover:bg-red-500 hover:bg-opacity-30 font-medium rounded-xl transition-all duration-200">
                <i class="fas fa-sign-out-alt mr-3 text-red-300 w-5 opacity-90"></i>
                <span>Çıkış Yap</span>
              </a>
            </div>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>
<?php endif; ?>

<!-- Inert polyfill & helper: ensures background content becomes inert when mobile menus/dialogs open -->
<script src="https://unpkg.com/wicg-inert@3.1.1/dist/inert.min.js"></script>
<script>
// Provides a global helper used by mobile menu code to toggle inert (with aria-hidden fallback)
window.setInertState = function(isInert) {
  try {
    var primary = document.querySelector('main') || document.getElementById('app') || document.querySelector('.container');
    var footer = document.querySelector('footer');

    if (typeof HTMLElement !== 'undefined' && 'inert' in HTMLElement.prototype) {
      if (primary) primary.inert = !!isInert;
      if (footer) footer.inert = !!isInert;
      return;
    }

    if (window.ShadyDOM || window.wicgInert || typeof window.__wicg_inert !== 'undefined') {
      if (primary) primary.inert = !!isInert;
      if (footer) footer.inert = !!isInert;
      return;
    }

    // Fallback: use aria-hidden as a last resort
    if (primary) {
      if (isInert) primary.setAttribute('aria-hidden', 'true'); else primary.removeAttribute('aria-hidden');
    }
    if (footer) {
      if (isInert) footer.setAttribute('aria-hidden', 'true'); else footer.removeAttribute('aria-hidden');
    }
  } catch (e) {
    console.warn('setInertState error', e);
  }
};
</script>

<script>
// Enhanced Universal Header JavaScript with Progressive Enhancement
document.addEventListener('DOMContentLoaded', function() {
  // Viewport and device detection
  <!-- Fixed touch device detection for mobile -->
  // Use robust touch detection: check for ontouchstart and navigator.maxTouchPoints existence
  const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints && navigator.maxTouchPoints > 0);
  const isMobile = window.innerWidth < 768;
  let lastScrollY = window.scrollY;
  let ticking = false;
  
  // Mobile menu elements
  const mobileMenuBtn = document.getElementById('mobileMenuBtn') || document.querySelector('[onclick="toggleMobileMenu()"]');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
  const hamburgerIcon = document.querySelector('.hamburger-icon');
  const closeIcon = document.querySelector('.close-icon');
  
  // Swipe gesture support for mobile menu
  let startX = 0;
  let startY = 0;
  let currentX = 0;
  let currentY = 0;
  let isSwipeGesture = false;
  
  // Enhanced mobile menu toggle function
  window.toggleMobileMenu = function() {
    if (!mobileMenu) return;
    
    const isHidden = mobileMenu.classList.contains('hidden');
    
    if (isHidden) {
      // Show menu
      mobileMenu.classList.remove('hidden');
      if (mobileMenuOverlay) mobileMenuOverlay.classList.remove('hidden');
      
      // Update button state
      if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', 'true');
      if (hamburgerIcon) hamburgerIcon.classList.add('active');
      if (closeIcon) closeIcon.classList.add('active');
      
      // Prevent body scroll
      document.body.style.overflow = 'hidden';
      // Make background inert for assistive tech
      try { if (typeof window.setInertState === 'function') window.setInertState(true); } catch (e) {}
      
      // Focus management
      setTimeout(() => {
        const firstLink = mobileMenu.querySelector('a');
        if (firstLink) firstLink.focus();
      }, 100);
      
    } else {
      // Hide menu with animation
      mobileMenu.classList.add('hiding');
      if (mobileMenuOverlay) mobileMenuOverlay.classList.add('hiding');
      
      setTimeout(() => {
        mobileMenu.classList.add('hidden');
        mobileMenu.classList.remove('hiding');
        if (mobileMenuOverlay) {
          mobileMenuOverlay.classList.add('hidden');
          mobileMenuOverlay.classList.remove('hiding');
        }
      }, 300);
      
      // Update button state
      if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', 'false');
      if (hamburgerIcon) hamburgerIcon.classList.remove('active');
      if (closeIcon) closeIcon.classList.remove('active');
      
      // Restore body scroll
      document.body.style.overflow = '';
      try { if (typeof window.setInertState === 'function') window.setInertState(false); } catch (e) {}
      
      // Return focus to button
      if (mobileMenuBtn) mobileMenuBtn.focus();
    }
  };
  
  // Close mobile menu when clicking outside or on overlay
  if (mobileMenuOverlay) {
    mobileMenuOverlay.addEventListener('click', function() {
      window.toggleMobileMenu();
    });
  }
  
  // Enhanced keyboard navigation
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && mobileMenu && !mobileMenu.classList.contains('hidden')) {
      window.toggleMobileMenu();
    }
    
    // Tab trapping in mobile menu
    if (event.key === 'Tab' && mobileMenu && !mobileMenu.classList.contains('hidden')) {
      const focusableElements = mobileMenu.querySelectorAll('a, button, [tabindex]:not([tabindex="-1"])');
      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];
      
      if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
      } else if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
      }
    }
  });
  
  // Touch gesture support for mobile menu
  if (isTouch && mobileMenu) {
    mobileMenu.addEventListener('touchstart', function(event) {
      startX = event.touches[0].clientX;
      startY = event.touches[0].clientY;
      isSwipeGesture = false;
    }, { passive: true });
    
    mobileMenu.addEventListener('touchmove', function(event) {
      if (!startX || !startY) return;
      
      currentX = event.touches[0].clientX;
      currentY = event.touches[0].clientY;
      
      const deltaX = startX - currentX;
      const deltaY = startY - currentY;
      
      // Detect horizontal swipe (left to close menu)
      if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
        isSwipeGesture = true;
        if (deltaX > 0) { // Swipe left
          window.toggleMobileMenu();
        }
      }
    }, { passive: true });
    
    mobileMenu.addEventListener('touchend', function() {
      startX = 0;
      startY = 0;
      currentX = 0;
      currentY = 0;
      isSwipeGesture = false;
    }, { passive: true });
  }
  
  // Smart header scroll behavior (hide/show on scroll)
  function updateHeader() {
    const header = document.querySelector('header');
    if (!header) return;
    
    const currentScrollY = window.scrollY;
    
    if (currentScrollY > lastScrollY && currentScrollY > 100) {
      // Scrolling down
      header.style.transform = 'translateY(-100%)';
    } else {
      // Scrolling up
      header.style.transform = 'translateY(0)';
    }
    
    lastScrollY = currentScrollY;
    ticking = false;
  }
  
  // Throttled scroll handler
  function handleScroll() {
    if (!ticking && window.innerWidth >= 768) { // Only on desktop
      requestAnimationFrame(updateHeader);
      ticking = true;
    }
  }
  
  window.addEventListener('scroll', handleScroll, { passive: true });
  
  // Responsive behavior on window resize
  function handleResize() {
    const newIsMobile = window.innerWidth < 768;
    
    // Close mobile menu when switching to desktop
    if (!newIsMobile && mobileMenu && !mobileMenu.classList.contains('hidden')) {
      window.toggleMobileMenu();
    }
    
    // Reset header transform on mobile
    const header = document.querySelector('header');
    if (newIsMobile && header) {
      header.style.transform = 'translateY(0)';
    }
    
    // Update viewport height for mobile browsers
    if (newIsMobile) {
      const vh = window.innerHeight * 0.01;
      document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
  }
  
  // Debounced resize handler
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(handleResize, 150);
  });
  
  // Touch device optimizations
  if (isTouch) {
    document.body.classList.add('touch-device');
    
    // Improve touch responsiveness
    const touchTargets = document.querySelectorAll('a, button, .nav-link');
    touchTargets.forEach(target => {
      target.addEventListener('touchstart', function() {
        this.classList.add('touch-active');
      }, { passive: true });
      
      target.addEventListener('touchend', function() {
        setTimeout(() => {
          this.classList.remove('touch-active');
        }, 150);
      }, { passive: true });
    });
  }
  
  // Accessibility enhancements
  function enhanceAccessibility() {
    // Add skip link if not present
    if (!document.querySelector('.skip-link')) {
      const skipLink = document.createElement('a');
      skipLink.href = '#main-content';
      skipLink.className = 'skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded z-50';
      skipLink.textContent = 'Ana içeriğe geç';
      document.body.insertBefore(skipLink, document.body.firstChild);
    }
    
    // Ensure all interactive elements have proper focus styles
    const interactiveElements = document.querySelectorAll('a, button, input, select, textarea');
    interactiveElements.forEach(element => {
      if (!element.classList.contains('focus-visible')) {
        element.addEventListener('focus', function() {
          this.setAttribute('data-focus-visible', 'true');
        });
        element.addEventListener('blur', function() {
          this.removeAttribute('data-focus-visible');
        });
      }
    });
  }
  
  // Initialize accessibility enhancements
  enhanceAccessibility();
  
  // Performance optimization: Intersection Observer for animations
  if ('IntersectionObserver' in window) {
    const animatedElements = document.querySelectorAll('.nav-link, .btn-primary, .btn-secondary');
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-in');
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '50px'
    });
    
    animatedElements.forEach(el => observer.observe(el));
  }
  
  // Initialize viewport height CSS custom property
  const vh = window.innerHeight * 0.01;
  document.documentElement.style.setProperty('--vh', `${vh}px`);
  
  console.log('🎉 Universal Header: Enhanced responsive functionality loaded successfully!');
});

// Legacy support for older toggle function calls
function toggleMobileMenu() {
  if (window.toggleMobileMenu) {
    window.toggleMobileMenu();
  }
}
</script>



