<?php
/**
 * Index Page Header Component for CarWash Website
 * Specialized header designed specifically for the homepage
 * Features modern design, full responsiveness, and elegant animations
 * 
 * Features:
 * - Logo on the left (clickable, links to homepage)
 * - Navigation links (Home, About, Contact)
 * - Call-to-action button (Get Started)
 * - Modern, clean, visually attractive design
 * - Elegant spacing and subtle hover effects
 * - Fully responsive (desktop, tablet, mobile)
 * - Mobile hamburger menu
 * 
 * Usage: 
 * <?php include 'backend/includes/index-header.php'; ?>
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect the base URL automatically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/carwash_project';

// Build navigation URLs
$home_url = $base_url . '/backend/index.php';
$about_url = $base_url . '/backend/about.php';
$contact_url = $base_url . '/backend/contact.php';
$signup_url = $base_url . '/backend/auth/Customer_Registration.php';
$login_url = $base_url . '/backend/auth/login.php';

// Dashboard URL resolution (role-aware)
$customer_dashboard = $base_url . '/backend/dashboard/Customer_Dashboard.php';
$carwash_dashboard = $base_url . '/backend/dashboard/Car_Wash_Dashboard.php';
$admin_dashboard = $base_url . '/backend/dashboard/admin_panel.php';
$dashboard_url = $customer_dashboard;
if ($is_logged_in) {
  $sessRole = $_SESSION['role'] ?? strtolower($_SESSION['user_type'] ?? '');
  if (class_exists(\App\Classes\Auth::class) && method_exists(\App\Classes\Auth::class, 'hasRole')) {
    try {
      if (\App\Classes\Auth::hasRole('carwash')) $dashboard_url = $carwash_dashboard;
      elseif (\App\Classes\Auth::hasRole('admin')) $dashboard_url = $admin_dashboard;
    } catch (Throwable $e) {
      if ($sessRole === 'carwash') $dashboard_url = $carwash_dashboard;
      elseif ($sessRole === 'admin') $dashboard_url = $admin_dashboard;
    }
  } else {
    if ($sessRole === 'carwash') $dashboard_url = $carwash_dashboard;
    elseif ($sessRole === 'admin') $dashboard_url = $admin_dashboard;
  }
}

// Set defaults
$page_title = isset($page_title) ? $page_title : 'CarWash - En Ä°yi Online AraÃ§ YÄ±kama Rezervasyon Platformu';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = $is_logged_in ? ($_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User') : '';
// Fetch user's profile image if logged in (legacy DB helper is available)
$profile_img = '/carwash_project/frontend/assets/img/default-user.png';
if ($is_logged_in) {
  // Try to include legacy DB helper which provides $pdo/$conn
  try {
    if (!function_exists('getDBConnection') && file_exists(__DIR__ . '/db.php')) {
      require_once __DIR__ . '/db.php';
    }

    $uid = intval($_SESSION['user_id']);

    // Prefer PDO if available
    if (isset($pdo) && $pdo instanceof PDO) {
      $stmt = $pdo->prepare("SELECT up.profile_image AS profile_img, u.profile_image AS profile_img2 FROM users u LEFT JOIN user_profiles up ON up.user_id = u.id WHERE u.id = :uid LIMIT 1");
      $stmt->execute([':uid' => $uid]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        if (!empty($row['profile_img'])) $profile_img = $row['profile_img'];
        elseif (!empty($row['profile_img2'])) $profile_img = $row['profile_img2'];
      }
    } elseif (isset($conn)) {
      $q = mysqli_query($conn, "SELECT up.profile_image AS profile_img, u.profile_image AS profile_img2 FROM users u LEFT JOIN user_profiles up ON up.user_id = u.id WHERE u.id = $uid LIMIT 1");
      if ($q && $r = mysqli_fetch_assoc($q)) {
        if (!empty($r['profile_img'])) $profile_img = $r['profile_img'];
        elseif (!empty($r['profile_img2'])) $profile_img = $r['profile_img2'];
      }
    }
  } catch (Exception $e) {
    // Ignore DB errors here; fall back to default image
    error_log('index-header: could not fetch profile image: ' . $e->getMessage());
  }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  
  <!-- Tailwind CSS (local build) -->
  <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <?php 
  // Include Universal CSS Styles for entire website
  include_once(__DIR__ . '/universal_styles.php');
  ?>
  
  <?php
  // Ensure the client-side CSRF helper is available for AJAX/fetch calls
  $csrf_js_path = $base_url . '/frontend/js/csrf-helper.js';
  echo "\n  <script defer src=\"{$csrf_js_path}\"></script>\n";
  ?>
  
  <style>
    /* Index Page Specific Header Styles */
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --primary-color: #667eea;
      --secondary-color: #764ba2;
      --shadow-elevation: 0 10px 30px rgba(0, 0, 0, 0.1);
      --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.15);
      --shadow-subtle: 0 4px 15px rgba(102, 126, 234, 0.2);
    }
    
    html {
      scroll-behavior: smooth;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      position: relative;
    }
    
    /* Prevent body scroll when dropdown is open */
    body.dropdown-open {
      overflow-x: hidden;
    }
    
    /* Elite Index Header Styling */
    .index-header {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      box-shadow: var(--shadow-elevation);
      border-bottom: 1px solid rgba(255, 255, 255, 0.3);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: visible !important;
    }
    
    .index-header.scrolled {
      background: rgba(255, 255, 255, 0.95);
      box-shadow: var(--shadow-hover);
    }
    
    /* Ensure header container allows dropdown overflow */
    .index-header .container {
      overflow: visible !important;
    }
    
    /* Logo Styling */
    .logo-container {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .logo-container:hover {
      transform: translateY(-2px);
    }
    
    .logo-icon {
      background: var(--primary-gradient);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: var(--shadow-subtle);
    }
    
    .logo-container:hover .logo-icon {
      transform: scale(1.1);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    
    .logo-text {
      background: var(--primary-gradient);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-weight: 800;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Inline logo image sizing used on homepage header (override to avoid header overlap) */
    .logo-image {
      width: 80px; /* fixed width to avoid layout overlap */
      height: auto;
      max-height: 64px;
      object-fit: contain;
      display: block;
    }

    .logo-text-inline {
      line-height: 1;
      display: inline-block;
      margin-left: 0.25rem;
      vertical-align: middle;
    }
    /* Ensure logo text container allows truncation and prevents flex overflow */
    .logo-text-container { min-width: 0; }

    /* Dropdown visible state when toggled by JS */
    .dropdown-menu.open {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      pointer-events: auto;
    }

    /* Allow dropdown item text to wrap and be fully readable */
    .dropdown-item { white-space: normal; }
    
    .logo-container:hover .logo-text {
      transform: scale(1.05);
    }
    
    /* Navigation Links */
    .nav-link {
      position: relative;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: 500;
      color: #374151;
      padding: 0.75rem 1rem;
      border-radius: 0.75rem;
    }
    
    .nav-link::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 3px;
      background: var(--primary-gradient);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      transform: translateX(-50%);
      border-radius: 2px;
    }
    
    .nav-link:hover::before {
      width: 80%;
    }
    
    .nav-link:hover {
      color: var(--primary-color);
      background: rgba(102, 126, 234, 0.05);
      transform: translateY(-2px);
    }
    
    .nav-link.active {
      color: var(--primary-color);
      background: rgba(102, 126, 234, 0.1);
    }
    
    .nav-link.active::before {
      width: 80%;
    }
    
    /* CTA Button */
    .cta-button {
      background: var(--primary-gradient);
      color: white;
      padding: 0.75rem 2rem;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: var(--shadow-subtle);
      border: none;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .cta-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
      background: linear-gradient(135deg, #5a6fd8 0%, #6b5b95 100%);
    }
    
    .cta-button:active {
      transform: translateY(-1px);
    }
    
    /* Secondary Button */
    .secondary-button {
      color: var(--primary-color);
      padding: 0.75rem 1.5rem;
      border: 2px solid var(--primary-color);
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: transparent;
    }
    
    .secondary-button:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-2px);
      box-shadow: var(--shadow-subtle);
    }
    
    /* Mobile Menu Button */
    .mobile-menu-button {
      background: var(--primary-gradient);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 12px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: var(--shadow-subtle);
    }
    
    .mobile-menu-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    
    .mobile-menu-button.active {
      background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    }
    
    /* Mobile Menu */
    .mobile-menu {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      box-shadow: var(--shadow-elevation);
      border-radius: 0 0 20px 20px;
      transform: translateY(-100%);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      opacity: 0;
      visibility: hidden;
      max-height: calc(100vh - 80px);
      overflow-y: auto;
    }
    
    .mobile-menu.active {
      transform: translateY(0);
      opacity: 1;
      visibility: visible;
    }
    
    /* Mobile Menu Backdrop */
    .mobile-menu-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      z-index: 30;
    }
    
    .mobile-menu-backdrop.active {
      opacity: 1;
      visibility: visible;
    }
    
    .mobile-nav-link {
      color: #374151;
      padding: 1rem 1.5rem;
      font-weight: 500;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 12px;
      margin: 0.25rem 0;
      text-decoration: none;
      display: block;
    }
    
    .mobile-nav-link:hover {
      background: rgba(102, 126, 234, 0.1);
      color: var(--primary-color);
      transform: translateX(10px);
    }
    
    /* User Menu */
    .user-menu {
      position: relative;
    }
    
    .user-menu-button {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem 1rem;
      border-radius: 50px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      background: rgba(102, 126, 234, 0.05);
      border: 2px solid transparent;
      cursor: pointer;
    }
    
    .user-menu-button:hover {
      background: rgba(102, 126, 234, 0.1);
      border-color: rgba(102, 126, 234, 0.2);
      transform: translateY(-2px);
    }
    
    .user-avatar {
      width: 2.5rem;
      height: 2.5rem;
      background: var(--primary-gradient);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      box-shadow: var(--shadow-subtle);
    }

    /* Header avatar image sizing for index header (overrides inline defaults) */
    #indexUserAvatar {
      width: 60px !important;
      height: 60px !important;
      border-radius: 50%;
      object-fit: cover;
      display: block;
    }

    #indexAvatarFallback {
      width: 60px;
      height: 60px;
      display: none;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }
    
    /* Dropdown Menu */
    .dropdown-menu {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 0.75rem;
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.08);
      border: 2px solid rgba(102, 126, 234, 0.1);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      pointer-events: none;
      z-index: 99999;
      min-width: 200px;
      backdrop-filter: blur(10px);
    }
    
    .user-menu:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      pointer-events: auto;
    }
    
    /* Ensure dropdown is always on top of everything */
    .user-menu {
      position: relative;
      z-index: 10000;
    }
    
    .dropdown-item {
      padding: 1rem 1.5rem;
      color: #1f2937;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.875rem;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 12px;
      margin: 0.25rem 0.5rem;
      font-size: 0.9375rem;
      font-weight: 600;
      position: relative;
    }
    
    .dropdown-item:hover {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.12) 0%, rgba(118, 75, 162, 0.12) 100%);
      color: var(--primary-color);
      transform: translateX(5px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    
    .dropdown-item i {
      width: 1.5rem;
      text-align: center;
      font-size: 1.125rem;
    }
    
    /* Dropdown border separator */
    .dropdown-menu .py-2 {
      padding: 0.5rem 0;
    }
    
    .dropdown-menu .border-b {
      border-bottom-width: 2px;
      border-color: rgba(229, 231, 235, 0.8);
      margin-bottom: 0.5rem;
      padding: 0.75rem 1.25rem;
    }
    
    .dropdown-menu .border-b p {
      font-weight: 700;
      color: #111827;
    }
    
    /* Animations */
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .animate-fade-in-down {
      animation: fadeInDown 0.6s ease-out forwards;
    }
    
    /* Responsive Design */
    @media (max-width: 480px) {
      .logo-text {
        font-size: 1.25rem;
        font-weight: 700;
      }
      
      .logo-icon {
        width: 2rem;
        height: 2rem;
      }
      
      .logo-icon i {
        font-size: 0.875rem;
      }
      
      .index-header {
        padding: 0.5rem 0;
      }
      
      .mobile-menu-button {
        padding: 8px;
        border-radius: 8px;
      }
      
      .mobile-nav-link {
        padding: 0.875rem 1rem;
        font-size: 0.9rem;
      }
      
      .cta-button {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        border-radius: 25px;
      }
      
      .user-avatar {
        width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
      }
    }

    /* Responsive adjustments for inline logo and header avatar */
    @media (max-width: 480px) {
      .logo-image {
        width: 100px;
        max-height: 48px;
      }
      #indexUserAvatar, #indexAvatarFallback {
        width: 40px !important;
        height: 40px !important;
      }
    }

    @media (min-width: 481px) and (max-width: 1024px) {
      .logo-image {
        width: 120px;
        max-height: 56px;
      }
      #indexUserAvatar, #indexAvatarFallback {
        width: 48px !important;
        height: 48px !important;
      }
    }
    
    @media (min-width: 481px) and (max-width: 640px) {
      .logo-text {
        font-size: 1.375rem;
      }
      
      .logo-icon {
        width: 2.25rem;
        height: 2.25rem;
      }
      
      .mobile-nav-link {
        padding: 1rem 1.25rem;
        font-size: 0.95rem;
      }
      
      .cta-button {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
      }
    }
    
    @media (min-width: 641px) and (max-width: 768px) {
      .logo-text {
        font-size: 1.5rem;
      }
      
      .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
      }
      
      .cta-button {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
      }
      
      .secondary-button {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
      }
    }
    
    @media (min-width: 769px) and (max-width: 1024px) {
      .nav-link {
        padding: 0.625rem 0.875rem;
        font-size: 0.9rem;
        gap: 1rem;
      }
      
      .cta-button {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
      }
      
      .secondary-button {
        padding: 0.75rem 1.25rem;
        font-size: 0.9rem;
      }
      
      .user-menu-button {
        padding: 0.375rem 0.75rem;
      }
      
      .dropdown-menu {
        min-width: 12rem;
      }
    }

    @media (max-width: 1024px) {
      .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
      }
      
      .cta-button {
        padding: 0.625rem 1.5rem;
        font-size: 0.9rem;
      }
    }

    @media (max-width: 768px) {
      .logo-text {
        font-size: 1.5rem;
      }
      
      .logo-icon {
        width: 2.5rem;
        height: 2.5rem;
      }
      
      .index-header {
        backdrop-filter: blur(15px);
      }
      
      .mobile-menu {
        backdrop-filter: blur(15px);
      }
    }
    
    @media (min-width: 1025px) and (max-width: 1280px) {
      .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
      }
      
      .cta-button,
      .secondary-button {
        padding: 0.75rem 1.75rem;
        font-size: 0.95rem;
      }
    }
    
    @media (min-width: 1281px) {
      .nav-link {
        padding: 0.875rem 1.25rem;
        font-size: 1rem;
      }
      
      .cta-button,
      .secondary-button {
        padding: 0.875rem 2rem;
        font-size: 1rem;
      }
      
      .logo-text {
        font-size: 2rem;
      }
      
      .logo-icon {
        width: 22rem;
        height: 3rem;
      }
     
    }
    
    /* Touch device optimizations */
    @media (hover: none) and (pointer: coarse) {
      .nav-link:hover,
      .mobile-nav-link:hover {
        transform: none;
      }
      
      .nav-link:active,
      .mobile-nav-link:active {
        transform: scale(0.98);
        background: rgba(102, 126, 234, 0.15);
      }
      
      .cta-button:hover,
      .secondary-button:hover {
        transform: none;
      }
      
      .cta-button:active,
      .secondary-button:active {
        transform: scale(0.96);
      }
      
      .mobile-menu-button:hover {
        transform: none;
      }
      
      .mobile-menu-button:active {
        transform: scale(0.95);
      }
    }
    
    /* High DPI displays */
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
      .logo-icon {
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
      }
      
      .index-header {
        border-bottom: 0.5px solid rgba(255, 255, 255, 0.3);
      }
    }

    /* Print Styles */
    @media print {
      .mobile-menu-button,
      .mobile-menu {
        display: none !important;
      }
      
      .index-header {
        position: static !important;
        box-shadow: none !important;
        background: white !important;
        border-bottom: 2px solid #000 !important;
      }
      
      .cta-button,
      .secondary-button {
        border: 2px solid #000 !important;
        background: white !important;
        color: #000 !important;
      }
    }
    /* -------------------------
       Additional layout tweaks for index page
       - Only adjust alignment/spacing/positioning (no color/font changes)
       - Targets: hero (#home), services (#services), and hero-gradient info blocks
       ------------------------- */

    /* Ensure hero columns align vertically and buttons align with text */
    #home .container > .grid {
      align-items: center; /* keep text vertically centered with image */
    }

    /* Make hero image and text area center vertically and respect available space */
    @media (min-width: 1024px) {
      #home .container > .grid > div {
        display: flex;
        flex-direction: column;
        justify-content: center;
      }

      /* Ensure the image block doesn't overflow and is centered in its column */
      #home .container > .grid > div.animate-slide-in {
        align-items: center;
      }

      /* Slightly tighten the button group spacing on desktop */
      #home .container .flex.flex-col.sm\:flex-row,
      #home .container .flex.flex-col.sm\:flex-row > * {
        gap: 0.9rem !important;
      }
    }

    /* SERVICES: lay out items in a single centered row on desktop and place icons on the right */
    @media (min-width: 1024px) {
      #services .grid {
        display: flex !important;
        flex-wrap: nowrap;
        gap: 1.5rem;
        justify-content: center;
        align-items: stretch;
      }

      /* Give each service a stable width so spacing is equal */
      #services .grid > div {
        flex: 0 0 320px;
        max-width: 320px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.75rem;
        align-items: center;
      }

      /* Move the first child (icon container) to the right column and let text flow in the left column */
      #services .grid > div > :first-child {
        grid-column: 2;
        grid-row: 1 / 4;
        justify-self: center;
        margin: 0;
      }

      /* Ensure heading, description and price occupy the left column */
      #services .grid > div > h3,
      #services .grid > div > p,
      #services .grid > div > div.text-price,
      #services .grid > div > .text-xl {
        grid-column: 1;
      }

      /* Slight vertical nudge to visually center the first service for balance */
      #services .grid > div:first-child {
        transform: translateY(8px);
      }
    }

    /* CARWASH INFO / TARGET blocks: place icons on the right and text on the left in a single row */
    @media (min-width: 1024px) {
      /* Targets sections that use hero-gradient and contain small icon+text blocks */
      .hero-gradient .grid.grid-cols-2 .grid.grid-cols-2,
      .hero-gradient .grid.grid-cols-2 .grid-cols-2 {
        display: flex !important;
        gap: 1.25rem;
        align-items: center;
        justify-content: center;
      }

      /* For the small feature blocks that are currently column-oriented, switch to row with icon on right */
      .hero-gradient .grid.grid-cols-2 .flex.flex-col,
      .hero-gradient .grid.grid-cols-2 .flex.flex-col > * {
        display: flex !important;
        flex-direction: row-reverse !important;
        align-items: center !important;
        gap: 0.9rem !important;
        text-align: left !important;
      }

      .hero-gradient .grid.grid-cols-2 .flex.flex-col i {
        font-size: 1.6rem !important;
      }
    }

    /* Mobile: ensure stacking remains natural and readable (no layout overrides below 1024px) */
    @media (max-width: 1023px) {
      #services .grid > div { display: block; grid-template-columns: none; flex: 1 1 auto; transform: none; }
      #services .grid > div > :first-child { grid-column: auto; grid-row: auto; justify-self: auto; }
      .hero-gradient .grid.grid-cols-2 .flex.flex-col { flex-direction: column !important; text-align: center !important; }
      #home .container > .grid > div { display: block; }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Index Page Header -->
<header class="index-header fixed top-0 left-0 right-0 z-50 animate-fade-in-down">
  <div class="container mx-auto px-3 sm:px-4 md:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16 sm:h-18 md:h-20">
      
      <!-- Logo Section -->
      <div class="site-logo" style="display: flex; align-items: center; gap: 0.5rem; min-width:0;">
        <a href="<?php echo $home_url; ?>" style="display: flex; align-items: center; text-decoration: none; min-width:0;" class="flex items-center">
          <!-- Use same logo as dashboard headers to ensure consistent branding -->
          <img src="<?php echo htmlspecialchars($logo_src ?? ($base_url . '/backend/logo01.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Site Logo" class="logo-image flex-shrink-0 header-logo">
          <div class="logo-text-container min-w-0 ml-2">
            <span class="logo-text-inline block truncate" style="font-size: 1.5rem; font-weight: 400; font-family: 'Momo Signature', cursive; color: #019be5;">MYCAR</span>
          </div>
        </a>
      </div>

      <!-- Desktop Navigation -->
      <nav class="hidden md:flex lg:flex items-center gap-1 md:gap-2 lg:gap-3 xl:gap-4">
        <a href="#home" class="nav-link active">
          <i class="fas fa-home mr-1 md:mr-2"></i>
          <span class="hidden lg:inline">Ana Sayfa</span>
          <span class="lg:hidden">Ana</span>
        </a>
        <a href="#services" class="nav-link">
          <i class="fas fa-cogs mr-1 md:mr-2"></i>
          <span class="hidden lg:inline">Hizmetlerimiz</span>
          <span class="lg:hidden">Hizmet</span>
        </a>
        <a href="#about" class="nav-link">
          <i class="fas fa-info-circle mr-1 md:mr-2"></i>
          <span class="hidden lg:inline">HakkÄ±mÄ±zda</span>
          <span class="lg:hidden">HakkÄ±nda</span>
        </a>
        <a href="#contact" class="nav-link">
          <i class="fas fa-envelope mr-1 md:mr-2"></i>
          <span class="hidden lg:inline">Ä°letiÅŸim</span>
          <span class="lg:hidden">Ä°letiÅŸim</span>
        </a>
      </nav>

      <!-- Desktop Action Buttons -->
      <div class="hidden md:flex lg:flex items-center gap-2 md:gap-3 lg:gap-4">
        <?php if ($is_logged_in): ?>
          <!-- Logged In User Menu -->
          <div class="user-menu relative">
            <button class="user-menu-button" id="indexUserMenuBtn">
              <div class="user-avatar" style="position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center;">
                <img id="indexUserAvatar" src="<?php echo htmlspecialchars($profile_img); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" style="width:2.5rem;height:2.5rem;border-radius:50%;object-fit:cover;display:block;" onerror="this.style.display='none'; document.getElementById('indexAvatarFallback') && (document.getElementById('indexAvatarFallback').style.display='flex');">
                <div id="indexAvatarFallback" style="display: none; width:2.5rem; height:2.5rem; align-items:center; justify-content:center; border-radius:50%; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; font-weight:700;">
                  <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
              </div>
              <div class="hidden lg:block xl:block">
                <p class="text-xs sm:text-sm font-medium text-gray-700 truncate max-w-24 lg:max-w-32"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="text-xs text-gray-500">HoÅŸ geldiniz</p>
              </div>
              <i class="fas fa-chevron-down text-gray-400 hidden lg:block xl:block text-xs"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div id="indexDropdownMenu" class="dropdown-menu absolute right-0 mt-2 w-40 md:w-44 lg:w-48" role="menu" aria-labelledby="indexUserMenuBtn" aria-hidden="true">
              <div class="py-2">
                <div class="px-3 md:px-4 py-2 border-b border-gray-100">
                  <p class="text-xs md:text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($user_name); ?></p>
                  <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                </div>
                <a href="<?php echo $dashboard_url; ?>" class="dropdown-item">
                  <i class="fas fa-tachometer-alt text-blue-600 text-xs md:text-sm"></i>
                  <span class="text-xs md:text-sm">Dashboard</span>
                </a>
                <a href="<?php echo $base_url; ?>/backend/auth/logout.php" class="dropdown-item text-red-600">
                  <i class="fas fa-sign-out-alt text-xs md:text-sm"></i>
                  <span class="text-xs md:text-sm">Ã‡Ä±kÄ±ÅŸ Yap</span>
                </a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- Auth Buttons for Non-logged Users -->
          <a href="<?php echo $login_url; ?>" class="secondary-button text-xs sm:text-sm md:text-base">
            <i class="fas fa-sign-in-alt text-xs sm:text-sm"></i>
            <span class="hidden md:inline">GiriÅŸ</span>
            <span class="md:hidden">GiriÅŸ</span>
          </a>
          <a href="<?php echo $signup_url; ?>" class="cta-button text-xs sm:text-sm md:text-base">
            <i class="fas fa-rocket text-xs sm:text-sm"></i>
            <span class="hidden sm:inline">BaÅŸla</span>
            <span class="sm:hidden">+</span>
          </a>
        <?php endif; ?>
      </div>

      <!-- Mobile Menu Button -->
      <button onclick="toggleMobileMenu()" class="mobile-menu-button md:hidden" id="mobileMenuBtn" aria-label="MenÃ¼yÃ¼ AÃ§/Kapat" aria-expanded="false">
        <i class="fas fa-bars text-sm sm:text-base" id="menuIcon"></i>
      </button>
    </div>

    <!-- Mobile Menu Backdrop -->
    <div id="mobileMenuBackdrop" class="mobile-menu-backdrop" onclick="closeMobileMenu()"></div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="mobile-menu md:hidden absolute top-full left-0 right-0 z-40">
      <div class="container mx-auto px-4 py-4 sm:py-6">
        <nav class="space-y-1 sm:space-y-2">
          <a href="#home" class="mobile-nav-link" onclick="closeMobileMenu()">
            <i class="fas fa-home mr-3 flex-shrink-0"></i>
            <span>Ana Sayfa</span>
          </a>
          <a href="#services" class="mobile-nav-link" onclick="closeMobileMenu()">
            <i class="fas fa-cogs mr-3 flex-shrink-0"></i>
            <span>Hizmetlerimiz</span>
          </a>
          <a href="#about" class="mobile-nav-link" onclick="closeMobileMenu()">
            <i class="fas fa-info-circle mr-3 flex-shrink-0"></i>
            <span>HakkÄ±mÄ±zda</span>
          </a>
          <a href="#contact" class="mobile-nav-link" onclick="closeMobileMenu()">
            <i class="fas fa-envelope mr-3 flex-shrink-0"></i>
            <span>Ä°letiÅŸim</span>
          </a>
          
          <?php if ($is_logged_in): ?>
            <div class="border-t border-gray-200 pt-3 sm:pt-4 mt-3 sm:mt-4">
              <div class="flex items-center gap-3 mb-3 sm:mb-4 px-3 sm:px-4">
                <div class="user-avatar flex-shrink-0">
                  <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="font-medium text-gray-900 text-sm sm:text-base truncate"><?php echo htmlspecialchars($user_name); ?></p>
                  <p class="text-xs sm:text-sm text-gray-500">HoÅŸ geldiniz</p>
                </div>
              </div>
              <a href="<?php echo $dashboard_url; ?>" class="mobile-nav-link">
                <i class="fas fa-tachometer-alt mr-3 flex-shrink-0"></i>
                <span>Dashboard</span>
              </a>
              <a href="<?php echo $base_url; ?>/backend/auth/logout.php" class="mobile-nav-link text-red-600">
                <i class="fas fa-sign-out-alt mr-3 flex-shrink-0"></i>
                <span>Ã‡Ä±kÄ±ÅŸ Yap</span>
              </a>
            </div>
          <?php else: ?>
            <div class="border-t border-gray-200 pt-3 sm:pt-4 mt-3 sm:mt-4 space-y-2 sm:space-y-3">
              <a href="<?php echo $login_url; ?>" class="block w-full text-center py-2.5 sm:py-3 border-2 border-blue-600 text-blue-600 rounded-lg sm:rounded-xl font-medium hover:bg-blue-50 transition-colors text-sm sm:text-base">
                <i class="fas fa-sign-in-alt mr-2"></i>GiriÅŸ Yap
              </a>
              <a href="<?php echo $signup_url; ?>" class="cta-button w-full justify-center text-sm sm:text-base">
                <i class="fas fa-rocket"></i>BaÅŸla
              </a>
            </div>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </div>
</header>

<script>
  // Global variables for responsive behavior
  let isMobile = window.innerWidth < 768;
  let isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
  let isDesktop = window.innerWidth >= 1024;
  let touchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  
  // Mobile menu toggle functionality
  function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const btn = document.getElementById('mobileMenuBtn');
    const icon = document.getElementById('menuIcon');
    const backdrop = document.getElementById('mobileMenuBackdrop');
    
    if (!menu || !btn || !icon) return;
    
    const isActive = menu.classList.contains('active');
    
    menu.classList.toggle('active');
    btn.classList.toggle('active');
    if (backdrop) backdrop.classList.toggle('active');
    btn.setAttribute('aria-expanded', !isActive);
    
    if (!isActive) {
      icon.className = 'fas fa-times text-sm sm:text-base';
      document.body.style.overflow = 'hidden';
      
      // Add focus trap for accessibility
      const focusableElements = menu.querySelectorAll('a, button');
      if (focusableElements.length > 0) {
        setTimeout(() => focusableElements[0].focus(), 100);
      }
    } else {
      icon.className = 'fas fa-bars text-sm sm:text-base';
      document.body.style.overflow = '';
      btn.focus();
    }
  }
  
  // Function to close mobile menu
  function closeMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const btn = document.getElementById('mobileMenuBtn');
    const icon = document.getElementById('menuIcon');
    const backdrop = document.getElementById('mobileMenuBackdrop');
    
    if (!menu || !btn || !icon) return;
    
    if (menu.classList.contains('active')) {
      menu.classList.remove('active');
      btn.classList.remove('active');
      if (backdrop) backdrop.classList.remove('active');
      btn.setAttribute('aria-expanded', 'false');
      icon.className = 'fas fa-bars text-sm sm:text-base';
      document.body.style.overflow = '';
    }
  }
  
  // Make closeMobileMenu available globally
  window.closeMobileMenu = closeMobileMenu;

  // Header scroll effect with responsive adjustments
  function handleHeaderScroll() {
    const header = document.querySelector('.index-header');
    const scrollThreshold = isMobile ? 30 : 50;
    
    if (window.scrollY > scrollThreshold) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  }

  // Responsive breakpoint detection
  function updateResponsiveVariables() {
    const prevMobile = isMobile;
    const prevTablet = isTablet;
    const prevDesktop = isDesktop;
    
    isMobile = window.innerWidth < 768;
    isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
    isDesktop = window.innerWidth >= 1024;
    
    // Close mobile menu on breakpoint change
    if ((prevMobile && !isMobile) || (prevTablet && !isTablet) || (prevDesktop && !isDesktop)) {
      const menu = document.getElementById('mobileMenu');
      const btn = document.getElementById('mobileMenuBtn');
      const icon = document.getElementById('menuIcon');
      
      if (menu && menu.classList.contains('active')) {
        menu.classList.remove('active');
        btn.classList.remove('active');
        btn.setAttribute('aria-expanded', 'false');
        icon.className = 'fas fa-bars text-sm sm:text-base';
        document.body.style.overflow = '';
      }
    }
  }

  // Enhanced scroll event listener
  window.addEventListener('scroll', handleHeaderScroll, { passive: true });

  // Close mobile menu when clicking outside
  document.addEventListener('click', function(event) {
    const menu = document.getElementById('mobileMenu');
    const btn = document.getElementById('mobileMenuBtn');
    
    if (menu && btn && !menu.contains(event.target) && !btn.contains(event.target) && menu.classList.contains('active')) {
      toggleMobileMenu();
    }
  });

  // Enhanced window resize handler
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
      updateResponsiveVariables();
    }, 150);
  });

  // Keyboard navigation for mobile menu
  document.addEventListener('keydown', function(event) {
    const menu = document.getElementById('mobileMenu');
    
    if (menu && menu.classList.contains('active')) {
      if (event.key === 'Escape') {
        toggleMobileMenu();
      } else if (event.key === 'Tab') {
        // Handle focus trap
        const focusableElements = menu.querySelectorAll('a, button');
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
    }
  });

  // Prevent scrolling on dropdown menu links
  document.querySelectorAll('.dropdown-menu a[href^="#"], .dropdown-item').forEach(link => {
    link.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      // Only prevent default if it's a hash link (not a real URL)
      if (href && href.startsWith('#') && href.length > 1) {
        const target = document.querySelector(href);
        if (!target) {
          // No target found, it's just a placeholder link
          e.preventDefault();
          console.log('Dropdown link clicked:', href);
        }
      }
    });
  });

  // Smooth scrolling for anchor links with responsive offset (excluding dropdown items)
  document.querySelectorAll('a[href^="#"]:not(.dropdown-item)').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      const target = document.querySelector(href);
      
      if (target) {
        e.preventDefault();
        
        // Calculate responsive offset for fixed header
        let headerOffset;
        if (isMobile) {
          headerOffset = 64; // 4rem
        } else if (isTablet) {
          headerOffset = 72; // 4.5rem
        } else {
          headerOffset = 80; // 5rem
        }
        
        const elementPosition = target.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
        
        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });
        
        // Update active navigation link
        updateActiveNavLink(href);
        
        // Close mobile menu if open
        const menu = document.getElementById('mobileMenu');
        if (menu && menu.classList.contains('active')) {
          toggleMobileMenu();
        }
      }
    });
  });

  // Update active navigation link
  function updateActiveNavLink(activeHref) {
    // Remove active class from all nav links
    document.querySelectorAll('.nav-link, .mobile-nav-link').forEach(link => {
      link.classList.remove('active');
    });
    
    // Add active class to current nav links
    document.querySelectorAll(`a[href="${activeHref}"]`).forEach(link => {
      if (link.classList.contains('nav-link') || link.classList.contains('mobile-nav-link')) {
        link.classList.add('active');
      }
    });
  }

  // Intersection Observer for automatic active section detection with responsive margins
  function createIntersectionObserver() {
    const observerOptions = {
      root: null,
      rootMargin: isMobile ? '-64px 0px -50% 0px' : '-80px 0px -50% 0px',
      threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const targetId = '#' + entry.target.id;
          updateActiveNavLink(targetId);
        }
      });
    }, observerOptions);

    // Observe all sections
    const sections = document.querySelectorAll('#home, #services, #about, #contact');
    sections.forEach(section => {
      observer.observe(section);
    });
    
    return observer;
  }

  // Touch gesture support for mobile menu
  let touchStartY = 0;
  let touchEndY = 0;

  if (touchDevice) {
    document.addEventListener('touchstart', function(event) {
      touchStartY = event.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', function(event) {
      touchEndY = event.changedTouches[0].screenY;
      
      const menu = document.getElementById('mobileMenu');
      if (menu && menu.classList.contains('active')) {
        // Swipe up to close menu
        if (touchStartY - touchEndY > 50) {
          toggleMobileMenu();
        }
      }
    }, { passive: true });
  }

  // Add loading states to buttons with responsive feedback
  document.querySelectorAll('.cta-button, .secondary-button').forEach(button => {
    button.addEventListener('click', function() {
      if (this.href && !this.href.includes('#')) {
        const originalContent = this.innerHTML;
        const spinner = '<i class="fas fa-spinner fa-spin mr-2"></i>';
        const loadingText = isMobile ? 'YÃ¼kleniyor...' : 'YÃ¼kleniyor...';
        
        this.innerHTML = spinner + loadingText;
        this.style.pointerEvents = 'none';
        this.style.opacity = '0.8';
        
        // Restore after a short delay if still on page
        setTimeout(() => {
          this.innerHTML = originalContent;
          this.style.pointerEvents = '';
          this.style.opacity = '';
        }, 3000);
      }
    });
  });

  // User menu dropdown behavior for touch devices
  if (touchDevice) {
    const userMenuButton = document.querySelector('.user-menu-button');
    const userMenu = document.querySelector('.user-menu');
    const indexDropdownMenuTouch = document.getElementById('indexDropdownMenu') || document.querySelector('.dropdown-menu');
    
    if (userMenuButton && userMenu) {
      userMenuButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        userMenu.classList.toggle('active');
        if (indexDropdownMenuTouch) {
          const isOpen = indexDropdownMenuTouch.classList.toggle('open');
          indexDropdownMenuTouch.dataset.clickOpen = isOpen ? 'true' : 'false';
          indexDropdownMenuTouch.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
          userMenuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
      });
      
      document.addEventListener('click', function(event) {
        if (!userMenu.contains(event.target)) {
          userMenu.classList.remove('active');
          if (indexDropdownMenuTouch) {
            indexDropdownMenuTouch.classList.remove('open');
            indexDropdownMenuTouch.dataset.clickOpen = 'false';
            indexDropdownMenuTouch.setAttribute('aria-hidden', 'true');
          }
        }
      });
    }
  } else {
    // For desktop devices: allow click to toggle the dropdown (in addition to hover)
    const userMenuButtonDesktop = document.getElementById('indexUserMenuBtn') || document.querySelector('.user-menu-button');
    const indexDropdown = document.getElementById('indexDropdownMenu') || document.querySelector('.dropdown-menu');

    if (userMenuButtonDesktop && indexDropdown) {
      // Ensure ARIA initial state
      userMenuButtonDesktop.setAttribute('aria-haspopup', 'true');
      userMenuButtonDesktop.setAttribute('aria-expanded', 'false');

      // Toggle function (marks click-open state so hover doesn't immediately close it)
      function toggleIndexDropdown(ev) {
        ev && ev.stopPropagation();
        const isOpen = indexDropdown.classList.contains('open');
        if (isOpen) {
          indexDropdown.classList.remove('open');
          indexDropdown.dataset.clickOpen = 'false';
          userMenuButtonDesktop.setAttribute('aria-expanded', 'false');
          indexDropdown.setAttribute('aria-hidden', 'true');
        } else {
          indexDropdown.classList.add('open');
          indexDropdown.dataset.clickOpen = 'true';
          userMenuButtonDesktop.setAttribute('aria-expanded', 'true');
          indexDropdown.setAttribute('aria-hidden', 'false');
        }
      }

      // Click toggles
      userMenuButtonDesktop.addEventListener('click', function(ev) {
        ev.preventDefault();
        toggleIndexDropdown(ev);
      });

      // Close when clicking outside
      document.addEventListener('click', function(ev) {
        if (!indexDropdown.contains(ev.target) && !userMenuButtonDesktop.contains(ev.target)) {
          if (indexDropdown.classList.contains('open')) {
            indexDropdown.classList.remove('open');
            indexDropdown.dataset.clickOpen = 'false';
            userMenuButtonDesktop.setAttribute('aria-expanded', 'false');
            indexDropdown.setAttribute('aria-hidden', 'true');
          }
        }
      });

      // Close on Escape key
      document.addEventListener('keydown', function(ev) {
        if (ev.key === 'Escape' && indexDropdown.classList.contains('open')) {
          indexDropdown.classList.remove('open');
          indexDropdown.dataset.clickOpen = 'false';
          userMenuButtonDesktop.setAttribute('aria-expanded', 'false');
          indexDropdown.setAttribute('aria-hidden', 'true');
          userMenuButtonDesktop.focus();
        }
      });

      // Also keep hover behavior consistent by adding/removing 'open' on enter/leave
      const userMenuRoot = userMenuButtonDesktop.closest('.user-menu');
      if (userMenuRoot) {
        userMenuRoot.addEventListener('mouseenter', function() {
          indexDropdown.classList.add('open');
          indexDropdown.setAttribute('aria-hidden', 'false');
          userMenuButtonDesktop.setAttribute('aria-expanded', 'true');
        });
        userMenuRoot.addEventListener('mouseleave', function() {
          // only close on hover-leave if it wasn't opened explicitly by click
          if (indexDropdown.dataset.clickOpen === 'true') return; // keep open when user clicked to open
          indexDropdown.classList.remove('open');
          indexDropdown.setAttribute('aria-hidden', 'true');
          userMenuButtonDesktop.setAttribute('aria-expanded', 'false');
        });
      }
    }
  }

  // Initialize on DOM content loaded
  document.addEventListener('DOMContentLoaded', function() {
    updateResponsiveVariables();
    createIntersectionObserver();
    
    // Add enhanced touch feedback for mobile
    if (touchDevice) {
      document.querySelectorAll('.nav-link, .mobile-nav-link, .cta-button, .secondary-button').forEach(element => {
        element.addEventListener('touchstart', function() {
          this.style.transform = 'scale(0.98)';
        }, { passive: true });
        
        element.addEventListener('touchend', function() {
          setTimeout(() => {
            this.style.transform = '';
          }, 150);
        }, { passive: true });
      });
    }
    
    console.log('CarWash Responsive Index Header loaded successfully!');
    console.log('Device type:', isMobile ? 'Mobile' : isTablet ? 'Tablet' : 'Desktop');
    console.log('Touch device:', touchDevice);
  });

  // Keep header avatar in sync with localStorage updates (set by dashboard on profile change)
  (function() {
    function updateHeaderAvatarFromStorage() {
      try {
        const imgUrl = localStorage.getItem('carwash_profile_image');
        if (!imgUrl) return;
        const img = document.getElementById('indexUserAvatar');
        const fallback = document.getElementById('indexAvatarFallback');
        if (img) {
          img.src = imgUrl;
          img.style.display = 'block';
          if (fallback) fallback.style.display = 'none';
        }
      } catch (e) {
        console.warn('Could not read profile image from localStorage', e);
      }
    }

    // Initial update on load (if localStorage has a more recent image)
    document.addEventListener('DOMContentLoaded', function() {
      updateHeaderAvatarFromStorage();
    });

    // Listen for storage events (cross-tab updates)
    window.addEventListener('storage', function(e) {
      if (e.key === 'carwash_profile_image' || e.key === 'carwash_profile_image_ts') {
        updateHeaderAvatarFromStorage();
      }
    });
  })();

  // Prevent zoom on double tap for better mobile UX
  if (touchDevice) {
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function (event) {
      const now = (new Date()).getTime();
      if (now - lastTouchEnd <= 300) {
        event.preventDefault();
      }
      lastTouchEnd = now;
    }, false);
  }

  // Performance optimization: Debounced scroll handler
  let scrollTimeout;
  let lastScrollY = window.scrollY;
  
  window.addEventListener('scroll', function() {
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(function() {
      if (Math.abs(window.scrollY - lastScrollY) > 5) {
        handleHeaderScroll();
        lastScrollY = window.scrollY;
      }
    }, 10);
  }, { passive: true });
</script>


