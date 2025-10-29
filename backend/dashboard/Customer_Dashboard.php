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
          <div id="newReservationForm" class="p-6 hidden">
            <h3 class="text-xl font-bold mb-6">Yeni Rezervasyon Oluştur</h3>

            <div id="embeddedBooking" class="space-y-6">
              <!-- Services loaded dynamically -->
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Hizmet Seçin</label>
                <div id="embeddedServices" class="space-y-2">
                  <div class="text-sm muted">Hizmetler yükleniyor...</div>
                </div>
              </div>

              <!-- Vehicle selector (existing vehicles) -->
              <div>
                <label for="vehicle" class="block text-sm font-bold text-gray-700 mb-2">Araç Seçin</label>
                <select id="vehicle" name="vehicle_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  <option value="">Araç Seçiniz</option>
                  <!-- TODO: populate user vehicles dynamically -->
                </select>
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

                const el = id => document.getElementById(id);

                // Populate carwash options into #location
                async function loadCarwashes(){
                  try{
                    const resp = await fetch(API_CARWASHES,{cache:'no-store'});
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
                    const resp = await fetch(API_SERVICES + '?carwash_id=' + encodeURIComponent(carwashId),{cache:'no-store'});
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
                  if(!selectedService){ alert('Lütfen hizmet seçin'); return; }
                  const carwashId = el('location').value; const date = el('reservationDate').value; const time = el('reservationTime').value; const notes = el('notes').value || '';
                  if(!carwashId || !date || !time){ alert('Lütfen tüm zorunlu alanları doldurun'); return; }
                  const fd = new FormData(); fd.append('carwash_id', carwashId); fd.append('service_id', selectedService.id); fd.append('date', date); fd.append('time', time); fd.append('notes', notes);
                  el('embeddedConfirm').disabled = true; el('embeddedConfirm').textContent = 'Gönderiliyor...';
                  try{
                    const r = await fetch(API_CREATE, { method:'POST', body: fd, credentials: 'same-origin' });
                    const json = await r.json();
                    if(json.success){ alert('Rezervasyon başarılı. ID: '+json.booking_id); window.location.reload(); }
                    else { alert('Hata: '+(json.errors?json.errors.join('\n'):(json.message||'Bilinmeyen hata'))); }
                  }catch(e){ console.error(e); alert('Sunucu hatası'); }
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
                    <label class="block text-sm font-bold text-gray-700 mb-2">Ad</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required">
                  </div>
                  <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Soyad</label>
                    <input type="text" name="surname" value="<?php echo htmlspecialchars($_SESSION['surname'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">E-posta</label>
                  <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" data-validate="required|email">
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Telefon</label>
                  <input type="tel" name="phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Adres</label>
                  <textarea name="address" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"><?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?></textarea>
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Profil Fotoğrafı (isteğe bağlı)</label>
                  <input type="file" name="profile_photo" accept="image/*">
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

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex justify-between items-start mb-4">
              <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-car text-2xl text-blue-600"></i>
              </div>
              <button class="text-red-500 hover:text-red-700">
                <i class="fas fa-trash"></i>
              </button>
            </div>
            <h4 class="font-bold text-lg mb-2">Toyota Corolla</h4>
            <div class="space-y-1 text-sm text-gray-600">
              <p><span class="font-medium">Plaka:</span> 34 ABC 123</p>
              <p><span class="font-medium">Model:</span> 2020</p>
              <p><span class="font-medium">Renk:</span> Beyaz</p>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex justify-between items-start mb-4">
              <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-car text-2xl text-green-600"></i>
              </div>
              <button class="text-red-500 hover:text-red-700">
                <i class="fas fa-trash"></i>
              </button>
            </div>
            <h4 class="font-bold text-lg mb-2">Honda Civic</h4>
            <div class="space-y-1 text-sm text-gray-600">
              <p><span class="font-medium">Plaka:</span> 34 XYZ 789</p>
              <p><span class="font-medium">Model:</span> 2019</p>
              <p><span class="font-medium">Renk:</span> Siyah</p>
            </div>
          </div>
        </div>
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
        <p class="text-xs text-gray-500">1 gün önce</p>
      </div>
      <div class="p-4 border-b hover:bg-gray-50">
        <p class="text-sm">Yeni kampanya başladı!</p>
        <p class="text-xs text-gray-500">2 gün önce</p>
      </div>
    </div>
  </div>

  <!-- Vehicle Modal -->
  <div id="vehicleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4">
      <h3 class="text-xl font-bold mb-6">Yeni Araç Ekle</h3>
      <form id="vehicleForm" action="Customer_Dashboard_process.php" method="post" data-enable-validation="1">
        <input type="hidden" name="action" value="update_vehicle">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Marka</label>
          <input name="car_brand" type="text" placeholder="Toyota" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Model</label>
          <input name="car_model" type="text" placeholder="Corolla" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Plaka</label>
          <input name="license_plate" type="text" placeholder="34 ABC 123" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Model Yılı</label>
          <input name="car_year" type="number" placeholder="2020" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Renk</label>
          <input name="car_color" type="text" placeholder="Beyaz" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div class="flex space-x-3">
          <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Ekle</button>
          <button type="button" onclick="closeVehicleModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
        </div>
      </form>
    </div>
  </div>

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
        const res = await fetch('/carwash_project/backend/api/carwashes/list.php', { credentials: 'same-origin' });
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

    function openVehicleModal() {
      document.getElementById('vehicleModal').classList.remove('hidden');
    }

    function closeVehicleModal() {
      document.getElementById('vehicleModal').classList.add('hidden');
    }

    // Functions for New Reservation Form
    function showNewReservationForm() {
      document.getElementById('reservationListView').classList.add('hidden');
      document.getElementById('newReservationForm').classList.remove('hidden');
    }

    function hideNewReservationForm() {
      document.getElementById('newReservationForm').classList.add('hidden');
      document.getElementById('reservationListView').classList.remove('hidden');
    }

    function submitNewReservation() {
      // Here you would collect form data and send it to your backend
      const service = document.getElementById('service').value;
      const vehicle = document.getElementById('vehicle').value;
      const date = document.getElementById('reservationDate').value;
      const time = document.getElementById('reservationTime').value;
      const location = document.getElementById('location').value; // This will be the selected car wash name
      const notes = document.getElementById('notes').value;

      // Basic validation (you'd want more robust validation)
      if (!service || !vehicle || !date || !time || !location) {
        alert('Lütfen tüm zorunlu alanları doldurun.');
        return;
      }

      console.log('New Reservation Data:', { service, vehicle, date, time, location, notes });
      alert('Rezervasyonunuz başarıyla oluşturuldu! (Bu bir demo mesajıdır)');
      
      // Optionally, clear the form
      document.getElementById('service').value = '';
      document.getElementById('vehicle').value = '';
      document.getElementById('reservationDate').value = '';
      document.getElementById('reservationTime').value = '';
      document.getElementById('location').value = '';
      document.getElementById('notes').value = '';

      hideNewReservationForm(); // Go back to the reservation list after submission
    }

    // Car Wash Selection Functions
    function loadDistrictOptions() {
      const cityFilter = document.getElementById('cityFilter');
      const districtFilter = document.getElementById('districtFilter');
      const selectedCity = cityFilter.value;

      districtFilter.innerHTML = '<option value="">Tüm Mahalleler</option>'; // Reset districts

      if (selectedCity && districtsByCity[selectedCity]) {
        districtsByCity[selectedCity].forEach(district => {
          const option = document.createElement('option');
          option.value = district;
          option.textContent = district;
          districtFilter.appendChild(option);
        });
      }
    }

    function filterCarWashes() {
      const cityFilter = document.getElementById('cityFilter').value.toLowerCase();
      const districtFilter = document.getElementById('districtFilter').value.toLowerCase();
      const carWashNameFilter = document.getElementById('carWashNameFilter').value.toLowerCase();
      const favoriteFilter = document.getElementById('favoriteFilter').checked;
      const carWashListDiv = document.getElementById('carWashList');
      carWashListDiv.innerHTML = ''; // Clear current list

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

      filteredWashes.forEach(carWash => {
        const carWashCard = `
          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg flex flex-col">
            <div class="flex justify-between items-start mb-4">
              <h4 class="font-bold text-xl text-gray-800">${carWash.name}</h4>
              <button onclick="toggleFavorite(${carWash.id})" class="text-gray-400 hover:text-red-500 transition-colors">
                <i class="${carWash.isFavorite ? 'fas text-red-500' : 'far'} fa-heart text-xl"></i>
              </button>
            </div>
            <p class="text-sm text-gray-600 mb-2"><i class="fas fa-map-marker-alt mr-2"></i>${carWash.district}, ${carWash.city}</p>
            <p class="text-sm text-gray-600 mb-4"><i class="fas fa-star text-yellow-400 mr-2"></i>${carWash.rating} (${(Math.random() * 100).toFixed(0)} yorum)</p>
            <div class="flex flex-wrap gap-2 mb-4">
        ${(carWash.services || []).map(service => `<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">${service}</span>`).join('')}
            </div>
            <a href="#" role="button" onclick="selectCarWashForReservation(event, ${carWash.id}, '${carWash.name.replace(/'/g, "\\'")}')" class="mt-auto inline-block text-center gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all" aria-label="${carWash.name} için rezervasyon yap">
              <i class="fas fa-calendar-alt mr-2"></i>Rezervasyon Yap
            </a>
          </div>
        `;
        carWashListDiv.innerHTML += carWashCard;
      });
    }

    function toggleFavorite(carWashId) {
      const carWashIndex = allCarWashes.findIndex(cw => cw.id === carWashId);
      if (carWashIndex > -1) {
        allCarWashes[carWashIndex].isFavorite = !allCarWashes[carWashIndex].isFavorite;
        filterCarWashes(); // Re-render the list to update heart icon
      }
    }
    
    // Helper to insert HTML and run contained scripts when loading a partial form via fetch
    function insertHTMLWithScripts(container, html) {
      container.innerHTML = html;
      // Execute any inline scripts inside the injected HTML
      const scripts = Array.from(container.querySelectorAll('script'));
      scripts.forEach(s => {
        const ns = document.createElement('script');
        if (s.src) {
          ns.src = s.src;
          ns.async = false;
          document.head.appendChild(ns);
        } else {
          ns.textContent = s.textContent;
          document.head.appendChild(ns);
          document.head.removeChild(ns);
        }
        s.parentNode.removeChild(s);
      });
    }

    async function selectCarWashForReservation(ev, carWashId, carWashName) {
      // Open the existing embedded reservation form and pre-fill city/district/carwash.
      ev && ev.preventDefault && ev.preventDefault();
      try {
        const cw = allCarWashes.find(c => Number(c.id) === Number(carWashId)) || {};

        // Preselect city and district in the filters so the user sees the correct area
        const citySel = document.getElementById('cityFilter');
        const districtSel = document.getElementById('districtFilter');
        if (citySel && cw.city) {
          citySel.value = cw.city;
          // rebuild district options for the selected city
          loadDistrictOptions();
          if (districtSel && cw.district) districtSel.value = cw.district;
        }

        // Show reservations section and embedded form (keeps everything inside dashboard)
        showSection('reservations');
        showNewReservationForm();

        // Populate the location select from in-memory carwash list if it's not already populated
        const loc = document.getElementById('location');
        if (loc) {
          if (loc.options.length <= 1) { // only default option present
            loc.innerHTML = '<option value="">Konum Seçiniz</option>';
            allCarWashes.forEach(c => {
              const o = document.createElement('option');
              o.value = c.id;
              o.textContent = c.name + (c.district ? (' — ' + c.district) : '');
              loc.appendChild(o);
            });
          }

          // Set selected carwash and trigger change so embedded script loads services
          loc.value = String(carWashId);
          const changeEvt = new Event('change', { bubbles: true });
          loc.dispatchEvent(changeEvt);
        }

        // Focus the date input for accessibility
        const container = document.getElementById('newReservationForm');
        if (container) {
          container.classList.remove('hidden');
          container.scrollIntoView({ behavior: 'smooth' });
          const dateInput = container.querySelector('#reservationDate');
          if (dateInput) dateInput.focus();
        }
      } catch (e) {
        console.warn('Failed to open embedded reservation form, falling back to full navigation', e);
        window.location.href = '/carwash_project/backend/booking/new_booking.php?carwash_id=' + encodeURIComponent(carWashId);
      }
    }


    // Close modals when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('vehicleModal');
      const notificationPanel = document.getElementById('notificationPanel');

      if (event.target == modal) {
        modal.classList.add('hidden');
      }

      if (!event.target.closest('#notificationPanel') && !event.target.closest('[onclick="toggleNotifications()"]')) {
        notificationPanel.classList.add('hidden');
      }
    }

    // Handle window resize for responsive behavior
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 1024) {
        // Desktop view - close mobile sidebar if open
        closeMobileSidebar();
      }
    });

    // Prevent body scroll when mobile menu is open
    function preventBodyScroll(prevent) {
      if (prevent) {
        document.body.style.overflow = 'hidden';
        document.body.style.height = '100%';
      } else {
        document.body.style.overflow = '';
        document.body.style.height = '';
      }
    }

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      showSection('dashboard');
      
      // Set minimum date for reservation form to today
      const today = new Date().toISOString().split('T')[0];
      const dateInput = document.getElementById('reservationDate');
      if (dateInput) {
        dateInput.setAttribute('min', today);
      }
    });
  </script>

</div> <!-- End Dashboard Layout -->

<?php 
// Include the universal footer
include '../includes/footer.php'; 
?>
