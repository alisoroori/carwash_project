<?php
/**
 * Customer Dashboard for CarWash Web Application
 * Uses the universal header/footer system with dashboard context
 */

// Replace manual session_start with bootstrap and RBAC enforcement
// Previously used an incorrect relative path which caused the fatal error.
// Use the project's bootstrap if available, otherwise fallback to vendor/autoload.php at repo root.
$bootstrapPath = __DIR__ . '/../includes/bootstrap.php';
$vendorAutoloadFallback = __DIR__ . '/../../vendor/autoload.php';

if (file_exists($bootstrapPath)) {
	require_once $bootstrapPath;
} elseif (file_exists($vendorAutoloadFallback)) {
	// minimal fallback: load composer autoloader if bootstrap missing
	require_once $vendorAutoloadFallback;
} else {
	// Log and show friendly message (do not leak paths/stack traces)
	error_log('Bootstrap/autoload not found for Customer_Dashboard.php');
	http_response_code(500);
	echo 'Application initialization failed. Please contact the administrator.';
	exit;
}

use App\Classes\Session;
use App\Classes\Auth;

// Start session via Session wrapper if available, otherwise fallback
if (class_exists(Session::class) && method_exists(Session::class, 'start')) {
    Session::start();
} else {
    if (session_status() == PHP_SESSION_NONE) session_start();
}

// Require authenticated customer
Auth::requireRole('customer');

// Ensure server-side CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // fallback
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Set page-specific variables for the dashboard header
$dashboard_type = 'customer';  // Specify this is the customer dashboard
$page_title = 'Müşteri Paneli - CarWash';
$current_page = 'dashboard';

// Include the universal dashboard header
include '../includes/dashboard_header.php';
?>

<!-- Dashboard Specific Styles -->
<style>
    /* Dashboard-specific overrides only - Universal fixes included via header */
    
    /* Dashboard Content Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-30px); }
      to { opacity: 1; transform: translateX(0); }
    }

    .animate-fade-in-up {
      animation: fadeInUp 0.6s ease-out forwards;
    }

    .animate-slide-in {
      animation: slideIn 0.5s ease-out forwards;
    }

    .gradient-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .sidebar-gradient {
      background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    }

    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }

    .status-confirmed {
      background: #d1fae5;
      color: #065f46;
    }

    .status-completed {
      background: #e0e7ff;
      color: #3730a3;
    }

    /* Dashboard-specific responsive design - Single scroll layout */
    
    /* Remove default body/html styles from header */
    html, body {
      height: auto !important;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }
    
    body {
      display: block !important;
      flex-direction: initial !important;
    }
    
    /* Dashboard container - positioned below header */
    .dashboard-container {
      position: relative;
      width: 100%;
      min-height: 100vh; /* Full viewport height to ensure sidebar stretches */
      display: flex;
      flex-direction: column;
      background: #f8fafc;
    }

    /* Dashboard Sidebar Styles */
    .mobile-sidebar {
      position: fixed;
      top: 0;
      left: -100%;
      width: 280px;
      height: 100vh;
      z-index: 40;
      transition: left 0.3s ease;
      overflow-y: auto;
      padding-top: 70px; /* Space for header */
    }

    .mobile-sidebar.active {
      left: 0;
    }

    .mobile-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      background: rgba(0, 0, 0, 0.5);
      z-index: 39;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .mobile-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    /* Dashboard Mobile Menu Button */
    .mobile-menu-btn {
      display: block;
      position: fixed;
      top: 75px;
      left: 1rem;
      z-index: 50;
      background: #667eea;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 12px 16px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .mobile-menu-btn:hover {
      background: #5a67d8;
      transform: translateY(-1px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .mobile-menu-btn:active {
      transform: translateY(0);
    }

    .mobile-menu-btn.active {
      background: #e53e3e;
    }

    /* Desktop Sidebar - Sticky position inside container */
    .desktop-sidebar {
      display: none;
      position: sticky;
      top: 65px; /* Stick below header */
      left: 0;
      width: 280px;
      min-height: calc(100vh - 65px); /* Minimum height from header to bottom of viewport */
      overflow: hidden; /* no internal scroll by default */
      flex-shrink: 0; /* Don't shrink in flex container */
      z-index: 30;
      background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
      align-self: stretch; /* Stretch to fill full height of flex container */
    }
    
    /* Desktop sidebar inner content - pinned and anchored */
    .desktop-sidebar .p-6 {
      height: 100%;
      overflow: hidden; /* prevent internal scroll */
      display: flex;
      flex-direction: column;
      padding: 1rem 1.5rem;
      box-sizing: border-box;
      position: relative; /* ensure children are positioned relative to sidebar */
    }
    
    /* User profile section - compact */
    .desktop-sidebar .text-center {
      flex-shrink: 0;
    }
    
    /* Navigation section - anchor to top so items don't shift when page scrolls */
    .desktop-sidebar nav {
      flex: 0 0 auto; /* do not expand to force scroll */
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 0.4rem;
      max-height: calc(100% - 120px); /* leave space for profile area */
    }

    /* If nav has more items than fit, optionally allow internal scrolling while keeping sidebar fixed */
    .desktop-sidebar nav.scrollable {
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }
    
    /* Reduce spacing for nav items to fit all content */
    .desktop-sidebar nav.space-y-2 {
      gap: 0.4rem;
      display: flex;
      flex-direction: column;
    }
    
    .desktop-sidebar .sidebar-link {
      padding: 0.625rem 0.75rem;
      flex-shrink: 0;
    }
    
    /* Compact user profile */
    .desktop-sidebar .w-20.h-20 {
      width: 4rem;
      height: 4rem;
    }
    
    .desktop-sidebar .text-center.mb-8 {
      margin-bottom: 1.5rem;
    }
    
    /* Ensure sidebar icons and text fit */
    .desktop-sidebar h3 {
      font-size: 1.125rem;
    }
    
    .desktop-sidebar .text-sm {
      font-size: 0.813rem;
    }

    /* Main Content Area - Single scroll for all content */
    .main-content {
      flex: 1; /* Take remaining space */
      padding: 1rem;
      min-height: calc(100vh - 65px);
    }

    /* Responsive Breakpoints */
    @media (min-width: 1024px) {
      .mobile-menu-btn {
        display: none;
      }
      
      .mobile-sidebar {
        display: none;
      }
      
      .desktop-sidebar {
        display: block;
      }
      
      .main-content {
        padding: 2rem;
      }
      
      .dashboard-container {
        flex-direction: row;
      }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
      .main-content {
        padding: 1.5rem;
      }
      
      .mobile-sidebar {
        width: 320px;
      }
    }
    
    @media (max-width: 767px) {
      .main-content {
        padding: 1rem 0.75rem;
      }
      
      .mobile-menu-btn {
        top: 70px;
        left: 0.75rem;
        padding: 10px 14px;
      }
    }
    
    /* Footer adjustments */
    footer {
      margin-top: 0 !important;
      position: relative;
    }
    
    /* Section content styling */
    .section-content {
      width: 100%;
      max-width: 100%;
    }
    
    /* Card and content max-widths for readability */
    @media (min-width: 1400px) {
      .main-content {
        max-width: calc(1200px + 280px);
      }
    }
    
    /* Smooth scrollbar styling */
    .desktop-sidebar::-webkit-scrollbar {
      display: none;
    }
</style>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleMobileSidebar()" id="mobileMenuBtn">
    <i class="fas fa-bars" id="menuIcon"></i>
</button>

<!-- Mobile Overlay -->
<div class="mobile-overlay" onclick="closeMobileSidebar()" id="mobileOverlay"></div>

<!-- Dashboard Layout -->
<div class="dashboard-container">
    <!-- Mobile Sidebar -->
    <aside class="mobile-sidebar sidebar-gradient text-white" id="mobileSidebar">
      <div class="p-6">
        <div class="text-center mb-8">
          <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user text-3xl"></i>
          </div>
          <h3 class="text-xl font-bold"><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User'); ?></h3>
          <p class="text-sm opacity-75"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
        </div>

        <nav class="space-y-2">
          <a href="#dashboard" onclick="showSection('dashboard')" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20 sidebar-link">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Genel Bakış
          </a>
          <a href="#carWashSelection" onclick="showSection('carWashSelection')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-hand-pointer mr-3"></i>
            Oto Yıkama Seçimi
          </a>
          <a href="#reservations" onclick="showSection('reservations')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-calendar-check mr-3"></i>
            Rezervasyonlarım
          </a>
          <a href="#profile" onclick="showSection('profile')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-user-edit mr-3"></i>
            Profil Yönetimi
          </a>
          <a href="#vehicles" onclick="showSection('vehicles')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-car mr-3"></i>
            Araçlarım
          </a>
          <a href="#history" onclick="showSection('history')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-history mr-3"></i>
            Geçmiş İşlemler
          </a>
          <a href="#support" onclick="showSection('support')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-headset mr-3"></i>
            Destek
          </a>
          <a href="#settings" onclick="showSection('settings')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-cog mr-3"></i>
            Ayarlar
          </a>
        </nav>
      </div>
    </aside>

    <!-- Desktop Sidebar -->
    <aside class="desktop-sidebar sidebar-gradient text-white">
      <div class="p-6">
        <div class="text-center mb-8">
          <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user text-3xl"></i>
          </div>
          <h3 class="text-xl font-bold"><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User'); ?></h3>
          <p class="text-sm opacity-75"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
        </div>

        <nav class="space-y-2">
          <a href="#dashboard" onclick="showSection('dashboard')" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20 sidebar-link">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Genel Bakış
          </a>
          <a href="#carWashSelection" onclick="showSection('carWashSelection')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-hand-pointer mr-3"></i>
            Oto Yıkama Seçimi
          </a>
          <a href="#reservations" onclick="showSection('reservations')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-calendar-check mr-3"></i>
            Rezervasyonlarım
          </a>
          <a href="#profile" onclick="showSection('profile')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-user-edit mr-3"></i>
            Profil Yönetimi
          </a>
          <a href="#vehicles" onclick="showSection('vehicles')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-car mr-3"></i>
            Araçlarım
          </a>
          <a href="#history" onclick="showSection('history')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-history mr-3"></i>
            Geçmiş İşlemler
          </a>
          <a href="#support" onclick="showSection('support')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-headset mr-3"></i>
            Destek
          </a>
          <a href="#settings" onclick="showSection('settings')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors sidebar-link">
            <i class="fas fa-cog mr-3"></i>
            Ayarlar
          </a>
        </nav>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Dashboard Overview -->
      <section id="dashboard" class="section-content">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Genel Bakış</h2>
          <p class="text-gray-600">Hesabınızın özeti ve son aktiviteleriniz</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Toplam Rezervasyon</p>
                <p class="text-3xl font-bold text-blue-600">24</p>
              </div>
              <i class="fas fa-calendar-check text-4xl text-blue-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Bu Ay</p>
                <p class="text-3xl font-bold text-green-600">5</p>
              </div>
              <i class="fas fa-calendar-day text-4xl text-green-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Toplam Harcama</p>
                <p class="text-3xl font-bold text-purple-600">₺1,240</p>
              </div>
              <i class="fas fa-money-bill-wave text-4xl text-purple-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Ortalama Puan</p>
                <p class="text-3xl font-bold text-yellow-600">4.8★</p>
              </div>
              <i class="fas fa-star text-4xl text-yellow-600 opacity-20"></i>
            </div>
          </div>
        </div>

        <!-- Recent Reservations -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-clock text-blue-600 mr-2"></i>
              Yaklaşan Rezervasyonlar
            </h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                  <h4 class="font-bold">Dış Yıkama + İç Temizlik</h4>
                  <p class="text-sm text-gray-600">Bugün, 14:00 - CarWash Merkez</p>
                </div>
                <span class="status-confirmed px-3 py-1 rounded-full text-xs font-bold">Onaylandı</span>
              </div>

              <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                  <h4 class="font-bold">Tam Detaylandırma</h4>
                  <p class="text-sm text-gray-600">Yarın, 10:00 - CarWash Premium</p>
                </div>
                <span class="status-pending px-3 py-1 rounded-full text-xs font-bold">Bekliyor</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-bell text-blue-600 mr-2"></i>
              Bildirimler
            </h3>
            <div class="space-y-4">
              <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-600">
                <p class="text-sm">Rezervasyonunuz onaylandı. 14:00'te CarWash Merkez'de olun.</p>
                <p class="text-xs text-gray-500 mt-1">2 saat önce</p>
              </div>

              <div class="p-4 bg-green-50 rounded-lg border-l-4 border-green-600">
                <p class="text-sm">Önceki hizmetiniz tamamlandı. Puan vererek deneyimlerinizi paylaşın!</p>
                <p class="text-xs text-gray-500 mt-1">1 gün önce</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Oto Yıkama Seçimi Section -->
      <section id="carWashSelection" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Oto Yıkama Seçimi</h2>
          <p class="text-gray-600">Size en uygun oto yıkama merkezini bulun ve rezervasyon yapın.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Filtreleme Seçenekleri</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label for="cityFilter" class="block text-sm font-bold text-gray-700 mb-2">Şehir</label>
              <select id="cityFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">Tüm Şehirler</option>
                <!-- Cities are loaded dynamically from the carwashes API -->
              </select>
            </div>
            <div>
              <label for="districtFilter" class="block text-sm font-bold text-gray-700 mb-2">Mahalle</label>
              <select id="districtFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">Tüm Mahalleler</option>
                <!-- Options will be dynamically loaded based on city -->
              </select>
            </div>
            <div>
              <label for="carWashNameFilter" class="block text-sm font-bold text-gray-700 mb-2">CarWash Adı</label>
              <input type="text" id="carWashNameFilter" onkeyup="filterCarWashes()" placeholder="CarWash adı girin..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex items-end">
              <label for="favoriteFilter" class="flex items-center cursor-pointer">
                <input type="checkbox" id="favoriteFilter" onchange="filterCarWashes()" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                <span class="ml-2 text-sm font-bold text-gray-700">Favorilerim</span>
              </label>
            </div>
          </div>
        </div>

        <div id="carWashList" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
          <!-- Car wash cards will be loaded here by JavaScript -->
        </div>
      </section>

      <!-- Reservations Section -->
      <section id="reservations" class="section-content hidden">
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
                <button onclick="showNewReservationForm()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                  <i class="fas fa-plus mr-2"></i>Yeni Rezervasyon
                </button>
              </div>
            </div>

            <div class="universal-table-container">
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
                <tbody class="divide-y divide-gray-200">
                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                      <div>
                        <div class="font-medium">Dış Yıkama + İç Temizlik</div>
                        <div class="text-sm text-gray-500">Toyota Corolla - 34 ABC 123</div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm">15.12.2024<br>14:00</td>
                    <td class="px-6 py-4 text-sm">CarWash Merkez</td>
                    <td class="px-6 py-4"><span class="status-confirmed px-2 py-1 rounded-full text-xs">Onaylandı</span></td>
                    <td class="px-6 py-4 font-medium">₺130</td>
                    <td class="px-6 py-4 text-sm">
                      <button class="text-blue-600 hover:text-blue-900 mr-3">Düzenle</button>
                      <button class="text-red-600 hover:text-red-900">İptal</button>
                    </td>
                  </tr>

                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                      <div>
                        <div class="font-medium">Tam Detaylandırma</div>
                        <div class="text-sm text-gray-500">Honda Civic - 34 XYZ 789</div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm">16.12.2024<br>10:00</td>
                    <td class="px-6 py-4 text-sm">CarWash Premium</td>
                    <td class="px-6 py-4"><span class="status-pending px-2 py-1 rounded-full text-xs">Bekliyor</span></td>
                    <td class="px-6 py-4 font-medium">₺200</td>
                    <td class="px-6 py-4 text-sm">
                      <button class="text-blue-600 hover:text-blue-900 mr-3">Düzenle</button>
                      <button class="text-red-600 hover:text-red-900">İptal</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- New Reservation Form (embedded booking UI) -->
          <script>
          /* Replacement: renderVehiclesList ensures safe image fallback and proper event wiring.
             Note: this script is plain HTML/JS and must not be inside a &lt;?php ?&gt; block. */
          
          function escapeHtml(text) {
            if (text == null) return '';
            return String(text)
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#39;');
          }
          
          function resolveVehicleImageUrl(path) {
            if (!path || path.trim() === '') {
              return '/carwash_project/frontend/assets/default-car.png';
            }
            
            // If already relative or HTTP/HTTPS, return as-is
            if (path.startsWith('/') || path.startsWith('http://') || path.startsWith('https://')) {
              return path;
            }
            
            // If absolute Windows path, try to convert to relative
            if (/^[A-Za-z]:/.test(path)) {
              // This shouldn't happen with the database fixes, but handle it just in case
              const docRoot = '/carwash_project'; // Assuming this is the web root
              // For absolute paths, we'd need server-side conversion, but for now return default
              console.warn('Absolute path detected in resolveVehicleImageUrl:', path);
              return '/carwash_project/frontend/assets/default-car.png';
            }
            
            // Other cases - assume relative
            return path;
          }
          
          function renderVehiclesList(vehicles) {
            const container = document.getElementById('vehiclesList');
            container.innerHTML = '';
            if (!Array.isArray(vehicles) || vehicles.length === 0) {
              container.innerHTML = '<p class="text-gray-500">Kayıtlı araç bulunamadı.</p>';
              return;
            }
            vehicles.forEach(v => {
              const card = document.createElement('div');
              card.className = 'bg-white rounded-2xl p-6 card-hover shadow-lg';
              card.setAttribute('data-vehicle-id', v.id);
              const imgSrc = resolveVehicleImageUrl(v.image_path);
              const brandModel = `${escapeHtml(v.brand || '')} ${escapeHtml(v.model || '')}`.trim();
          
              card.innerHTML = `
                <div class="flex justify-between items-start mb-4">
                  <div class="flex items-start gap-4">
                    <div class="w-20 h-12 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center flex-shrink-0">
                      <img
                        src="${imgSrc}"
                        alt="${escapeHtml(brandModel || 'Araç')}"
                        class="w-full h-full object-cover"
                        data-original-src="${imgSrc}"
                        onerror="this.onerror=null;this.src='/carwash_project/frontend/assets/default-car.png';"
                        loading="lazy"
                      />
                    </div>
          
                    <div>
                      <h4 class="font-bold text-lg text-gray-800">${escapeHtml(v.brand || '')} ${escapeHtml(v.model || '')}</h4>
                      <p class="text-xs text-gray-500 mt-1">${escapeHtml(v.year || '')} • ${escapeHtml(v.color || '')}</p>
                    </div>
                  </div>
          
                  <div>
                    <button class="text-blue-600 mr-3" data-action="edit" aria-label="Düzenle">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="text-red-600" data-action="delete" aria-label="Sil">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
          
                <div class="space-y-1 text-sm text-gray-600">
                  <p><span class="font-medium">Plaka:</span> ${escapeHtml(v.license_plate || '—')}</p>
                  <p><span class="font-medium">Model:</span> ${escapeHtml(v.model || '—')}</p>
                  <p><span class="font-medium">Renk:</span> ${escapeHtml(v.color || '—')}</p>
                </div>
              `;
          
              // Attach event handlers for edit/delete
              card.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                  const action = btn.getAttribute('data-action');
                  if (action === 'edit') {
                    // Prefer existing modal function if available
                    if (typeof openEditVehicleModal === 'function') {
                      openEditVehicleModal(v);
                    } else {
                      openVehicleModal({ id: v.id, brand: v.brand, model: v.model, license_plate: v.license_plate, year: v.year, color: v.color });
                    }
                  } else if (action === 'delete') {
                    deleteVehicle(v.id);  // Now defined below
                  }
                });
              });
          
              container.appendChild(card);
            });
          }

          </script>
          <div id="newReservationForm" class="p-6 hidden">
            <h3 class="text-xl font-bold mb-6">Yeni Rezervasyon Oluştur</h3>

            <div id="embeddedBooking" class="space-y-6">
              <input type="hidden" id="editingBookingId" value="">
              <!-- Services loaded dynamically -->
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Hizmet Seçin</label>
                <div id="embeddedServices" class="space-y-2">
                  <div class="text-sm muted">Hizmetler yükleniyor...</div>
                </div>
              </div>

              <!-- Vehicle selector (existing vehicles) with persistent preview image -->
              <div>
                <label for="vehicle" class="block text-sm font-bold text-gray-700 mb-2">Araç Seçin</label>
                <div class="flex items-center gap-4">
                  <img id="vehiclePreview" src="/carwash_project/frontend/assets/default-car.png" alt="Araç" style="width:72px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
                  <select id="vehicle" name="vehicle_id" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="">Araç Seçiniz</option>
                    <!-- user vehicles populated dynamically -->
                  </select>
                </div>
              </div>

              <!-- Date & Time -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
                <div>
                  <label for="reservationDate" class="block text-sm font-bold text-gray-700 mb-2">Tarih</label>
                  <input type="date" id="reservationDate" name="reservation_date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required">
                </div>
                <div>
                  <label for="reservationTime" class="block text-sm font-bold text-gray-700 mb-2">Saat</label>
                  <select id="reservationTime" name="reservation_time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required">
                    <option value="">Saat seçin</option>
                  </select>
                </div>
              </div>

              <!-- Location select already exists on dashboard; keep it for consistency -->
              <div>
                <label for="location" class="block text-sm font-bold text-gray-700 mb-2">Konum</label>
                <select id="location" name="carwash_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required">
                  <option value="">Konum Seçiniz</option>
                </select>
              </div>

              <!-- Notes -->
              <div>
                <label for="notes" class="block text-sm font-bold text-gray-700 mb-2">Ek Notlar (İsteğe Bağlı)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Özel istekleriniz veya notlarınız..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
              </div>

              <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
                <button type="button" onclick="hideNewReservationForm()" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors">
                  Geri Dön
                </button>
                <button id="embeddedConfirm" type="button" class="gradient-bg text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg transition-all">
                  <i class="fas fa-calendar-plus mr-2"></i>Rezervasyon Yap
                </button>
              </div>
            </div>

            <script>
              (function(){
                const API_SERVICES = '/carwash_project/backend/api/services/list.php';
                const API_CARWASHES = '/carwash_project/backend/api/carwashes/list.php';
                const API_CREATE = '/carwash_project/backend/api/bookings/create.php';
                const API_UPDATE = '/carwash_project/backend/api/bookings/update.php';

                const el = id => document.getElementById(id);

                // Populate carwash options into #location
                async function loadCarwashes(){
                  try{
                    const resp = await fetch(API_CARWASHES,{
                      cache: 'no-store',
                      credentials: 'same-origin',
                      headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                      }
                    });
                    const list = await resp.json();
                    const loc = el('location');
                    loc.innerHTML = '<option value="">Konum Seçiniz</option>';
                    list.forEach(cw=>{ const o = document.createElement('option'); o.value=cw.id; o.textContent=cw.name; loc.appendChild(o); });
                    // If redirected from dashboard with query params, preselect
                    const qs = new URLSearchParams(window.location.search);
                    const preId = qs.get('carwash_id');
                    if(preId) loc.value = preId;
                    if(loc.value) loadServicesForCarwash(loc.value);
                  }catch(e){ console.warn('Failed to load carwashes',e); }
                }

                async function loadServicesForCarwash(carwashId){
                  try{
                    const resp = await fetch(API_SERVICES + '?carwash_id=' + encodeURIComponent(carwashId),{
                      cache: 'no-store',
                      credentials: 'same-origin',
                      headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                      }
                    });
                    const svcs = await resp.json();
                    const container = el('embeddedServices');
                    container.innerHTML = '';
                    if(!Array.isArray(svcs) || svcs.length===0){ container.innerHTML = '<div class="muted">Hizmet bulunamadı</div>'; return; }
                    svcs.forEach(s=>{
                      const d = document.createElement('div'); d.className='p-3 border rounded-lg flex justify-between items-center cursor-pointer'; d.innerHTML = `<div><div style=\"font-weight:600\">${s.name}</div><div class=\"small muted\">${s.description||''}</div></div><div style=\"font-weight:700\">₺${Number(s.price||0).toFixed(2)}</div>`;
                      d.onclick = ()=>{ selectServiceEmbedded(s); };
                      container.appendChild(d);
                    });
                  }catch(e){ console.warn('Failed to load services',e); }
                }

                let selectedService = null;
                function selectServiceEmbedded(s){ selectedService = s; document.querySelectorAll('#embeddedServices > div').forEach(n=>n.style.outline=''); const items = Array.from(document.querySelectorAll('#embeddedServices > div')); const idx = items.findIndex(it=>it.innerText.includes(s.name)); if(items[idx]) items[idx].style.outline='3px solid rgba(37,99,235,0.12)'; }

                function populateTimes(){ const timeSel = el('reservationTime'); timeSel.innerHTML=''; for(let h=9; h<18; h++){ ['00','30'].forEach(m=>{ const o=document.createElement('option'); o.value = `${String(h).padStart(2,'0')}:${m}`; o.textContent = `${String(h).padStart(2,'0')}:${m}`; timeSel.appendChild(o); }); } }

                async function submitEmbedded(){
                  const msgEl = document.getElementById('reservationMessage');
                  if (msgEl) { msgEl.textContent = ''; msgEl.className = ''; }
                  if(!selectedService){ if(msgEl){ msgEl.textContent='Lütfen hizmet seçin'; msgEl.className='text-red-600'; } return; }
                  const carwashId = el('location').value; const date = el('reservationDate').value; const time = el('reservationTime').value; const notes = el('notes').value || '';
                  const vehicleId = el('vehicle') ? el('vehicle').value : '';
                  if(!carwashId || !date || !time){ if(msgEl){ msgEl.textContent='Lütfen tüm zorunlu alanları doldurun'; msgEl.className='text-red-600'; } return; }
                  const bookingIdInput = document.getElementById('editingBookingId');
                  const isEdit = bookingIdInput && bookingIdInput.value;

                  const fd = new FormData();
                  fd.append('carwash_id', carwashId);
                  fd.append('service_id', selectedService.id);
                  fd.append('date', date);
                  fd.append('time', time);
                  fd.append('notes', notes);
                  if (vehicleId) fd.append('vehicle_id', vehicleId);
                  // CSRF token
                  const csrf = document.querySelector('#vehicleFormInline input[name="csrf_token"]') ? document.querySelector('#vehicleFormInline input[name="csrf_token"]').value : (window.csrfToken || '');
                  if (csrf) fd.append('csrf_token', csrf);

                  el('embeddedConfirm').disabled = true; el('embeddedConfirm').textContent = 'Gönderiliyor...';
                  try{
                    const url = isEdit ? API_UPDATE : API_CREATE;
                    if (isEdit) fd.append('booking_id', bookingIdInput.value);
                    const r = await fetch(url, {
                      method: 'POST',
                      body: fd,
                      credentials: 'same-origin',
                      headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                      }
                    });
                    const json = await r.json();
                    if(json && json.success){
                      if (msgEl) { msgEl.textContent = isEdit ? 'Rezervasyon başarıyla güncellendi.' : 'Rezervasyon başarıyla oluşturuldu.'; msgEl.className='text-green-600'; }
                      document.dispatchEvent(new CustomEvent('booking:updated', { detail: { booking_id: json.booking_id || (bookingIdInput && bookingIdInput.value) } }));
                      if (isEdit) bookingIdInput.value = '';
                      // refresh reservation list if available by emitting event
                    } else {
                      // Enhanced error handling for better user feedback
                      if (msgEl) {
                          const errorMessage = json && (json.errors ? (Array.isArray(json.errors) ? json.errors.join('\n') : json.errors) : (json.message || json.error)) || 'An unexpected error occurred. Please try again later.';
                          msgEl.textContent = errorMessage;
                          msgEl.className = 'text-red-600';
                      }
                    }
                  }catch(e){ logError('submitEmbedded error', e); if (msgEl) { msgEl.textContent='Sunucu hatası'; msgEl.className='text-red-600'; } }
                  finally{ el('embeddedConfirm').disabled = false; el('embeddedConfirm').textContent = 'Rezervasyon Yap'; }
                }

                document.addEventListener('DOMContentLoaded', function(){ populateTimes(); loadCarwashes(); el('location').addEventListener('change', ()=>{ const v=el('location').value; if(v) loadServicesForCarwash(v); }); el('embeddedConfirm').addEventListener('click', submitEmbedded); });
              })();
            </script>
          </div>
        </div>
      </section>

      <!-- Profile Management Section -->
      <section id="profile" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Profil Yönetimi</h2>
          <p class="text-gray-600">Kişisel bilgilerinizi güncelleyin</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
          <div class="xl:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <h3 class="text-xl font-bold mb-6">Kişisel Bilgiler</h3>
              <form class="space-y-6" action="Customer_Dashboard_process.php" method="post" enctype="multipart/form-data" data-enable-validation="1">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
                  <div>
                    <label for="profile_name" class="block text-sm font-bold text-gray-700 mb-2">Ad</label>
                    <input id="profile_name" type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required">
                  </div>
                  <div>
                    <label for="profile_surname" class="block text-sm font-bold text-gray-700 mb-2">Soyad</label>
                    <input id="profile_surname" type="text" name="surname" value="<?php echo htmlspecialchars($_SESSION['surname'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  </div>
                </div>

                <div>
                  <label for="profile_email" class="block text-sm font-bold text-gray-700 mb-2">E-posta</label>
                  <input id="profile_email" type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required|email">
                </div>

                <div>
                  <label for="profile_phone" class="block text-sm font-bold text-gray-700 mb-2">Telefon</label>
                  <input id="profile_phone" type="tel" name="phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div>
                  <label for="profile_address" class="block text-sm font-bold text-gray-700 mb-2">Adres</label>
                  <textarea id="profile_address" name="address" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"><?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?></textarea>
                </div>

                <div>
                  <label for="profile_photo" class="block text-sm font-bold text-gray-700 mb-2">Profil Fotoğrafı (isteğe bağlı)</label>
                  <input id="profile_photo" type="file" name="profile_photo" accept="image/*">
                </div>

                <button type="submit" class="gradient-bg text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg transition-all">
                  <i class="fas fa-save mr-2"></i>Bilgileri Güncelle
                </button>
              </form>
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-xl font-bold mb-6">Profil Fotoğrafı</h3>
            <div class="text-center">
              <div class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user text-4xl text-gray-400"></i>
              </div>
              <button class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                <i class="fas fa-camera mr-2"></i>Fotoğraf Değiştir
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Vehicles Section -->
      <section id="vehicles" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Araçlarım</h2>
          <p class="text-gray-600">Kayıtlı araçlarınızı yönetin</p>
        </div>

        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold">Kayıtlı Araçlar</h3>
          <button onclick="openVehicleModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>Araç Ekle
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6" id="vehiclesList">
          <!-- Vehicles will be loaded here via AJAX -->
        </div>

        <!-- Inline vehicle form shown in Main Content when adding/editing vehicles -->
        <section id="vehicleInlineSection" class="card p-6 mb-6" style="display:none;">
          <div class="flex items-center justify-between mb-4">
            <h3 id="vehicleInlineTitle" class="text-xl font-bold">Yeni Araç Ekle</h3>
            <button id="vehicleInlineClose" type="button" class="text-gray-600 hover:text-gray-900" aria-label="Kapat">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <form id="vehicleFormInline" action="Customer_Dashboard_process.php" method="post" data-enable-validation="1" class="space-y-4">
            <input type="hidden" name="action" id="vehicleFormAction" value="create">
            <input type="hidden" name="vehicle_id" id="vehicle_id_input_inline" value="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="car_brand_inline" class="block text-sm font-bold text-gray-700 mb-2">Marka</label>
                <input name="car_brand" id="car_brand_inline" type="text" placeholder="Toyota" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
              <div>
                <label for="car_model_inline" class="block text-sm font-bold text-gray-700 mb-2">Model</label>
                <input name="car_model" id="car_model_inline" type="text" placeholder="Corolla" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
              <div>
                <label for="license_plate_inline" class="block text-sm font-bold text-gray-700 mb-2">Plaka</label>
                <input name="license_plate" id="license_plate_inline" type="text" placeholder="34 ABC 123" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
              <div>
                <label for="car_year_inline" class="block text-sm font-bold text-gray-700 mb-2">Model Yılı</label>
                <input name="car_year" id="car_year_inline" type="number" placeholder="2020" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
              <div>
                <label for="car_color_inline" class="block text-sm font-bold text-gray-700 mb-2">Renk</label>
                <input name="car_color" id="car_color_inline" type="text" placeholder="Beyaz" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
              <div>
                <label for="vehicle_image_inline" class="block text-sm font-bold text-gray-700 mb-2">Araç Görseli (isteğe bağlı)</label>
                <input name="vehicle_image" id="vehicle_image_inline" type="file" accept="image/*" class="w-full text-sm">
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" id="vehicleInlineCancel" class="px-4 py-2 border rounded text-gray-700">İptal</button>
              <button type="submit" id="vehicleInlineSubmit" class="px-4 py-2 gradient-bg text-white rounded font-bold">Kaydet</button>
            </div>

            <div id="vehicleFormMessageInline" class="mt-2 text-sm"></div>
          </form>
        </section>
      </section>

      <!-- History Section -->
      <section id="history" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Geçmiş İşlemler</h2>
          <p class="text-gray-600">Tamamlanan hizmetlerinizin geçmişi</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <div class="p-6 border-b">
            <h3 class="text-xl font-bold">İşlem Geçmişi</h3>
          </div>

          <div class="universal-table-container">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Araç</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Konum</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm">10.12.2024<br>15:30</td>
                  <td class="px-6 py-4 text-sm">Tam Detaylandırma</td>
                  <td class="px-6 py-4 text-sm">Toyota Corolla</td>
                  <td class="px-6 py-4 text-sm">CarWash Premium</td>
                  <td class="px-6 py-4 font-medium">₺180</td>
                  <td class="px-6 py-4"><span class="status-completed px-2 py-1 rounded-full text-xs">Tamamlandı</span></td>
                  <td class="px-6 py-4">
                    <div class="flex text-yellow-400">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                    </div>
                  </td>
                </tr>

                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm">05.12.2024<br>11:00</td>
                  <td class="px-6 py-4 text-sm">Dış Yıkama</td>
                  <td class="px-6 py-4 text-sm">Honda Civic</td>
                  <td class="px-6 py-4 text-sm">CarWash Express</td>
                  <td class="px-6 py-4 font-medium">₺50</td>
                  <td class="px-6 py-4"><span class="status-completed px-2 py-1 rounded-full text-xs">Tamamlandı</span></td>
                  <td class="px-6 py-4">
                    <div class="flex text-yellow-400">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="far fa-star"></i>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Support Section -->
      <section id="support" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Destek</h2>
          <p class="text-gray-600">Yardıma mı ihtiyacınız var? Size yardımcı olalım</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-4">
              <i class="fas fa-question-circle text-blue-600 mr-2"></i>
              Sık Sorulan Sorular
            </h3>
            <div class="space-y-4">
              <div>
                <h4 class="font-bold text-gray-800">Rezervasyonumu nasıl iptal edebilirim?</h4>
                <p class="text-sm text-gray-600 mt-1">Rezervasyonlar bölümünden iptal edebilirsiniz.</p>
              </div>
              <div>
                <h4 class="font-bold text-gray-800">Ödeme yöntemleri nelerdir?</h4>
                <p class="text-sm text-gray-600 mt-1">Nakit, kredi kartı ve mobil ödeme kabul ediyoruz.</p>
              </div>
              <div>
                <h4 class="font-bold text-gray-800">Hizmet kalitesi nasıl garanti ediliyor?</h4>
                <p class="text-sm text-gray-600 mt-1">Tüm hizmetlerimizde kalite garantisi veriyoruz.</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-4">
              <i class="fas fa-headset text-blue-600 mr-2"></i>
              Bize Ulaşın
            </h3>
            <form class="space-y-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Konu</label>
                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  <option>Rezervasyon Sorunu</option>
                  <option>Ödeme Sorunu</option>
                  <option>Hizmet Kalitesi</option>
                  <option>Diğer</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Mesajınız</label>
                <textarea rows="4" placeholder="Sorunuzu veya şikayetinizi yazın..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
              </div>

              <button type="submit" class="w-full gradient-bg text-white py-3 rounded-lg font-bold hover:shadow-lg transition-all">
                <i class="fas fa-paper-plane mr-2"></i>Mesaj Gönder
              </button>
            </form>
          </div>
        </div>
      </section>

      <!-- Settings Section -->
      <section id="settings" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Ayarlar</h2>
          <p class="text-gray-600">Hesap ayarlarınızı yönetin</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
          <h3 class="text-xl font-bold mb-6">Bildirim Tercihleri</h3>
          <div class="space-y-4">
            <label class="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <h4 class="font-bold">E-posta Bildirimleri</h4>
                <p class="text-sm text-gray-600">Rezervasyon onayları ve güncellemeler</p>
              </div>
              <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
            </label>

            <label class="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <h4 class="font-bold">SMS Bildirimleri</h4>
                <p class="text-sm text-gray-600">Acil durumlar için SMS</p>
              </div>
              <input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
            </label>

            <label class="flex items-center justify-between p-4 border rounded-lg">
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
    </main>
  </div>

  <!-- Notification Panel -->
  <div id="notificationPanel" class="fixed top-20 right-4 w-80 bg-white rounded-2xl shadow-2xl z-50 hidden">
    <div class="p-4 border-b">
      <div class="flex justify-between items-center">
        <h3 class="font-bold">Bildirimler</h3>
        <button onclick="closeNotifications()" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <div class="max-h-96 overflow-y-auto">
      <div class="p-4 border-b hover:bg-gray-50">
        <p class="text-sm">Rezervasyonunuz onaylandı</p>
        <p class="text-xs text-gray-500">2 saat önce</p>
      </div>
      <div class="p-4 border-b hover:bg-gray-50">
        <p class="text-sm">Önceki hizmetiniz tamamlandı</p>
        <p class="text-xs text-gray-500 mt-1">1 gün önce</p>
      </div>
      <div class="p-4 border-b hover:bg-gray-50">
        <p class="text-sm">Yeni kampanya başladı!</p>
        <p class="text-xs text-gray-500 mt-1">2 gün önce</p>
      </div>
    </div>
  </div>

  <!-- legacy vehicle modal removed; inline vehicle form is used in Main Content -->

  <!-- vehicleInlineSection moved into Vehicles main content to display in the Main Content area -->

  <script>
    // Mobile Sidebar Functions
    function toggleMobileSidebar() {
      const sidebar = document.getElementById('mobileSidebar');
      const overlay = document.getElementById('mobileOverlay');
      const menuBtn = document.getElementById('mobileMenuBtn');
      const menuIcon = document.getElementById('menuIcon');

      if (sidebar.classList.contains('active')) {
        closeMobileSidebar();
      } else {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        menuBtn.classList.add('active');
        menuIcon.className = 'fas fa-times';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
      }
    }

    function closeMobileSidebar() {
      const sidebar = document.getElementById('mobileSidebar');
      const overlay = document.getElementById('mobileOverlay');
      const menuBtn = document.getElementById('mobileMenuBtn');
      const menuIcon = document.getElementById('menuIcon');

      sidebar.classList.remove('active');
      overlay.classList.remove('active');
      menuBtn.classList.remove('active');
      menuIcon.className = 'fas fa-bars';
      document.body.style.overflow = ''; // Restore scrolling
    }

    // Load car washes from API (ensure IDs match database)
    const allCarWashes = [];
    async function loadCarWashesFromApi() {
      try {
        const res = await fetch('/carwash_project/backend/api/carwashes/list.php', {
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        let json = [];
        try { json = await res.json(); } catch (e) { json = []; }

        // If API returned nothing, provide sample entries so booking UI can be tested
        if (!Array.isArray(json) || json.length === 0) {
          json = [
            {id: 1, name: 'CarWash Premium', city: 'İstanbul', district: 'Beşiktaş'},
            {id: 2, name: 'Beşiktaş AutoCare', city: 'İstanbul', district: 'Beşiktaş'},
            {id: 3, name: 'Ankara Express', city: 'Ankara', district: 'Çankaya'},
            {id: 4, name: 'Capitol Clean', city: 'Ankara', district: 'Keçiören'},
            {id: 5, name: 'İzmir Shine', city: 'İzmir', district: 'Konak'},
            {id: 6, name: 'Ege AutoWash', city: 'İzmir', district: 'Karşıyaka'},
            {id: 7, name: 'Antalya WashCenter', city: 'Antalya', district: 'Muratpaşa'},
            {id: 8, name: 'Lara Clean', city: 'Antalya', district: 'Konyaaltı'},
            {id: 9, name: 'Bursa Spot', city: 'Bursa', district: 'Osmangazi'},
            {id: 10, name: 'Nilüfer CarCare', city: 'Bursa', district: 'Nilüfer'}
          ];
        }

        // normalize fields (city/district may be missing depending on API)
        allCarWashes.length = 0;
        json.forEach(cw => allCarWashes.push(Object.assign({ city: cw.city || '', district: cw.district || '', rating: cw.rating || 0, isFavorite: false, services: cw.services || [] }, cw)));

        // Build city -> districts mapping and populate cityFilter select
        const citySet = new Map();
        allCarWashes.forEach(cw => {
          const city = cw.city || '';
          const district = cw.district || '';
          if (!city) return;
          if (!citySet.has(city)) citySet.set(city, new Set());
          if (district) citySet.get(city).add(district);
        });
        // Populate cityFilter options
        const citySel = document.getElementById('cityFilter');
        if (citySel) {
          // remove any existing dynamic options
          Array.from(citySel.querySelectorAll('option[data-dynamic]')).forEach(o => o.remove());
          for (const [city, districts] of citySet.entries()) {
            const opt = document.createElement('option'); opt.value = city; opt.textContent = city; opt.setAttribute('data-dynamic', '1'); citySel.appendChild(opt);
          }
        }
        // Build districtsByCity mapping used by filterCarWashes and expose globally
        Object.keys(districtsByCity).forEach(k => delete districtsByCity[k]);
        for (const [city, districts] of citySet.entries()) {
          districtsByCity[city] = Array.from(districts);
        }
        window.districtsByCity = districtsByCity;
        filterCarWashes();
      } catch (e) {
        console.warn('Failed to load carwashes from API', e);
        // fallback to sample data if fetch fails
        if (allCarWashes.length === 0) {
          allCarWashes.push({id:1,name:'CarWash Premium',city:'İstanbul',district:'Beşiktaş'});
          districtsByCity['İstanbul'] = ['Beşiktaş'];
          window.districtsByCity = districtsByCity;
          filterCarWashes();
        } else {
          document.getElementById('carWashList').innerHTML = '<p class="text-gray-600 text-center col-span-full">CarWash listesi yüklenemiyor.</p>';
        }
      }
    }

    // Districts data (dynamic loading) — expanded to requested cities/areas
    const districtsByCity = {
      'İstanbul': ['Kadıköy', 'Beşiktaş', 'Üsküdar'],

      'Ankara': ['Çankaya', 'Keçiören', 'Etimesgut'],
      'İzmir': ['Konak', 'Karşıyaka', 'Bornova'],
      'Antalya': ['Muratpaşa', 'Konyaaltı', 'Kepez'],
      'Bursa': ['Osmangazi', 'Nilüfer', 'Yıldırım']
    };

    function showSection(sectionId) {
      // Hide all sections
      document.querySelectorAll('.section-content').forEach(section => {
        section.classList.add('hidden');
      });

      // Show selected section
      document.getElementById(sectionId).classList.remove('hidden');

      // Update sidebar active state for both mobile and desktop
      document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('bg-white', 'bg-opacity-20');
        link.classList.add('hover:bg-white', 'hover:bg-opacity-20');
      });

      // Add active state to clicked link
      let targetLink = event.target;
      while (targetLink && !targetLink.classList.contains('sidebar-link')) {
        targetLink = targetLink.parentNode;
      }
      if (targetLink) {
        targetLink.classList.add('bg-white', 'bg-opacity-20');
        targetLink.classList.remove('hover:bg-white', 'hover:bg-opacity-20');
      }

      // Special handling for carWashSelection to load list
      if (sectionId === 'carWashSelection') {
        loadDistrictOptions(); // Load districts for the default city or all
        // Load carwashes from API (will call filterCarWashes when done)
        loadCarWashesFromApi();
      }
      // Load vehicles when navigating to vehicles section
      if (sectionId === 'vehicles') {
        if (typeof loadUserVehicles === 'function') {
          loadUserVehicles();
        } else {
          // graceful fallback: try to fetch via generic endpoint
          console.warn('loadUserVehicles not defined yet');
        }
      }
      // Ensure reservation list is shown by default when navigating to reservations
      if (sectionId === 'reservations') {
        hideNewReservationForm(); // Ensure the list view is active
      }

      // Close mobile sidebar after selection
      if (window.innerWidth < 1024) {
        closeMobileSidebar();
      }
    }

    function toggleNotifications() {
      const panel = document.getElementById('notificationPanel');
      panel.classList.toggle('hidden');
    }

    function closeNotifications() {
      document.getElementById('notificationPanel').classList.add('hidden');
    }

    // Populate and show the inline vehicle form for create/edit
    function openVehicleModal(vehicle = null) {
      const section = document.getElementById('vehicleInlineSection');
      const form = document.getElementById('vehicleFormInline');
      const title = document.getElementById('vehicleInlineTitle');
      const actionInput = document.getElementById('vehicleFormAction');
      const idInput = document.getElementById('vehicle_id_input_inline');

      if (vehicle && typeof vehicle === 'object') {
        title.textContent = 'Araç Düzenle';
        actionInput.value = 'update';
        idInput.value = vehicle.id || '';
        document.getElementById('car_brand_inline').value = vehicle.brand || '';
        document.getElementById('car_model_inline').value = vehicle.model || '';
        document.getElementById('license_plate_inline').value = vehicle.license_plate || '';
        document.getElementById('car_year_inline').value = vehicle.year || '';
        document.getElementById('car_color_inline').value = vehicle.color || '';
        // Scroll to form within dashboard
        section.style.display = 'block';
        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else {
        title.textContent = 'Yeni Araç Ekle';
        actionInput.value = 'create';
        idInput.value = '';
        form.reset();
        section.style.display = 'block';
        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      // Show a small helper message
      const msg = document.getElementById('vehicleFormMessageInline');
      if (msg) { msg.textContent = ''; msg.className = ''; }
    }

    // Hide the inline vehicle form
    function closeVehicleModal() {
      const section = document.getElementById('vehicleInlineSection');
      const form = document.getElementById('vehicleFormInline');
      if (!section) return;
      form.reset();
      section.style.display = 'none';
    }

    // Wire inline form buttons and submission behavior (AJAX fallback)
    document.addEventListener('DOMContentLoaded', function () {
      const closeBtn = document.getElementById('vehicleInlineClose');
      const cancelBtn = document.getElementById('vehicleInlineCancel');
      const form = document.getElementById('vehicleFormInline');
      const msg = document.getElementById('vehicleFormMessageInline');

      if (closeBtn) closeBtn.addEventListener('click', closeVehicleModal);
      if (cancelBtn) cancelBtn.addEventListener('click', closeVehicleModal);

      if (form) {
        form.addEventListener('submit', async function (ev) {
          ev.preventDefault();
          if (!form) return;
          const fd = new FormData(form);
          // Change to use vehicle_api.php
          const action = document.getElementById('vehicleFormAction').value;
          fd.set('action', action);  // 'create' or 'update'

          // For update, set 'id' from vehicle_id_input_inline
          const vid = document.getElementById('vehicle_id_input_inline').value;
          if (action !== 'create' && vid) {
            fd.set('id', vid);
          }

          try {
            const res = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
              method: 'POST',
              credentials: 'same-origin',
              body: fd,
              headers: { 'Accept': 'application/json' }
            });

            // Read text first to guard against HTML/warnings
            const raw = await res.text();
            let json = null;
            try {
              json = raw ? JSON.parse(raw) : null;
            } catch (parseErr) {
              console.error('Non-JSON response from vehicle_api.php:', raw.slice(0, 2000));
              if (msg) { msg.textContent = 'Sunucudan beklenmeyen yanıt alındı.'; msg.className = 'text-sm text-red-600'; }
              return;
            }

            if (res.ok && json && json.success) {
              if (msg) { msg.textContent = 'Araç kaydedildi.'; msg.className = 'text-sm text-green-600'; }

              // Close modal/inline form
              closeVehicleModal();

              // If this was an edit/update, refresh the whole page once so every UI piece updates.
              // For create, reload the vehicles list only to avoid full page reload.
              if (action === 'update') {
                // Small delay to allow modal close animation
                setTimeout(() => { try { location.reload(); } catch(e){ if (typeof loadUserVehicles === 'function') loadUserVehicles(); } }, 300);
              } else {
                // Create path: refresh vehicles list inline
                if (typeof loadUserVehicles === 'function') loadUserVehicles();
              }
            } else {
              // Enhanced error handling for better user feedback
              if (msg) {
                  const errorMessage = json && (json.errors ? (Array.isArray(json.errors) ? json.errors.join('\n') : json.errors) : (json.message || json.error)) || 'An unexpected error occurred. Please try again later.';
                  msg.textContent = errorMessage;
                  msg.className = 'text-red-600';
              }
            }
          } catch (err) {
            logError('Vehicle inline submit error', err);
            if (msg) { msg.textContent = 'İşlem başarısız: ' + (err.message || 'Ağ hatası'); msg.className='text-sm text-red-600'; }
          } finally {
            const submitBtn = document.getElementById('vehicleInlineSubmit');
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Kaydet'; }
          }
        });
      }
    });

    // Load user vehicles via AJAX and populate vehicles list + booking select
    async function loadUserVehicles() {
      const container = document.getElementById('vehiclesList');
      const msgEl = document.getElementById('vehicleFormMessageInline');
      try {
        const res = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) {
          const txt = await res.text();
          console.error('Failed to load vehicles', res.status, txt);
          if (msgEl) { msgEl.textContent = 'Araçlar yüklenemedi.'; msgEl.className = 'text-sm text-red-600'; }
          return;
        }

        // Accept non-JSON responses gracefully (some servers may return HTML on error)
        const raw = await res.text();
        let json = null;
        try {
          json = raw ? JSON.parse(raw) : null;
        } catch (parseErr) {
          console.error('Non-JSON response from vehicle_api.php (GET):', raw.slice(0,2000));
          if (msgEl) { msgEl.textContent = 'Araçlar yüklenemedi.'; msgEl.className = 'text-sm text-red-600'; }
          renderVehiclesList([]);
          refreshVehicleSelect([]);
          return;
        }

        if (!json || json.status !== 'success') {
          console.warn('vehicle_api returned no vehicles', json);
          if (msgEl) { msgEl.textContent = json.message || 'Araç bulunamadı.'; msgEl.className = 'text-sm text-gray-600'; }
          renderVehiclesList([]);
          refreshVehicleSelect([]);
          return;
        }

        const vehicles = json.data && Array.isArray(json.data.vehicles) ? json.data.vehicles : [];
        console.log('Loaded vehicles:', vehicles);
        
        // Verify vehicle images
        await verifyVehicleImages(vehicles);
        
        renderVehiclesList(vehicles);
        refreshVehicleSelect(vehicles);
      } catch (e) {
        console.error('Error loading vehicles', e);
        if (msgEl) { msgEl.textContent = 'Araçlar yüklenirken hata oluştu.'; msgEl.className = 'text-sm text-red-600'; }
      }
    }

    // Refresh booking vehicle select with vehicles array
    function refreshVehicleSelect(vehicles) {
      const sel = document.getElementById('vehicle');
      const preview = document.getElementById('vehiclePreview');
      if (!sel) return;
      // Clear existing options except placeholder
      const placeholder = sel.querySelector('option[value=""]') ? sel.querySelector('option[value=""]').outerHTML : '<option value="">Araç Seçiniz</option>';
      sel.innerHTML = placeholder;
      if (!Array.isArray(vehicles) || vehicles.length === 0) return;
      // helper: normalize stored upload paths to web-accessible URLs
      function resolveUploadUrl(vehicleId, path) {
        return resolveVehicleImageUrl(path);
      }

      vehicles.forEach(v => {
        const o = document.createElement('option');
        o.value = v.id;
        o.textContent = (v.brand || '') + ' ' + (v.model || '') + (v.license_plate ? (' — ' + v.license_plate) : '');
        if (v.image_path) o.dataset.preview = resolveUploadUrl(v.id, v.image_path);
        sel.appendChild(o);
      });

      // Update preview when select changes
      sel.addEventListener('change', function () {
        const opt = sel.selectedOptions[0];
        const url = opt && opt.dataset && opt.dataset.preview ? opt.dataset.preview : '/carwash_project/frontend/assets/default-car.png';
        if (preview) preview.src = url;
      });
    }

    // Fallback implementations for functions used by other shared pages
    // Only add if not already defined (prevents clobbering global implementations)
    if (typeof loadDistrictOptions !== 'function') {
      function loadDistrictOptions() {
        try {
          const citySel = document.getElementById('cityFilter');
          const districtSel = document.getElementById('districtFilter');
          if (!citySel || !districtSel) return;
          const city = citySel.value || '';
          const districts = (window.districtsByCity && window.districtsByCity[city]) ? window.districtsByCity[city] : [];
          // Clear existing dynamic options
          Array.from(districtSel.querySelectorAll('option[data-dynamic]')).forEach(o=>o.remove());
          districtSel.innerHTML = '<option value="">Tüm Mahalleler</option>';
          districts.forEach(d => {
            const o = document.createElement('option');
            o.value = d; o.textContent = d; o.setAttribute('data-dynamic','1'); districtSel.appendChild(o);
          });
        } catch (e) { console.warn('loadDistrictOptions fallback failed', e); }
      }
    }

    if (typeof showNewReservationForm !== 'function') {
      function showNewReservationForm() {
        try {
          const listView = document.getElementById('reservationListView');
          const form = document.getElementById('newReservationForm');
          if (listView) listView.classList.add('hidden');
          if (form) form.classList.remove('hidden');
          // scroll into view for good UX
          if (form && typeof form.scrollIntoView === 'function') form.scrollIntoView({behavior:'smooth', block:'center'});
        } catch (e) { console.warn('showNewReservationForm fallback failed', e); }
      }
    }

    if (typeof hideNewReservationForm !== 'function') {
      function hideNewReservationForm() {
        try {
          const listView = document.getElementById('reservationListView');
          const form = document.getElementById('newReservationForm');
          if (form) form.classList.add('hidden');
          if (listView) listView.classList.remove('hidden');
          if (listView && typeof listView.scrollIntoView === 'function') listView.scrollIntoView({behavior:'smooth', block:'start'});
        } catch (e) { console.warn('hideNewReservationForm fallback failed', e); }
      }
    }

    // Vehicle delete function
    async function deleteVehicle(vehicleId) {
      if (!confirm('Bu aracı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        return;
      }

      const csrfToken = await fetchCsrfToken();
      try {
        const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'delete',
            id: vehicleId,
            csrf_token: csrfToken
          })
        });

        const result = await response.json();
        if (result.success) {
          alert('Araç başarıyla silindi.');
          loadUserVehicles(); // Reload the list
        } else {
          alert('Araç silinirken hata oluştu: ' + (result.message || 'Bilinmeyen hata'));
        }
      } catch (error) {
        console.error('Delete vehicle error:', error);
        alert('Araç silinirken bir hata oluştu.');
      }
    }

    // Fix openEditVehicleModal function
    function openEditVehicleModal(vehicle) {
      const modal = document.getElementById('editVehicleModal');
      if (!modal) {
        console.error('Edit vehicle modal not found.');
        return;
      }

      // Populate modal fields with vehicle data
      document.getElementById('editVehicleId').value = vehicle.id;
      document.getElementById('editVehicleBrand').value = vehicle.brand || '';
      document.getElementById('editVehicleModel').value = vehicle.model || '';
      document.getElementById('editVehicleLicensePlate').value = vehicle.license_plate || '';
      document.getElementById('editVehicleYear').value = vehicle.year || '';
      document.getElementById('editVehicleColor').value = vehicle.color || '';

      modal.style.display = 'block';

      // Handle form submission
      const form = document.getElementById('editVehicleForm');
      form.onsubmit = async (event) => {
        event.preventDefault();

        const csrfToken = await fetchCsrfToken();
        const formData = new FormData(form);
        formData.append('action', 'update');
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert('Vehicle updated successfully.');
                modal.style.display = 'none';
                loadUserVehicles();
            } else {
                alert('Failed to update vehicle: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Update vehicle error:', error);
            alert('An error occurred while updating the vehicle.');
        }
      };
    }

    // Verify loadUserVehicles function
    async function loadUserVehicles() {
      try {
        const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php?action=list');
        const vehicles = await response.json();

        const container = document.getElementById('vehiclesList');
        container.innerHTML = '';

        if (!Array.isArray(vehicles) || vehicles.length === 0) {
          container.innerHTML = '<p class="text-gray-500">No vehicles found.</p>';
          return;
        }

        vehicles.forEach(vehicle => {
          const card = document.createElement('div');
          card.className = 'vehicle-card';

          const imgSrc = vehicle.image_path || '/carwash_project/frontend/assets/default-car.png';
          card.innerHTML = `
              <div>
                  <img src="${imgSrc}" alt="${vehicle.brand || 'Vehicle'}" onerror="this.onerror=null;this.src='/carwash_project/frontend/assets/default-car.png';">
                  <h4>${vehicle.brand || ''} ${vehicle.model || ''}</h4>
                  <p>${vehicle.license_plate || '—'}</p>
                  <button onclick="openEditVehicleModal(${JSON.stringify(vehicle)})">Edit</button>
                  <button onclick="deleteVehicle(${vehicle.id})">Delete</button>
              </div>
          `;

          container.appendChild(card);
        });
      } catch (error) {
        console.error('Failed to load vehicles:', error);
        alert('An error occurred while loading vehicles.');
      }
    }

    // Add event listener to close modal
    window.addEventListener('click', (event) => {
      const modal = document.getElementById('editVehicleModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });
  </script>
</div> <!-- End Dashboard Layout -->

<?php 
// Include the universal footer
include '../includes/footer.php'; 
?>

<!-- Vehicle Debug Helper -->
<script src="vehicle_debug_helper.js"></script>
<meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
