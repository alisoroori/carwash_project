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

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Dashboard header variables
$dashboard_type = 'customer';
$page_title = 'Müşteri Paneli - CarWash';
$current_page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- TailwindCSS - Production Build -->
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        /* ================================
           CSS CUSTOM PROPERTIES (Theme Variables)
           ================================ */
        :root {
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
        
        /* Layout Fixes for Sidebar & Content */
        #customer-sidebar {
            /* Sidebar spans from header to footer, always below header */
            z-index: 30; /* Below header (z-50) */
        }
        
        @media (min-width: 1024px) {
            #customer-sidebar {
                /* Sticky position on desktop - stretches to fill flex container */
                position: sticky !important;
                top: 4rem; /* 64px below fixed header */
                height: auto; /* Auto height to fill flex container */
                align-self: stretch; /* Stretch to match tallest flex item (main content) */
                overflow-y: auto; /* Allow scroll if content overflows */
            }
        }
        
        @media (max-width: 1023px) {
            #customer-sidebar {
                /* Fixed position on mobile - below header */
                position: fixed !important;
                top: 4rem; /* 64px below header */
                bottom: 0;
                height: calc(100vh - 4rem);
                overflow-y: auto; /* Allow scroll on mobile */
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
    x-data="{ mobileMenuOpen: false, currentSection: 'dashboard' }"
    x-effect="mobileMenuOpen ? document.body.classList.add('menu-open') : document.body.classList.remove('menu-open')"
>

<!-- ================================
     HEADER - Fixed at Top
     ================================ -->
<header class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 shadow-sm h-16 flex-none">
    <div class="flex items-center justify-between h-16 px-4 lg:px-6">
        
        <!-- Mobile Menu Button -->
        <button 
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 active:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                <img src="/carwash_project/backend/logo01.png" alt="MyCar logo" class="w-10 h-10 object-cover rounded-xl shadow-md" />
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
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
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
                        <span>Çıkış Yap</span>
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
         SIDEBAR - Stretches from header to footer
         Desktop: Sticky position, full height
         Mobile: Fixed position with overlay
         NO internal scroll - overflow-hidden
         ================================ -->
    <aside 
        id="customer-sidebar"
        class="w-64 bg-gradient-to-b from-blue-600 via-blue-700 to-purple-700 text-white shadow-2xl
               fixed top-16 left-0 bottom-0
               lg:sticky lg:top-16
               lg:self-stretch lg:h-auto
               transform transition-transform duration-300 ease-in-out
               -translate-x-full lg:translate-x-0
               flex flex-col overflow-y-auto
               z-30"
        :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="transform -translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition-transform ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform -translate-x-full"
        role="navigation"
        aria-label="Main navigation"
        :aria-hidden="!mobileMenuOpen && window.innerWidth < 1024"
    >
        <!-- User Profile Section (Better readability, always visible at top) -->
        <div class="flex-shrink-0 p-4 border-b border-white border-opacity-20 bg-blue-800 bg-opacity-30 sticky top-0 z-10">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-2 bg-white bg-opacity-20 rounded-full flex items-center justify-center shadow-lg ring-2 ring-white ring-opacity-10">
                    <i class="fas fa-user text-2xl text-white"></i>
                </div>
                <h3 class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($user_name); ?></h3>
                <p class="text-xs text-blue-100 opacity-90 truncate mt-1"><?php echo htmlspecialchars($user_email); ?></p>
            </div>
        </div>
        
        <!-- Navigation Menu (Better spacing and readability) -->
        <nav class="flex-1 px-3 py-3 space-y-1 flex flex-col overflow-y-auto" 
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
         MAIN CONTENT AREA - Takes remaining space beside sidebar
         Desktop: Sits next to sidebar with flex-1, pt-16 for fixed header
         Mobile: Takes full width, pt-16 for fixed header
         ================================ -->
    <main class="flex-1 bg-gray-50 overflow-y-auto pt-16" id="main-content">
        <div class="p-6 lg:p-8 max-w-7xl mx-auto">
        
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
        <section x-show="currentSection === 'vehicles'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" x-data="vehicleManager()" style="display: none;">
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
                    
                    <form @submit.prevent="saveVehicle()" class="p-6">
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
        <section x-show="currentSection === 'profile'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Profil Ayarları</h2>
                <p class="text-gray-600">Hesap bilgilerinizi güncelleyin</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8">
                <form class="space-y-6">
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
                                placeholder="+90 555 123 45 67"
                                autocomplete="tel"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                        </div>
                        
                        <!-- City -->
                        <div class="mb-4">
                            <label for="profile_city" class="block text-sm font-semibold text-gray-700 mb-2">Şehir</label>
                            <input 
                                type="text"
                                id="profile_city"
                                name="city"
                                placeholder="İstanbul"
                                autocomplete="address-level2"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
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
                    
                    <!-- Form Actions -->
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
                        <button 
                            type="button"
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
                        <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">SMS Bildirimleri</h4>
                            <p class="text-sm text-gray-600">Acil durumlar için SMS</p>
                        </div>
                        <input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">Promosyon Bildirimleri</h4>
                            <p class="text-sm text-gray-600">İndirim ve kampanya duyuruları</p>
                        </div>
                        <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
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
        
        <!-- Other sections (reservations, carWashSelection, history) would follow the same pattern -->
        
        </div> <!-- END: Max-width container -->
    </main>

</div> <!-- END: Flex Container (Sidebar + Content) -->

<!-- Footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Vehicle Manager JavaScript -->
<script>
function vehicleManager() {
    return {
        vehicles: [],
        showVehicleForm: false,
        editingVehicle: null,
        loading: false,
        message: '',
        messageType: '',
        imagePreview: '',
        csrfToken: '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>',
        formData: {
            brand: '',
            model: '',
            license_plate: '',
            year: '',
            color: ''
        },
        
        init() {
            this.loadVehicles();
        },
        
        /**
         * Load vehicles from API with proper error handling
         */
        async loadVehicles() {
            try {
                const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php?action=list', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Handle different response formats
                this.vehicles = data.vehicles || data.data?.vehicles || [];
                
                // Update stat count
                const statEl = document.getElementById('vehicleStatCount');
                if (statEl) {
                    statEl.textContent = this.vehicles.length;
                }
                
                console.log('✅ Vehicles loaded:', this.vehicles.length);
                
            } catch (error) {
                console.error('❌ Load vehicles error:', error);
                this.vehicles = [];
                this.showMessage('Araçlar yüklenemedi', 'error');
            }
        },
        
        /**
         * Open vehicle form (create or edit)
         */
        openVehicleForm(vehicle = null) {
            this.editingVehicle = vehicle;
            
            if (vehicle) {
                this.formData = {
                    brand: vehicle.brand || '',
                    model: vehicle.model || '',
                    license_plate: vehicle.license_plate || '',
                    year: vehicle.year || '',
                    color: vehicle.color || ''
                };
                this.imagePreview = vehicle.image_path || '';
            } else {
                this.resetForm();
            }
            
            this.showVehicleForm = true;
            document.body.classList.add('menu-open');
        },
        
        /**
         * Close vehicle form
         */
        closeVehicleForm() {
            this.showVehicleForm = false;
            this.resetForm();
            document.body.classList.remove('menu-open');
        },
        
        /**
         * Reset form data
         */
        resetForm() {
            this.editingVehicle = null;
            this.formData = { 
                brand: '', 
                model: '', 
                license_plate: '', 
                year: '', 
                color: '' 
            };
            this.imagePreview = '';
            this.message = '';
            this.messageType = '';
        },
        
        /**
         * Save vehicle (create or update) with proper async/await
         */
        async saveVehicle() {
            this.loading = true;
            this.message = '';
            
            try {
                const form = document.querySelector('#customer-sidebar').closest('body').querySelector('form[x-data]') || 
                             document.querySelector('form[x-data]');
                
                if (!form) {
                    throw new Error('Form not found');
                }
                
                const formData = new FormData(form);
                
                const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success || data.status === 'success') {
                    this.showMessage(
                        this.editingVehicle ? 'Araç güncellendi' : 'Araç eklendi', 
                        'success'
                    );
                    
                    // Reload vehicles list
                    await this.loadVehicles();
                    
                    // Close form after short delay
                    setTimeout(() => this.closeVehicleForm(), 1500);
                } else {
                    throw new Error(data.message || 'İşlem başarısız');
                }
                
            } catch (error) {
                console.error('❌ Save vehicle error:', error);
                this.showMessage(error.message || 'Kaydetme işlemi başarısız', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        /**
         * Edit existing vehicle
         */
        editVehicle(vehicle) {
            this.openVehicleForm(vehicle);
        },
        
        /**
         * Delete vehicle with confirmation
         */
        async deleteVehicle(id) {
            if (!confirm('Bu aracı silmek istediğinizden emin misiniz?')) {
                return;
            }
            
            this.loading = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                formData.append('csrf_token', this.csrfToken);
                
                const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success || data.status === 'success') {
                    this.showMessage('Araç başarıyla silindi', 'success');
                    await this.loadVehicles();
                } else {
                    throw new Error(data.message || 'Silme işlemi başarısız');
                }
                
            } catch (error) {
                console.error('❌ Delete vehicle error:', error);
                this.showMessage(error.message || 'Silme işlemi başarısız', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        /**
         * Preview image before upload
         */
        previewImage(event) {
            const file = event.target.files[0];
            
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                
                reader.onerror = () => {
                    this.showMessage('Resim yüklenirken hata oluştu', 'error');
                };
                
                reader.readAsDataURL(file);
            } else if (file) {
                this.showMessage('Lütfen geçerli bir resim dosyası seçin', 'error');
            }
        },
        
        /**
         * Show message with auto-hide
         */
        showMessage(msg, type = 'success') {
            this.message = msg;
            this.messageType = type;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.message = '';
                this.messageType = '';
            }, 5000);
        }
    }
}

console.log('✅ Customer Dashboard loaded successfully');
</script>

<script>
// ================================
// Mobile Sidebar Toggle - Enhanced Behavior
// ================================
(function() {
    'use strict';
    
    // Ensure sidebar is hidden by default on mobile
    function ensureSidebarState() {
        const sidebar = document.getElementById('customer-sidebar');
        if (!sidebar) return;
        
        if (window.innerWidth < 1024) {
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
        if ((e.key === 'Escape' || e.keyCode === 27) && window.innerWidth < 1024) {
            const body = document.body;
            if (body && body.__x && body.__x.$data && body.__x.$data.mobileMenuOpen) {
                body.__x.$data.mobileMenuOpen = false;
                console.log('🔐 Sidebar closed via ESC key');
            }
        }
    });
    
    // Close sidebar when clicking menu links on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 1024) {
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
                    if (window.innerWidth < 1024) {
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
        if (window.innerWidth >= 1024) {
            const body = document.body;
            if (body && body.__x && body.__x.$data && body.__x.$data.mobileMenuOpen) {
                body.__x.$data.mobileMenuOpen = false;
                console.log('💻 Resized to desktop, sidebar auto-closed');
            }
        }
    });
    
    console.log('✅ Mobile sidebar toggle initialized');
    
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

