<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;

// Require customer authentication
Auth::requireRole(['customer']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User';
$user_email = $_SESSION['email'] ?? '';

// Fetch complete user profile data from database
$db = Database::getInstance();
$userData = $db->fetchOne(
    "SELECT u.*, up.profile_image as profile_img, up.address, up.city 
     FROM users u 
     LEFT JOIN user_profiles up ON u.id = up.user_id 
     WHERE u.id = :user_id",
    ['user_id' => $user_id]
);

// Extract user data with defaults
$user_phone = $userData['phone'] ?? '';
$user_home_phone = $userData['home_phone'] ?? '';
$user_national_id = $userData['national_id'] ?? '';
$user_driver_license = $userData['driver_license'] ?? '';
$user_profile_image = $userData['profile_img'] ?? $userData['profile_image'] ?? '';
$user_address = $userData['address'] ?? '';
$user_city = $userData['city'] ?? '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Dashboard header variables
$dashboard_type = 'customer';
$page_title = 'MÃ¼ÅŸteri Paneli - CarWash';
$current_page = 'dashboard';

// Ensure $base_url is available (some templates expect this variable)
if (!isset($base_url)) {
    if (defined('BASE_URL')) {
        $base_url = BASE_URL;
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base_url = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';
    }
}
?>

<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- TailwindCSS - Production Build -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Vehicle manager & local Alpine factories (load before Alpine so factories can register) -->
    <script defer src="<?php echo $base_url; ?>/frontend/js/vehicleManager.js"></script>
    <script defer src="<?php echo $base_url; ?>/frontend/js/alpine-components.js"></script>
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        /* ================================
           CSS CUSTOM PROPERTIES (Theme Variables)
           ================================ */
        :root {
            /* Layout Dynamic Heights (computed by JS) */
            --header-height: 80px;           /* Fixed header height */
            --footer-height: 0px;            /* Default, updated by JS */
            --sidebar-width: 250px;          /* Fixed sidebar width (desktop) */
            
            /* Primary Colors */
            --color-primary: #2563eb;        /* Blue-600 */
            --color-primary-light: #3b82f6; /* Blue-500 */
            --color-primary-dark: #1d4ed8;  /* Blue-700 */
            --color-primary-50: #eff6ff;
            --color-primary-100: #dbeafe;
            --color-primary-200: #bfdbfe;
            
            /* Secondary Colors */
            --color-secondary: #9333ea;       /* Purple-600 */
            --color-secondary-light: #a855f7; /* Purple-500 */
            --color-secondary-dark: #7e22ce;  /* Purple-700 */
            --color-secondary-50: #faf5ff;
            --color-secondary-100: #f3e8ff;
            
            /* Success */
            --color-success: #10b981;        /* Green-500 */
            --color-success-light: #34d399;  /* Green-400 */
            --color-success-bg: #f0fdf4;     /* Green-50 */
            
            /* Error */
            --color-error: #ef4444;          /* Red-500 */
            --color-error-light: #f87171;    /* Red-400 */
            --color-error-bg: #fef2f2;       /* Red-50 */
            
            /* Warning */
            --color-warning: #f59e0b;        /* Amber-500 */
            --color-warning-light: #fbbf24;  /* Amber-400 */
            --color-warning-bg: #fffbeb;     /* Amber-50 */
            
            /* Neutral */
            --color-gray-50: #f9fafb;
            --color-gray-100: #f3f4f6;
            --color-gray-200: #e5e7eb;
            --color-gray-300: #d1d5db;
            --color-gray-400: #9ca3af;
            --color-gray-500: #6b7280;
            --color-gray-600: #4b5563;
            --color-gray-700: #374151;
            --color-gray-800: #1f2937;
            --color-gray-900: #111827;
            
            /* Text Colors */
            --text-primary: var(--color-gray-900);
            --text-secondary: var(--color-gray-600);
            --text-inverse: #ffffff;
            
            /* Background Colors */
            --bg-body: var(--color-gray-50);
            --bg-card: #ffffff;
            --bg-sidebar: linear-gradient(to bottom, var(--color-primary), var(--color-secondary));
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* ================================
           CUSTOM SCROLLBAR
           ================================ */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--color-gray-100);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--color-gray-300);
            border-radius: var(--radius-sm);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--color-gray-400);
        }
        
        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        /* Prevent body scroll when mobile menu open */
        body.menu-open {
            overflow: hidden !important;
            position: fixed;
            width: 100%;
        }
        
        /* ================================
           ANIMATIONS
           ================================ */
        /* Mobile sidebar animations */
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutLeft {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(-100%);
                opacity: 0;
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        /* ================================
           BUTTONS - Consistent States
           ================================ */
        .btn-primary {
            background: linear-gradient(to right, var(--color-primary), var(--color-secondary));
            color: var(--text-inverse);
            transition: all var(--transition-base);
        }
        
        .btn-primary:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-1px);
        }
        
        .btn-primary:active {
            box-shadow: var(--shadow-md);
            transform: translateY(0);
        }
        
        .btn-primary:focus {
            outline: 2px solid var(--color-primary-200);
            outline-offset: 2px;
        }
        
        .btn-secondary {
            background-color: var(--bg-card);
            color: var(--color-primary);
            border: 2px solid var(--color-primary);
            transition: all var(--transition-base);
        }
        
        .btn-secondary:hover {
            background-color: var(--color-primary-50);
        }
        
        .btn-secondary:active {
            background-color: var(--color-primary-100);
        }
        
        .btn-secondary:focus {
            outline: 2px solid var(--color-primary-200);
            outline-offset: 2px;
        }
        
        /* ================================
           SIDEBAR STYLING
           ================================ */
        #customer-sidebar {
            background: var(--bg-sidebar);
        }
        
        /* Ensure sidebar is hidden off-screen on mobile by default */
        @media (max-width: 1023px) {
            #customer-sidebar {
                transition: transform var(--transition-slow);
            }
        }
        
        /* ================================
           CARDS & CONTAINERS
           ================================ */
        .card {
            background-color: var(--bg-card);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        /* ================================
           FORM INPUT STATES
           ================================ */
        input:focus, textarea:focus, select:focus {
            outline: none;
        }
        
        input.error, textarea.error, select.error {
            border-color: var(--color-error) !important;
            background-color: var(--color-error-bg);
        }
        
        input.success, textarea.success, select.success {
            border-color: var(--color-success) !important;
            background-color: var(--color-success-bg);
        }
        
        input::placeholder, textarea::placeholder {
            color: var(--color-gray-400);
        }
        
        /* Form validation messages */
        .form-error {
            color: var(--color-error);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .form-success {
            color: var(--color-success);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        /* ================================
           TEXT UTILITIES
           ================================ */
        .text-primary {
            color: var(--color-primary);
        }
        
        .text-secondary {
            color: var(--color-secondary);
        }
        
        .bg-primary {
            background-color: var(--color-primary);
        }
        
        .bg-secondary {
            background-color: var(--color-secondary);
        }
        
        .bg-gradient-primary {
            background: linear-gradient(to right, var(--color-primary), var(--color-secondary));
        }
        
        /* ================================
           COMPLETE FIXED SIDEBAR LAYOUT
           ================================ */
        
        /* === 1. Fixed Header (80px height) === */
        header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: 80px !important;        /* Fixed header height */
            z-index: 50 !important;
            background: white;
            border-bottom: 1px solid #e5e7eb;
        }
        
        /* === 2. Fixed Sidebar (Left Side) === */
        #customer-sidebar {
            position: fixed !important;
            top: 80px;                      /* Start below 80px header */
            bottom: 0;                      /* Extend to page bottom */
            left: 0;
            width: 250px;                   /* Fixed width on desktop */
            overflow: hidden !important;     /* NO internal scrolling */
            z-index: 30 !important;
            display: flex;
            flex-direction: column;
            background: linear-gradient(to bottom, #2563eb, #7c3aed);
            transition: transform 0.3s ease;
            box-shadow: 4px 0 15px rgba(0,0,0,0.12);
            padding: 0;
        }
        
        /* Sidebar Profile Section */
        #customer-sidebar .flex-shrink-0:first-of-type {
            padding: 1rem;
        }
        
        /* Sidebar Profile Image - 80x80px on desktop */
        #customer-sidebar img#sidebarProfileImage {
            width: 80px !important;
            height: 80px !important;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Header profile image - reduced size (header-only) */
        #userAvatarTop {
            width: 60px !important;
            height: 60px !important;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        /* Header profile container sizing (matches image sizes) */
        #headerProfileContainer {
            width: 60px;
            height: 60px;
        }

        /* Site logo sizing is controlled globally in universal styles to enforce
           a single source-of-truth (80px). Removed local #siteLogo rules so the
           global CSS in backend/includes/universal_styles.php can apply. */
        
        /* Sidebar Navigation Menu */
        #customer-sidebar nav {
            flex: 1;
            padding: 0.75rem;
            overflow: visible;
        }
        
        /* === 3. Main Content Area === */
        #main-content {
            position: relative;              /* Normal document flow */
            margin-left: 250px;             /* Offset by sidebar width */
            margin-top: 80px;               /* Start below 80px header */
            min-height: calc(100vh - 80px); /* Full viewport minus header */
            z-index: 1;
            background: #f9fafb;
        }
        
        /* === 4. Footer (Full Width) === */
        footer, #site-footer {
            position: relative;              /* Normal document flow */
            z-index: 40 !important;
            width: 100%;
            margin-left: 250px;             /* Align with main content */
            background: #111827;
        }
        
        /* ================================
           RESPONSIVE BREAKPOINTS
           ================================ */
        
        /* === Small Screens (<900px): Reduce sidebar to 200px === */
        @media (max-width: 899px) and (min-width: 768px) {
            :root {
                --sidebar-width: 200px;     /* Narrower sidebar */
            }
            
            #customer-sidebar {
                width: 200px;
            }
            
            #customer-sidebar img#sidebarProfileImage {
                width: 64px !important;     /* Smaller profile image */
                height: 64px !important;
            }

            #userAvatarTop {
                width: 48px !important;
                height: 48px !important;
            }

            #headerProfileContainer {
                width: 48px;
                height: 48px;
            }

            /* Logo sizing is handled globally; local overrides removed. */
            
            #main-content {
                margin-left: 200px;
            }
            
            footer, #site-footer {
                margin-left: 200px;
            }
        }
        
        /* === Desktop Layout (â‰¥900px) === */
        @media (min-width: 900px) {
            #customer-sidebar {
                transform: translateX(0) !important;  /* Always visible */
            }
        }
        
        /* === Mobile Layout (<768px) === */
        @media (max-width: 767px) {
            #customer-sidebar {
                width: 250px;
                transform: translateX(-100%);    /* Hidden by default */
                overflow-y: auto !important;     /* Allow scroll on mobile */
                bottom: 0;                       /* Full height */
                top: 80px;
                z-index: 48;                    /* Above main content but below header (header z-50) */
            }
            
            #customer-sidebar img#sidebarProfileImage {
                width: 64px !important;         /* Smaller on mobile */
                height: 64px !important;
            }

            /* Header avatar smaller on mobile */
            #userAvatarTop {
                width: 48px !important;
                height: 48px !important;
            }

            #headerProfileContainer {
                width: 48px;
                height: 48px;
            }

            /* Logo sizing is handled globally; local overrides removed. */
            
            #main-content {
                margin-left: 0;                 /* Full width content */
                margin-top: 80px;
            }
            
            footer, #site-footer {
                margin-left: 0 !important;      /* Full width footer */
            }
        }
        }

        /* Mobile (<=900px) explicit rules to ensure hamburger menu visibility and layering */
        @media (max-width: 900px) {
            /* Ensure header stays on top */
            header {
                z-index: 50 !important;
            }

            /* Sidebar becomes a slide-in mobile panel beneath header */
            #customer-sidebar {
                position: fixed !important;
                top: 80px;
                left: 0;
                height: calc(100vh - 80px);
                width: 80%; /* take most of the screen on small devices */
                max-width: 320px;
                transform: translateX(-100%);
                transition: transform 300ms ease-in-out;
                box-shadow: 4px 0 20px rgba(0,0,0,0.25);
                z-index: 48; /* below header but above main content */
            }

            /* When mobile menu is open (class applied), show it */
            #customer-sidebar.mobile-open {
                transform: translateX(0) !important;
            }

            /* Overlay backdrop sits below the sidebar but above main content */
            .mobile-menu-backdrop-dashboard {
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.45);
                z-index: 47;
                display: none;
            }

            .mobile-menu-backdrop-dashboard.active {
                display: block;
            }

            /* Ensure the hamburger button is visible */
            .hamburger-toggle-dashboard {
                display: inline-flex !important;
            }
        }
        /* Hide scrollbar but keep scroll functionality if needed */
        #customer-sidebar::-webkit-scrollbar {
            width: 0;
            display: none;
        }
        
        #customer-sidebar {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        /* Smooth scrollbar for main content only */
        #main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        #main-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        
        #main-content::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        
        #main-content::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body 
    class="bg-gray-50 overflow-x-hidden flex flex-col min-h-screen" 
    x-data="(typeof customerDashboard !== 'undefined') ? customerDashboard() : { mobileMenuOpen: false, currentSection: 'dashboard', init(){} }"
    x-effect="mobileMenuOpen ? document.body.classList.add('menu-open') : document.body.classList.remove('menu-open')"
>

<!-- ================================
     HEADER - Fixed at Top (80px height)
     ================================ -->
<header class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 shadow-sm flex-none" style="height: 80px;">
    <div class="flex items-center justify-between h-full px-4 lg:px-6">
        
        <!-- Mobile Menu Button -->
        <button 
            id="mobileMenuToggleBtn"
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 active:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 hamburger-toggle-dashboard"
            aria-label="Toggle menu"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!mobileMenuOpen">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="mobileMenuOpen" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        
        <!-- Logo -->
            <div class="flex items-center space-x-3">
            <!-- Main header logo placed before the site title -->
            <div>
                <img id="siteLogo" src="/carwash_project/backend/logo01.png" alt="MyCar logo" class="logo-image object-contain rounded-xl shadow-md" />
            </div>
            <div class="hidden sm:block">
                <h1 class="text-lg font-bold text-gray-900 leading-tight">MyCar</h1>
                <p class="text-xs text-gray-500 -mt-1">Customer Panel</p>
            </div>
        </div>
        
        <!-- User Menu -->
        <div class="relative" x-data="{ userMenuOpen: false }">
            <button 
                @click="userMenuOpen = !userMenuOpen"
                @click.away="userMenuOpen = false"
                class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-expanded="false"
                aria-haspopup="true"
            >
                <!-- Header profile image (updates when user uploads new image) -->
                <div id="headerProfileContainer" class="rounded-full overflow-hidden shadow-sm flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600">
                    <img id="userAvatarTop" src="<?php echo !empty($user_profile_image) ? htmlspecialchars($user_profile_image) : '/carwash_project/frontend/assets/img/default-user.png'; ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="object-cover w-full h-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <div id="userAvatarFallback" class="text-white font-semibold text-sm" style="display: none;">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                </div>

                <!-- Small company logo placed before the user name for quick branding -->


                <span class="hidden md:block text-sm font-medium text-gray-700 max-w-[150px] truncate"><?php echo htmlspecialchars($user_name); ?></span>
                <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:block"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div 
                x-show="userMenuOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 transform -translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden z-50"
                style="display: none;"
            >
                <!-- User Info Header -->
                <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-purple-50 border-b border-gray-200">
                    <p class="text-sm font-semibold text-gray-900 truncate">
                        <?php echo htmlspecialchars($user_name); ?>
                    </p>
                    <p class="text-xs text-gray-600 truncate">
                        <?php echo htmlspecialchars($user_email); ?>
                    </p>
                </div>
                
                <!-- Menu Items -->
                <div class="py-2">
                    <a href="#profile" @click="currentSection = 'profile'; userMenuOpen = false" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                        <i class="fas fa-user-circle w-5 text-blue-600 mr-3"></i>
                        <span>Profil</span>
                    </a>
                    <a href="/carwash_project/backend/index.php" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                        <i class="fas fa-home w-5 text-blue-600 mr-3"></i>
                        <span>Ana Sayfa</span>
                    </a>
                </div>
                
                <!-- Logout -->
                <div class="border-t border-gray-200">
                    <a href="/carwash_project/backend/includes/logout.php" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium">
                        <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                        <span>Ã‡Ä±kÄ±ÅŸ Yap</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ================================
     LAYOUT WRAPPER - Proper Flex Layout Structure
     Sidebar: Fixed between Header and Footer (no internal scroll)
     ================================ -->

<!-- Mobile Overlay (backdrop when sidebar is open on mobile, closes sidebar on click) -->
<div 
    x-show="mobileMenuOpen"
    @click="mobileMenuOpen = false"
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-black bg-opacity-60 z-[45] lg:hidden"
    style="display: none;"
    aria-hidden="true"
    role="button"
    aria-label="Close sidebar"
    tabindex="0"
    @keydown.enter="mobileMenuOpen = false"
    @keydown.space.prevent="mobileMenuOpen = false"
></div>

<!-- Main Content Wrapper: Takes flex-1 to push footer down -->
<div class="flex flex-1">
    
    <!-- ================================
         SIDEBAR - Fixed below header, extends to above footer
         Desktop: No internal scroll, uses CSS variables for positioning
         Mobile: Overlay with internal scroll
         ================================ -->
    <aside 
        id="customer-sidebar"
        class="bg-gradient-to-b from-blue-600 via-blue-700 to-purple-700 text-white shadow-2xl
               transform transition-transform duration-300 ease-in-out
               flex flex-col"
        :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="transform -translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition-transform ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform -translate-x-full"
        role="navigation"
        aria-label="Main navigation"
        :inert="!mobileMenuOpen && window.innerWidth < 1024"
    >
        <!-- User Profile Section (Better readability, always visible at top) -->
        <div class="flex-shrink-0 p-4 border-b border-white border-opacity-20 bg-blue-800 bg-opacity-30">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-2 rounded-full overflow-hidden shadow-lg ring-2 ring-white ring-opacity-30">
                    <img 
                        id="sidebarProfileImage" 
                        src="<?php echo !empty($user_profile_image) ? htmlspecialchars($user_profile_image) : '/carwash_project/frontend/assets/img/default-user.png'; ?>" 
                        alt="<?php echo htmlspecialchars($user_name); ?>"
                        class="w-full h-full object-cover"
                        onerror="this.src='/carwash_project/frontend/assets/img/default-user.png'"
                    >
                </div>
                <h3 class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($user_name); ?></h3>
                <p class="text-xs text-blue-100 opacity-90 truncate mt-1"><?php echo htmlspecialchars($user_email); ?></p>
            </div>
        </div>
        
        <!-- Navigation Menu (Better spacing and readability) -->
        <nav class="flex-1 px-3 py-3 space-y-1 flex flex-col" 
             aria-label="Primary navigation"
        >
            
            <!-- Dashboard -->
            <a 
                href="#dashboard" 
                @click="currentSection = 'dashboard'; mobileMenuOpen = false"
                :class="currentSection === 'dashboard' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-tachometer-alt text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Genel BakÄ±ÅŸ</span>
            </a>
            
            <!-- Car Wash Selection -->
            <a 
                href="#carWashSelection" 
                @click="currentSection = 'carWashSelection'; mobileMenuOpen = false"
                :class="currentSection === 'carWashSelection' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-hand-pointer text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Oto YÄ±kama SeÃ§imi</span>
            </a>
            
            <!-- Reservations -->
            <a 
                href="#reservations" 
                @click="currentSection = 'reservations'; mobileMenuOpen = false"
                :class="currentSection === 'reservations' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-calendar-check text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">RezervasyonlarÄ±m</span>
            </a>
            
            <!-- Vehicles -->
            <a 
                href="#vehicles" 
                @click="currentSection = 'vehicles'; mobileMenuOpen = false"
                :class="currentSection === 'vehicles' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-car text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">AraÃ§larÄ±m</span>
            </a>
            
            <!-- History -->
            <a 
                href="#history" 
                @click="currentSection = 'history'; mobileMenuOpen = false"
                :class="currentSection === 'history' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-history text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">GeÃ§miÅŸ</span>
            </a>
            
            <!-- Profile -->
            <a 
                href="#profile" 
                @click="currentSection = 'profile'; mobileMenuOpen = false"
                :class="currentSection === 'profile' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-user-circle text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Profil</span>
            </a>
            
            <!-- Support -->
            <a 
                href="#support" 
                @click="currentSection = 'support'; mobileMenuOpen = false"
                :class="currentSection === 'support' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-headset text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Destek</span>
            </a>
        </nav>
        
        <!-- Settings (Fixed at bottom, better readability) -->
        <div class="flex-shrink-0 p-3 border-t border-white border-opacity-20 bg-blue-800 bg-opacity-20">
            <a 
                href="#settings" 
                @click="currentSection = 'settings'; mobileMenuOpen = false"
                :class="currentSection === 'settings' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-cog text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Ayarlar</span>
            </a>
        </div>
    </aside>

    <!-- ================================
         MAIN CONTENT AREA - Uses CSS variables for offset
         Desktop: margin-left and margin-top from CSS
         Mobile: Full width with mt-20 for fixed header
         ================================ -->
    <main class="flex-1 bg-gray-50" id="main-content">
        <div class="p-6 lg:p-8 max-w-7xl mx-auto">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                </div>
            <?php endif; ?>
        
        <!-- ========== DASHBOARD SECTION ========== -->
        <section x-show="currentSection === 'dashboard'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Dashboard</h2>
                <p class="text-gray-600">HoÅŸ geldiniz, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>
            
            <!-- Stats Grid - Responsive with consistent spacing -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <!-- Stat Card 1 - Total Reservations -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-700">Toplam Rezervasyon</h4>
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-2">24</p>
                    <p class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-arrow-up text-green-500 mr-1.5 text-xs"></i>
                        <span>12% artÄ±ÅŸ</span>
                    </p>
                </div>
                
                <!-- Stat Card 2 - Completed -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-700">Tamamlanan</h4>
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-2">18</p>
                    <p class="text-sm text-gray-500">Bu ay</p>
                </div>
                
                <!-- Stat Card 3 - Pending -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-700">Bekleyen</h4>
                        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-2">4</p>
                    <p class="text-sm text-gray-500">Onay bekliyor</p>
                </div>
                
                <!-- Stat Card 4 - Vehicles -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-700">AraÃ§</h4>
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-car text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-2" id="vehicleStatCount">-</p>
                    <p class="text-sm text-gray-500">Aktif</p>
                </div>
            </div>
            
            <!-- Quick Actions - Responsive Grid -->
            <div class="mt-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">HÄ±zlÄ± Ä°ÅŸlemler</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                    <!-- New Reservation Card -->
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-6 lg:p-8 text-white shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <i class="fas fa-plus-circle text-4xl lg:text-5xl mb-4 opacity-90"></i>
                        <h4 class="text-xl font-bold mb-2">Yeni Rezervasyon</h4>
                        <p class="text-blue-100 mb-6 text-sm lg:text-base">AraÃ§ yÄ±kama hizmeti rezervasyonu oluÅŸturun</p>
                        <button 
                            @click="currentSection = 'carWashSelection'"
                            class="bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 active:bg-blue-100 transition-colors inline-flex items-center gap-2 shadow-md"
                        >
                            <span>Rezervasyon Yap</span>
                            <i class="fas fa-arrow-right text-sm"></i>
                        </button>
                    </div>
                    
                    <!-- Add Vehicle Card -->
                    <div class="bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl p-6 lg:p-8 text-white shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <i class="fas fa-car text-4xl lg:text-5xl mb-4 opacity-90"></i>
                        <h4 class="text-xl font-bold mb-2">AraÃ§ Ekle</h4>
                        <p class="text-green-100 mb-6 text-sm lg:text-base">Yeni araÃ§ bilgisi kaydedin</p>
                        <button 
                            @click="currentSection = 'vehicles'"
                            class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 active:bg-green-100 transition-colors inline-flex items-center gap-2 shadow-md"
                        >
                            <span>AraÃ§ Ekle</span>
                            <i class="fas fa-arrow-right text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- ========== VEHICLES SECTION ========== -->
    <section x-show="currentSection === 'vehicles'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" x-data="(typeof vehicleManager !== 'undefined') ? vehicleManager() : (window.vehicleManager ? (console.info('Using window.vehicleManager fallback'), window.vehicleManager()) : (console.warn('vehicleManager factory missing — using minimal fallback'), { vehicles: [], showVehicleForm: false, editingVehicle: null, loading: false, message:'', messageType:'', csrfToken: '', imagePreview: '', formData: { brand: '', model: '', license_plate: '', year: '', color: '' } }))" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">AraÃ§larÄ±m</h2>
                <p class="text-gray-600">AraÃ§larÄ±nÄ±zÄ± yÃ¶netin</p>
            </div>
            
            <!-- Action Buttons - Responsive -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <p class="text-sm text-gray-600" x-text="vehicles.length + ' araÃ§ kayÄ±tlÄ±'"></p>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <button 
                        @click="loadVehicles()"
                        class="w-full sm:w-auto h-11 px-5 border-2 border-blue-600 text-blue-600 rounded-xl font-semibold hover:bg-blue-50 active:bg-blue-100 transition-colors inline-flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-sync-alt text-sm"></i>
                        <span>Yenile</span>
                    </button>
                    <button 
                        @click="openVehicleForm()"
                        class="w-full sm:w-auto h-11 px-5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg active:shadow-md transition-all inline-flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-plus text-sm"></i>
                        <span>AraÃ§ Ekle</span>
                    </button>
                </div>
            </div>
            
            <!-- Vehicles Grid - Responsive -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6" id="vehiclesList">
                <template x-for="vehicle in vehicles" :key="vehicle.id">
                    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-start gap-4 mb-4">
                            <img 
                                :src="vehicle.image_path || '/carwash_project/frontend/assets/images/default-car.png'" 
                                :alt="vehicle.brand + ' ' + vehicle.model"
                                class="w-20 h-20 rounded-xl object-cover bg-gray-100 flex-shrink-0 ring-2 ring-gray-200"
                                @error="$el.src='/carwash_project/frontend/assets/images/default-car.png'"
                            >
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-lg text-gray-900 truncate mb-1" x-text="vehicle.brand + ' ' + vehicle.model"></h4>
                                <p class="text-sm text-gray-600 flex items-center gap-1.5">
                                    <i class="fas fa-id-card text-xs"></i>
                                    <span x-text="vehicle.license_plate"></span>
                                </p>
                                <div class="flex items-center flex-wrap gap-x-3 gap-y-1 mt-2 text-xs text-gray-500">
                                    <span x-show="vehicle.year" class="flex items-center gap-1">
                                        <i class="fas fa-calendar"></i>
                                        <span x-text="vehicle.year"></span>
                                    </span>
                                    <span x-show="vehicle.color" class="flex items-center gap-1">
                                        <i class="fas fa-palette"></i>
                                        <span x-text="vehicle.color"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-4 border-t border-gray-100">
                            <button 
                                @click="editVehicle(vehicle)"
                                class="flex-1 h-10 text-blue-600 hover:bg-blue-50 active:bg-blue-100 rounded-lg transition-colors font-medium text-sm inline-flex items-center justify-center gap-1.5"
                            >
                                <i class="fas fa-edit text-xs"></i>
                                <span>DÃ¼zenle</span>
                            </button>
                            <button 
                                @click="deleteVehicle(vehicle.id)"
                                class="flex-1 h-10 text-red-600 hover:bg-red-50 active:bg-red-100 rounded-lg transition-colors font-medium text-sm inline-flex items-center justify-center gap-1.5"
                            >
                                <i class="fas fa-trash text-xs"></i>
                                <span>Sil</span>
                            </button>
                        </div>
                    </div>
                </template>
                
                <!-- Empty State -->
                <template x-if="vehicles.length === 0">
                    <div class="col-span-full text-center py-16 bg-white rounded-2xl border-2 border-dashed border-gray-300">
                        <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg mb-4">HenÃ¼z araÃ§ yok</p>
                        <button 
                            @click="openVehicleForm()"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center space-x-2"
                        >
                            <i class="fas fa-plus"></i>
                            <span>Ä°lk AracÄ±nÄ±zÄ± Ekleyin</span>
                        </button>
                    </div>
                </template>
            </div>
            
            <!-- Vehicle Form Modal -->
            <div 
                x-show="showVehicleForm"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto"
                style="display: none;"
                @click.self="closeVehicleForm()"
            >
                <div 
                    class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto my-8"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    <div class="sticky top-0 bg-white p-6 border-b border-gray-200 z-10">
                        <div class="flex justify-between items-center">
                            <h3 class="text-2xl font-bold text-gray-900" x-text="editingVehicle ? 'AraÃ§ DÃ¼zenle' : 'Yeni AraÃ§ Ekle'"></h3>
                            <button @click="closeVehicleForm()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-lg hover:bg-gray-100">
                                <i class="fas fa-times text-2xl"></i>
                            </button>
                        </div>
                    </div>
                    
                    <form id="vehicleForm" @submit.prevent="saveVehicle()" class="p-6" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" :value="csrfToken">
                        <input type="hidden" name="action" :value="editingVehicle ? 'update' : 'create'">
                        <input type="hidden" name="id" :value="editingVehicle?.id || ''">
                        
                        <!-- Form Fields Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <!-- Brand -->
                            <div class="mb-4">
                                <label for="vehicle_brand" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Marka <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="vehicle_brand"
                                    name="car_brand"
                                    x-model="formData.brand"
                                    required
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    :class="{'border-red-500': formData.brand === ''}"
                                    placeholder="Toyota"
                                >
                            </div>
                            
                            <!-- Model -->
                            <div class="mb-4">
                                <label for="vehicle_model" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Model <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="vehicle_model"
                                    name="car_model"
                                    x-model="formData.model"
                                    required
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    :class="{'border-red-500': formData.model === ''}"
                                    placeholder="Corolla"
                                >
                            </div>
                            
                            <!-- License Plate -->
                            <div class="mb-4">
                                <label for="vehicle_license_plate" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Plaka <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="vehicle_license_plate"
                                    name="license_plate"
                                    x-model="formData.license_plate"
                                    required
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 uppercase focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    :class="{'border-red-500': formData.license_plate === ''}"
                                    placeholder="34 ABC 123"
                                >
                            </div>
                            
                            <!-- Year -->
                            <div class="mb-4">
                                <label for="vehicle_year" class="block text-sm font-semibold text-gray-700 mb-2">YÄ±l</label>
                                <input 
                                    type="number"
                                    id="vehicle_year"
                                    name="car_year"
                                    x-model="formData.year"
                                    min="1900"
                                    :max="new Date().getFullYear()"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="2020"
                                >
                            </div>
                            
                            <!-- Color -->
                            <div class="mb-4">
                                <label for="vehicle_color" class="block text-sm font-semibold text-gray-700 mb-2">Renk</label>
                                <input 
                                    type="text"
                                    id="vehicle_color"
                                    name="car_color"
                                    x-model="formData.color"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="Beyaz"
                                >
                            </div>
                            
                            <!-- Vehicle Image -->
                            <div class="mb-4">
                                <label for="vehicle_image" class="block text-sm font-semibold text-gray-700 mb-2">AraÃ§ FotoÄŸrafÄ±</label>
                                <input 
                                    type="file"
                                    id="vehicle_image"
                                    name="vehicle_image"
                                    @change="previewImage($event)"
                                    accept="image/*"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                >
                            </div>
                        </div>
                        
                        <!-- Image Preview -->
                        <div class="mb-4">
                            <p class="block text-sm font-semibold text-gray-700 mb-2">Ã–nizleme</p>
                            <img 
                                :src="imagePreview || '/carwash_project/frontend/assets/images/default-car.png'"
                                alt="Preview"
                                class="w-32 h-24 object-cover rounded-lg border-2 border-gray-300 shadow-sm"
                            >
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                            <button 
                                type="button"
                                @click="closeVehicleForm()"
                                class="w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors"
                            >
                                Ä°ptal
                            </button>
                            <button 
                                type="submit"
                                :disabled="loading"
                                class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center space-x-2"
                            >
                                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                <span x-text="loading ? 'Kaydediliyor...' : 'Kaydet'"></span>
                            </button>
                        </div>
                        
                        <div x-show="message" class="p-4 rounded-xl" :class="messageType === 'error' ? 'bg-red-50 border-2 border-red-200 text-red-700' : 'bg-green-50 border-2 border-green-200 text-green-700'" style="display: none;">
                            <div class="flex items-start space-x-3">
                                <i class="fas text-lg mt-0.5" :class="messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'"></i>
                                <p class="flex-1" x-text="message"></p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        
        <!-- ========== PROFILE SECTION ========== -->
        <section x-show="currentSection === 'profile'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Profil AyarlarÄ±</h2>
                <p class="text-gray-600">Hesap bilgilerinizi gÃ¼ncelleyin</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8">
                <form id="profileForm" class="space-y-6" enctype="multipart/form-data">
                    <?php
                    // Idempotent ensure session and CSRF token for profile & password change forms
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        \App\Classes\Session::start();
                    }
                    if (empty($_SESSION['csrf_token'])) {
                        $csrf_helper = __DIR__ . '/../../includes/csrf_protect.php';
                        if (file_exists($csrf_helper)) {
                            require_once $csrf_helper;
                            if (function_exists('generate_csrf_token')) {
                                generate_csrf_token();
                            } else {
                                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                            }
                        } else {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                        }
                    }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <!-- Profile Image Upload Section -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">Profil FotoÄŸrafÄ±</h4>
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                            <!-- Current Profile Image -->
                            <div class="flex-shrink-0">
                                <div class="relative w-32 h-32 rounded-full overflow-hidden border-4 border-gray-200 bg-gray-100">
                                    <img 
                                        id="profileImagePreview" 
                                        src="<?php echo !empty($user_profile_image) ? htmlspecialchars($user_profile_image) : '/carwash_project/frontend/images/default-avatar.svg'; ?>" 
                                        alt="Profile" 
                                        class="w-full h-full object-cover"
                                        onerror="this.src='/carwash_project/frontend/images/default-avatar.svg'"
                                    >
                                </div>
                            </div>
                            
                            <!-- Upload Controls -->
                            <div class="flex-1">
                                <label for="profile_image" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Yeni FotoÄŸraf YÃ¼kle
                                </label>
                                <input 
                                    type="file" 
                                    id="profile_image" 
                                    name="profile_image" 
                                    accept="image/jpeg,image/png,image/jpg,image/webp"
                                    class="block w-full text-sm text-gray-900 border-2 border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                >
                                <p class="mt-2 text-xs text-gray-500">JPG, PNG veya WEBP formatÄ±nda. Maksimum 2MB.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <!-- Name -->
                        <div class="mb-4">
                            <label for="profile_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                Ad Soyad <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                id="profile_name"
                                name="name"
                                value="<?php echo htmlspecialchars($user_name); ?>"
                                required
                                autocomplete="name"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                placeholder="AdÄ±nÄ±z SoyadÄ±nÄ±z"
                            >
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-4">
                            <label for="profile_email" class="block text-sm font-semibold text-gray-700 mb-2">
                                E-posta <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email"
                                id="profile_email"
                                name="email"
                                value="<?php echo htmlspecialchars($user_email); ?>"
                                required
                                autocomplete="email"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                placeholder="ornek@email.com"
                            >
                        </div>
                        
                        <!-- Phone -->
                        <div class="mb-4">
                            <label for="profile_phone" class="block text-sm font-semibold text-gray-700 mb-2">Telefon</label>
                            <input 
                                type="tel"
                                id="profile_phone"
                                name="phone"
                                value="<?php echo htmlspecialchars($user_phone); ?>"
                                placeholder="+90 555 123 45 67"
                                autocomplete="tel"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                        </div>
                        
                        <!-- Home Phone (Required) -->
                        <div class="mb-4">
                            <label for="profile_home_phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                Ev Telefonu <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="tel"
                                id="profile_home_phone"
                                name="home_phone"
                                value="<?php echo htmlspecialchars($user_home_phone); ?>"
                                required
                                placeholder="+90 212 345 67 89"
                                autocomplete="tel-local"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                        </div>
                        
                        <!-- National ID (Required) -->
                        <div class="mb-4">
                            <label for="profile_national_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                T.C. Kimlik No <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                id="profile_national_id"
                                name="national_id"
                                value="<?php echo htmlspecialchars($user_national_id); ?>"
                                required
                                maxlength="11"
                                pattern="[0-9]{11}"
                                placeholder="12345678901"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                            <p class="mt-1 text-xs text-gray-500">11 haneli T.C. Kimlik numaranÄ±zÄ± girin</p>
                        </div>
                        
                        <!-- Driver License (Optional) -->
                        <div class="mb-4">
                            <label for="profile_driver_license" class="block text-sm font-semibold text-gray-700 mb-2">
                                SÃ¼rÃ¼cÃ¼ Belgesi No
                            </label>
                            <input 
                                type="text"
                                id="profile_driver_license"
                                name="driver_license"
                                value="<?php echo htmlspecialchars($user_driver_license); ?>"
                                placeholder="A1234567"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                            <p class="mt-1 text-xs text-gray-500">Ä°steÄŸe baÄŸlÄ± alan</p>
                        </div>
                        
                        <!-- City -->
                        <div class="mb-4">
                            <label for="profile_city" class="block text-sm font-semibold text-gray-700 mb-2">Åžehir</label>
                            <input 
                                type="text"
                                id="profile_city"
                                name="city"
                                value="<?php echo htmlspecialchars($user_city); ?>"
                                placeholder="Ä°stanbul"
                                autocomplete="address-level2"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                        </div>
                        
                        <!-- Address -->
                        <div class="mb-4 md:col-span-2">
                            <label for="profile_address" class="block text-sm font-semibold text-gray-700 mb-2">Adres</label>
                            <textarea 
                                id="profile_address"
                                name="address"
                                rows="3"
                                placeholder="Tam adresiniz"
                                autocomplete="street-address"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors resize-none"
                            ><?php echo htmlspecialchars($user_address); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Password Change Section -->
                    <div class="pt-6 border-t border-gray-200">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">Åžifre DeÄŸiÅŸtir</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <!-- Current Password -->
                            <div class="mb-4">
                                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">Mevcut Åžifre</label>
                                <input 
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    autocomplete="current-password"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢"
                                >
                            </div>
                            
                            <!-- New Password -->
                            <div class="mb-4">
                                <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">Yeni Åžifre</label>
                                <input 
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <div class="hidden mb-4 p-4 border-2 border-green-500 bg-green-50 text-green-700 rounded-lg" id="profile-success">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>Profil baÅŸarÄ±yla gÃ¼ncellendi!</span>
                        </div>
                    </div>
                    
                    <div class="hidden mb-4 p-4 border-2 border-red-500 bg-red-50 text-red-600 rounded-lg" id="profile-error">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Bir hata oluÅŸtu. LÃ¼tfen tekrar deneyin.</span>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
                        <button 
                            type="button"
                            class="w-full sm:w-auto h-11 px-6 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 active:bg-gray-100 transition-colors"
                        >
                            Ä°ptal
                        </button>
                        <button 
                            type="submit"
                            class="w-full sm:w-auto h-11 px-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg active:shadow-md transition-all inline-flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-save text-sm"></i>
                            <span>Kaydet</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- ========== SUPPORT SECTION ========== -->
        <section x-show="currentSection === 'support'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Destek</h2>
                <p class="text-gray-600">YardÄ±ma mÄ± ihtiyacÄ±nÄ±z var? Bize ulaÅŸÄ±n</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8">
                <form class="space-y-6">
                    <!-- Subject -->
                    <div class="mb-4">
                        <label for="support_subject" class="block text-sm font-semibold text-gray-700 mb-2">
                            Konu <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text"
                            id="support_subject"
                            name="subject"
                            required
                            placeholder="Sorununuzun kÄ±sa aÃ§Ä±klamasÄ±"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                        >
                    </div>
                    
                    <!-- Category -->
                    <div class="mb-4">
                        <label for="support_category" class="block text-sm font-semibold text-gray-700 mb-2">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="support_category"
                            name="category"
                            required
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                        >
                            <option value="">Kategori seÃ§in</option>
                            <option value="reservation">Rezervasyon</option>
                            <option value="payment">Ã–deme</option>
                            <option value="vehicle">AraÃ§ Bilgileri</option>
                            <option value="account">Hesap AyarlarÄ±</option>
                            <option value="other">DiÄŸer</option>
                        </select>
                    </div>
                    
                    <!-- Message -->
                    <div class="mb-4">
                        <label for="support_message" class="block text-sm font-semibold text-gray-700 mb-2">
                            Mesaj <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="support_message"
                            name="message"
                            rows="6"
                            required
                            placeholder="Sorununuzu detaylÄ± olarak aÃ§Ä±klayÄ±n"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors resize-none"
                        ></textarea>
                        <p class="mt-2 text-xs text-gray-500">Minimum 20 karakter</p>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <div class="hidden mb-4 p-4 border-2 border-green-500 bg-green-50 text-green-700 rounded-lg" id="support-success">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>MesajÄ±nÄ±z baÅŸarÄ±yla gÃ¶nderildi!</span>
                        </div>
                    </div>
                    
                    <div class="hidden mb-4 p-4 border-2 border-red-500 bg-red-50 text-red-600 rounded-lg" id="support-error">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Mesaj gÃ¶nderilemedi. LÃ¼tfen tÃ¼m alanlarÄ± doldurun.</span>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            class="w-full sm:w-auto h-11 px-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg active:shadow-md transition-all inline-flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-paper-plane text-sm"></i>
                            <span>GÃ¶nder</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- ========== SETTINGS SECTION ========== -->
        <section x-show="currentSection === 'settings'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Ayarlar</h2>
                <p class="text-gray-600">Hesap ayarlarÄ±nÄ±zÄ± yÃ¶netin</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Bildirim Tercihleri</h3>
                <div class="flex flex-col gap-4">
                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">E-posta Bildirimleri</h4>
                            <p class="text-sm text-gray-600">Rezervasyon onaylarÄ± ve gÃ¼ncellemeler</p>
                        </div>
                        <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">SMS Bildirimleri</h4>
                            <p class="text-sm text-gray-600">Acil durumlar iÃ§in SMS</p>
                        </div>
                        <input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">Promosyon Bildirimleri</h4>
                            <p class="text-sm text-gray-600">Ä°ndirim ve kampanya duyurularÄ±</p>
                        </div>
                        <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                    </label>
                </div>

                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-xl font-bold mb-6">GÃ¼venlik</h3>
                    <div class="space-y-4">
                        <button class="w-full text-left p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                            <h4 class="font-bold">Åžifre DeÄŸiÅŸtir</h4>
                            <p class="text-sm text-gray-600">Hesap gÃ¼venliÄŸiniz iÃ§in ÅŸifrenizi gÃ¼ncelleyin</p>
                        </button>

                        <button class="w-full text-left p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                            <h4 class="font-bold">Ä°ki FaktÃ¶rlÃ¼ DoÄŸrulama</h4>
                            <p class="text-sm text-gray-600">Ek gÃ¼venlik katmanÄ± ekleyin</p>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Other sections (reservations, carWashSelection, history) would follow the same pattern -->
        
        </div> <!-- END: Max-width container -->
    </main>

</div> <!-- END: Flex Container (Sidebar + Content) -->

<!-- Footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Dynamic Layout Height Calculator -->
<script>
// ================================
// Layout Height Manager
// Sets header to fixed 80px and computes footer height
// Updates CSS variables on load, resize, and content changes
// ================================
(function() {
    'use strict';
    
    function updateLayoutHeights() {
        const root = document.documentElement;
        
        // Set fixed header height
        root.style.setProperty('--header-height', '80px');
        
        // Update footer height if needed (for mobile calculations)
        const footer = document.querySelector('#site-footer');
        if (footer) {
            const footerHeight = Math.round(footer.getBoundingClientRect().height);
            if (footerHeight > 0) {
                root.style.setProperty('--footer-height', `${footerHeight}px`);
            }
        }
        
        // Ensure sidebar width consistency
        const viewportWidth = window.innerWidth;
        let sidebarWidth = 250;
        
        if (viewportWidth < 768) {
            sidebarWidth = 250; // Mobile
        } else if (viewportWidth < 900) {
            sidebarWidth = 200; // Small screens
        } else {
            sidebarWidth = 250; // Desktop
        }
        
        root.style.setProperty('--sidebar-width', `${sidebarWidth}px`);
        
        console.log('âœ… Layout updated - Header: 80px, Footer:', root.style.getPropertyValue('--footer-height'), 'Sidebar:', sidebarWidth + 'px');
    }
    
    // Update on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateLayoutHeights);
    } else {
        updateLayoutHeights();
    }
    
    // Update on resize (debounced)
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updateLayoutHeights, 250);
    });
    
    // Update after images load (footer might change height)
    window.addEventListener('load', function() {
        setTimeout(updateLayoutHeights, 100);
    });
})();
</script>

<!-- Profile Form JavaScript -->
<script>
// ================================
// Profile Image Preview Handler
// ================================
(function() {
    'use strict';
    
    const profileImageInput = document.getElementById('profile_image');
    const profileImagePreview = document.getElementById('profileImagePreview');
    
    if (profileImageInput && profileImagePreview) {
        profileImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('LÃ¼tfen geÃ§erli bir resim dosyasÄ± seÃ§in (JPG, PNG veya WEBP)');
                    e.target.value = '';
                    return;
                }
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Resim boyutu 2MB\'dan kÃ¼Ã§Ã¼k olmalÄ±dÄ±r');
                    e.target.value = '';
                    return;
                }
                
                // Create preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    profileImagePreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
})();

// ================================
// Profile Form Submission Handler
// ================================
(function() {
    'use strict';
    
    const profileForm = document.getElementById('profileForm');
    
    if (!profileForm) {
        console.warn('Profile form not found');
        return;
    }
    
    profileForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Get form elements
        const submitBtn = profileForm.querySelector('button[type="submit"]');
        const successMsg = document.getElementById('profile-success');
        const errorMsg = document.getElementById('profile-error');
        
        // Hide previous messages
        if (successMsg) successMsg.classList.add('hidden');
        if (errorMsg) errorMsg.classList.add('hidden');
        
        // Disable submit button
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Kaydediliyor...</span>';
        }
        
        try {
            // Create FormData from form
            const formData = new FormData(profileForm);
            formData.append('action', 'update_profile');
            
            // Add CSRF token
            const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
            formData.append('csrf_token', csrfToken);
            
            // Submit form
            const response = await fetch('/carwash_project/backend/dashboard/Customer_Dashboard_process.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            // Parse response
            const result = await response.json();
            
            if (result.success) {
                // Show success message
                if (successMsg) {
                    successMsg.classList.remove('hidden');
                    successMsg.querySelector('span').textContent = result.message || 'Profil baÅŸarÄ±yla gÃ¼ncellendi!';
                }
                
                // Update session if needed
                if (result.data && result.data.image) {
                    const topAvatar = document.getElementById('userAvatarTop');
                    if (topAvatar) {
                        topAvatar.src = result.data.image;
                        topAvatar.style.display = 'block';
                        // Hide fallback initial if present
                        const fallback = document.getElementById('userAvatarFallback');
                        if (fallback) fallback.style.display = 'none';
                    }
                    
                    // Update sidebar profile image
                    const sidebarImg = document.getElementById('sidebarProfileImage');
                    if (sidebarImg) {
                        sidebarImg.src = result.data.image;
                        sidebarImg.style.display = 'block';
                        console.log('âœ… Sidebar image updated:', result.data.image);
                    }
                    // Persist new profile image to localStorage so other pages (like the homepage)
                    // can update their header avatars in real-time (or across tabs).
                    try {
                        localStorage.setItem('carwash_profile_image', result.data.image);
                        // Optionally store a timestamp for cache-busting listeners
                        localStorage.setItem('carwash_profile_image_ts', Date.now().toString());
                    } catch (lsErr) {
                        console.warn('Could not write profile image to localStorage:', lsErr);
                    }
                }
                
                // Scroll to top of form to show success message
                profileForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
            } else {
                throw new Error(result.message || 'Profil gÃ¼ncellenirken bir hata oluÅŸtu');
            }
            
        } catch (error) {
            console.error('Profile update error:', error);
            
            // Show error message
            if (errorMsg) {
                errorMsg.classList.remove('hidden');
                errorMsg.querySelector('span').textContent = error.message || 'Bir hata oluÅŸtu. LÃ¼tfen tekrar deneyin.';
            }
            
            // Scroll to error message
            if (errorMsg) {
                errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } finally {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save text-sm"></i> <span>Kaydet</span>';
            }
        }
    });
})();
</script>

<script>
// ================================
// Mobile Sidebar Toggle - Enhanced Behavior
// Handles sidebar visibility on mobile devices (<768px)
// ================================
(function() {
    'use strict';
    
    // Ensure sidebar is hidden by default on mobile
    function ensureSidebarState() {
        const sidebar = document.getElementById('customer-sidebar');
        if (!sidebar) return;
        
        if (window.innerWidth < 768) {
            // Mobile: hidden by default
            const body = document.body;
            if (body && body.__x && body.__x.$data) {
                if (!body.__x.$data.hasOwnProperty('mobileMenuOpen')) {
                    body.__x.$data.mobileMenuOpen = false;
                }
            }
        }
    }
    
    // Keyboard: ESC closes mobile sidebar
    document.addEventListener('keydown', function(e) {
        if ((e.key === 'Escape' || e.keyCode === 27) && window.innerWidth < 768) {
            const body = document.body;
            if (body && body.__x && body.__x.$data && body.__x.$data.mobileMenuOpen) {
                body.__x.$data.mobileMenuOpen = false;
                console.log('ðŸ” Sidebar closed via ESC key');
            }
        }
    });
    
    // Close sidebar when clicking menu links on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            const link = e.target.closest('#customer-sidebar a[href^="#"]');
            if (link) {
                const body = document.body;
                if (body && body.__x && body.__x.$data) {
                    body.__x.$data.mobileMenuOpen = false;
                    console.log('ðŸ”— Sidebar closed after link click');
                }
            }
        }
    });
    
    // Enhanced body scroll prevention
    if (typeof Alpine !== 'undefined') {
        document.addEventListener('alpine:init', function() {
            Alpine.effect(() => {
                const body = document.body;
                if (body && body.__x && body.__x.$data) {
                    const data = body.__x.$data;
                    
                    // Only on mobile
                    if (window.innerWidth < 768) {
                        if (data.mobileMenuOpen) {
                            // Save scroll position
                            const scrollY = window.scrollY;
                            document.body.style.position = 'fixed';
                            document.body.style.top = `-${scrollY}px`;
                            document.body.style.width = '100%';
                            document.body.style.overflow = 'hidden';
                            console.log('ðŸ“± Mobile sidebar opened, body scroll prevented');
                        } else {
                            // Restore scroll position
                            const scrollY = document.body.style.top;
                            document.body.style.position = '';
                            document.body.style.top = '';
                            document.body.style.width = '';
                            document.body.style.overflow = '';
                            if (scrollY) {
                                window.scrollTo(0, parseInt(scrollY || '0') * -1);
                            }
                            console.log('ðŸ“± Mobile sidebar closed, body scroll restored');
                        }
                    } else {
                        // Desktop: ensure body scroll is enabled
                        document.body.style.position = '';
                        document.body.style.top = '';
                        document.body.style.width = '';
                        document.body.style.overflow = '';
                    }
                }
            });
        });
    }
    
    // Initialize sidebar state on load
    ensureSidebarState();
    
    // Re-check on resize
    window.addEventListener('resize', function() {
        ensureSidebarState();
        
        // Close sidebar if resizing to desktop width
        if (window.innerWidth >= 768) {
            const body = document.body;
            if (body && body.__x && body.__x.$data && body.__x.$data.mobileMenuOpen) {
                body.__x.$data.mobileMenuOpen = false;
                console.log('ðŸ’» Resized to desktop, sidebar auto-closed');
            }
        }
    });
    
    console.log('âœ… Mobile sidebar toggle initialized');
    
})();

    // Sync Alpine mobileMenuOpen state to CSS classes and provide a non-Alpine fallback
    (function() {
        'use strict';

        const sidebar = document.getElementById('customer-sidebar');
        // Select overlay by multiple class attributes instead of chained classes (avoid CSS escape issues)
        const overlay = document.querySelector('div[role="button"][aria-label="Close sidebar"]');
        const toggleBtn = document.getElementById('mobileMenuToggleBtn');

        function applyOpenState(isOpen) {
            if (!sidebar) return;
            if (isOpen) {
                sidebar.classList.add('mobile-open');
                if (overlay) overlay.classList.add('active');
                document.body.classList.add('menu-open');
            } else {
                sidebar.classList.remove('mobile-open');
                if (overlay) overlay.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        }

        // Alpine-aware observer
        if (typeof Alpine !== 'undefined') {
            document.addEventListener('alpine:init', function() {
                Alpine.effect(() => {
                    try {
                        const body = document.body;
                        const isOpen = body && body.__x && body.__x.$data && !!body.__x.$data.mobileMenuOpen;
                        applyOpenState(isOpen);
                    } catch (e) {
                        // ignore
                    }
                });
            });
        }

        // Non-Alpine fallback: toggle classes directly when hamburger is clicked
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                // If Alpine is present, let it control state; fallback only if not
                if (typeof Alpine === 'undefined' || !document.body.__x) {
                    const isOpen = sidebar.classList.contains('mobile-open');
                    applyOpenState(!isOpen);
                }
            });
        }

        // Close on ESC (fallback)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                // If Alpine present, set its state; otherwise use fallback
                if (document.body && document.body.__x && document.body.__x.$data) {
                    document.body.__x.$data.mobileMenuOpen = false;
                } else {
                    applyOpenState(false);
                }
            }
        });

    })();

// ================================
// Accessibility: Focus Management & Inert Support
// ================================
(function() {
    'use strict';
    
    const sidebar = document.getElementById('customer-sidebar');
    if (!sidebar) return;
    
    /**
     * Manage focusable elements in sidebar based on visibility
     * Prevents keyboard focus on hidden sidebar links
     */
    function manageSidebarFocusability(isVisible, isMobile) {
        if (!sidebar) return;
        
        // Get all focusable elements in sidebar
        const focusableElements = sidebar.querySelectorAll(
            'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        focusableElements.forEach(element => {
            if (isMobile && !isVisible) {
                // On mobile when closed: make unfocusable
                element.setAttribute('tabindex', '-1');
            } else {
                // On desktop or when open: restore focusability
                element.removeAttribute('tabindex');
            }
        });
        
        console.log(`â™¿ Sidebar focusability: ${isVisible ? 'enabled' : 'disabled'} (mobile: ${isMobile})`);
    }
    
    /**
     * Check if viewport is mobile
     */
    function isMobileViewport() {
        return window.innerWidth < 1024;
    }
    
    /**
     * Update sidebar focusability based on current state
     */
    function updateSidebarAccessibility() {
        const body = document.body;
        const isMobile = isMobileViewport();
        const isOpen = body && body.__x && body.__x.$data && body.__x.$data.mobileMenuOpen;
        
        manageSidebarFocusability(isOpen || !isMobile, isMobile);
    }
    
    // Watch for Alpine.js state changes
    if (typeof Alpine !== 'undefined') {
        document.addEventListener('alpine:init', function() {
            Alpine.effect(() => {
                updateSidebarAccessibility();
            });
        });
    }
    
    // Update on page load
    setTimeout(updateSidebarAccessibility, 100);
    
    // Update on window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updateSidebarAccessibility, 150);
    });
    
    // Update when sidebar transitions complete
    sidebar.addEventListener('transitionend', function(e) {
        if (e.propertyName === 'transform') {
            updateSidebarAccessibility();
        }
    });
    
    console.log('âœ… Sidebar accessibility manager initialized');
    
})();

// ================================
// Dynamic Content Observer
// Ensures proper rendering of dynamic content
// ================================
(function() {
    'use strict';
    
    console.log('âœ… Dashboard layout initialized with proper flex structure');
    
})();

// ================================
// Form Validation & Styling
// ================================
(function() {
    'use strict';
    
    // Add real-time validation for required fields
    function validateInput(input) {
        const value = input.value.trim();
        const isRequired = input.hasAttribute('required');
        
        if (isRequired && value === '') {
            input.classList.add('error');
            input.classList.remove('success');
            return false;
        } else if (isRequired && value !== '') {
            input.classList.remove('error');
            input.classList.add('success');
            return true;
        }
        
        // Email validation
        if (input.type === 'email' && value !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                input.classList.add('error');
                input.classList.remove('success');
                return false;
            } else {
                input.classList.remove('error');
                input.classList.add('success');
                return true;
            }
        }
        
        // Textarea min length
        if (input.tagName === 'TEXTAREA' && value !== '' && value.length < 20) {
            input.classList.add('error');
            input.classList.remove('success');
            return false;
        } else if (input.tagName === 'TEXTAREA' && value.length >= 20) {
            input.classList.remove('error');
            input.classList.add('success');
            return true;
        }
        
        // Clear classes for optional valid fields
        if (!isRequired && value !== '') {
            input.classList.remove('error', 'success');
        }
        
        return true;
    }
    
    // Add event listeners to all form inputs
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input:not([type="hidden"]):not([type="file"]), textarea, select');
        
        inputs.forEach(input => {
            // Validate on blur
            input.addEventListener('blur', function() {
                validateInput(this);
            });
            
            // Clear error on focus
            input.addEventListener('focus', function() {
                this.classList.remove('error');
            });
            
            // Real-time validation for certain fields
            if (input.type === 'email' || input.tagName === 'TEXTAREA') {
                input.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        setTimeout(() => validateInput(this), 500);
                    }
                });
            }
        });
        
        // Form submission validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const formInputs = this.querySelectorAll('input:not([type="hidden"]):not([type="file"]), textarea, select');
                
                formInputs.forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    const firstError = this.querySelector('.error');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
        
        console.log('âœ… Form validation initialized');
    });
    
})();
</script>

</body>
</html>



