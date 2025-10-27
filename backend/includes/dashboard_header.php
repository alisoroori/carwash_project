<?php
/**
 * Universal Dashboard Header Component for CarWash System
 * Multi-purpose header that works for ALL dashboard types:
 * - Customer Dashboard
 * - Admin Dashboard  
 * - Car Wash Dashboard
 * 
 * Features:
 * - Automatic role detection and context-aware navigation
 * - Fully responsive design (mobile, tablet, desktop)
 * - Consistent styling matching website theme and footer
 * - Dynamic menu based on user role
 * - Professional gradient theme with white text
 * - Mobile hamburger menu with smooth animations
 * 
 * Usage:
 * $dashboard_type = 'customer'; // or 'admin' or 'carwash'
 * $page_title = 'Dashboard - CarWash';
 * $current_page = 'dashboard';
 * include $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/backend/includes/dashboard_header.php';
 * 
 * Optional variables to set before including:
 * - $dashboard_type: 'customer', 'admin', or 'carwash' (auto-detected if not set)
 * - $page_title: Custom page title
 * - $current_page: Current page identifier for active states
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check - ensure user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Detect the base URL automatically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/carwash_project';

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User';
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? 'customer';

// Auto-detect dashboard type if not set
if (!isset($dashboard_type)) {
    $dashboard_type = $user_role;
}

// Set defaults
$page_title = isset($page_title) ? $page_title : ucfirst($dashboard_type) . ' Dashboard - CarWash';
$current_page = isset($current_page) ? $current_page : 'dashboard';

// Build common URLs
$home_url = $base_url . '/backend/index.php';
$about_url = $base_url . '/backend/about.php';
$contact_url = $base_url . '/backend/contact.php';
$logout_url = $base_url . '/backend/includes/logout.php';

// Build role-specific dashboard URLs
$customer_dashboard_url = $base_url . '/backend/dashboard/Customer_Dashboard.php';
$admin_dashboard_url = $base_url . '/backend/dashboard/admin_panel.php';
$carwash_dashboard_url = $base_url . '/backend/dashboard/Car_Wash_Dashboard.php';

// Set current dashboard URL based on role
switch ($dashboard_type) {
    case 'admin':
        $current_dashboard_url = $admin_dashboard_url;
        $dashboard_icon = 'fa-user-shield';
        $dashboard_label = 'Admin Paneli';
        break;
    case 'carwash':
        $current_dashboard_url = $carwash_dashboard_url;
        $dashboard_icon = 'fa-car-wash';
        $dashboard_label = 'İşletme Paneli';
        break;
    case 'customer':
    default:
        $current_dashboard_url = $customer_dashboard_url;
        $dashboard_icon = 'fa-tachometer-alt';
        $dashboard_label = 'Müşteri Paneli';
        break;
}

// Navigation menu removed - header now displays only logo and user menu
$navigation_menu = array();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php 
    // Include Universal CSS Styles for entire website
    if (file_exists(__DIR__ . '/universal_styles.php')) {
        include_once(__DIR__ . '/universal_styles.php');
    }
    ?>
    
    <style>
        /* Dashboard Header Styles - Matching Footer Gray Theme */
        :root {
            --dashboard-primary: #1f2937;
            --dashboard-secondary: #111827;
            --dashboard-gradient: #1f2937;
            --shadow-elevation: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            background: #1f2937; /* Gray-800 to match footer */
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-elevation);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .dashboard-header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Logo Section */
        .dashboard-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .dashboard-logo:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }
        
        .logo-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); /* Blue to purple gradient like footer logo */
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }
        
        .dashboard-badge {
            font-size: 0.625rem;
            background: rgba(255, 255, 255, 0.25);
            color: white;
            padding: 0.15rem 0.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        /* Desktop Navigation */
        .dashboard-nav {
            display: none;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (min-width: 768px) {
            .dashboard-nav {
                display: flex;
            }
        }
        
        .nav-item {
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }
        
        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .nav-item i {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        /* User Menu */
        .user-menu {
            position: relative;
        }
        
        .user-menu-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
        }
        
        .user-menu-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .user-avatar {
            width: 2rem;
            height: 2rem;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .user-info {
            display: none;
            flex-direction: column;
            align-items: flex-start;
        }
        
        @media (min-width: 640px) {
            .user-info {
                display: flex;
            }
        }
        
        .user-name {
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.2;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-role {
            font-size: 0.688rem;
            opacity: 0.8;
            text-transform: capitalize;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            overflow: hidden;
            pointer-events: none;
            z-index: 1000;
        }
        
        .user-menu:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .dropdown-email {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }
        
        .dropdown-item:hover {
            background: #f3f4f6;
        }
        
        .dropdown-item i {
            width: 1.25rem;
            text-align: center;
            font-size: 0.875rem;
        }
        
        .dropdown-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 0.5rem 0;
        }
        
        .dropdown-logout {
            color: #dc2626;
        }
        
        .dropdown-logout:hover {
            background: #fef2f2;
        }
        
        /* Mobile Menu Button */
        .mobile-menu-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
        }
        
        @media (min-width: 768px) {
            .mobile-menu-button {
                display: none;
            }
        }
        
        .mobile-menu-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .mobile-menu-button:active {
            transform: scale(0.95);
        }
        
        /* Mobile Menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
        }
        
        .mobile-menu.active {
            display: block;
        }
        
        .mobile-menu-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }
        
        .mobile-menu-panel {
            position: absolute;
            top: 0;
            left: 0;
            width: 85%;
            max-width: 320px;
            height: 100%;
            background: white;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.2);
            animation: slideInLeft 0.3s ease;
            overflow-y: auto;
        }
        
        .mobile-menu-header {
            background: #1f2937; /* Gray-800 to match header */
            padding: 1.5rem 1rem;
            color: white;
        }
        
        .mobile-menu-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 2rem;
            height: 2rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 0.375rem;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .mobile-menu-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .mobile-menu-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .mobile-user-avatar {
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .mobile-menu-nav {
            padding: 1rem 0;
        }
        
        .mobile-nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .mobile-nav-item:hover {
            background: #f3f4f6;
            border-left-color: #3b82f6;
        }
        
        .mobile-nav-item.active {
            background: #eff6ff;
            border-left-color: #3b82f6;
            color: #3b82f6;
        }
        
        .mobile-nav-item i {
            width: 1.5rem;
            text-align: center;
            font-size: 1rem;
        }
        
        .mobile-nav-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 0.5rem 1rem;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInLeft {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 479px) {
            .dashboard-header-container {
                padding: 0 0.75rem;
            }
            
            .logo-icon {
                width: 2rem;
                height: 2rem;
            }
            
            .logo-text {
                font-size: 1.125rem;
            }
            
            .dashboard-badge {
                font-size: 0.563rem;
                padding: 0.125rem 0.375rem;
            }
        }
        
        @media (min-width: 1024px) {
            .nav-item {
                padding: 0.625rem 1.25rem;
                font-size: 0.938rem;
            }
        }
        
        /* Print styles */
        @media print {
            .mobile-menu-button,
            .user-menu,
            .dashboard-nav {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Dashboard Header -->
<header class="dashboard-header">
    <div class="dashboard-header-container">
        <div class="flex items-center justify-between py-3">
            
            <!-- Logo & Dashboard Type -->
            <div class="flex items-center gap-3">
                <a href="<?php echo $current_dashboard_url; ?>" class="dashboard-logo">
                    <div class="logo-icon">
                        <i class="fas <?php echo $dashboard_icon; ?> text-white text-lg"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="logo-text">CarWash</span>
                        <span class="dashboard-badge"><?php echo $dashboard_label; ?></span>
                    </div>
                </a>
            </div>
            
            <!-- User Menu & Mobile Button -->
            <div class="flex items-center gap-2">
                
                <!-- Custom Header Content (if provided) -->
                <?php if (isset($custom_header_content) && !empty($custom_header_content)): ?>
                    <?php echo $custom_header_content; ?>
                <?php endif; ?>
                
                <!-- User Menu (Desktop) -->
                <div class="user-menu">
                    <button class="user-menu-button" aria-label="User menu">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                            <span class="user-role"><?php echo htmlspecialchars($dashboard_type); ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <!-- Dropdown -->
                    <div class="user-dropdown">
                        <div class="dropdown-header">
                            <div class="font-semibold text-gray-900 text-sm">
                                <?php echo htmlspecialchars($user_name); ?>
                            </div>
                            <div class="dropdown-email">
                                <?php echo htmlspecialchars($user_email); ?>
                            </div>
                        </div>
                        
                        <div class="py-1">
                            <a href="<?php echo $current_dashboard_url; ?>" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                            
                            <?php if ($dashboard_type === 'customer'): ?>
                                <a href="#profile" class="dropdown-item">
                                    <i class="fas fa-user-circle"></i>
                                    <span>Profilim</span>
                                </a>
                                <a href="#settings" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Ayarlar</span>
                                </a>
                            <?php elseif ($dashboard_type === 'admin'): ?>
                                <a href="#settings" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Sistem Ayarları</span>
                                </a>
                                <a href="#logs" class="dropdown-item">
                                    <i class="fas fa-list"></i>
                                    <span>Sistem Logları</span>
                                </a>
                            <?php elseif ($dashboard_type === 'carwash'): ?>
                                <a href="#profile" class="dropdown-item">
                                    <i class="fas fa-building"></i>
                                    <span>İşletme Profili</span>
                                </a>
                                <a href="#settings" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Ayarlar</span>
                                </a>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            
                            <a href="<?php echo $home_url; ?>" class="dropdown-item">
                                <i class="fas fa-home"></i>
                                <span>Ana Sayfa</span>
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            
                            <a href="<?php echo $logout_url; ?>" class="dropdown-item dropdown-logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Çıkış Yap</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Menu Button -->
                <button class="mobile-menu-button" onclick="toggleMobileMenu()" aria-label="Toggle menu">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>
            
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div id="mobileMenu" class="mobile-menu">
    <div class="mobile-menu-overlay" onclick="toggleMobileMenu()"></div>
    <div class="mobile-menu-panel">
        
        <!-- Mobile Menu Header -->
        <div class="mobile-menu-header">
            <button class="mobile-menu-close" onclick="toggleMobileMenu()" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="mobile-menu-user">
                <div class="mobile-user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="font-semibold text-base">
                        <?php echo htmlspecialchars($user_name); ?>
                    </div>
                    <div class="text-xs opacity-80 capitalize">
                        <?php echo htmlspecialchars($dashboard_type); ?>
                    </div>
                </div>
            </div>
            
            <div class="text-xs opacity-75 mt-2">
                <?php echo htmlspecialchars($user_email); ?>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <nav class="mobile-menu-nav">
            
            <a href="<?php echo $home_url; ?>" class="mobile-nav-item" onclick="toggleMobileMenu()">
                <i class="fas fa-home"></i>
                <span>Ana Sayfa</span>
            </a>
            
            <a href="<?php echo $about_url; ?>" class="mobile-nav-item" onclick="toggleMobileMenu()">
                <i class="fas fa-info-circle"></i>
                <span>Hakkımızda</span>
            </a>
            
            <a href="<?php echo $contact_url; ?>" class="mobile-nav-item" onclick="toggleMobileMenu()">
                <i class="fas fa-envelope"></i>
                <span>İletişim</span>
            </a>
            
            <div class="mobile-nav-divider"></div>
            
            <a href="<?php echo $logout_url; ?>" class="mobile-nav-item" style="color: #dc2626;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Çıkış Yap</span>
            </a>
        </nav>
        
    </div>
</div>

<!-- Dashboard Header JavaScript -->
<script>
// Mobile menu toggle function
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        if (mobileMenu.classList.contains('active')) {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        } else {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
}

// Close mobile menu on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenu && mobileMenu.classList.contains('active')) {
            toggleMobileMenu();
        }
    }
});

// Close mobile menu on window resize to desktop
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        if (window.innerWidth >= 768) {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                toggleMobileMenu();
            }
        }
    }, 250);
});

// Prevent scrolling on dropdown menu links
document.querySelectorAll('.user-dropdown a[href^="#"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        // Handle dropdown actions here if needed
        console.log('Dropdown link clicked:', this.getAttribute('href'));
    });
});

// Smooth scrolling for anchor links (excluding dropdown menu)
document.querySelectorAll('a[href^="#"]:not(.dropdown-item)').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Close mobile menu if open
                const mobileMenu = document.getElementById('mobileMenu');
                if (mobileMenu && mobileMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            }
        }
    });
});

// Prevent user menu button from causing any scroll behavior
document.querySelectorAll('.user-menu-button').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
    });
});

console.log('✅ Dashboard Header: Loaded successfully for <?php echo strtoupper($dashboard_type); ?> dashboard');
</script>
