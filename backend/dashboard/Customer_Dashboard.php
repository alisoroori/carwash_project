<?php

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
$page_title = 'Müşteri Paneli - CarWash';
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
    
    <!-- CSRF Token Meta Tag for JavaScript -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- TailwindCSS - Production Build -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Initialize Global CONFIG object with CSRF token -->
    <script>
        window.CONFIG = window.CONFIG || {};
        window.CONFIG.CSRF_TOKEN = '<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>';
        window.CONFIG.BASE_URL = '<?php echo htmlspecialchars($base_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>';
    </script>
    
    <!-- CSRF Helper - Auto-inject CSRF tokens in all POST requests -->
    <script defer src="<?php echo $base_url; ?>/frontend/js/csrf-helper.js"></script>
    
    <!-- Vehicle manager & local Alpine factories (load before Alpine so factories can register) -->
    <!-- Ensure api-utils is loaded before vehicleManager (provides window.apiCall) -->
    <script defer src="<?php echo $base_url; ?>/frontend/js/api-utils.js"></script>
    <script defer src="<?php echo $base_url; ?>/frontend/js/vehicleManager.js"></script>
    <script defer src="<?php echo $base_url; ?>/frontend/js/alpine-components.js"></script>
    <!-- Lightweight Alpine data factory for profile section (defines profileSection) -->
    <script>
        (function(){
            // Build initial profile data safely via json_encode to avoid attribute quoting issues
            const profileInit = <?php echo json_encode([
                'editMode' => false,
                'profileData' => [
                    'name' => $user_name,
                    'email' => $user_email,
                    'username' => $userData['username'] ?? $_SESSION['username'] ?? '',
                    'phone' => $user_phone,
                    'home_phone' => $user_home_phone,
                    'national_id' => $user_national_id,
                    'driver_license' => $user_driver_license,
                    'city' => $user_city,
                    'address' => $user_address,
                    'profile_image' => !empty($user_profile_image) ? $user_profile_image : ($base_url . '/frontend/images/default-avatar.svg')
                ]
            ], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;

            // Expose factory used by Alpine's x-data="profileSection()"
            function profileSection() {
                const state = {
                    editMode: profileInit.editMode || false,
                    profileData: profileInit.profileData || {},
                    toggleEdit() {
                        this.editMode = !this.editMode;
                        if (!this.editMode) {
                            // Clear password fields when exiting edit mode
                            const form = document.getElementById('profileForm');
                            if (form) {
                                ['current_password','new_password','confirm_password'].forEach(n => {
                                    const f = form.querySelector(`[name="${n}"]`);
                                    if (f) f.value = '';
                                });
                            }
                        }
                    },
                    updateProfile(data) {
                        if (!data || typeof data !== 'object') return;
                        Object.keys(data).forEach(k => {
                            if (k === 'profile_image') {
                                this.profileData.profile_image = data[k];
                            } else if (data[k] !== undefined) {
                                this.profileData[k] = data[k];
                            }
                        });
                    }
                };
                return state;
            }

            // Make factory globally available (Alpine will call it by name)
            window.profileSection = profileSection;
        })();
    </script>
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        /* ================================
           CSS CUSTOM PROPERTIES (Theme Variables)
           ================================ */
        :root {
            /* Layout Dynamic Heights (computed by JS) */
            --header-height: 80px;           /* Fixed header height */
            /* Footer removed from this page; no footer height variable needed */
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
            /* Ensure root-level layout sizing is robust for fixed children */
            /* (Non-functional layout assist: does not change visuals) */
            --layout-root-height: 100vh;
        }

        /* Ensure html/body occupy full height for fixed layout calculations */
        html, body {
            height: 100%;
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
            top: var(--header-height);      /* Start below header */
            bottom: 0; /* No footer on this page */
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
            height: calc(100vh - var(--header-height));
        }
        
        /* Sidebar Profile Section */
        #customer-sidebar .flex-shrink-0:first-of-type {
            padding: 1rem;
        }
        
        /* Sidebar Profile Image - match header profile (60x60 on desktop) */
        #customer-sidebar img#sidebarProfileImage {
            width: 60px !important;
            height: 60px !important;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: block;
            margin: 0 auto;
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

          /* Compact sidebar adjustments to avoid vertical overflow
              - Sidebar is fixed below the header using CSS vars
           - Reduce font-size and line-height slightly
           - Reduce paddings for profile and nav items
           - Ensure no vertical scrollbar appears on desktop (mobile overrides allow scrolling)
           These changes are intentionally minimal and limited to the sidebar only.
        */
        #customer-sidebar {
            /* Keep visual sizing compact */
            font-size: 13px; /* slightly smaller text to fit more content */
            line-height: 1.15;

            /* Layout: fixed below the header, anchored to viewport bottom */
            position: fixed !important;
            top: var(--header-height);
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            box-sizing: border-box;

            /* Desktop: hide internal scrollbar; mobile rules below re-enable scrolling */
            overflow-y: hidden !important; /* prevent vertical scrollbar on desktop */
            z-index: 49; /* sit below header (z-50) but above main content */
        }

        #customer-sidebar .flex-shrink-0:first-of-type {
            padding: 0.6rem; /* slightly reduce profile padding */
        }

        #customer-sidebar img#sidebarProfileImage {
            width: 60px !important;
            height: 60px !important;
        }

        #customer-sidebar nav {
            padding: 0.5rem; /* tighten nav padding */
        }

        #customer-sidebar nav a {
            padding-top: .45rem;
            padding-bottom: .45rem;
        }

        #customer-sidebar .flex-1 > * { margin-bottom: .25rem; }

        #customer-sidebar .flex-shrink-0.p-3 { padding: .5rem; }
        
        /* === 3. Main Content Area === */
        #main-content {
            /* Fix the main content area so it sits below the fixed header
               and scrolls independently (behaves like the fixed sidebar). */
            position: fixed !important;
            top: var(--header-height);
            left: var(--sidebar-width);
            right: 0;
            bottom: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-sizing: border-box;
            /* Ensure internal scrolling respects the fixed header */
            scroll-padding-top: var(--header-height);
            /* Explicit height to match sidebar (top + bottom anchors already set) */
            height: calc(100vh - var(--header-height));
            /* keep background and stacking order as before */
            z-index: 1;
            background: #f9fafb;
        }
        
        /* Footer removed from this page - no styles required */
        
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
                width: 48px !important;     /* Match header avatar on small screens */
                height: 48px !important;
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
                /* Match the fixed layout using the sidebar variable */
                left: var(--sidebar-width);
                right: 0;
                top: var(--header-height);
                bottom: 0;
                position: fixed !important;
                overflow-y: auto;
                box-sizing: border-box;
                scroll-padding-top: var(--header-height);
                height: calc(100vh - var(--header-height));
            }
            
            /* Footer removed from this page */
        }
        
        /* === Desktop Layout (≥900px) === */
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
                top: var(--header-height);
                bottom: 0;
                height: calc(100vh - var(--header-height));
                z-index: 48;                    /* Above main content but below header (header z-50) */
            }
            
            #customer-sidebar img#sidebarProfileImage {
                width: 48px !important;         /* Smaller on mobile */
                height: 48px !important;
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
                /* Mobile: full width fixed area below header */
                position: fixed !important;
                top: var(--header-height);
                left: 0;
                right: 0;
                bottom: 0;
                overflow-y: auto;
                box-sizing: border-box;
                scroll-padding-top: var(--header-height);
                height: calc(100vh - var(--header-height));
            }
            
            /* Footer removed from this page */
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
                top: var(--header-height);
                left: 0;
                height: calc(100vh - var(--header-height));
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
                top: var(--header-height);
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
                <img id="siteLogo" src="<?php echo htmlspecialchars($_SESSION['logo_path'] ?? '/carwash_project/backend/logo01.png', ENT_QUOTES, 'UTF-8'); ?>" alt="MyCar logo" class="logo-image object-contain rounded-xl shadow-md header-logo sidebar-logo" />
            </div>
            <div class="hidden sm:block">
                <h1 class="text-lg font-bold text-gray-900 leading-tight">MyCar</h1>
                <p class="text-xs text-gray-500 -mt-1">Customer Panel</p>
            </div>
        </div>
        
        <!-- User Menu (shared fragment) -->
        <?php
            // Prepare variables expected by the fragment
            $profile_src = !empty($user_profile_image) ? $user_profile_image : ($base_url . '/frontend/assets/img/default-user.png');
            $home_url = $base_url . '/backend/index.php';
            $logout_url = $base_url . '/backend/includes/logout.php';
            include __DIR__ . '/../includes/profile_header_fragment.php';
        ?>
    </div>
</header>

<!-- ================================
    LAYOUT WRAPPER - Proper Flex Layout Structure
    Sidebar: Fixed below Header (no internal scroll)
     ================================ -->

<!-- Mobile Overlay (backdrop when sidebar is open on mobile, closes sidebar on click)
     NOTE: avoid aria-hidden/tabindex on overlays that can receive focus — make the backdrop non-focusable
-->
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
></div>

<!-- Main Content Wrapper: Takes flex-1 to fill remaining viewport space -->
<div class="flex flex-1">
    
    <!-- ================================
         SIDEBAR - Fixed below header
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
                <span class="text-sm font-medium">Genel Bakış</span>
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
                <span class="text-sm font-medium">Oto Yıkama Seçimi</span>
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
                <span class="text-sm font-medium">Rezervasyonlarım</span>
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
                <span class="text-sm font-medium">Araçlarım</span>
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
                <span class="text-sm font-medium">Geçmiş</span>
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
                <p class="text-gray-600">Hoş geldiniz, <?php echo htmlspecialchars($user_name); ?>!</p>
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
                        <span>12% artış</span>
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
                        <h4 class="text-sm font-semibold text-gray-700">Araç</h4>
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
                <h3 class="text-xl font-bold text-gray-900 mb-6">Hızlı İşlemler</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                    <!-- New Reservation Card -->
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-6 lg:p-8 text-white shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <i class="fas fa-plus-circle text-4xl lg:text-5xl mb-4 opacity-90"></i>
                        <h4 class="text-xl font-bold mb-2">Yeni Rezervasyon</h4>
                        <p class="text-blue-100 mb-6 text-sm lg:text-base">Araç yıkama hizmeti rezervasyonu oluşturun</p>
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
                        <h4 class="text-xl font-bold mb-2">Araç Ekle</h4>
                        <p class="text-green-100 mb-6 text-sm lg:text-base">Yeni araç bilgisi kaydedin</p>
                        <button 
                            @click="currentSection = 'vehicles'"
                            class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 active:bg-green-100 transition-colors inline-flex items-center gap-2 shadow-md"
                        >
                            <span>Araç Ekle</span>
                            <i class="fas fa-arrow-right text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- ========== VEHICLES SECTION ========== -->
    <section x-show="currentSection === 'vehicles'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" x-data="(typeof vehicleManager !== 'undefined') ? vehicleManager() : (window.vehicleManager ? (console.info('Using window.vehicleManager fallback'), window.vehicleManager()) : (console.warn('vehicleManager factory missing � using minimal fallback'), { vehicles: [], showVehicleForm: false, editingVehicle: null, loading: false, message:'', messageType:'', csrfToken: '', imagePreview: '', formData: { brand: '', model: '', license_plate: '', year: '', color: '' } }))" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Araçlarım</h2>
                <p class="text-gray-600">Araçlarınızı yönetin</p>
            </div>
            
            <!-- Action Buttons - Responsive -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <p class="text-sm text-gray-600" x-text="vehicles.length + ' araç kayıtlı'"></p>
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
                        <span>Araç Ekle</span>
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
                                <span>Düzenle</span>
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
                        <p class="text-gray-500 text-lg mb-4">Henüz araç yok</p>
                        <button 
                            @click="openVehicleForm()"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center space-x-2"
                        >
                            <i class="fas fa-plus"></i>
                            <span>İlk Aracınızı Ekleyin</span>
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
                            <h3 class="text-2xl font-bold text-gray-900" x-text="editingVehicle ? 'Araç Düzenle' : 'Yeni Araç Ekle'"></h3>
                            <button @click="closeVehicleForm()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-lg hover:bg-gray-100">
                                <i class="fas fa-times text-2xl"></i>
                            </button>
                        </div>
                    </div>
                    
                    <form id="vehicleForm" @submit.prevent="saveVehicle()" class="p-6" enctype="multipart/form-data">
                        <label for="auto_label_108" class="sr-only">CSRF Token</label>
                        <input type="hidden" name="csrf_token" :value="csrfToken" id="auto_label_108">
                        <label for="auto_label_107" class="sr-only">Action</label>
                        <input type="hidden" name="action" :value="editingVehicle ? 'update' : 'create'" id="auto_label_107">
                        <label for="auto_label_106" class="sr-only">Vehicle ID</label>
                        <input type="hidden" name="id" :value="editingVehicle?.id || ''" id="auto_label_106">
                        
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
                                <label for="vehicle_year" class="block text-sm font-semibold text-gray-700 mb-2">Yıl</label>
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
                                <label for="vehicle_image" class="block text-sm font-semibold text-gray-700 mb-2">Araç Fotoğrafı</label>
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
                            <p class="block text-sm font-semibold text-gray-700 mb-2">Önizleme</p>
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
                                İptal
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
        <section 
            x-show="currentSection === 'profile'" 
            x-transition:enter="transition ease-out duration-300" 
            x-transition:enter-start="opacity-0 transform translate-y-4" 
            x-transition:enter-end="opacity-100 transform translate-y-0" 
            class="space-y-6" 
            style="display: none;"
            x-data="profileSection()"
        >
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Profil Ayarları</h2>
                    <p class="text-gray-600" x-text="editMode ? 'Bilgilerinizi güncelleyin' : 'Profil bilgilerinizi görüntüleyin'"></p>
                </div>
                <button 
                    x-show="!editMode"
                    @click="toggleEdit()"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center gap-2"
                >
                    <i class="fas fa-edit"></i>
                    <span>Düzenle</span>
                </button>
            </div>
            
            <!-- VIEW MODE: Display Profile Info -->
            <div 
                x-show="!editMode"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8"
            >
                <div class="space-y-6">
                    <!-- Profile Header -->
                    <div class="flex items-center gap-6 pb-6 border-b border-gray-200">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-blue-100 bg-gray-100">
                            <img 
                                :src="profileData.profile_image" 
                                alt="Profile" 
                                class="w-full h-full object-cover"
                                onerror="this.src='/carwash_project/frontend/images/default-avatar.svg'"
                            >
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900" x-text="profileData.name"></h3>
                            <p class="text-gray-600 mt-1" x-text="profileData.email"></p>
                        </div>
                    </div>
                    
                    <!-- Profile Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Kullanıcı Adı</label>
                            <p class="text-base text-gray-900" x-text="profileData.username || '-'"></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Telefon</label>
                            <p class="text-base text-gray-900" x-text="profileData.phone || '-'"></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Ev Telefonu</label>
                            <p class="text-base text-gray-900" x-text="profileData.home_phone || '-'"></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">T.C. Kimlik No</label>
                            <p class="text-base text-gray-900" x-text="profileData.national_id || '-'"></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Sürücü Belgesi No</label>
                            <p class="text-base text-gray-900" x-text="profileData.driver_license || '-'"></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Şehir</label>
                            <p class="text-base text-gray-900" x-text="profileData.city || '-'"></p>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-semibold text-gray-500">Adres</label>
                            <p class="text-base text-gray-900" x-text="profileData.address || '-'"></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- EDIT MODE: Profile Form -->
            <div 
                x-show="editMode"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8"
            >
                <form id="profileForm" class="space-y-6" enctype="multipart/form-data" method="POST">
                    <input type="hidden" name="action" value="update_profile">
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
                    <label for="auto_label_105" class="sr-only">Csrf token</label>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" id="auto_label_105">
                    <!-- Profile Image Upload Section -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">Profil Fotoğrafı</h4>
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
                                    Yeni Fotoğraf Yükle
                                </label>
                                <input 
                                    type="file" 
                                    id="profile_image" 
                                    name="profile_image" 
                                    accept="image/jpeg,image/png,image/jpg,image/webp"
                                    class="block w-full text-sm text-gray-900 border-2 border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                >
                                <p class="mt-2 text-xs text-gray-500">JPG, PNG veya WEBP formatında. Maksimum 3MB.</p>
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
                                placeholder="Adınız Soyadınız"
                            >
                        </div>
                        
                        <!-- Username -->
                        <div class="mb-4">
                            <label for="profile_username" class="block text-sm font-semibold text-gray-700 mb-2">
                                Kullanıcı Adı <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                id="profile_username"
                                name="username"
                                value="<?php echo htmlspecialchars($userData['username'] ?? $_SESSION['username'] ?? ''); ?>"
                                required
                                autocomplete="username"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                placeholder="kullanici_adi"
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
                            <p class="mt-1 text-xs text-gray-500">11 haneli T.C. Kimlik numaranızı girin</p>
                        </div>
                        
                        <!-- Driver License (Optional) -->
                        <div class="mb-4">
                            <label for="profile_driver_license" class="block text-sm font-semibold text-gray-700 mb-2">
                                Sürücü Belgesi No
                            </label>
                            <input 
                                type="text"
                                id="profile_driver_license"
                                name="driver_license"
                                value="<?php echo htmlspecialchars($user_driver_license); ?>"
                                placeholder="A1234567"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                            <p class="mt-1 text-xs text-gray-500">İsteğe bağlı alan</p>
                        </div>
                        
                        <!-- City -->
                        <div class="mb-4">
                            <label for="profile_city" class="block text-sm font-semibold text-gray-700 mb-2">Şehir</label>
                            <input 
                                type="text"
                                id="profile_city"
                                name="city"
                                value="<?php echo htmlspecialchars($user_city); ?>"
                                placeholder="İstanbul"
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
                        <h4 class="text-lg font-bold text-gray-900 mb-4">Şifre Değiştir</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <!-- Current Password -->
                            <div class="mb-4">
                                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">Mevcut Şifre</label>
                                <input 
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    autocomplete="current-password"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="••••••••"
                                >
                            </div>
                            
                            <!-- New Password -->
                            <div class="mb-4">
                                <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">Yeni Şifre</label>
                                <input 
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="••••••••"
                                >
                            </div>
                            <!-- Confirm New Password -->
                            <div class="mb-4">
                                <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">Yeni Şifre (Tekrar)</label>
                                <input 
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="••••••••"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <div class="hidden mb-4 p-4 border-2 border-green-500 bg-green-50 text-green-700 rounded-lg" id="profile-success">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>Profil başarıyla güncellendi!</span>
                        </div>
                    </div>
                    
                    <div class="hidden mb-4 p-4 border-2 border-red-500 bg-red-50 text-red-600 rounded-lg" id="profile-error">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Bir hata oluştu. Lütfen tekrar deneyin.</span>
                        </div>
                    </div>

                    <!-- Global message component (reusable) -->
                    <div id="global-message" class="fixed top-6 right-6 z-50 hidden max-w-md w-full pointer-events-auto">
                        <div id="global-message-box" class="rounded-lg shadow-lg p-4 flex items-start gap-3">
                            <div id="global-message-icon" class="mt-0.5"></div>
                            <div class="flex-1">
                                <div id="global-message-text" class="font-medium"></div>
                                <div id="global-message-sub" class="text-sm mt-1 opacity-80"></div>
                            </div>
                            <button id="global-message-close" aria-label="Close message" class="ml-3 text-gray-600 hover:text-gray-900">&times;</button>
                        </div>
                    </div>

                    <!-- Form-level validation errors (populated by client-side or server responses) -->
                    <div id="form-errors" class="hidden mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        <strong class="block font-semibold mb-2">Lütfen aşağıdaki hataları düzeltin:</strong>
                        <ul id="form-errors-list" class="list-disc pl-5 space-y-1"></ul>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
                        <button 
                            type="button"
                            @click="toggleEdit()"
                            class="w-full sm:w-auto h-11 px-6 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 active:bg-gray-100 transition-colors"
                        >
                            İptal
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
                <p class="text-gray-600">Yardıma mı ihtiyacınız var? Bize ulaşın</p>
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
                            placeholder="Sorununuzun kısa açıklaması"
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
                            <option value="">Kategori seçin</option>
                            <option value="reservation">Rezervasyon</option>
                            <option value="payment">Ödeme</option>
                            <option value="vehicle">Araç Bilgileri</option>
                            <option value="account">Hesap Ayarları</option>
                            <option value="other">Diğer</option>
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
                            placeholder="Sorununuzu detaylı olarak açıklayın"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors resize-none"
                        ></textarea>
                        <p class="mt-2 text-xs text-gray-500">Minimum 20 karakter</p>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <div class="hidden mb-4 p-4 border-2 border-green-500 bg-green-50 text-green-700 rounded-lg" id="support-success">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>Mesajınız başarıyla gönderildi!</span>
                        </div>
                    </div>
                    
                    <div class="hidden mb-4 p-4 border-2 border-red-500 bg-red-50 text-red-600 rounded-lg" id="support-error">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Mesaj gönderilemedi. Lütfen tüm alanları doldurun.</span>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            class="w-full sm:w-auto h-11 px-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg active:shadow-md transition-all inline-flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-paper-plane text-sm"></i>
                            <span>Gönder</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- ========== SETTINGS SECTION ========== -->
        <section x-show="currentSection === 'settings'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Ayarlar</h2>
                <p class="text-gray-600">Hesap ayarlarınızı yönetin</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Bildirim Tercihleri</h3>
                <div class="flex flex-col gap-4">
                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">E-posta Bildirimleri</h4>
                            <p class="text-sm text-gray-600">Rezervasyon onayları ve güncellemeler</p>
                        </div>
                        <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_171" aria-label="E-posta Bildirimleri">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">SMS Bildirimleri</h4>
                            <p class="text-sm text-gray-600">Acil durumlar için SMS</p>
                        </div>
                        <input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_172" aria-label="SMS Bildirimleri">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">Promosyon Bildirimleri</h4>
                            <p class="text-sm text-gray-600">İndirim ve kampanya duyuruları</p>
                        </div>
                        <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_173" aria-label="Promosyon Bildirimleri">
                    </label>
                </div>

                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-xl font-bold mb-6">Güvenlik</h3>
                    <div class="space-y-4">
                        <button class="w-full text-left p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                            <h4 class="font-bold">Şifre Değiştir</h4>
                            <p class="text-sm text-gray-600">Hesap güvenliğiniz için şifrenizi güncelleyin</p>
                        </button>

                        <button class="w-full text-left p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                            <h4 class="font-bold">İki Faktörlü Doğrulama</h4>
                            <p class="text-sm text-gray-600">Ek güvenlik katmanı ekleyin</p>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
                <!-- ========== CARWASH SELECTION SECTION (Extracted from customer_profile.html) ========== -->
                <section x-show="currentSection === 'carWashSelection'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" style="display: none;">
                        <div class="mb-8">
                                <h2 class="text-3xl font-bold text-gray-800 mb-2">Oto Yıkama Seçimi</h2>
                                <p class="text-gray-600">Size en uygun oto yıkama merkezini bulun ve rezervasyon yapın.</p>
                        </div>

                        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Filtreleme Seçenekleri</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="cityFilter" class="block text-sm font-bold text-gray-700 mb-2">Şehir</label>
                                    <?php
                                        // Fetch carwashes from DB for dynamic city/district lists and client-side filtering
                                        // Use canonical `carwashes` table and alias columns to the keys the frontend expects
                                        $carwashes = [];
                                        $carwash_error = null;
                                        try {
                                            // Runtime-detect which table exists and prefer a table that contains rows
                                            $pdo = $db->getPdo();
                                            $tblExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tbl");

                                            // Use canonical `carwashes` table only for customer selection UI
                                            $carwashes = [];
                                            try {
                                                $pdo = $db->getPdo();
                                                $tblExistsStmt->execute(['tbl' => 'carwashes']);
                                                if ((int)$tblExistsStmt->fetchColumn() > 0) {
                                                    // Use a full-row select so new/changed columns are automatically included
                                                    $sql = "SELECT * FROM carwashes ORDER BY name";
                                                    $carwashes = $db->fetchAll($sql);
                                                } else {
                                                    // No canonical table found — surface clear message to JavaScript/UI
                                                    throw new Exception('No `carwashes` table found in database');
                                                }
                                            } catch (Exception $e) {
                                                $carwash_error = $e->getMessage();
                                                $carwashes = [];
                                            }

                                            // Normalize rows to ensure consistent keys for the frontend
                                            foreach ($carwashes as &$cw) {
                                                // name
                                                if (empty($cw['name']) && !empty($cw['business_name'])) $cw['name'] = $cw['business_name'];

                                                // phone: prefer phone, then mobile_phone, then social_media
                                                if (empty($cw['phone'])) {
                                                    if (!empty($cw['mobile_phone'])) $cw['phone'] = $cw['mobile_phone'];
                                                    elseif (!empty($cw['contact_phone'])) $cw['phone'] = $cw['contact_phone'] ?? '';
                                                    else {
                                                        // try social_media JSON
                                                        if (!empty($cw['social_media'])) {
                                                            $sm = json_decode($cw['social_media'], true);
                                                            if (is_array($sm)) {
                                                                foreach (['mobile_phone','mobile','phone','telephone','tel'] as $k) {
                                                                    if (!empty($sm[$k])) { $cw['phone'] = $sm[$k]; break; }
                                                                }
                                                                if (empty($cw['phone']) && isset($sm['whatsapp'])) {
                                                                    if (is_array($sm['whatsapp'])) $cw['phone'] = $sm['whatsapp']['number'] ?? $sm['whatsapp']['phone'] ?? $cw['phone'];
                                                                    elseif (is_string($sm['whatsapp'])) $cw['phone'] = $sm['whatsapp'];
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                // logo path normalization
                                                if (empty($cw['logo_path'])) {
                                                    if (!empty($cw['featured_image'])) $cw['logo_path'] = $cw['featured_image'];
                                                }

                                                // working_hours normalization
                                                if (!empty($cw['working_hours']) && is_string($cw['working_hours'])) {
                                                    $decoded = json_decode($cw['working_hours'], true);
                                                    $cw['working_hours'] = $decoded === null ? $cw['working_hours'] : $decoded;
                                                }

                                                // Provide defaults for missing keys used by frontend
                                                $cw['city'] = $cw['city'] ?? '';
                                                $cw['district'] = $cw['district'] ?? '';
                                                $cw['address'] = $cw['address'] ?? '';
                                                $cw['status'] = $cw['status'] ?? '';
                                                $cw['rating'] = isset($cw['rating']) ? (float)$cw['rating'] : 4.6;
                                                $cw['services'] = isset($cw['services']) ? (is_string($cw['services']) ? (json_decode($cw['services'], true) ?: []) : ($cw['services'] ?: [])) : [];
                                            }
                                            unset($cw);
                                        } catch (Exception $e) {
                                            // Keep $carwashes empty and record error for JS display
                                            $carwash_error = $e->getMessage();
                                            $carwashes = [];
                                        }

                                        // Collect unique cities for the city filter
                                        $cities = [];
                                        $districtsByCity = [];
                                        foreach ($carwashes as $cw) {
                                            $city = isset($cw['city']) ? trim($cw['city']) : null;
                                            $district = isset($cw['district']) ? trim($cw['district']) : null;
                                            if ($city !== null && $city !== '') {
                                                $cities[$city] = true;
                                                if (!isset($districtsByCity[$city])) $districtsByCity[$city] = [];
                                                if ($district !== null && $district !== '' && !in_array($district, $districtsByCity[$city], true)) {
                                                    $districtsByCity[$city][] = $district;
                                                }
                                            }
                                        }

                                        // Sort city names for consistent UI
                                        $cities = array_keys($cities);
                                        sort($cities, SORT_STRING | SORT_FLAG_CASE);
                                    ?>
                                    <select id="cityFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <option value="">Tüm Şehirler</option>
                                        <?php foreach ($cities as $c): ?>
                                            <option><?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="districtFilter" class="block text-sm font-bold text-gray-700 mb-2">Mahalle</label>
                                    <select id="districtFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <option value="">Tüm Mahalleler</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="carWashNameFilter" class="block text-sm font-bold text-gray-700 mb-2">CarWash Adı</label>
                                    <input type="text" id="carWashNameFilter" onkeyup="filterCarWashes()" placeholder="CarWash adı girin..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                </div>
                                <div class="flex items-end">
                                    <label for="favoriteFilter" class="flex items-center cursor-pointer">
                                        <input id="favoriteFilter" type="checkbox" onchange="filterCarWashes()" class="mr-2">
                                        Sadece Favoriler
                                    </label>
                                </div>
                            </div>
                        </div>

                            <div id="carWashList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php if (empty($carwashes)): ?>
                                    <div class="col-span-full text-center text-gray-600 py-8">No carwashes available.</div>
                                <?php else: ?>
                                    <!-- Initial server-rendered cards (client-side filtering will replace content when active) -->
                                    <?php foreach ($carwashes as $cw): ?>
                                        <?php
                                            $cw_id = htmlspecialchars($cw['id'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_name = htmlspecialchars($cw['name'] ?? 'Unnamed', ENT_QUOTES, 'UTF-8');
                                            $cw_address = htmlspecialchars($cw['address'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_phone = htmlspecialchars($cw['phone'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_city = htmlspecialchars($cw['city'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_district = htmlspecialchars($cw['district'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_status = htmlspecialchars($cw['status'] ?? '', ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <div data-id="<?php echo $cw_id; ?>" data-name="<?php echo $cw_name; ?>" class="bg-white rounded-2xl p-6 card-hover shadow-lg flex flex-col">
                                            <div class="flex justify-between items-start mb-4">
                                                <div>
                                                    <h4 class="font-bold text-lg"><?php echo $cw_name; ?></h4>
                                                    <p class="text-sm text-gray-500"><?php echo ($cw_city || $cw_district) ? ($cw_city . ' • ' . $cw_district) : $cw_address; ?></p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm text-gray-500"><?php echo $cw_status; ?></p>
                                                </div>
                                            </div>
                                            <?php if (!empty($cw_address)): ?><p class="text-sm text-gray-600 mb-2"><i class="fas fa-map-marker-alt mr-2"></i><?php echo $cw_address; ?></p><?php endif; ?>
                                            <?php if (!empty($cw_phone)): ?><p class="text-sm text-gray-600 mb-4"><i class="fas fa-phone mr-2"></i><?php echo $cw_phone; ?></p><?php endif; ?>
                                            <div class="mt-auto">
                                                <button data-id="<?php echo $cw_id; ?>" data-name="<?php echo $cw_name; ?>" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg select-for-reservation">Rezervasyon Yap</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </div>

                            <!-- Inline holder for reservation form when opened from carwash cards -->
                            <div id="carWashInlineFormHolder" class="hidden p-6 bg-white rounded-2xl shadow-lg mb-8"></div>

                            <script>
                        (function(){
                            'use strict';

                            // Provide initial data to client-side filtering from server
                            const allCarWashes = <?php echo json_encode(array_values($carwashes), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || [];
                            const districtsByCity = <?php echo json_encode($districtsByCity, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || {};
                            const carwashLoadError = <?php echo json_encode($carwash_error, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || null;

                            // Log how many records were loaded from server for quick debugging
                            try { console.log('Carwash records available:', Array.isArray(allCarWashes) ? allCarWashes.length : 0); } catch(e){}

                            if (carwashLoadError) {
                                console.error('Carwashes load error:', carwashLoadError);
                                const list = document.getElementById('carWashList');
                                if (list) list.innerHTML = '<div class="col-span-full text-center text-red-600 py-8">Hizmetler alınırken bir hata oluştu.</div>';
                            }

                            // Helper to safely find elements
                            function $id(id){ return document.getElementById(id); }

                            function loadDistrictOptions(){
                                const cityFilter = $id('cityFilter');
                                const districtFilter = $id('districtFilter');
                                const selectedCity = cityFilter.value;

                                districtFilter.innerHTML = '<option value="">Tüm Mahalleler</option>';
                                if (selectedCity && districtsByCity[selectedCity]){
                                    districtsByCity[selectedCity].forEach(d => {
                                        const opt = document.createElement('option'); opt.value = d; opt.textContent = d; districtFilter.appendChild(opt);
                                    });
                                }
                            }

                            function filterCarWashes(){
                                const cityFilter = ($id('cityFilter')?.value || '').toLowerCase();
                                const districtFilter = ($id('districtFilter')?.value || '').toLowerCase();
                                const carWashNameFilter = ($id('carWashNameFilter')?.value || '').toLowerCase();
                                const favoriteFilter = $id('favoriteFilter')?.checked;
                                const carWashListDiv = $id('carWashList');
                                if (!carWashListDiv) return;
                                carWashListDiv.innerHTML = '';

                                const filteredWashes = allCarWashes.filter(carWash => {
                                    const matchesCity = !cityFilter || carWash.city.toLowerCase().includes(cityFilter);
                                    const matchesDistrict = !districtFilter || carWash.district.toLowerCase().includes(districtFilter);
                                    const matchesName = !carWashNameFilter || carWash.name.toLowerCase().includes(carWashNameFilter);
                                    const matchesFavorite = !favoriteFilter || carWash.isFavorite;
                                    return matchesCity && matchesDistrict && matchesName && matchesFavorite;
                                });

                                if (filteredWashes.length === 0) {
                                    carWashListDiv.innerHTML = '<p class="text-gray-600 text-center col-span-full">Seçiminize uygun oto yıkama bulunamadı.</p>';
                                    return;
                                }

                                console.log('Carwash records available:', filteredWashes.length);
                                filteredWashes.forEach(carWash => {
                                    const div = document.createElement('div');
                                    div.className = 'bg-white rounded-2xl p-6 card-hover shadow-lg flex flex-col';
                                    // store id/name attributes to allow the whole card to be clickable
                                    div.setAttribute('data-id', carWash.id || '');
                                    div.setAttribute('data-name', carWash.name || '');
                                    div.innerHTML = `
                                        <div class="flex justify-between items-start mb-4">
                                            <div>
                                                <h4 class="font-bold text-lg">${escapeHtml(carWash.name)}</h4>
                                                <p class="text-sm text-gray-500">${escapeHtml(carWash.city)} • ${escapeHtml(carWash.district)}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-yellow-400 font-semibold">${carWash.rating}</p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-2"><i class="fas fa-map-marker-alt mr-2"></i>${escapeHtml(carWash.district)}, ${escapeHtml(carWash.city)}</p>
                                        <p class="text-sm text-gray-600 mb-4"><i class="fas fa-star text-yellow-400 mr-2"></i>${carWash.rating} (${Math.floor(Math.random()*100)} yorum)</p>
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            ${ (carWash.services || []).map(s=>`<span class="px-2 py-1 text-xs bg-gray-100 rounded">${escapeHtml(s)}</span>`).join('') }
                                        </div>
                                        <button data-id="${carWash.id || ''}" data-name="${escapeAttr(carWash.name)}" class="mt-auto gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg select-for-reservation">Rezervasyon Yap</button>
                                    `;
                                    carWashListDiv.appendChild(div);

                                    // Make the whole card clickable (except when clicking controls like links or buttons)
                                    div.addEventListener('click', function(evt){
                                        const tag = (evt.target && evt.target.tagName) ? evt.target.tagName.toLowerCase() : '';
                                        if (tag === 'a' || tag === 'button' || evt.target.closest && evt.target.closest('button, a')) return;
                                        const id = this.getAttribute('data-id') || '';
                                        const name = this.getAttribute('data-name') || '';
                                        if (id) selectCarWashForReservation(name, id);
                                    });
                                });

                                // Attach reservation handlers
                                document.querySelectorAll('.select-for-reservation').forEach(btn => {
                                    btn.removeEventListener('click', btn._selHandler);
                                    btn._selHandler = function(){
                                        const name = this.getAttribute('data-name') || '';
                                        const id = this.getAttribute('data-id') || '';
                                        selectCarWashForReservation(name, id);
                                    };
                                    btn.addEventListener('click', btn._selHandler);
                                });
                            }

                            function selectCarWashForReservation(carWashName, carWashId){
                                // Show the reservation form inside the carWashSelection section (no section switch)
                                try {
                                    const holder = document.getElementById('carWashInlineFormHolder');
                                    const origFormWrapper = document.getElementById('newReservationForm'); // wrapper DIV for the form
                                    if (holder && origFormWrapper) {
                                        // remember original parent id so we can move it back later
                                        if (!origFormWrapper.dataset.originalParentId) {
                                            // Use a stable restore point inside the reservations section
                                            origFormWrapper.dataset.originalParentId = 'reservationFormRestorePoint';
                                        }

                                        // move into holder
                                        holder.appendChild(origFormWrapper);
                                        holder.classList.remove('hidden');
                                        // hide the grid to focus on form
                                        document.getElementById('carWashList')?.classList.add('hidden');
                                        origFormWrapper.classList.remove('hidden');
                                    }
                                } catch (e) {
                                    console.warn('Could not relocate reservation form into carwash section', e);
                                }

                                // Populate the location field inside the moved form
                                const loc = $id('location');
                                if (loc) {
                                    // Use option value = id (preferred) and text = name
                                    let opt = Array.from(loc.options).find(o => o.value === String(carWashId) || o.textContent === carWashName);
                                    if (!opt) {
                                        opt = document.createElement('option');
                                        opt.value = carWashId || carWashName;
                                        opt.textContent = carWashName || carWashId;
                                        loc.appendChild(opt);
                                    }
                                    // select by id if available, otherwise by name
                                    loc.value = carWashId || carWashName;
                                }

                                // Set hidden id field if present
                                const locId = $id('location_id');
                                if (locId && carWashId) locId.value = carWashId;
                            }

                            function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>\"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }

                                // -----------------------------
                                // Bookings (Reservations) Management
                                // -----------------------------
                                function getCsrfToken() {
                                    return (window.CONFIG && window.CONFIG.CSRF_TOKEN) || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';
                                }

                                function statusLabel(status) {
                                    if (!status) return '<span class="px-2 py-1 rounded-full text-xs bg-gray-200 text-gray-700">Bilinmiyor</span>';
                                    const s = status.toLowerCase();
                                    if (s === 'confirmed' || s === 'paid') return '<span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Onaylandı</span>';
                                    if (s === 'pending' || s === 'processing') return '<span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Beklemede</span>';
                                    if (s === 'cancelled' || s === 'cancel') return '<span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">İptal Edildi</span>';
                                    return '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">'+escapeHtml(status)+'</span>';
                                }

                                async function loadBookings() {
                                    const tbody = document.getElementById('reservationsTableBody');
                                    if (!tbody) return;
                                    // show loading
                                    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Yükleniyor...</td></tr>';
                                    try {
                                        const resp = await fetch('/carwash_project/backend/api/bookings/list.php', {
                                            credentials: 'same-origin',
                                            headers: { 'Accept': 'application/json' }
                                        });

                                        // Read response as text first so we can handle empty/non-JSON responses gracefully
                                        const text = await resp.text();

                                        if (!resp.ok) {
                                            console.error('Bookings API responded with status', resp.status, text);
                                            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-red-500">Rezervasyonlar yüklenemedi.</td></tr>';
                                            return;
                                        }

                                        if (!text || text.trim() === '') {
                                            // No content — treat as empty bookings list
                                            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Aktif rezervasyonunuz yok.</td></tr>';
                                            return;
                                        }

                                        let result;
                                        try {
                                            result = JSON.parse(text);
                                        } catch (parseErr) {
                                            console.error('Failed to parse bookings JSON:', parseErr, text);
                                            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-red-500">Rezervasyon verisi alınamadı.</td></tr>';
                                            return;
                                        }

                                        if (!result || !result.success) {
                                            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-red-500">Rezervasyonlar yüklenemedi.</td></tr>';
                                            return;
                                        }
                                        // Normalize rows: bookings/list.php may merge rows into top-level response
                                        let rows = [];
                                        if (Array.isArray(result.data)) rows = result.data;
                                        else {
                                            for (const k in result) {
                                                if (k === 'success' || k === 'message') continue;
                                                // numeric keys contain rows
                                                if (!isNaN(k)) rows.push(result[k]);
                                            }
                                        }

                                        if (!rows || rows.length === 0) {
                                            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Aktif rezervasyonunuz yok.</td></tr>';
                                            return;
                                        }

                                        // Build rows HTML
                                        const html = rows.map(r => {
                                            const id = r.id || '';
                                            const service = r.service_name || r.service_type || r.service || '';
                                            const date = r.booking_date || r.date || '';
                                            const time = r.booking_time || r.time || '';
                                            const location = r.carwash_name || r.location || '';
                                            const price = r.total_price || r.price || 0;
                                            const status = r.status || '';
                                            // Store useful attributes for edit
                                            const dataAttrs = 'data-booking="'+encodeURIComponent(JSON.stringify({id:id,carwash_id:r.carwash_id||r.location_id,service_id:r.service_id||null,date:date,time:time,notes:r.notes||''}))+'"';
                                            return '<tr '+dataAttrs+' class="hover:bg-gray-50">'
                                                +'<td class="px-6 py-4"><div><div class="font-medium">'+escapeHtml(service)+'</div><div class="text-sm text-gray-500">'+escapeHtml(r.user_name || r.vehicle || '')+'</div></div></td>'
                                                +'<td class="px-6 py-4 text-sm">'+escapeHtml(date)+'<br>'+escapeHtml(time)+'</td>'
                                                +'<td class="px-6 py-4 text-sm">'+escapeHtml(location)+'</td>'
                                                +'<td class="px-6 py-4">'+statusLabel(status)+'</td>'
                                                +'<td class="px-6 py-4 font-medium">'+(price ? ('₺'+parseFloat(price).toFixed(2)) : '')+'</td>'
                                                +'<td class="px-6 py-4 text-sm">'
                                                    +'<button class="edit-booking-btn text-blue-600 hover:text-blue-900 mr-3" data-id="'+escapeHtml(id)+'">Düzenle</button>'
                                                    +'<button class="cancel-booking-btn text-red-600 hover:text-red-900" data-id="'+escapeHtml(id)+'">İptal</button>'
                                                +'</td>'
                                            +'</tr>';
                                        }).join('');

                                        tbody.innerHTML = html;

                                    } catch (err) {
                                        console.error('Load bookings error', err);
                                        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-red-500">Sunucu hatası.</td></tr>';
                                    }
                                }

                                // Expose bookings helpers to global scope so other script blocks can call them
                                // (these functions are defined inside this IIFE but needed elsewhere on the page)
                                try {
                                    window.loadBookings = loadBookings;
                                    window.showEditBookingModalFromRow = showEditBookingModalFromRow;
                                    window.hideEditBookingModal = hideEditBookingModal;
                                    window.submitEditBooking = submitEditBooking;
                                    window.cancelBookingById = cancelBookingById;
                                } catch (e) {
                                    // ignore if assignment fails in restricted environments
                                }

                                function showEditBookingModalFromRow(tr) {
                                    try {
                                        const d = tr.getAttribute('data-booking');
                                        if (!d) return;
                                        const obj = JSON.parse(decodeURIComponent(d));
                                        document.getElementById('edit_booking_id').value = obj.id || '';
                                        document.getElementById('edit_carwash_id').value = obj.carwash_id || '';
                                        document.getElementById('edit_service_id').value = obj.service_id || '';
                                        document.getElementById('edit_date').value = obj.date || '';
                                        document.getElementById('edit_time').value = obj.time || '';
                                        document.getElementById('edit_notes').value = obj.notes || '';
                                        const modal = document.getElementById('editBookingModal');
                                        modal.classList.remove('hidden');
                                        modal.classList.add('flex','items-center','justify-center');
                                    } catch (e) { console.error(e); }
                                }

                                function hideEditBookingModal() {
                                    const modal = document.getElementById('editBookingModal');
                                    if (!modal) return;
                                    modal.classList.add('hidden');
                                    modal.classList.remove('flex','items-center','justify-center');
                                }

                                async function submitEditBooking(evt) {
                                    evt.preventDefault();
                                    const bookingId = document.getElementById('edit_booking_id').value;
                                    const carwashId = document.getElementById('edit_carwash_id').value;
                                    const serviceId = document.getElementById('edit_service_id').value;
                                    const date = document.getElementById('edit_date').value;
                                    const time = document.getElementById('edit_time').value;
                                    const notes = document.getElementById('edit_notes').value;
                                    if (!bookingId) return alert('Booking id missing');
                                    const fd = new FormData();
                                    fd.append('booking_id', bookingId);
                                    fd.append('carwash_id', carwashId);
                                    fd.append('service_id', serviceId);
                                    fd.append('date', date);
                                    fd.append('time', time);
                                    fd.append('notes', notes);
                                    fd.append('csrf_token', getCsrfToken());
                                    try {
                                        const resp = await fetch('/carwash_project/backend/api/bookings/update.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                                        const result = await resp.json();
                                        if (result && result.success) {
                                            hideEditBookingModal();
                                            await loadBookings();
                                            return;
                                        }
                                        alert((result && result.errors && result.errors.join) ? result.errors.join('\n') : (result && result.message) || 'Güncelleme başarısız');
                                    } catch (err) {
                                        console.error('Edit booking error', err);
                                        alert('Sunucu hatası oluştu.');
                                    }
                                }

                                async function cancelBookingById(id) {
                                    if (!confirm('Rezervasyonu iptal etmek istiyor musunuz?')) return;
                                    const fd = new FormData();
                                    fd.append('booking_id', id);
                                    fd.append('csrf_token', getCsrfToken());
                                    try {
                                        const resp = await fetch('/carwash_project/backend/api/bookings/cancel.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                                        const result = await resp.json();
                                        if (result && result.success) {
                                            await loadBookings();
                                            return;
                                        }
                                        alert(result && result.error ? result.error : 'İptal başarısız');
                                    } catch (err) { console.error(err); alert('Sunucu hatası'); }
                                }
                            function escapeAttr(s){ return (s||'').replace(/\"/g,'&quot;'); }

                            // Initialize controls when DOM ready
                            document.addEventListener('DOMContentLoaded', function(){
                                loadDistrictOptions();
                                filterCarWashes();
                                // also update districts when city changes
                                $id('cityFilter')?.addEventListener('change', function(){ loadDistrictOptions(); filterCarWashes(); });
                                $id('districtFilter')?.addEventListener('change', filterCarWashes);
                                $id('carWashNameFilter')?.addEventListener('input', filterCarWashes);
                                $id('favoriteFilter')?.addEventListener('change', filterCarWashes);

                                // Attach click handlers to any initial server-rendered cards so whole-card click works
                                try {
                                    document.querySelectorAll('#carWashList > div[data-id]').forEach(function(card){
                                        card.addEventListener('click', function(evt){
                                            const tag = (evt.target && evt.target.tagName) ? evt.target.tagName.toLowerCase() : '';
                                            if (tag === 'a' || tag === 'button' || (evt.target.closest && evt.target.closest('button, a'))) return;
                                            const id = this.getAttribute('data-id') || '';
                                            const name = this.getAttribute('data-name') || '';
                                            if (id) selectCarWashForReservation(name, id);
                                        });
                                    });

                                    // Ensure server-rendered "Rezervasyon Yap" buttons also trigger selection
                                    document.querySelectorAll('.select-for-reservation').forEach(btn => {
                                        btn.removeEventListener('click', btn._selHandler);
                                        btn._selHandler = function(){
                                            const name = this.getAttribute('data-name') || '';
                                            const id = this.getAttribute('data-id') || '';
                                            selectCarWashForReservation(name, id);
                                        };
                                        btn.addEventListener('click', btn._selHandler);
                                    });
                                } catch (e) { console.warn('Attach initial carwash handlers failed', e); }
                            });

                        })();
                        </script>
                                </section>

                <!-- ========== RESERVATIONS SECTION (Inserted from customer_profile.html) ========== -->
                <section id="reservations" x-show="currentSection === 'reservations'" class="space-y-6" style="display: none;">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Rezervasyonlarım</h2>
                        <p class="text-gray-600">Tüm rezervasyonlarınızı görüntüleyin ve yönetin</p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <!-- Reservation List View -->
                        <div id="reservationListView">
                            <div class="p-6 border-b">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-xl font-bold">Aktif Rezervasyonlar</h3>
                                    <button type="button" id="newReservationBtn" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                                        <i class="fas fa-plus mr-2"></i>Yeni Rezervasyon
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih/Saat</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Konum</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reservationsTableBody" class="divide-y divide-gray-200">
                                        <tr id="reservationsLoadingRow">
                                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Yükleniyor...</td>
                                        </tr>
                                        <!-- Reservation rows will be injected here -->
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- New Reservation Form -->
                        <div id="reservationFormRestorePoint"></div>
                        <div id="newReservationForm" class="p-6 hidden">
                            <h3 class="text-xl font-bold mb-6">Yeni Rezervasyon Oluştur</h3>
                            <form id="newReservationFormElement" class="space-y-6">
                                <div>
                                    <label for="service" class="block text-sm font-bold text-gray-700 mb-2">Hizmet Seçin</label>
                                    <select id="service" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <option value="">Hizmet Seçiniz</option>
                                        <option value="Dış Yıkama">Dış Yıkama</option>
                                        <option value="Dış Yıkama + İç Temizlik">Dış Yıkama + İç Temizlik</option>
                                        <option value="Tam Detaylandırma">Tam Detaylandırma</option>
                                        <option value="Motor Temizliği">Motor Temizliği</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="vehicle" class="block text-sm font-bold text-gray-700 mb-2">Araç Seçin</label>
                                    <select id="vehicle" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <option value="">Araç Seçiniz</option>
                                        <option value="Toyota Corolla - 34 ABC 123">Toyota Corolla - 34 ABC 123</option>
                                        <option value="Honda Civic - 34 XYZ 789">Honda Civic - 34 XYZ 789</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="reservationDate" class="block text-sm font-bold text-gray-700 mb-2">Tarih</label>
                                        <input type="date" id="reservationDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div>
                                            <label for="reservationTime" class="block text-sm font-bold text-gray-700 mb-2">Saat</label>
                                            <input type="time" id="reservationTime" name="reservationTime" step="60" min="00:00" max="23:59" placeholder="00:00" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" aria-label="Saat (24 saat formatı)">
                                            <p class="text-sm text-gray-500 mt-2">Lütfen saat seçimini 24 saat formatında girin (örn. 08:30 veya 18:45).</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="location" class="block text-sm font-bold text-gray-700 mb-2">Konum</label>
                                    <select id="location" name="location" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <option value="">Konum Seçiniz</option>
                                        <?php if (!empty($carwashes)): ?>
                                                <?php foreach ($carwashes as $cw_opt): ?>
                                                    <option value="<?php echo htmlspecialchars($cw_opt['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cw_opt['name'] ?? $cw_opt['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- fallback options left minimal -->
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" id="location_id" name="location_id" value="">
                                </div>

                                <div>
                                    <label for="notes" class="block text-sm font-bold text-gray-700 mb-2">Ek Notlar (İsteğe Bağlı)</label>
                                    <textarea id="notes" rows="3" placeholder="Özel istekleriniz veya notlarınız..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <button type="button" id="cancelNewReservation" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors">Geri Dön</button>
                                    <button type="submit" id="submitNewReservation" class="gradient-bg text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg transition-all"><i class="fas fa-calendar-plus mr-2"></i>Rezervasyon Yap</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Edit Booking Modal -->
                <div id="editBookingModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden">
                    <div class="bg-white rounded-lg w-full max-w-lg p-6">
                        <h3 class="text-lg font-bold mb-4">Rezervasyonu Düzenle</h3>
                        <form id="editBookingForm" class="space-y-4">
                            <input type="hidden" id="edit_booking_id" name="booking_id" value="">
                            <input type="hidden" id="edit_carwash_id" name="carwash_id" value="">
                            <input type="hidden" id="edit_service_id" name="service_id" value="">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tarih</label>
                                <input type="date" id="edit_date" name="date" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Saat</label>
                                <input type="time" id="edit_time" name="time" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notlar</label>
                                <textarea id="edit_notes" name="notes" rows="3" class="w-full px-3 py-2 border rounded"></textarea>
                            </div>

                            <div class="flex justify-end space-x-2">
                                <button type="button" id="editCancelBtn" class="px-4 py-2 border rounded">İptal</button>
                                <button type="submit" id="editSaveBtn" class="px-4 py-2 bg-blue-600 text-white rounded">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Other sections (carWashSelection, history) would follow the same pattern -->

                <script>
                (function(){
                    'use strict';

                    // Normalize various time input formats (e.g., user-typed "8:30 PM") to 24-hour HH:MM
                    function normalizeTimeTo24(s) {
                        if (!s) return '';
                        s = String(s).trim();

                        // Matches formats like "8:30 PM" or "08:30PM"
                        const ampm = s.match(/^(\d{1,2}):(\d{2})\s*([AaPp][Mm])$/);
                        if (ampm) {
                            let hh = parseInt(ampm[1], 10);
                            const mm = ampm[2];
                            const ap = ampm[3].toLowerCase();
                            if (ap === 'pm' && hh < 12) hh += 12;
                            if (ap === 'am' && hh === 12) hh = 0;
                            return (hh < 10 ? '0' + hh : '' + hh) + ':' + mm;
                        }

                        // Matches HH:MM or H:MM (possibly with seconds) — keep only HH:MM
                        const simple = s.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
                        if (simple) {
                            let hh = parseInt(simple[1], 10);
                            const mm = simple[2];
                            if (hh >= 0 && hh <= 24) {
                                return (hh < 10 ? '0' + hh : '' + hh) + ':' + mm;
                            }
                        }

                        // Fallback: return original string
                        return s;
                    }
                    // Show new reservation form (optionally inside the carwash section)
                    function showNewReservationForm(targetHolderId){
                        const origFormWrapper = document.getElementById('newReservationForm');
                        if (targetHolderId && origFormWrapper) {
                            const holder = document.getElementById(targetHolderId);
                            if (holder) {
                                // remember original parent id to restore later
                                if (!origFormWrapper.dataset.originalParentId) {
                                    const parent = origFormWrapper.parentElement;
                                    if (parent && parent.id) origFormWrapper.dataset.originalParentId = parent.id;
                                }
                                holder.appendChild(origFormWrapper);
                                holder.classList.remove('hidden');
                                document.getElementById('carWashList')?.classList.add('hidden');
                                origFormWrapper.classList.remove('hidden');
                                origFormWrapper.scrollIntoView({ behavior: 'smooth' });
                                return;
                            }
                        }

                        // Fallback: show in-place (existing behavior)
                        document.getElementById('newReservationForm')?.classList.remove('hidden');
                        document.getElementById('reservationListView')?.classList.add('hidden');
                        document.getElementById('newReservationForm')?.scrollIntoView({ behavior: 'smooth' });
                    }

                    // Hide new reservation form and restore it to its original parent if moved
                    function hideNewReservationForm(){
                        const origFormWrapper = document.getElementById('newReservationForm');
                        if (!origFormWrapper) return;

                        // restore to original parent if it was moved
                        const origParentId = origFormWrapper.dataset.originalParentId;
                        if (origParentId) {
                            const origParent = document.getElementById(origParentId);
                            if (origParent) {
                                origParent.appendChild(origFormWrapper);
                            }
                            delete origFormWrapper.dataset.originalParentId;
                        }

                        // update visibility of lists
                        document.getElementById('carWashList')?.classList.remove('hidden');
                        document.getElementById('reservationListView')?.classList.remove('hidden');
                        origFormWrapper.classList.add('hidden');
                    }

                    // Submit new reservation: POST to server API, then redirect to invoice/checkout
                    async function submitNewReservation(evt){
                        if (evt && evt.preventDefault) evt.preventDefault();

                        const form = (evt && evt.target && (evt.target.tagName === 'FORM' ? evt.target : evt.target.closest('form'))) || document.getElementById('newReservationFormElement');
                        if (!form) {
                            alert('Form bulunamadı. Lütfen sayfayı yenileyin.');
                            return;
                        }

                        const service = (form.querySelector('#service') || form.querySelector('[name="service"]'))?.value || '';
                        const vehicle = (form.querySelector('#vehicle') || form.querySelector('[name="vehicle"]'))?.value || '';
                        const date = (form.querySelector('#reservationDate') || form.querySelector('[name="reservationDate"]'))?.value || '';
                        let time = (form.querySelector('#reservationTime') || form.querySelector('[name="reservationTime"]'))?.value || '';
                        time = normalizeTimeTo24(time);
                        const location = (form.querySelector('#location') || form.querySelector('[name="location"]'))?.value || '';
                        const location_id = (form.querySelector('#location_id') || form.querySelector('[name="location_id"]'))?.value || '';
                        const notes = (form.querySelector('#notes') || form.querySelector('[name="notes"]'))?.value || '';

                        if (!service || !vehicle || !date || !time || !location) {
                            alert('Lütfen tüm zorunlu alanları doldurun.');
                            return;
                        }

                        // Build FormData for POST
                        const fd = new FormData();
                        fd.append('service', service);
                        fd.append('vehicle', vehicle);
                        fd.append('reservationDate', date);
                        fd.append('reservationTime', time);
                        fd.append('location', location);
                        fd.append('location_id', location_id);
                        fd.append('notes', notes);
                        fd.append('csrf_token', window.CONFIG && window.CONFIG.CSRF_TOKEN ? window.CONFIG.CSRF_TOKEN : document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                        try {
                            const resp = await fetch('/carwash_project/backend/api/reservations/create.php', {
                                method: 'POST',
                                body: fd,
                                credentials: 'same-origin'
                            });
                            const result = await resp.json();
                            if (!result || !result.success) {
                                alert(result && result.message ? result.message : 'Rezervasyon oluşturulamadı.');
                                return;
                            }

                            // Redirect to invoice/checkout page provided by server
                            if (result.redirect) {
                                window.location.href = result.redirect;
                                return;
                            }

                            alert('Rezervasyon oluşturuldu, fakat yönlendirme bilgisi alınamadı.');
                        } catch (err) {
                            console.error('Reservation create error:', err);
                            alert('Sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.');
                        }
                    }

                    function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>\"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }

                    // Attach handlers
                    document.addEventListener('DOMContentLoaded', function(){
                        document.getElementById('newReservationBtn')?.addEventListener('click', showNewReservationForm);
                        document.getElementById('cancelNewReservation')?.addEventListener('click', hideNewReservationForm);
                        document.getElementById('newReservationFormElement')?.addEventListener('submit', submitNewReservation);
                        // support buttons if they exist elsewhere
                        window.showNewReservationForm = showNewReservationForm;
                        window.hideNewReservationForm = hideNewReservationForm;
                        window.submitNewReservation = submitNewReservation;

                        // bookings: load and delegate actions
                        loadBookings();

                        // Delegate Edit / Cancel clicks
                        document.getElementById('reservationsTableBody')?.addEventListener('click', function(e){
                            const target = e.target;
                            if (target.matches('.edit-booking-btn')) {
                                const tr = target.closest('tr');
                                if (tr) showEditBookingModalFromRow(tr);
                                return;
                            }
                            if (target.matches('.cancel-booking-btn')) {
                                const id = target.getAttribute('data-id');
                                if (id) cancelBookingById(id);
                                return;
                            }
                        });

                        // Edit modal handlers
                        document.getElementById('editCancelBtn')?.addEventListener('click', hideEditBookingModal);
                        document.getElementById('editBookingForm')?.addEventListener('submit', submitEditBooking);
                    });

                })();
                </script>
        </div> <!-- END: Max-width container -->
    </main>

</div> <!-- END: Flex Container (Sidebar + Content) -->

<!-- Dynamic Layout Height Calculator -->
<script>
// ================================
// Layout Height Manager
// Sets header to fixed 80px and computes layout sizes
// Updates CSS variables on load, resize, and content changes
// ================================
(function() {
    'use strict';
    
    function updateLayoutHeights() {
        const root = document.documentElement;
        
        // Set fixed header height
        root.style.setProperty('--header-height', '80px');
        
        // Footer removed from this page; no footer height calculations required
        
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
        
        console.log('✅ Layout updated - Header: 80px, Sidebar:', sidebarWidth + 'px');
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
    
    // Update after images load (layout might change)
    window.addEventListener('load', function() {
        setTimeout(updateLayoutHeights, 100);
    });
})();
</script>

<!-- Profile Form JavaScript -->
<script>
// ================================
// Profile Form Helpers, Preview & Submission
// ================================
(function() {
    'use strict';

    const profileForm = document.getElementById('profileForm');
    const profileImageInput = document.getElementById('profile_image');
    const profileImagePreview = document.getElementById('profileImagePreview');
    const formErrors = document.getElementById('form-errors');
    const formErrorsList = document.getElementById('form-errors-list');
    const successMsg = document.getElementById('profile-success');
    const errorMsg = document.getElementById('profile-error');

    // Store original values for change detection
    const originalValues = {
        name: (profileForm.querySelector('[name="name"]')?.value || '').trim(),
        email: (profileForm.querySelector('[name="email"]')?.value || '').trim(),
        username: (profileForm.querySelector('[name="username"]')?.value || '').trim(),
        national_id: (profileForm.querySelector('[name="national_id"]')?.value || '').trim()
    };

    function clearFieldHighlights() {
        const fields = ['profile_name','profile_email','profile_username','profile_national_id','profile_image','current_password','new_password','confirm_password'];
        fields.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('border-red-500');
                el.classList.remove('error');
            }
        });
    }

    function clearFormErrors() {
        if (!formErrors || !formErrorsList) return;
        formErrorsList.innerHTML = '';
        formErrors.classList.add('hidden');
    }

    // Global message helpers
    let globalMessageTimer = null;
    const globalMessage = document.getElementById('global-message');
    const globalMessageBox = document.getElementById('global-message-box');
    const globalMessageText = document.getElementById('global-message-text');
    const globalMessageSub = document.getElementById('global-message-sub');
    const globalMessageClose = document.getElementById('global-message-close');
    const globalMessageIcon = document.getElementById('global-message-icon');

    function hideGlobalMessage() {
        if (!globalMessage) return;
        globalMessage.classList.add('hidden');
        if (globalMessageTimer) { clearTimeout(globalMessageTimer); globalMessageTimer = null; }
    }

    function showGlobalMessage(type, message, sub = '', timeout = 6000) {
        if (!globalMessage || !globalMessageBox) return;
        // reset
        globalMessageBox.className = 'rounded-lg shadow-lg p-4 flex items-start gap-3';
        globalMessageText.textContent = message || '';
        globalMessageSub.textContent = sub || '';

        if (type === 'success') {
            globalMessageBox.classList.add('bg-green-50', 'border', 'border-green-200');
            globalMessageText.classList.remove('text-red-700');
            globalMessageText.classList.add('text-green-700');
            globalMessageIcon.innerHTML = '<i class="fas fa-check-circle text-green-600 text-xl"></i>';
        } else {
            globalMessageBox.classList.add('bg-red-50', 'border', 'border-red-200');
            globalMessageText.classList.remove('text-green-700');
            globalMessageText.classList.add('text-red-700');
            globalMessageIcon.innerHTML = '<i class="fas fa-exclamation-circle text-red-600 text-xl"></i>';
        }

        globalMessage.classList.remove('hidden');
        if (globalMessageTimer) clearTimeout(globalMessageTimer);
        if (timeout && timeout > 0) globalMessageTimer = setTimeout(hideGlobalMessage, timeout);
    }

    if (globalMessageClose) globalMessageClose.addEventListener('click', hideGlobalMessage);

    function showSuccess(msg, sub) { showGlobalMessage('success', msg || 'İşlem başarıyla tamamlandı', sub || '', 5000); }
    function showError(msg, sub) { showGlobalMessage('error', msg || 'Bir hata oluştu. Lütfen tekrar deneyin.', sub || '', 8000); }

    function showFormErrors(errors, fieldHints) {
        if (!formErrors || !formErrorsList) return;
        formErrorsList.innerHTML = '';
        errors.forEach(msg => {
            const li = document.createElement('li');
            li.textContent = msg;
            formErrorsList.appendChild(li);
        });
        formErrors.classList.remove('hidden');

        // Highlight fields if hints provided
        if (fieldHints && typeof fieldHints === 'object') {
            Object.keys(fieldHints).forEach(fn => {
                const el = document.querySelector('[name="' + fn + '"]') || document.getElementById(fn);
                if (el) {
                    el.classList.add('border-red-500');
                    el.classList.add('error');
                }
            });
        }
    }

    // Client-side preview & validation for image
    if (profileImageInput && profileImagePreview) {
        profileImageInput.addEventListener('change', function(e) {
            clearFormErrors();
            clearFieldHighlights();
            const file = e.target.files[0];
            if (!file) return;

            const validTypes = ['image/jpeg','image/png','image/webp'];
            const maxSize = 3 * 1024 * 1024; // 3MB

            if (!validTypes.includes(file.type)) {
                showFormErrors(['Profile image must be JPG, PNG, or WEBP.'], { profile_image: true });
                e.target.value = '';
                return;
            }

            if (file.size > maxSize) {
                showFormErrors(['Profile image must be under 3MB.'], { profile_image: true });
                e.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(ev) {
                profileImagePreview.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    if (!profileForm) return;

    // Client-side validation before submitting
    // Only validate fields that have changed from their original values (matches backend behavior)
    function clientValidate() {
        const errs = [];
        const fields = {};
        const name = (profileForm.querySelector('[name="name"]')?.value || '').trim();
        const email = (profileForm.querySelector('[name="email"]')?.value || '').trim();
        const username = (profileForm.querySelector('[name="username"]')?.value || '').trim();
        const national_id = (profileForm.querySelector('[name="national_id"]')?.value || '').trim();
        const new_password = (profileForm.querySelector('[name="new_password"]')?.value || '').trim();
        const confirm_password = (profileForm.querySelector('[name="confirm_password"]')?.value || '').trim();

        // Only validate name if it changed
        if (name !== originalValues.name) {
            if (name.length < 2 || name.length > 50 || !/^[\p{L}0-9 _]+$/u.test(name)) {
                errs.push('Display name must be 2–50 characters and contain only letters, numbers, or spaces.');
                fields['name'] = true;
            }
        }

        // Only validate email if it changed
        if (email !== originalValues.email) {
            if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
                errs.push('Invalid email format.');
                fields['email'] = true;
            }
        }

        // Only validate username if it changed
        if (username !== originalValues.username) {
            if (!/^[A-Za-z0-9_]{3,}$/.test(username)) {
                errs.push('Username must be at least 3 characters and contain no spaces.');
                fields['username'] = true;
            }
        }

        // Only validate national_id if it changed
        if (national_id !== originalValues.national_id) {
            if (!/^[0-9]{11}$/.test(national_id)) {
                errs.push('T.C. Kimlik No 11 haneli olmalıdır');
                fields['national_id'] = true;
            }
        }

        // Always validate password if user is trying to change it
        if (new_password) {
            if (new_password.length < 8 || !/[A-Za-z]/.test(new_password) || !/[0-9]/.test(new_password)) {
                errs.push('New password must be at least 8 characters and contain letters and numbers.');
                fields['new_password'] = true;
            }
            if (new_password !== confirm_password) {
                errs.push('New password and confirmation do not match.');
                fields['confirm_password'] = true;
            }
        }

        return { errs, fields };
    }

    profileForm.addEventListener('submit', async function(evt) {
        evt.preventDefault();
        clearFormErrors();
        clearFieldHighlights();

        const submitBtn = profileForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Kaydediliyor...</span>';
        }

        // Client validation
        const client = clientValidate();
        if (client.errs.length) {
            showFormErrors(client.errs, client.fields);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save text-sm"></i> <span>Kaydet</span>';
            }
            return;
        }

        try {
            // Prepare FormData; if image is oversized, resize client-side before sending
            const maxSize = 3 * 1024 * 1024; // 3MB
            const formData = new FormData(profileForm);
            formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>');

            if (profileImageInput && profileImageInput.files && profileImageInput.files[0]) {
                const file = profileImageInput.files[0];
                // Prevent attempting to upload extremely large files that exceed server input caps
                const inputCap = 10 * 1024 * 1024; // 10MB server input cap (matches server-side limit)
                if (file.size > inputCap) {
                    showFormErrors(['Profile image exceeds the maximum allowed upload size of 10MB.'], { profile_image: true });
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save text-sm"></i> <span>Kaydet</span>'; }
                    return;
                }

                if (file.size > maxSize) {
                    // Try to resize on client to reduce upload size
                    const resizedBlob = await (async function resizeImage(file, maxW = 1600, quality = 0.85) {
                        return new Promise((resolve, reject) => {
                            try {
                                const img = new Image();
                                img.onload = async function() {
                                    try {
                                        const canvas = document.createElement('canvas');
                                        const scale = (img.width > maxW) ? (maxW / img.width) : 1.0;
                                        canvas.width = Math.max(1, Math.round(img.width * scale));
                                        canvas.height = Math.max(1, Math.round(img.height * scale));
                                        const ctx = canvas.getContext('2d');
                                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                                        canvas.toBlob(function(blob) {
                                            if (blob) resolve(blob); else reject(new Error('Canvas conversion failed'));
                                        }, file.type === 'image/png' ? 'image/png' : (file.type === 'image/webp' ? 'image/webp' : 'image/jpeg'), quality);
                                    } catch (e) { reject(e); }
                                };
                                img.onerror = function(e) { reject(new Error('Image load error')); };
                                // load via blob URL for memory efficiency
                                const url = URL.createObjectURL(file);
                                img.src = url;
                            } catch (err) { reject(err); }
                        });
                    })(file, 1600, 0.85).catch(() => null);

                    if (resizedBlob) {
                        // Replace the profile_image in FormData with the resized blob
                        try { formData.delete('profile_image'); } catch (e) {}
                        const name = file.name || 'profile.jpg';
                        formData.append('profile_image', resizedBlob, name);
                        // Update preview (optional)
                        try {
                            const reader = new FileReader();
                            reader.onload = function(ev) { if (profileImagePreview) profileImagePreview.src = ev.target.result; };
                            reader.readAsDataURL(resizedBlob);
                        } catch (e) {}
                    } else {
                        showFormErrors(['Profile image is over 3MB and could not be resized in your browser. Please upload a smaller image.'], { profile_image: true });
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save text-sm"></i> <span>Kaydet</span>'; }
                        return;
                    }
                }
            }

            const resp = await fetch('/carwash_project/backend/dashboard/Customer_Dashboard_process.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            const result = await resp.json();
            // Keep the Profile tab active and update UI accordingly
            function activateProfileTab() {
                try {
                    // If Alpine is available and dashboard component exists, set the currentSection
                    if (typeof Alpine !== 'undefined' && document.body && document.body.__x && document.body.__x.$data) {
                        document.body.__x.$data.currentSection = 'profile';
                    }
                } catch (e) {
                    // fallback: ensure profile section is visible by showing element
                    const profileSection = document.querySelector("section[x-show='currentSection === 'profile']");
                    if (profileSection) profileSection.style.display = 'block';
                }
            }

            if (result.success) {
                clearFormErrors();
                if (errorMsg) { errorMsg.classList.add('hidden'); }
                
                // Show success notification with subtitle
                try { showSuccess('Profil Başarıyla Güncellendi!', 'Değişiklikler kaydedildi'); } catch (e) {}

                // Get Alpine.js component data
                const profileSection = document.querySelector("section[x-show=\"currentSection === 'profile'\"]");
                const alpineData = profileSection?.__x?.$data;

                // Prepare updated profile data
                const updatedData = {};
                
                // Get data from server response or form fields
                if (result.data) {
                    updatedData.name = result.data.name || profileForm.querySelector('[name="name"]')?.value;
                    updatedData.email = result.data.email || profileForm.querySelector('[name="email"]')?.value;
                    updatedData.username = result.data.username || profileForm.querySelector('[name="username"]')?.value;
                    updatedData.phone = result.data.phone !== undefined ? result.data.phone : profileForm.querySelector('[name="phone"]')?.value;
                    updatedData.home_phone = result.data.home_phone !== undefined ? result.data.home_phone : profileForm.querySelector('[name="home_phone"]')?.value;
                    updatedData.national_id = result.data.national_id !== undefined ? result.data.national_id : profileForm.querySelector('[name="national_id"]')?.value;
                    updatedData.driver_license = result.data.driver_license !== undefined ? result.data.driver_license : profileForm.querySelector('[name="driver_license"]')?.value;
                    updatedData.city = result.data.city !== undefined ? result.data.city : profileForm.querySelector('[name="city"]')?.value;
                    updatedData.address = result.data.address !== undefined ? result.data.address : profileForm.querySelector('[name="address"]')?.value;
                } else {
                    // Fallback: get all values from form
                    updatedData.name = profileForm.querySelector('[name="name"]')?.value;
                    updatedData.email = profileForm.querySelector('[name="email"]')?.value;
                    updatedData.username = profileForm.querySelector('[name="username"]')?.value;
                    updatedData.phone = profileForm.querySelector('[name="phone"]')?.value;
                    updatedData.home_phone = profileForm.querySelector('[name="home_phone"]')?.value;
                    updatedData.national_id = profileForm.querySelector('[name="national_id"]')?.value;
                    updatedData.driver_license = profileForm.querySelector('[name="driver_license"]')?.value;
                    updatedData.city = profileForm.querySelector('[name="city"]')?.value;
                    updatedData.address = profileForm.querySelector('[name="address"]')?.value;
                }
                
                // Handle profile image update
                const newImage = (result.data && result.data.image) ? result.data.image : (result.avatarUrl || result.data?.avatarUrl || null);
                if (newImage) {
                    const imageUrl = newImage + (newImage.includes('?') ? '&' : '?') + 'v=' + Date.now();
                    updatedData.profile_image = imageUrl;
                    
                    // Update all image instances
                    const topAvatar = document.getElementById('userAvatarTop');
                    if (topAvatar) topAvatar.src = imageUrl;
                    const sidebarImg = document.getElementById('sidebarProfileImage');
                    if (sidebarImg) sidebarImg.src = imageUrl;
                    if (profileImagePreview) profileImagePreview.src = imageUrl;
                    try { 
                        localStorage.setItem('carwash_profile_image', imageUrl); 
                        localStorage.setItem('carwash_profile_image_ts', Date.now().toString()); 
                    } catch(e){}
                }
                
                // Update Alpine.js reactive profile data
                if (alpineData && typeof alpineData.updateProfile === 'function') {
                    alpineData.updateProfile(updatedData);
                    // Close edit mode and show view mode
                    alpineData.editMode = false;
                }
                
                // Update form fields with server data for next edit
                if (result.data) {
                    Object.keys(updatedData).forEach(key => {
                        const field = profileForm.querySelector(`[name="${key}"]`);
                        if (field && updatedData[key] !== undefined) {
                            field.value = updatedData[key];
                        }
                    });
                }
                
                // Clear password fields
                ['current_password', 'new_password', 'confirm_password'].forEach(name => {
                    const field = profileForm.querySelector(`[name="${name}"]`);
                    if (field) field.value = '';
                });
                
                // Update original values for change detection
                originalValues.name = updatedData.name;
                originalValues.email = updatedData.email;
                originalValues.username = updatedData.username;
                originalValues.national_id = updatedData.national_id;

                // Smooth scroll to profile section
                if (profileSection) {
                    profileSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // Ensure profile tab remains active
                activateProfileTab();
            } else {
                // Hide any success message
                if (successMsg) successMsg.classList.add('hidden');
                // Show server-side validation errors if present
                if (Array.isArray(result.errors) && result.errors.length) {
                    showFormErrors(result.errors, result.fieldErrors || {});
                    showError(result.message || 'Validation failed');
                } else {
                    const msg = result.message || 'Profil güncellenirken bir hata oluştu.';
                    showFormErrors([msg]);
                    showError(msg);
                }
                // Keep profile tab active so user sees errors
                try { if (typeof activateProfileTab === 'function') activateProfileTab(); } catch(e){}
            }

        } catch (err) {
            console.error('Profile update error:', err);
            // Generic fallback for network/parse/server errors
            showError('An unexpected error occurred. Lütfen sayfayı yenileyip tekrar deneyin.');
            // Also show form-level error for visibility
            showFormErrors(['A network or server error occurred.']);
        } finally {
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
                console.log('🔐 Sidebar closed via ESC key');
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
                    console.log('🔗 Sidebar closed after link click');
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
                            console.log('📱 Mobile sidebar opened, body scroll prevented');
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
                            console.log('📱 Mobile sidebar closed, body scroll restored');
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
                console.log('💻 Resized to desktop, sidebar auto-closed');
            }
        }
    });
    
    console.log('✅ Mobile sidebar toggle initialized');
    
})();

    // Sync Alpine mobileMenuOpen state to CSS classes and provide a non-Alpine fallback
    (function() {
        'use strict';

        const sidebar = document.getElementById('customer-sidebar');
        // Select overlay by multiple class attributes instead of chained classes (avoid CSS escape issues)
        const overlay = document.querySelector('div[role="button"][aria-label="Close sidebar"]');
        const toggleBtn = document.getElementById('mobileMenuToggleBtn');

        // Helper to apply inert/aria-hidden/tabindex state to overlay, sidebar and main content
        function applyInertState(isOpen) {
            try {
                const main = document.getElementById('main-content');

                // Prefer modern inert attribute
                if (typeof HTMLElement !== 'undefined' && 'inert' in HTMLElement.prototype) {
                    if (main) main.inert = isOpen;
                } else {
                    // Fallback: set aria-hidden and manage focusable elements inside main only
                    if (main) main.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
                    const focusables = (el) => el ? el.querySelectorAll('a, button, input, select, textarea, [tabindex]') : [];
                    if (main) focusables(main).forEach(f => { if (isOpen) { if (f.hasAttribute('tabindex')) f.dataset._oldTab = f.getAttribute('tabindex'); f.setAttribute('tabindex','-1'); } else { if (f.dataset && f.dataset._oldTab !== undefined) { f.setAttribute('tabindex', f.dataset._oldTab); delete f.dataset._oldTab; } else { f.removeAttribute('tabindex'); } }});
                }

                // Overlay: focusable only when visible
                if (overlay) {
                    overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                    overlay.tabIndex = isOpen ? 0 : -1;
                }

                // Sidebar itself should not be focusable when closed on mobile
                if (sidebar) {
                    if (typeof HTMLElement !== 'undefined' && 'inert' in HTMLElement.prototype) sidebar.inert = !isOpen && window.innerWidth < 1024;
                    else sidebar.setAttribute('aria-hidden', !isOpen && window.innerWidth < 1024 ? 'true' : 'false');
                }
            } catch (e) {
                console.warn('applyInertState failed', e);
            }
        }

        function applyOpenState(isOpen) {
            if (!sidebar) return;
            if (isOpen) {
                sidebar.classList.add('mobile-open');
                if (overlay) overlay.classList.add('active');
                document.body.classList.add('menu-open');
                applyInertState(true);
            } else {
                sidebar.classList.remove('mobile-open');
                if (overlay) overlay.classList.remove('active');
                document.body.classList.remove('menu-open');
                applyInertState(false);
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
        
        console.log(`♿ Sidebar focusability: ${isVisible ? 'enabled' : 'disabled'} (mobile: ${isMobile})`);
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
    
    console.log('✅ Sidebar accessibility manager initialized');
    
})();

// ================================
// Dynamic Content Observer
// Ensures proper rendering of dynamic content
// ================================
(function() {
    'use strict';
    
    console.log('✅ Dashboard layout initialized with proper flex structure');
    
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
        
        console.log('✅ Form validation initialized');
    });
    
})();
</script>

</body>
</html>
<script>
// ================================