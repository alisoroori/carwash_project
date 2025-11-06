<?php
/**
 * Enhanced Customer Dashboard Header
 * Clean, Professional, Fully Responsive Design with TailwindCSS
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/carwash_project';

// User info
$user_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User';
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? 'customer';
$user_avatar = $_SESSION['avatar'] ?? null;

// Dashboard type
$dashboard_type = $user_role;
$page_title = isset($page_title) ? $page_title : 'Customer Dashboard - CarWash';

// URLs
$home_url = $base_url . '/backend/index.php';
$dashboard_url = $base_url . '/backend/dashboard/Customer_Dashboard.php';
$profile_url = $dashboard_url . '#profile';
$settings_url = $dashboard_url . '#settings';
$logout_url = $base_url . '/backend/includes/logout.php';
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- TailwindCSS CDN (for development - replace with compiled CSS in production) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom TailwindCSS Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        secondary: {
                            500: '#8b5cf6',
                            600: '#7c3aed',
                        }
                    },
                    boxShadow: {
                        'header': '0 4px 12px rgba(0, 0, 0, 0.08)',
                        'dropdown': '0 8px 24px rgba(0, 0, 0, 0.15)',
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Custom animations and utilities */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
            }
            to {
                transform: translateX(0);
            }
        }
        
        .dropdown-menu {
            animation: slideDown 0.2s ease-out;
        }
        
        .mobile-menu-panel {
            animation: slideInLeft 0.3s ease-out;
        }
        
        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        /* Custom scrollbar for mobile menu */
        .mobile-menu-panel::-webkit-scrollbar {
            width: 4px;
        }
        
        .mobile-menu-panel::-webkit-scrollbar-track {
            background: #f3f4f6;
        }
        
        .mobile-menu-panel::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }
        
        /* Prevent body scroll when mobile menu open */
        body.menu-open {
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- ================================
     HEADER SECTION - FIXED TOP
     ================================ -->
<header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-18">
            
            <!-- ============ LEFT: Logo & Brand ============ -->
            <div class="flex items-center space-x-3 lg:space-x-4">
                <!-- Mobile Menu Button (visible only on mobile) -->
                <button 
                    onclick="toggleMobileMenu()" 
                    class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                    aria-label="Toggle menu"
                    id="mobileMenuBtn"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                
                <!-- Logo -->
                <a href="<?php echo $dashboard_url; ?>" class="flex items-center space-x-2 group">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:scale-105 transition-all">
                        <i class="fas fa-car-wash text-white text-lg lg:text-xl"></i>
                    </div>
                    <div class="hidden sm:block">
                        <h1 class="text-lg lg:text-xl font-bold text-gray-900 leading-tight">
                            MYCAR
                        </h1>
                        <p class="text-xs text-gray-500 -mt-1">Customer Panel</p>
                    </div>
                </a>
            </div>
            
            <!-- ============ CENTER: Navigation (Desktop only) ============ -->
            <nav class="hidden lg:flex items-center space-x-1">
                <a href="<?php echo $dashboard_url; ?>" class="nav-link px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all">
                    <i class="fas fa-home mr-2"></i>
                    Dashboard
                </a>
                <a href="<?php echo $dashboard_url; ?>#carWashSelection" class="nav-link px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all">
                    <i class="fas fa-store mr-2"></i>
                    Car Washes
                </a>
                <a href="<?php echo $dashboard_url; ?>#reservations" class="nav-link px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all">
                    <i class="fas fa-calendar-check mr-2"></i>
                    Reservations
                </a>
                <a href="<?php echo $dashboard_url; ?>#vehicles" class="nav-link px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all">
                    <i class="fas fa-car mr-2"></i>
                    My Vehicles
                </a>
            </nav>
            
            <!-- ============ RIGHT: User Menu & Actions ============ -->
            <div class="flex items-center space-x-2 lg:space-x-3">
                
                <!-- Notifications (optional) -->
                <button class="hidden sm:flex p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all relative">
                    <i class="fas fa-bell text-lg"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                
                <!-- User Menu Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        @click.away="open = false"
                        class="flex items-center space-x-2 lg:space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                        aria-expanded="false"
                        aria-haspopup="true"
                    >
                        <!-- Avatar -->
                        <div class="w-8 h-8 lg:w-10 lg:h-10 bg-gradient-to-br from-primary-500 to-secondary-500 rounded-full flex items-center justify-center text-white font-semibold text-sm lg:text-base shadow-md">
                            <?php if ($user_avatar): ?>
                                <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="w-full h-full rounded-full object-cover">
                            <?php else: ?>
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- User Info (hidden on mobile) -->
                        <div class="hidden lg:block text-left">
                            <p class="text-sm font-semibold text-gray-900 leading-tight">
                                <?php echo htmlspecialchars($user_name); ?>
                            </p>
                            <p class="text-xs text-gray-500 capitalize">
                                <?php echo htmlspecialchars($user_role); ?>
                            </p>
                        </div>
                        
                        <!-- Dropdown Arrow -->
                        <i class="fas fa-chevron-down text-xs text-gray-400 hidden lg:block" :class="{ 'rotate-180': open }"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div 
                        x-show="open"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-dropdown ring-1 ring-black ring-opacity-5 overflow-hidden z-50"
                        style="display: none;"
                    >
                        <!-- User Info Header -->
                        <div class="px-4 py-3 bg-gradient-to-r from-primary-50 to-secondary-50 border-b border-gray-200">
                            <p class="text-sm font-semibold text-gray-900">
                                <?php echo htmlspecialchars($user_name); ?>
                            </p>
                            <p class="text-xs text-gray-600 truncate">
                                <?php echo htmlspecialchars($user_email); ?>
                            </p>
                        </div>
                        
                        <!-- Menu Items -->
                        <div class="py-2">
                            <a href="<?php echo $profile_url; ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all">
                                <i class="fas fa-user-circle w-5 text-primary-500 mr-3"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="<?php echo $settings_url; ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all">
                                <i class="fas fa-cog w-5 text-primary-500 mr-3"></i>
                                <span>Settings</span>
                            </a>
                            <a href="<?php echo $home_url; ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all">
                                <i class="fas fa-home w-5 text-primary-500 mr-3"></i>
                                <span>Home Page</span>
                            </a>
                        </div>
                        
                        <!-- Logout -->
                        <div class="border-t border-gray-200">
                            <a href="<?php echo $logout_url; ?>" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-all font-medium">
                                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</header>

<!-- ================================
     MOBILE MENU SIDEBAR
     ================================ -->
<div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleMobileMenu()"></div>

<div id="mobileMenuPanel" class="fixed top-0 left-0 h-full w-80 max-w-[85%] bg-white shadow-2xl z-50 lg:hidden transform -translate-x-full transition-transform duration-300 overflow-y-auto">
    <!-- Mobile Menu Header -->
    <div class="sticky top-0 bg-gradient-to-r from-primary-600 to-secondary-600 p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold">Menu</h2>
            <button 
                onclick="toggleMobileMenu()" 
                class="p-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all"
                aria-label="Close menu"
            >
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- User Info in Mobile Menu -->
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-white bg-opacity-30 rounded-full flex items-center justify-center text-white font-bold text-lg">
                <?php if ($user_avatar): ?>
                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="w-full h-full rounded-full object-cover">
                <?php else: ?>
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div>
                <p class="font-semibold text-base"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="text-xs text-white text-opacity-80 capitalize"><?php echo htmlspecialchars($user_role); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Mobile Navigation Links -->
    <nav class="p-4 space-y-2">
        <a href="<?php echo $dashboard_url; ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all" onclick="toggleMobileMenu()">
            <i class="fas fa-home w-5 text-primary-500"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        
        <a href="<?php echo $dashboard_url; ?>#carWashSelection" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all" onclick="toggleMobileMenu()">
            <i class="fas fa-store w-5 text-primary-500"></i>
            <span class="font-medium">Car Washes</span>
        </a>
        
        <a href="<?php echo $dashboard_url; ?>#reservations" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all" onclick="toggleMobileMenu()">
            <i class="fas fa-calendar-check w-5 text-primary-500"></i>
            <span class="font-medium">My Reservations</span>
        </a>
        
        <a href="<?php echo $dashboard_url; ?>#vehicles" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all" onclick="toggleMobileMenu()">
            <i class="fas fa-car w-5 text-primary-500"></i>
            <span class="font-medium">My Vehicles</span>
        </a>
        
        <div class="border-t border-gray-200 my-3"></div>
        
        <a href="<?php echo $profile_url; ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all" onclick="toggleMobileMenu()">
            <i class="fas fa-user-circle w-5 text-primary-500"></i>
            <span class="font-medium">My Profile</span>
        </a>
        
        <a href="<?php echo $settings_url; ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all" onclick="toggleMobileMenu()">
            <i class="fas fa-cog w-5 text-primary-500"></i>
            <span class="font-medium">Settings</span>
        </a>
        
        <a href="<?php echo $home_url; ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-all" onclick="toggleMobileMenu()">
            <i class="fas fa-home w-5 text-primary-500"></i>
            <span class="font-medium">Home Page</span>
        </a>
        
        <div class="border-t border-gray-200 my-3"></div>
        
        <a href="<?php echo $logout_url; ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition-all font-medium">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<!-- Alpine.js for dropdown functionality (lightweight alternative to custom JS) -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Mobile Menu Toggle Script -->
<script>
function toggleMobileMenu() {
    const overlay = document.getElementById('mobileMenuOverlay');
    const panel = document.getElementById('mobileMenuPanel');
    const body = document.body;
    
    const isOpen = !panel.classList.contains('-translate-x-full');
    
    if (isOpen) {
        // Close menu
        panel.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        body.classList.remove('menu-open');
    } else {
        // Open menu
        panel.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        body.classList.add('menu-open');
    }
}

// Close menu on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const panel = document.getElementById('mobileMenuPanel');
        if (!panel.classList.contains('-translate-x-full')) {
            toggleMobileMenu();
        }
    }
});

// Close menu on window resize to desktop
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        const panel = document.getElementById('mobileMenuPanel');
        const overlay = document.getElementById('mobileMenuOverlay');
        const body = document.body;
        
        panel.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        body.classList.remove('menu-open');
    }
});

console.log('✅ Enhanced Dashboard Header loaded successfully');
</script>