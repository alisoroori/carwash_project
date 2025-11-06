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
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        /* Prevent body scroll when mobile menu open */
        body.menu-open {
            overflow: hidden;
        }
        
        /* Animation keyframes */
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
            }
            to {
                transform: translateX(0);
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
    </style>
</head>

<body 
    class="bg-gray-50 overflow-x-hidden" 
    x-data="{ mobileMenuOpen: false, currentSection: 'dashboard' }"
    x-effect="mobileMenuOpen ? document.body.classList.add('menu-open') : document.body.classList.remove('menu-open')"
>

<!-- ================================
     HEADER - Fixed at Top
     ================================ -->
<header class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 shadow-sm">
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
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-md">
                <i class="fas fa-car-wash text-white text-lg"></i>
            </div>
            <div class="hidden sm:block">
                <h1 class="text-lg font-bold text-gray-900 leading-tight">MYCAR</h1>
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
     SIDEBAR - Desktop Fixed, Mobile Slide-out
     ================================ -->

<!-- Mobile Sidebar Overlay -->
<div 
    x-show="mobileMenuOpen"
    @click="mobileMenuOpen = false"
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
    style="display: none;"
></div>

<!-- Sidebar -->
<aside 
    class="fixed top-16 left-0 h-[calc(100vh-4rem)] w-72 bg-gradient-to-b from-blue-600 via-blue-700 to-purple-700 text-white overflow-y-auto z-40 transition-transform duration-300 lg:translate-x-0 shadow-xl"
    :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="p-6">
        <!-- User Profile -->
        <div class="bg-white bg-opacity-10 rounded-2xl p-4 mb-6 backdrop-blur-sm border border-white border-opacity-20">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-white bg-opacity-30 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold truncate text-white"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="text-xs text-white text-opacity-80 truncate"><?php echo htmlspecialchars($user_email); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="space-y-1">
            <a 
                href="#dashboard" 
                @click="currentSection = 'dashboard'; mobileMenuOpen = false"
                :class="currentSection === 'dashboard' ? 'bg-white bg-opacity-20 shadow-md' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center px-4 py-3 rounded-xl transition-all duration-200"
            >
                <i class="fas fa-tachometer-alt w-5 mr-3 text-lg"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a 
                href="#carWashSelection" 
                @click="currentSection = 'carWashSelection'; mobileMenuOpen = false"
                :class="currentSection === 'carWashSelection' ? 'bg-white bg-opacity-20 shadow-md' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center px-4 py-3 rounded-xl transition-all duration-200"
            >
                <i class="fas fa-store w-5 mr-3 text-lg"></i>
                <span class="font-medium">Yıkama Yerleri</span>
            </a>
            
            <a 
                href="#reservations" 
                @click="currentSection = 'reservations'; mobileMenuOpen = false"
                :class="currentSection === 'reservations' ? 'bg-white bg-opacity-20 shadow-md' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center px-4 py-3 rounded-xl transition-all duration-200"
            >
                <i class="fas fa-calendar-check w-5 mr-3 text-lg"></i>
                <span class="font-medium">Rezervasyonlar</span>
            </a>
            
            <a 
                href="#vehicles" 
                @click="currentSection = 'vehicles'; mobileMenuOpen = false"
                :class="currentSection === 'vehicles' ? 'bg-white bg-opacity-20 shadow-md' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center px-4 py-3 rounded-xl transition-all duration-200"
            >
                <i class="fas fa-car w-5 mr-3 text-lg"></i>
                <span class="font-medium">Araçlarım</span>
            </a>
            
            <a 
                href="#history" 
                @click="currentSection = 'history'; mobileMenuOpen = false"
                :class="currentSection === 'history' ? 'bg-white bg-opacity-20 shadow-md' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center px-4 py-3 rounded-xl transition-all duration-200"
            >
                <i class="fas fa-history w-5 mr-3 text-lg"></i>
                <span class="font-medium">Geçmiş</span>
            </a>
            
            <a 
                href="#profile" 
                @click="currentSection = 'profile'; mobileMenuOpen = false"
                :class="currentSection === 'profile' ? 'bg-white bg-opacity-20 shadow-md' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center px-4 py-3 rounded-xl transition-all duration-200"
            >
                <i class="fas fa-user-circle w-5 mr-3 text-lg"></i>
                <span class="font-medium">Profil</span>
            </a>
            
            <a 
                href="#support" 
                @click="currentSection = 'support'; mobileMenuOpen = false"
                :class="currentSection === 'support' ? 'bg-white bg-opacity-20 shadow-md' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center px-4 py-3 rounded-xl transition-all duration-200"
            >
                <i class="fas fa-headset w-5 mr-3 text-lg"></i>
                <span class="font-medium">Destek</span>
            </a>
        </nav>
    </div>
</aside>

<!-- ================================
     MAIN CONTENT AREA
     ================================ -->
<main class="pt-16 lg:ml-72 min-h-screen bg-gray-50">
    <div class="p-4 md:p-6 lg:p-8 max-w-7xl mx-auto">
        
        <!-- ========== DASHBOARD SECTION ========== -->
        <section x-show="currentSection === 'dashboard'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Dashboard</h2>
                <p class="text-gray-600">Hoş geldiniz, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                <!-- Stat Card 1 -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-600">Toplam Rezervasyon</h4>
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">24</p>
                    <p class="text-sm text-gray-500 mt-2 flex items-center">
                        <i class="fas fa-arrow-up text-green-500 mr-1 text-xs"></i>
                        12% artış
                    </p>
                </div>
                
                <!-- Stat Card 2 -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-600">Tamamlanan</h4>
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">18</p>
                    <p class="text-sm text-gray-500 mt-2">Bu ay</p>
                </div>
                
                <!-- Stat Card 3 -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-600">Bekleyen</h4>
                        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">4</p>
                    <p class="text-sm text-gray-500 mt-2">Onay bekliyor</p>
                </div>
                
                <!-- Stat Card 4 -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-600">Kayıtlı Araç</h4>
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-car text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900" id="vehicleStatCount">-</p>
                    <p class="text-sm text-gray-500 mt-2">Aktif</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="mt-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Hızlı İşlemler</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                        <i class="fas fa-plus-circle text-5xl mb-4 opacity-90"></i>
                        <h4 class="text-xl font-bold mb-2">Yeni Rezervasyon</h4>
                        <p class="text-blue-100 mb-6">Araç yıkama hizmeti rezervasyonu oluşturun</p>
                        <button 
                            @click="currentSection = 'carWashSelection'"
                            class="bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 transition-colors inline-flex items-center space-x-2 shadow-md"
                        >
                            <span>Rezervasyon Yap</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                        <i class="fas fa-car text-5xl mb-4 opacity-90"></i>
                        <h4 class="text-xl font-bold mb-2">Araç Ekle</h4>
                        <p class="text-green-100 mb-6">Yeni araç bilgisi kaydedin</p>
                        <button 
                            @click="currentSection = 'vehicles'"
                            class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 transition-colors inline-flex items-center space-x-2 shadow-md"
                        >
                            <span>Araç Ekle</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- ========== VEHICLES SECTION ========== -->
        <section x-show="currentSection === 'vehicles'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6" x-data="vehicleManager()" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Araçlarım</h2>
                <p class="text-gray-600">Kayıtlı araçlarınızı yönetin</p>
            </div>
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <p class="text-sm text-gray-600" x-text="vehicles.length + ' kayıtlı araç'"></p>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <button 
                        @click="loadVehicles()"
                        class="w-full sm:w-auto px-4 py-2.5 border-2 border-blue-600 text-blue-600 rounded-xl font-semibold hover:bg-blue-50 active:bg-blue-100 transition-colors inline-flex items-center justify-center space-x-2"
                    >
                        <i class="fas fa-sync-alt"></i>
                        <span>Yenile</span>
                    </button>
                    <button 
                        @click="openVehicleForm()"
                        class="w-full sm:w-auto px-4 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg active:shadow-md transition-all inline-flex items-center justify-center space-x-2"
                    >
                        <i class="fas fa-plus"></i>
                        <span>Araç Ekle</span>
                    </button>
                </div>
            </div>
            
            <!-- Vehicles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6" id="vehiclesList">
                <template x-for="vehicle in vehicles" :key="vehicle.id">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-start space-x-4 mb-4">
                            <img 
                                :src="vehicle.image_path || '/carwash_project/frontend/assets/images/default-car.png'" 
                                :alt="vehicle.brand + ' ' + vehicle.model"
                                class="w-20 h-20 rounded-xl object-cover bg-gray-100 flex-shrink-0"
                                @error="$el.src='/carwash_project/frontend/assets/images/default-car.png'"
                            >
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-lg text-gray-900 truncate" x-text="vehicle.brand + ' ' + vehicle.model"></h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-id-card mr-1"></i>
                                    <span x-text="vehicle.license_plate"></span>
                                </p>
                                <div class="flex items-center flex-wrap gap-3 mt-2 text-xs text-gray-500">
                                    <span x-show="vehicle.year">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <span x-text="vehicle.year"></span>
                                    </span>
                                    <span x-show="vehicle.color">
                                        <i class="fas fa-palette mr-1"></i>
                                        <span x-text="vehicle.color"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-2 pt-4 border-t border-gray-100">
                            <button 
                                @click="editVehicle(vehicle)"
                                class="flex-1 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors font-medium text-sm"
                            >
                                <i class="fas fa-edit mr-1"></i>
                                Düzenle
                            </button>
                            <button 
                                @click="deleteVehicle(vehicle.id)"
                                class="flex-1 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors font-medium text-sm"
                            >
                                <i class="fas fa-trash mr-1"></i>
                                Sil
                            </button>
                        </div>
                    </div>
                </template>
                
                <!-- Empty State -->
                <template x-if="vehicles.length === 0">
                    <div class="col-span-full text-center py-16 bg-white rounded-2xl border-2 border-dashed border-gray-300">
                        <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg mb-4">Henüz kayıtlı araç yok</p>
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
                    
                    <form @submit.prevent="saveVehicle()" class="p-6 space-y-6">
                        <input type="hidden" name="csrf_token" :value="csrfToken">
                        <input type="hidden" name="action" :value="editingVehicle ? 'update' : 'create'">
                        <input type="hidden" name="id" :value="editingVehicle?.id || ''">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
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
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                                    placeholder="Corolla"
                                >
                            </div>
                        <div>
                            <div>
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
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                                    placeholder="Toyota"
                                >
                            </div>
                            
                            <div>
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
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                                    placeholder="34 ABC 123"
                                >
                            </div>
                            
                            <div>
                                <label for="vehicle_year" class="block text-sm font-semibold text-gray-700 mb-2">Yıl</label>
                                <input 
                                    type="number"
                                    id="vehicle_year"
                                    name="car_year"
                                    x-model="formData.year"
                                    min="1900"
                                    :max="new Date().getFullYear()"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                                    placeholder="2020"
                                >
                            </div>
                            
                            <div>
                                <label for="vehicle_color" class="block text-sm font-semibold text-gray-700 mb-2">Renk</label>
                                <input 
                                    type="text"
                                    id="vehicle_color"
                                    name="car_color"
                                    x-model="formData.color"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                                    placeholder="Beyaz"
                                >
                            </div>
                            
                            <div>
                                <label for="vehicle_image" class="block text-sm font-semibold text-gray-700 mb-2">Araç Fotoğrafı</label>
                                <input 
                                    type="file"
                                    id="vehicle_image"
                                    name="vehicle_image"
                                    @change="previewImage($event)"
                                    accept="image/*"
                                    class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border-2 border-gray-300 rounded-xl"
                                >
                            </div>
                        </div>
                        
                        <div>
                            <p class="block text-sm font-semibold text-gray-700 mb-2">Önizleme</p>
                            <img 
                                :src="imagePreview || '/carwash_project/frontend/assets/images/default-car.png'"
                                alt="Preview"
                                class="w-32 h-24 object-cover rounded-xl border-2 border-gray-300"
                            >
                        </div>
                        
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
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                <form class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="profile_name" class="block text-sm font-semibold text-gray-700 mb-2">Ad Soyad</label>
                            <input 
                                type="text"
                                id="profile_name"
                                name="name"
                                value="<?php echo htmlspecialchars($user_name); ?>"
                                autocomplete="name"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                            >
                        </div>
                        
                        <div>
                            <label for="profile_email" class="block text-sm font-semibold text-gray-700 mb-2">E-posta</label>
                            <input 
                                type="email"
                                id="profile_email"
                                name="email"
                                value="<?php echo htmlspecialchars($user_email); ?>"
                                autocomplete="email"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                            >
                        </div>
                        
                        <div>
                            <label for="profile_phone" class="block text-sm font-semibold text-gray-700 mb-2">Telefon</label>
                            <input 
                                type="tel"
                                id="profile_phone"
                                name="phone"
                                placeholder="+90 555 123 45 67"
                                autocomplete="tel"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                            >
                        </div>
                        
                        <div>
                            <label for="profile_city" class="block text-sm font-semibold text-gray-700 mb-2">Şehir</label>
                            <input 
                                type="text"
                                id="profile_city"
                                name="city"
                                placeholder="İstanbul"
                                autocomplete="address-level2"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                            >
                        </div>
                    </div>
                    
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
                        <button 
                            type="button"
                            class="w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors"
                        >
                            İptal
                        </button>
                        <button 
                            type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center justify-center space-x-2"
                        >
                            <i class="fas fa-save"></i>
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
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                <form class="space-y-6">
                    <div>
                        <label for="support_subject" class="block text-sm font-semibold text-gray-700 mb-2">Konu</label>
                        <input 
                            type="text"
                            id="support_subject"
                            name="subject"
                            placeholder="Sorununuzun kısa açıklaması"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                        >
                    </div>
                    
                    <div>
                        <label for="support_message" class="block text-sm font-semibold text-gray-700 mb-2">Mesaj</label>
                        <textarea 
                            id="support_message"
                            name="message"
                            rows="6"
                            placeholder="Sorununuzu detaylı olarak açıklayın"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors resize-none"
                        ></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center justify-center space-x-2"
                        >
                            <i class="fas fa-paper-plane"></i>
                            <span>Gönder</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- Other sections (reservations, carWashSelection, history) would follow the same pattern -->
        
    </div>
</main>

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
        
        async loadVehicles() {
            try {
                const res = await fetch('/carwash_project/backend/dashboard/vehicle_api.php?action=list', {
                    credentials: 'same-origin'
                });
                const data = await res.json();
                this.vehicles = data.vehicles || data.data?.vehicles || [];
                
                // Update stat count
                const statEl = document.getElementById('vehicleStatCount');
                if (statEl) statEl.textContent = this.vehicles.length;
            } catch (error) {
                console.error('Load vehicles error:', error);
                this.showMessage('Araçlar yüklenemedi', 'error');
            }
        },
        
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
        
        closeVehicleForm() {
            this.showVehicleForm = false;
            this.resetForm();
            document.body.classList.remove('menu-open');
        },
        
        resetForm() {
            this.editingVehicle = null;
            this.formData = { brand: '', model: '', license_plate: '', year: '', color: '' };
            this.imagePreview = '';
            this.message = '';
        },
        
        async saveVehicle() {
            this.loading = true;
            this.message = '';
            
            try {
                const form = document.querySelector('form[x-data]');
                const formData = new FormData(form);
                
                const res = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success || data.status === 'success') {
                    this.showMessage(this.editingVehicle ? 'Araç güncellendi' : 'Araç eklendi', 'success');
                    await this.loadVehicles();
                    setTimeout(() => this.closeVehicleForm(), 1500);
                } else {
                    this.showMessage(data.message || 'İşlem başarısız', 'error');
                }
            } catch (error) {
                console.error('Save vehicle error:', error);
                this.showMessage('Bir hata oluştu', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        editVehicle(vehicle) {
            this.openVehicleForm(vehicle);
        },
        
        async deleteVehicle(id) {
            if (!confirm('Bu aracı silmek istediğinizden emin misiniz?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                formData.append('csrf_token', this.csrfToken);
                
                const res = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success || data.status === 'success') {
                    await this.loadVehicles();
                } else {
                    alert(data.message || 'Silme işlemi başarısız');
                }
            } catch (error) {
                console.error('Delete vehicle error:', error);
                alert('Bir hata oluştu');
            }
        },
        
        previewImage(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        
        showMessage(msg, type) {
            this.message = msg;
            this.messageType = type;
            setTimeout(() => {
                this.message = '';
            }, 5000);
        }
    }
}

console.log('✅ Customer Dashboard loaded successfully');
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>

