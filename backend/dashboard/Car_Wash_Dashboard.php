<?php
/**
 * Car Wash Dashboard for CarWash Web Application
 * Uses the universal header/footer system with dashboard context
 * 
 * Farsça: داشبورد مدیریت کارواش با سیستم هدر/فوتر جهانی
 * Türkçe: Evrensel başlık/altbilgi sistemi ile araç yıkama yönetim paneli
 * English: Car wash management dashboard with universal header/footer system
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has carwash role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'carwash') {
    header('Location: ../auth/login.php');
    exit();
}

// Set page-specific variables for the dashboard header
$dashboard_type = 'carwash';  // Specify this is the car wash dashboard
$page_title = 'İşletme Paneli - CarWash';
$current_page = 'dashboard';

// Custom header content - On/Off Toggle Switch
$custom_header_content = '
<style>
    /* Toggle Switch Styles */
    .workplace-toggle-container {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-right: 1rem;
    }
    
    .toggle-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: white;
        display: none;
    }
    
    @media (min-width: 640px) {
        .toggle-label {
            display: block;
        }
    }
    
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ff3b30;
        transition: .4s;
        border-radius: 34px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    input:checked + .slider {
        background-color: #34c759;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .status-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.813rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .status-open {
        background: rgba(52, 199, 89, 0.15);
        color: #34c759;
        border: 1px solid rgba(52, 199, 89, 0.3);
    }
    
    .status-closed {
        background: rgba(255, 59, 48, 0.15);
        color: #ff3b30;
        border: 1px solid rgba(255, 59, 48, 0.3);
    }
    
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>

<div class="workplace-toggle-container">
    <span class="toggle-label" id="toggleLabel">İşletme Kapalı</span>
    <label class="toggle-switch" title="İşletme Durumu">
        <label for="workplaceStatus" class="sr-only">Input</label><input type="checkbox" id="workplaceStatus" checked onchange="toggleWorkplaceStatus()">
        <span class="slider"></span>
    </label>
    <div class="status-indicator status-open" id="statusIndicator">
        <span class="status-dot" style="background: currentColor;"></span>
        <span id="statusText">Açık</span>
    </div>
</div>
';

// Previous header include (kept commented for backup)
// include '../includes/dashboard_header.php';

// Ensure Composer autoload and app bootstrap are available before using classes
require_once __DIR__ . '/../includes/bootstrap.php';

// Use the Seller Header for the Carwash Dashboard (pasted/included Seller header)
include '../includes/seller_header.php';

// Load authoritative business data from the database so the form uses DB values
try {
  // Ensure Database class is available via autoload from bootstrap (header include does this)
  if (isset($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
    try {
      $db = App\Classes\Database::getInstance();
      $pdo = $db->getPdo();
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $tblCheck = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tbl");
      $tblCheck->execute(['tbl' => 'business_profiles']);
      $hasBusinessProfiles = (int) $tblCheck->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

      if ($hasBusinessProfiles) {
        $fetch = $pdo->prepare("SELECT id,user_id,business_name,address,postal_code,phone AS phone,mobile_phone AS mobile_phone,email AS email,working_hours AS working_hours,logo_path,created_at,updated_at FROM business_profiles WHERE user_id = :user_id LIMIT 1");
        $fetch->execute(['user_id' => $userId]);
        $business = $fetch->fetch(PDO::FETCH_ASSOC) ?: [];
        if (!empty($business['working_hours'])) {
          $decoded = json_decode($business['working_hours'], true);
          $business['working_hours'] = $decoded === null ? $business['working_hours'] : $decoded;
        }
      } else {
        $fetch = $pdo->prepare("SELECT id,user_id,business_name,address,postal_code,contact_phone AS phone, contact_email AS email, opening_hours AS working_hours, featured_image AS logo_path, social_media, created_at, updated_at FROM carwash_profiles WHERE user_id = :user_id LIMIT 1");
        $fetch->execute(['user_id' => $userId]);
        $business = $fetch->fetch(PDO::FETCH_ASSOC) ?: [];
        if (!empty($business)) {
          if (!empty($business['working_hours'])) {
            $decoded = json_decode($business['working_hours'], true);
            $business['working_hours'] = $decoded === null ? $business['working_hours'] : $decoded;
          }

          // Extract mobile_phone from social_media JSON (legacy fallback)
          $business['mobile_phone'] = $business['mobile_phone'] ?? null;
          if (!empty($business['social_media'])) {
            $sm = json_decode($business['social_media'], true);
            if (is_array($sm)) {
              foreach (['mobile_phone', 'mobile', 'phone', 'telephone', 'tel'] as $k) {
                if (!empty($sm[$k])) {
                  $business['mobile_phone'] = $sm[$k];
                  break;
                }
              }

              if (empty($business['mobile_phone']) && isset($sm['whatsapp'])) {
                if (is_array($sm['whatsapp'])) {
                  $business['mobile_phone'] = $sm['whatsapp']['number'] ?? $sm['whatsapp']['phone'] ?? $business['mobile_phone'];
                } elseif (is_string($sm['whatsapp'])) {
                  $business['mobile_phone'] = $sm['whatsapp'];
                }
              }
            }
          }

          // Remove social_media from $business to avoid leaking raw JSON into the view
          unset($business['social_media']);
        }
      }

      // Populate session fallbacks so view-mode that uses $_SESSION stays consistent
      if (!empty($business)) {
        if (!empty($business['business_name'])) $_SESSION['business_name'] = $business['business_name'];
        if (!empty($business['email'])) $_SESSION['email'] = $business['email'];
        if (!empty($business['phone'])) $_SESSION['phone'] = $business['phone'];
        if (!empty($business['address'])) $_SESSION['address'] = $business['address'];
        if (!empty($business['postal_code'])) $_SESSION['postal_code'] = $business['postal_code'];
        if (!empty($business['mobile_phone'])) $_SESSION['mobile_phone'] = $business['mobile_phone'];
        if (!empty($business['logo_path'])) $_SESSION['logo_path'] = $business['logo_path'];
      }
    } catch (Exception $e) {
      // Do not break the page if DB read fails; log and continue with session defaults
      error_log('Dashboard business fetch error: ' . $e->getMessage());
      $business = [];
    }
  } else {
    $business = [];
  }
} catch (Exception $e) {
  $business = [];
}
?>

<!-- Dashboard Specific Styles -->
<style>
    /* Dashboard-specific overrides only - Universal fixes included via header */
    
    /* Dashboard Content Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Farsça: انیمیشن برای ورود تدریجی عناصر از چپ به راست. */
    /* Türkçe: Öğelerin soldan sağa doğru yavaşça kayarak gelmesi için animasyon. */
    /* English: Animation for elements to slide in from left to right. */
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-30px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* Farsça: اعمال انیمیشن fadeInUp. */
    /* Türkçe: fadeInUp animasyonunu uygular. */
    /* English: Applies the fadeInUp animation. */
    .animate-fade-in-up {
      animation: fadeInUp 0.6s ease-out forwards;
    }

    /* Farsça: اعمال انیمیشن slideIn. */
    /* Türkçe: slideIn animasyonunu uygular. */
    /* English: Applies the slideIn animation. */
    .animate-slide-in {
      animation: slideIn 0.5s ease-out forwards;
    }

    /* Farsça: پس‌زمینه گرادیانت برای عناصر. */
    /* Türkçe: Öğeler için gradyan arka plan. */
    /* English: Gradient background for elements. */
    .gradient-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Farsça: گرادیانت برای نوار کناری. */
    /* Türkçe: Kenar çubuğu için gradyan. */
    /* English: Gradient for the sidebar. */
    .sidebar-gradient {
      background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    }

    /* Farsça: استایل کارت‌ها هنگام هاور: بزرگنمایی و سایه. */
    /* Türkçe: Kartların üzerine gelindiğinde stili: büyütme ve gölge. */
    /* English: Card style on hover: scale and shadow. */
    .card-hover {
      transition: all 0.3s ease;
    }
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    /* Farsça: استایل وضعیت "در انتظار". */
    /* Türkçe: "Bekliyor" durumu stili. */
    /* English: "Pending" status style. */
    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }

    /* Farsça: استایل وضعیت "تایید شده". */
    /* Türkçe: "Onaylandı" durumu stili. */
    /* English: "Confirmed" status style. */
    .status-confirmed {
      background: #d1fae5;
      color: #065f46;
    }

    /* Farsça: استایل وضعیت "در حال انجام". */
    /* Türkçe: "Devam Ediyor" durumu stili. */
    /* English: "In Progress" status style. */
    .status-in-progress {
      background: #dbeafe;
      color: #1e40af;
    }

    /* Farsça: استایل وضعیت "تکمیل شده". */
    /* Türkçe: "Tamamlandı" durumu stili. */
    /* English: "Completed" status style. */
    .status-completed {
      background: #e0e7ff;
      color: #3730a3;
    }

    /* Farsça: استایل وضعیت "لغو شده". */
    /* Türkçe: "İptal Edildi" durumu stili. */
    /* English: "Cancelled" status style. */
    .status-cancelled {
      background: #fecaca;
      color: #991b1b;
    }

    /* Farsça: استایل اولویت "بالا". */
    /* Türkçe: "Yüksek" öncelik stili. */
    /* English: "High" priority style. */
    .priority-high {
      background: #fee2e2;
      color: #dc2626;
    }

    /* Farsça: استایل اولویت "متوسط". */
    /* Türkçe: "Orta" öncelik stili. */
    /* English: "Medium" priority style. */
    .priority-medium {
      background: #fef3c7;
      color: #d97706;
    }

    /* Farsça: استایل اولویت "پایین". */
    /* Türkçe: "Düşük" öncelik stili. */
    /* English: "Low" priority style. */
    .priority-low {
      background: #d1fae5;
      color: #059669;
    }

    /* Dashboard-specific responsive design - Full Mobile/Tablet/Desktop Support */
    
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
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: #f8fafc;
    }

    /* Mobile Sidebar (Hidden by default, slides in) */
    .mobile-sidebar {
      position: fixed;
      top: 0;
      left: -100%;
      width: 280px;
      height: 100vh;
      z-index: 40;
      transition: left 0.3s ease;
      overflow-y: auto;
      padding-top: 70px;
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

    /* Mobile Menu Button */
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

    /* Accessibility: visually hidden text for screen readers */
    .sr-only {
      position: absolute !important;
      width: 1px !important;
      height: 1px !important;
      padding: 0 !important;
      margin: -1px !important;
      overflow: hidden !important;
      clip: rect(0, 0, 0, 0) !important;
      white-space: nowrap !important;
      border: 0 !important;
    }

    /* Desktop Sidebar - Sticky position inside container */
    .desktop-sidebar {
      display: none;
      position: sticky;
      top: 65px;
      left: 0;
      width: 280px;
      min-height: calc(100vh - 65px);
      overflow: hidden;
      flex-shrink: 0;
      z-index: 30;
      background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
      align-self: stretch;
    }
    
    /* Desktop sidebar inner content */
    .desktop-sidebar .p-6 {
      height: 100%;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      padding: 1rem 1.5rem;
      box-sizing: border-box;
    }
    
    /* Main Content Area */
    .main-content {
      flex: 1;
      padding: 1rem;
      min-height: calc(100vh - 65px);
    }

    /* Section content styling */
    .section-content {
      width: 100%;
      max-width: 100%;
    }

    /* Responsive Grid Adjustments */
    .stats-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: 1fr;
    }

    .content-grid-2 {
      display: grid;
      gap: 1rem;
      grid-template-columns: 1fr;
    }

    /* Table Responsiveness */
    .universal-table-container {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .universal-table-container table {
      min-width: 600px;
    }

    /* Card Responsiveness */
    .card-hover {
      transition: all 0.3s ease;
    }

    /* Mobile Responsive (< 768px) */
    @media (max-width: 767px) {
      .main-content {
        padding: 1rem 0.75rem;
      }
      
      .mobile-menu-btn {
        top: 70px;
        left: 0.75rem;
        padding: 10px 14px;
        font-size: 0.875rem;
      }

      .section-content h2 {
        font-size: 1.5rem;
      }

      .section-content p {
        font-size: 0.875rem;
      }

      /* Stack cards vertically */
      .stats-grid {
        gap: 0.75rem;
      }

      /* Adjust modal width */
      .modal-content {
        width: 95% !important;
        max-width: 95% !important;
        margin: 1rem;
      }

      /* Hide mobile sidebar text on very small screens */
      .mobile-sidebar nav a span {
        font-size: 0.875rem;
      }

      /* Compact status badges */
      .status-pending,
      .status-confirmed,
      .status-in-progress,
      .status-completed,
      .status-cancelled {
        font-size: 0.688rem;
        padding: 0.25rem 0.5rem;
      }
    }

    /* Tablet Responsive (768px - 1023px) */
    @media (min-width: 768px) and (max-width: 1023px) {
      .main-content {
        padding: 1.5rem;
      }
      
      .mobile-sidebar {
        width: 320px;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }

      .content-grid-2 {
        grid-template-columns: 1fr;
      }

      .section-content h2 {
        font-size: 2rem;
      }
    }

    /* Desktop Responsive (>= 1024px) */
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

      .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
      }

      .content-grid-2 {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
      }
    }

    /* Large Desktop (>= 1400px) */
    @media (min-width: 1400px) {
      .main-content {
        max-width: calc(1400px);
      }

      .section-content {
        max-width: 1200px;
      }
    }

    /* Print Styles */
    @media print {
      .mobile-menu-btn,
      .mobile-sidebar,
      .desktop-sidebar,
      .mobile-overlay {
        display: none !important;
      }

      .main-content {
        padding: 0;
      }
    }
    
    /* Footer adjustments */
    footer {
      margin-top: 0 !important;
      position: relative;
    }

    /* Smooth scrollbar styling */
    .desktop-sidebar::-webkit-scrollbar,
    .mobile-sidebar::-webkit-scrollbar {
      width: 6px;
    }

    .desktop-sidebar::-webkit-scrollbar-track,
    .mobile-sidebar::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
    }

    .desktop-sidebar::-webkit-scrollbar-thumb,
    .mobile-sidebar::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
      border-radius: 3px;
    }

    .desktop-sidebar::-webkit-scrollbar-thumb:hover,
    .mobile-sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.5);
    }
  </style>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleMobileSidebar()" id="mobileMenuBtn" title="Menüyü aç veya kapat" aria-label="Menüyü aç veya kapat">
  <i class="fas fa-bars" id="menuIcon" aria-hidden="true"></i>
  <span class="sr-only">Menüyü aç veya kapat</span>
</button>

<!-- Mobile Overlay -->
<div class="mobile-overlay" onclick="closeMobileSidebar()" id="mobileOverlay"></div>

<!-- Dashboard Layout -->
<div class="dashboard-container">
    <!-- Mobile Sidebar -->
    <aside class="mobile-sidebar sidebar-gradient text-white" id="mobileSidebar">
      <div class="p-6">
        <div class="text-center mb-8">
          <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4 overflow-hidden">
            <img id="mobileSidebarLogo" src="<?php echo htmlspecialchars($_SESSION['logo_path'] ?? '/carwash_project/backend/logo01.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Business Logo" class="w-full h-full object-cover sidebar-logo">
          </div>
          <h3 class="text-xl font-bold" id="mobileSidebarBusinessName">CarWash Merkez</h3>
          <p class="text-sm opacity-75">Premium İşletme</p>
        </div>

        <nav class="space-y-2">
          <a href="#dashboard" onclick="showSection('dashboard')" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Genel Bakış
          </a>
          <a href="#business" onclick="showSection('business')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-building mr-3"></i>
            İşletme Bilgileri
          </a>
          <a href="#reservations" onclick="showSection('reservations')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-calendar-alt mr-3"></i>
            Rezervasyonlar
          </a>
          <a href="#services" onclick="showSection('services')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-car-wash mr-3"></i>
            Hizmetler
          </a>
          <a href="#customers" onclick="showSection('customers')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-users mr-3"></i>
            Müşteriler
          </a>
          <a href="#staff" onclick="showSection('staff')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-user-tie mr-3"></i>
            Personel
          </a>
          <a href="#reports" onclick="showSection('reports')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-chart-bar mr-3"></i>
            Raporlar
          </a>
          <a href="#profile" onclick="showSection('profile')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-user-circle mr-3"></i>
            Profil
          </a>
          <a href="#settings" onclick="showSection('settings')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
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
          <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4 overflow-hidden">
            <img id="desktopSidebarLogo" src="<?php echo htmlspecialchars($_SESSION['logo_path'] ?? '/carwash_project/backend/logo01.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Business Logo" class="w-full h-full object-cover sidebar-logo">
          </div>
          <h3 class="text-xl font-bold" id="desktopSidebarBusinessName">CarWash Merkez</h3>
          <p class="text-sm opacity-75">Premium İşletme</p>
        </div>

        <nav class="space-y-2">
          <a href="#dashboard" onclick="showSection('dashboard')" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Genel Bakış
          </a>
          <a href="#business" onclick="showSection('business')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-building mr-3"></i>
            İşletme Bilgileri
          </a>
          <a href="#reservations" onclick="showSection('reservations')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-calendar-check mr-3"></i>
            Rezervasyonlar
          </a>
          <a href="#customers" onclick="showSection('customers')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-users mr-3"></i>
            Müşteriler
          </a>
          <a href="#services" onclick="showSection('services')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-wrench mr-3"></i>
            Hizmetler
          </a>
          <a href="#staff" onclick="showSection('staff')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-user-tie mr-3"></i>
            Personel
          </a>
          <a href="#financial" onclick="showSection('financial')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-chart-line mr-3"></i>
            Finansal
          </a>
          <a href="#reports" onclick="showSection('reports')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-chart-bar mr-3"></i>
            Raporlar
          </a>
          <a href="#invoices" onclick="showSection('invoices')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-file-invoice mr-3"></i>
            Faturalar
          </a>
          <a href="#profile" onclick="showSection('profile')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-user-circle mr-3"></i>
            Profil
          </a>
          <a href="#settings" onclick="showSection('settings')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-cog mr-3"></i>
            Ayarlar
          </a>
        </nav>
      </div>
    </aside>

    <!-- Main Content -->
    <!-- Farsça: محتوای اصلی داشبورد. -->
    <!-- Türkçe: Ana kontrol paneli içeriği. -->
    <!-- English: Main dashboard content. -->
    <main class="main-content">
      <!-- Dashboard Overview -->
      <!-- Farsça: بخش نمای کلی داشبورد. -->
      <!-- Türkçe: Kontrol paneli genel bakış bölümü. -->
      <!-- English: Dashboard Overview section. -->
      <section id="dashboard" class="section-content">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Genel Bakış</h2>
          <p class="text-gray-600">İşletmenizin günlük özeti ve performans metrikleri</p>
        </div>

        <!-- Key Metrics -->
        <!-- Farsça: معیارهای کلیدی عملکرد. -->
        <!-- Türkçe: Temel performans metrikleri. -->
        <!-- English: Key Metrics. -->
        <div class="stats-grid mb-8">
          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Bugünkü Rezervasyon</p>
                <p class="text-3xl font-bold text-blue-600">12</p>
                <p class="text-sm text-green-600 mt-1">+3 önceki güne göre</p>
              </div>
              <i class="fas fa-calendar-check text-4xl text-blue-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Aylık Gelir</p>
                <p class="text-3xl font-bold text-green-600">₺15,420</p>
                <p class="text-sm text-green-600 mt-1">+12% artış</p>
              </div>
              <i class="fas fa-money-bill-wave text-4xl text-green-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Aktif Müşteri</p>
                <p class="text-3xl font-bold text-purple-600">156</p>
                <p class="text-sm text-purple-600 mt-1">+8 yeni müşteri</p>
              </div>
              <i class="fas fa-users text-4xl text-purple-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Ortalama Puan</p>
                <p class="text-3xl font-bold text-yellow-600">4.8★</p>
                <p class="text-sm text-yellow-600 mt-1">95% memnuniyet</p>
              </div>
              <i class="fas fa-star text-4xl text-yellow-600 opacity-20"></i>
            </div>
          </div>
        </div>

        <!-- Today's Schedule & Recent Activity -->
        <!-- Farsça: برنامه امروز و فعالیت‌های اخیر. -->
        <!-- Türkçe: Bugünün Programı ve Son Aktiviteler. -->
        <!-- English: Today's Schedule & Recent Activity. -->
        <div class="content-grid-2">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-clock text-blue-600 mr-2"></i>
              Bugünün Programı
            </h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-car text-blue-600"></i>
                  </div>
                  <div>
                    <h4 class="font-bold">Dış Yıkama + İç Temizlik</h4>
                    <p class="text-sm text-gray-600">Ahmet Yılmaz - 34 ABC 123</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-blue-600">10:00</p>
                  <span class="status-confirmed px-2 py-1 rounded-full text-xs">Onaylandı</span>
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-car text-green-600"></i>
                  </div>
                  <div>
                    <h4 class="font-bold">Tam Detaylandırma</h4>
                    <p class="text-sm text-gray-600">Fatma Kaya - 34 XYZ 789</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-green-600">14:00</p>
                  <span class="status-in-progress px-2 py-1 rounded-full text-xs">Devam Ediyor</span>
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-car text-purple-600"></i>
                  </div>
                  <div>
                    <h4 class="font-bold">Premium Paket</h4>
                    <p class="text-sm text-gray-600">Mehmet Demir - 34 DEF 456</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-purple-600">16:00</p>
                  <span class="status-pending px-2 py-1 rounded-full text-xs">Bekliyor</span>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-bell text-blue-600 mr-2"></i>
              Son Aktiviteler
            </h3>
            <div class="space-y-4">
              <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg">
                <i class="fas fa-plus-circle text-blue-600 mt-1"></i>
                <div>
                  <p class="text-sm font-medium">Yeni rezervasyon alındı</p>
                  <p class="text-xs text-gray-600">Premium paket - 2 saat önce</p>
                </div>
              </div>

              <div class="flex items-start space-x-3 p-3 bg-green-50 rounded-lg">
                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                <div>
                  <p class="text-sm font-medium">Ödeme tamamlandı</p>
                  <p class="text-xs text-gray-600">₺180 - 3 saat önce</p>
                </div>
              </div>

              <div class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg">
                <i class="fas fa-star text-yellow-600 mt-1"></i>
                <div>
                  <p class="text-sm font-medium">Yeni yorum alındı</p>
                  <p class="text-xs text-gray-600">5 yıldız - 5 saat önce</p>
                </div>
              </div>

              <div class="flex items-start space-x-3 p-3 bg-purple-50 rounded-lg">
                <i class="fas fa-chart-line text-purple-600 mt-1"></i>
                <div>
                  <p class="text-sm font-medium">Aylık hedef aşıldı</p>
                  <p class="text-xs text-gray-600">₺15,420 / ₺15,000 - Bugün</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Reservations Management -->
      <!-- Farsça: بخش مدیریت رزروها. -->
      <!-- Türkçe: Rezervasyon Yönetimi bölümü. -->
      <!-- English: Reservations Management section. -->
      <section id="reservations" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Rezervasyon Yönetimi</h2>
          <p class="text-gray-600">Tüm rezervasyonları görüntüleyin, onaylayın ve yönetin</p>
        </div>

        <!-- Filters and Actions -->
        <!-- Farsça: فیلترها و اقدامات. -->
        <!-- Türkçe: Filtreler ve Eylemler. -->
        <!-- English: Filters and Actions. -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
          <div class="flex flex-wrap justify-between items-center gap-4">
            <div class="flex space-x-4">
              <label for="filterStatus" class="sr-only">Durum</label>
              <select id="filterStatus" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" title="Durum filtresi" aria-label="Durum filtresi">
                <option>Tüm Durumlar</option>
                <option>Bekliyor</option>
                <option>Onaylandı</option>
                <option>Devam Ediyor</option>
                <option>Tamamlandı</option>
                <option>İptal Edildi</option>
              </select>

              <label for="auto_113" class="sr-only">Date</label><input type="date" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_113">
            </div>

            <div class="flex space-x-2">
              <button onclick="openManualReservationModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                <i class="fas fa-plus mr-2"></i>Manuel Rezervasyon
              </button>
              <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-all">
                <i class="fas fa-download mr-2"></i>Export
              </button>
            </div>
          </div>
        </div>

        <!-- Reservations Table -->
        <!-- Farsça: جدول رزروها. -->
        <!-- Türkçe: Rezervasyon Tablosu. -->
        <!-- English: Reservations Table. -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Müşteri</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Araç</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih/Saat</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Öncelik</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div>
                      <div class="font-medium">Ahmet Yılmaz</div>
                      <div class="text-sm text-gray-500">0555 123 4567</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm">Dış Yıkama + İç Temizlik</td>
                  <td class="px-6 py-4 text-sm">Toyota Corolla<br>34 ABC 123</td>
                  <td class="px-6 py-4 text-sm">15.12.2024<br>10:00</td>
                  <td class="px-6 py-4"><span class="status-confirmed px-2 py-1 rounded-full text-xs">Onaylandı</span></td>
                  <td class="px-6 py-4 font-medium">₺130</td>
                  <td class="px-6 py-4"><span class="priority-medium px-2 py-1 rounded-full text-xs">Orta</span></td>
                  <td class="px-6 py-4 text-sm">
                    <button class="text-blue-600 hover:text-blue-900 mr-2">Düzenle</button>
                    <button class="text-green-600 hover:text-green-900 mr-2">Tamamla</button>
                    <button class="text-red-600 hover:text-red-900">İptal</button>
                  </td>
                </tr>

                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div>
                      <div class="font-medium">Fatma Kaya</div>
                      <div class="text-sm text-gray-500">0555 987 6543</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm">Tam Detaylandırma</td>
                  <td class="px-6 py-4 text-sm">Honda Civic<br>34 XYZ 789</td>
                  <td class="px-6 py-4 text-sm">15.12.2024<br>14:00</td>
                  <td class="px-6 py-4"><span class="status-in-progress px-2 py-1 rounded-full text-xs">Devam Ediyor</span></td>
                  <td class="px-6 py-4 font-medium">₺200</td>
                  <td class="px-6 py-4"><span class="priority-high px-2 py-1 rounded-full text-xs">Yüksek</span></td>
                  <td class="px-6 py-4 text-sm">
                    <button class="text-blue-600 hover:text-blue-900 mr-2">Detay</button>
                    <button class="text-green-600 hover:text-green-900 mr-2">Tamamla</button>
                  </td>
                </tr>

                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div>
                      <div class="font-medium">Mehmet Demir</div>
                      <div class="text-sm text-gray-500">0555 456 7890</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm">Premium Paket</td>
                  <td class="px-6 py-4 text-sm">BMW 3 Serisi<br>34 DEF 456</td>
                  <td class="px-6 py-4 text-sm">15.12.2024<br>16:00</td>
                  <td class="px-6 py-4"><span class="status-pending px-2 py-1 rounded-full text-xs">Bekliyor</span></td>
                  <td class="px-6 py-4 font-medium">₺250</td>
                  <td class="px-6 py-4"><span class="priority-low px-2 py-1 rounded-full text-xs">Düşük</span></td>
                  <td class="px-6 py-4 text-sm">
                    <button class="text-green-600 hover:text-green-900 mr-2">Onayla</button>
                    <button class="text-yellow-600 hover:text-yellow-900 mr-2">Yeniden Planla</button>
                    <button class="text-red-600 hover:text-red-900">Reddet</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Customer Management -->
      <!-- Farsça: بخش مدیریت مشتریان. -->
      <!-- Türkçe: Müşteri Yönetimi bölümü. -->
      <!-- English: Customer Management section. -->
      <section id="customers" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Müşteri Yönetimi</h2>
          <p class="text-gray-600">Müşteri bilgilerini yönetin ve geçmişlerini görüntüleyin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                  <h3 class="text-xl font-bold">Müşteri Listesi</h3>
                  <button onclick="openCustomerModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                    <i class="fas fa-plus mr-2"></i>Müşteri Ekle
                  </button>
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Müşteri</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İletişim</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Toplam Harcama</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Son Ziyaret</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="font-bold text-blue-600">AY</span>
                          </div>
                          <div>
                            <div class="font-medium">Ahmet Yılmaz</div>
                            <div class="text-sm text-gray-500">Premium Müşteri</div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm">
                        <div>ali.yilmaz@email.com</div>
                        <div class="text-gray-500">0555 123 4567</div>
                      </td>
                      <td class="px-6 py-4 font-medium">₺2,450</td>
                      <td class="px-6 py-4 text-sm">12.12.2024</td>
                      <td class="px-6 py-4"><span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Aktif</span></td>
                      <td class="px-6 py-4 text-sm">
                        <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                        <button class="text-yellow-600 hover:text-yellow-900">Düzenle</button>
                      </td>
                    </tr>

                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="font-bold text-green-600">FK</span>
                          </div>
                          <div>
                            <div class="font-medium">Fatma Kaya</div>
                            <div class="text-sm text-gray-500">Standart Müşteri</div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm">
                        <div>fatma.kaya@email.com</div>
                        <div class="text-gray-500">0555 987 6543</div>
                      </td>
                      <td class="px-6 py-4 font-medium">₺890</td>
                      <td class="px-6 py-4 text-sm">10.12.2024</td>
                      <td class="px-6 py-4"><span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Aktif</span></td>
                      <td class="px-6 py-4 text-sm">
                        <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                        <button class="text-yellow-600 hover:text-yellow-900">Düzenle</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Müşteri İstatistikleri</h3>
            <div class="space-y-6">
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">156</div>
                <div class="text-sm text-gray-600">Toplam Müşteri</div>
              </div>

              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span class="text-sm">Premium</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-20 bg-gray-200 rounded-full h-2">
                      <div class="bg-blue-600 h-2 rounded-full" style="width: 35%"></div>
                    </div>
                    <span class="text-sm font-medium">35%</span>
                  </div>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm">Standart</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-20 bg-gray-200 rounded-full h-2">
                      <div class="bg-green-600 h-2 rounded-full" style="width: 50%"></div>
                    </div>
                    <span class="text-sm font-medium">50%</span>
                  </div>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm">Tek Seferlik</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-20 bg-gray-200 rounded-full h-2">
                      <div class="bg-yellow-600 h-2 rounded-full" style="width: 15%"></div>
                    </div>
                    <span class="text-sm font-medium">15%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Services Management -->
      <!-- Farsça: بخش مدیریت خدمات. -->
      <!-- Türkçe: Hizmet Yönetimi bölümü. -->
      <!-- English: Services Management section. -->
      <section id="services" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Hizmet Yönetimi</h2>
          <p class="text-gray-600">Hizmetlerinizi yönetin, fiyatları güncelleyin ve yeni hizmetler ekleyin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Hizmet Listesi</h3>
                <button onclick="openServiceModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                  <i class="fas fa-plus mr-2"></i>Hizmet Ekle
                </button>
              </div>

              <div class="space-y-4">
                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-car text-blue-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Dış Yıkama + İç Temizlik</h4>
                      <p class="text-sm text-gray-600">45 dakika - Premium kalite</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-lg">₺130</div>
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900" title="Düzenle" aria-label="Hizmeti düzenle">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                        <span class="sr-only">Düzenle</span>
                      </button>
                      <button class="text-red-600 hover:text-red-900" title="Sil" aria-label="Hizmeti sil">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <span class="sr-only">Sil</span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-scrubber text-green-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Tam Detaylandırma</h4>
                      <p class="text-sm text-gray-600">90 dakika - Profesyonel bakım</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-lg">₺200</div>
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900" title="Düzenle" aria-label="Hizmeti düzenle">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                        <span class="sr-only">Düzenle</span>
                      </button>
                      <button class="text-red-600 hover:text-red-900" title="Sil" aria-label="Hizmeti sil">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <span class="sr-only">Sil</span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-crown text-purple-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Premium Paket</h4>
                      <p class="text-sm text-gray-600">120 dakika - VIP hizmet</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-lg">₺250</div>
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900" title="Düzenle" aria-label="Düzenle">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                        <span class="sr-only">Düzenle</span>
                      </button>
                      <button class="text-red-600 hover:text-red-900" title="Sil" aria-label="Sil">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <span class="sr-only">Sil</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Hizmet İstatistikleri</h3>
            <div class="space-y-6">
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">28</div>
                <div class="text-sm text-gray-600">Aktif Hizmet</div>
              </div>

              <div class="space-y-4">
                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>En Popüler</span>
                    <span>45%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 45%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Premium Paketler</span>
                    <span>30%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 30%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Temel Hizmetler</span>
                    <span>25%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 25%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Staff Management -->
      <!-- Farsça: بخش مدیریت پرسنل. -->
      <!-- Türkçe: Personel Yönetimi bölümü. -->
      <!-- English: Staff Management section. -->
      <section id="staff" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Personel Yönetimi</h2>
          <p class="text-gray-600">Personel bilgilerini yönetin ve performanslarını takip edin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Personel Listesi</h3>
                <button onclick="openStaffModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                  <i class="fas fa-plus mr-2"></i>Personel Ekle
                </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Ali Yılmaz</h4>
                      <p class="text-sm text-gray-600">Senior Teknisyen</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900" title="Düzenle" aria-label="Personeli düzenle">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                        <span class="sr-only">Düzenle</span>
                      </button>
                      <button class="text-red-600 hover:text-red-900" title="Sil" aria-label="Personeli sil">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <span class="sr-only">Sil</span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-green-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Fatma Kaya</h4>
                      <p class="text-sm text-gray-600">Teknisyen</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900" title="Düzenle" aria-label="Personeli düzenle">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                        <span class="sr-only">Düzenle</span>
                      </button>
                      <button class="text-red-600 hover:text-red-900" title="Sil" aria-label="Personeli sil">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <span class="sr-only">Sil</span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-purple-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Mehmet Demir</h4>
                      <p class="text-sm text-gray-600">Çırak</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900" title="Düzenle" aria-label="Personeli düzenle">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                        <span class="sr-only">Düzenle</span>
                      </button>
                      <button class="text-red-600 hover:text-red-900" title="Sil" aria-label="Personeli sil">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <span class="sr-only">Sil</span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-yellow-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Ayşe Şahin</h4>
                      <p class="text-sm text-gray-600">Resepsiyonist</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900" title="Düzenle" aria-label="Personeli düzenle">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                        <span class="sr-only">Düzenle</span>
                      </button>
                      <button class="text-red-600 hover:text-red-900" title="Sil" aria-label="Personeli sil">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        <span class="sr-only">Sil</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Personel Performansı</h3>
            <div class="space-y-6">
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">4</div>
                <div class="text-sm text-gray-600">Aktif Personel</div>
              </div>

              <div class="space-y-4">
                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Bu Ay Tamamlanan İş</span>
                    <span>85</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Müşteri Memnuniyeti</span>
                    <span>4.8/5</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 96%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Devamsızlık Oranı</span>
                    <span>2%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 2%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Financial Reports -->
      <!-- Farsça: بخش گزارشات مالی. -->
      <!-- Türkçe: Finansal Raporlar bölümü. -->
      <!-- English: Financial Reports section. -->
      <section id="financial" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Finansal Raporlar</h2>
          <p class="text-gray-600">Gelir, gider ve karlılık analizlerinizi görüntüleyin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Gelir-Gider Özeti</h3>
            <div class="space-y-4">
              <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
                <div>
                  <h4 class="font-bold text-green-800">Toplam Gelir</h4>
                  <p class="text-sm text-green-600">Bu ay</p>
                </div>
                <span class="text-2xl font-bold text-green-600">₺15,420</span>
              </div>

              <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg">
                <div>
                  <h4 class="font-bold text-red-800">Toplam Gider</h4>
                  <p class="text-sm text-red-600">Bu ay</p>
                </div>
                <span class="text-2xl font-bold text-red-600">₺8,750</span>
              </div>

              <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg">
                <div>
                  <h4 class="font-bold text-blue-800">Net Kar</h4>
                  <p class="text-sm text-blue-600">Bu ay</p>
                </div>
                <span class="text-2xl font-bold text-blue-600">₺6,670</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Hizmet Bazında Gelir</h3>
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <span class="text-sm">Premium Paket</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 40%"></div>
                  </div>
                  <span class="text-sm font-medium">₺6,168</span>
                </div>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-sm">Tam Detaylandırma</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 35%"></div>
                  </div>
                  <span class="text-sm font-medium">₺5,397</span>
                </div>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-sm">Dış Yıkama + İç</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 25%"></div>
                  </div>
                  <span class="text-sm font-medium">₺3,855</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
          <h3 class="text-xl font-bold mb-6">Aylık Trend</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">₺12,340</div>
              <div class="text-sm text-gray-600">Geçen Ay</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">₺15,420</div>
              <div class="text-sm text-gray-600">Bu Ay</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-purple-600">₺18,000</div>
              <div class="text-sm text-gray-600">Hedef</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Reports -->
      <!-- Farsça: بخش گزارشات و تحلیل‌ها. -->
      <!-- Türkçe: Raporlar ve Analitik bölümü. -->
      <!-- English: Reports and Analytics section. -->
      <section id="reports" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Raporlar ve Analitik</h2>
          <p class="text-gray-600">Detaylı raporlar ve iş zekası analizleri</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Zaman Bazında Performans</h3>
            <div class="space-y-4">
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>Pazartesi</span>
                  <span>85%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>Salı</span>
                  <span>92%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-green-600 h-2 rounded-full" style="width: 92%"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>Çarşamba</span>
                  <span>78%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-yellow-600 h-2 rounded-full" style="width: 78%"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>Perşembe</span>
                  <span>88%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-purple-600 h-2 rounded-full" style="width: 88%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Cuma</span>
                    <span>95%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 95%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Cumartesi</span>
                    <span>90%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 90%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Pazar</span>
                    <span>65%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-red-600 h-2 rounded-full" style="width: 65%"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-lg">
              <h3 class="text-xl font-bold mb-6">Müşteri Memnuniyeti</h3>
              <div class="space-y-6">
                <div class="text-center">
                  <div class="text-4xl font-bold text-yellow-600">4.8★</div>
                  <div class="text-sm text-gray-600">Ortalama Puan</div>
                </div>

                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                    </div>
                    <span class="text-sm font-medium">65%</span>
                  </div>

                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="far fa-star text-gray-300"></i>
                    </div>
                    <span class="text-sm font-medium">25%</span>
                  </div>

                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="far fa-star text-gray-300"></i>
                      <i class="far fa-star text-gray-300"></i>
                    </div>
                    <span class="text-sm font-medium">8%</span>
                  </div>

                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="far fa-star text-gray-300"></i>
                      <i class="far fa-star text-gray-300"></i>
                      <i class="far fa-star text-gray-300"></i>
                    </div>
                    <span class="text-sm font-medium">2%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Invoices -->
        <!-- Farsça: بخش فاکتورها. -->
        <!-- Türkçe: Faturalar bölümü. -->
        <!-- English: Invoices section. -->
        <section id="invoices" class="section-content hidden">
          <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Fatura Yönetimi</h2>
            <p class="text-gray-600">Otomatik faturalandırma ve fatura takibi</p>
          </div>

          <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center">
              <h3 class="text-xl font-bold">Otomatik Faturalandırma</h3>
              <div class="flex space-x-2">
                <button class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                  <i class="fas fa-plus mr-2"></i>Manuel Fatura
                </button>
                <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-all">
                  <i class="fas fa-cog mr-2"></i>Ayarlar
                </button>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fatura No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Müşteri</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tutar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">#INV-2024-001</td>
                    <td class="px-6 py-4">Ahmet Yılmaz</td>
                    <td class="px-6 py-4">Premium Paket</td>
                    <td class="px-6 py-4 font-medium">₺250</td>
                    <td class="px-6 py-4"><span class="status-completed px-2 py-1 rounded-full text-xs">Ödendi</span></td>
                    <td class="px-6 py-4 text-sm">15.12.2024</td>
                    <td class="px-6 py-4 text-sm">
                      <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                      <button class="text-green-600 hover:text-green-900 mr-2">Yazdır</button>
                      <button class="text-purple-600 hover:text-purple-900">E-posta</button>
                    </td>
                  </tr>

                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">#INV-2024-002</td>
                    <td class="px-6 py-4">Fatma Kaya</td>
                    <td class="px-6 py-4">Tam Detaylandırma</td>
                    <td class="px-6 py-4 font-medium">₺200</td>
                    <td class="px-6 py-4"><span class="status-pending px-2 py-1 rounded-full text-xs">Bekliyor</span></td>
                    <td class="px-6 py-4 text-sm">15.12.2024</td>
                    <td class="px-6 py-4 text-sm">
                      <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                      <button class="text-green-600 hover:text-green-900 mr-2">Gönder</button>
                      <button class="text-red-600 hover:text-red-900">İptal</button>
                    </td>
                  </tr>

                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">#INV-2024-003</td>
                    <td class="px-6 py-4">Mehmet Demir</td>
                    <td class="px-6 py-4">Dış Yıkama + İç</td>
                    <td class="px-6 py-4 font-medium">₺130</td>
                    <td class="px-6 py-4"><span class="status-completed px-2 py-1 rounded-full text-xs">Ödendi</span></td>
                    <td class="px-6 py-4 text-sm">14.12.2024</td>
                    <td class="px-6 py-4 text-sm">
                      <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                      <button class="text-green-600 hover:text-green-900 mr-2">Yazdır</button>
                      <button class="text-purple-600 hover:text-purple-900">E-posta</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Business (İşletme Bilgileri) - Independent Section -->
        <section id="business" class="section-content hidden">
          <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">İşletme Bilgileri</h2>
            <p class="text-gray-600">İşletme profil bilgilerinizi yönetin</p>
          </div>
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">İşletme Bilgileri</h3>

            <!-- Business VIEW MODE (read-only) -->
            <div id="businessViewMode" class="space-y-6">
              <div class="flex items-center gap-6 pb-6 border-b border-gray-200">
                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-blue-100 bg-gray-100">
                  <img id="businessViewLogo" src="<?php echo htmlspecialchars($_SESSION['logo_path'] ?? '/carwash_project/backend/logo01.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Business Logo" class="w-full h-full object-cover">
                </div>
                <div>
                  <h3 id="businessViewName" class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION['business_name'] ?? 'CarWash Merkez'); ?></h3>
                  <p id="businessViewEmail" class="text-gray-600 mt-1"><?php echo htmlspecialchars($_SESSION['email'] ?? 'info@carwashmerkez.com'); ?></p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-500">Telefon</label>
                  <p id="businessViewPhone" class="text-base text-gray-900"><?php echo htmlspecialchars($_SESSION['phone'] ?? '0216 123 4567'); ?></p>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-500">Adres</label>
                  <p id="businessViewAddress" class="text-base text-gray-900"><?php echo htmlspecialchars($_SESSION['address'] ?? 'İstanbul, Kadıköy, Moda Mahallesi, No: 123'); ?></p>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-500">Cep Telefonu</label>
                  <p id="businessViewMobile" class="text-base text-gray-900"><?php echo htmlspecialchars($_SESSION['mobile_phone'] ?? '05XX XXX XX XX'); ?></p>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-500">Posta Kodu</label>
                  <p id="businessViewPostal" class="text-base text-gray-900"><?php echo htmlspecialchars($_SESSION['postal_code'] ?? '-'); ?></p>
                </div>
              </div>

              <div class="flex justify-end pt-6 border-t border-gray-200">
                <button id="editBusinessBtn" type="button" onclick="toggleBusinessEdit(true)" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center gap-2">
                  <i class="fas fa-edit"></i>
                  <span>Düzenle</span>
                </button>
              </div>
            </div>

            <!-- Business EDIT MODE (form) - hidden by default -->
            <form id="businessInfoForm" class="space-y-4 hidden">
              <!-- Hidden business_id (optional) - backend will use session user_id if empty -->
              <input type="hidden" name="business_id" id="business_id" value="<?php echo htmlspecialchars($business['id'] ?? $_SESSION['business_id'] ?? ''); ?>">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">İşletme Adı</label>
                  <label for="auto_114" class="sr-only">Input</label>
                  <input type="text" name="business_name" value="<?php echo htmlspecialchars($business['business_name'] ?? $_SESSION['business_name'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_114">
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Adres</label>
                  <label for="auto_115" class="sr-only">Input</label><textarea name="address" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_115"><?php echo htmlspecialchars($business['address'] ?? $_SESSION['address'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Telefon</label>
                    <label for="auto_116" class="sr-only">Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($business['phone'] ?? $_SESSION['phone'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_116">
                  </div>
                  <!-- Farsça: فیلد شماره تلفن همراه. -->
                  <!-- Türkçe: Cep Telefonu Numarası Alanı. -->
                  <!-- English: Mobile Phone Number Field. -->
                  <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Cep Telefonu</label>
                    <label for="auto_117" class="sr-only">Phone</label>
                    <input type="tel" name="mobile_phone" value="<?php echo htmlspecialchars($business['mobile_phone'] ?? $_SESSION['mobile_phone'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_117">
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                  <label for="auto_118" class="sr-only">Email</label>
                  <input type="email" name="email" value="<?php echo htmlspecialchars($business['email'] ?? $_SESSION['email'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_118">
                </div>

                <!-- Farsça: گزینه بارگذاری لوگو. -->
                <!-- Türkçe: Logo Yükleme Seçeneği. -->
                <!-- English: Upload Logo Option. -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">İşletme Logosu</label>
                  <div class="flex items-center space-x-4">
                    <img id="currentLogo" src="<?php echo htmlspecialchars($business['logo_path'] ?? $_SESSION['logo_path'] ?? '/carwash_project/backend/logo01.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Current Business Logo" class="w-20 h-20 rounded-lg object-cover border header-logo sidebar-logo">
                    <label for="logoUpload" class="sr-only">Choose file</label><input type="file" id="logoUpload" name="logo" class="hidden" accept="image/*" onchange="previewLogo(event)">
                    <button type="button" onclick="document.getElementById('logoUpload').click()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                      <i class="fas fa-upload mr-2"></i>Logo Yükle
                    </button>
                  </div>
                </div>

                <!-- Farsça: ساعات کاری برای هر روز. -->
                <!-- Türkçe: Her Gün İçin Çalışma Saatleri. -->
                <!-- English: Working Hours for Each Day. -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Çalışma Saatleri</label>
                  <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Pazartesi:</span>
                      <label for="auto_119" class="sr-only">Başlangıç</label>
                      <input type="time" name="monday_start" value="<?php echo htmlspecialchars(($business['working_hours']['monday']['start'] ?? $business['working_hours']['monday_start'] ?? '08:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_119">
                      <span class="mx-2">-</span>
                      <label for="auto_120" class="sr-only">Bitiş</label>
                      <input type="time" name="monday_end" value="<?php echo htmlspecialchars(($business['working_hours']['monday']['end'] ?? $business['working_hours']['monday_end'] ?? '20:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_120">
                    </div>

                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Salı:</span>
                      <label for="auto_121" class="sr-only">Başlangıç</label>
                      <input type="time" name="tuesday_start" value="<?php echo htmlspecialchars(($business['working_hours']['tuesday']['start'] ?? $business['working_hours']['tuesday_start'] ?? '08:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_121">
                      <span class="mx-2">-</span>
                      <label for="auto_122" class="sr-only">Bitiş</label>
                      <input type="time" name="tuesday_end" value="<?php echo htmlspecialchars(($business['working_hours']['tuesday']['end'] ?? $business['working_hours']['tuesday_end'] ?? '20:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_122">
                    </div>

                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Çarşamba:</span>
                      <label for="auto_123" class="sr-only">Başlangıç</label>
                      <input type="time" name="wednesday_start" value="<?php echo htmlspecialchars(($business['working_hours']['wednesday']['start'] ?? $business['working_hours']['wednesday_start'] ?? '08:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_123">
                      <span class="mx-2">-</span>
                      <label for="auto_124" class="sr-only">Bitiş</label>
                      <input type="time" name="wednesday_end" value="<?php echo htmlspecialchars(($business['working_hours']['wednesday']['end'] ?? $business['working_hours']['wednesday_end'] ?? '20:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_124">
                    </div>

                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Perşembe:</span>
                      <label for="auto_125" class="sr-only">Başlangıç</label>
                      <input type="time" name="thursday_start" value="<?php echo htmlspecialchars(($business['working_hours']['thursday']['start'] ?? $business['working_hours']['thursday_start'] ?? '08:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_125">
                      <span class="mx-2">-</span>
                      <label for="auto_126" class="sr-only">Bitiş</label>
                      <input type="time" name="thursday_end" value="<?php echo htmlspecialchars(($business['working_hours']['thursday']['end'] ?? $business['working_hours']['thursday_end'] ?? '20:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_126">
                    </div>

                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Cuma:</span>
                      <label for="auto_127" class="sr-only">Başlangıç</label>
                      <input type="time" name="friday_start" value="<?php echo htmlspecialchars(($business['working_hours']['friday']['start'] ?? $business['working_hours']['friday_start'] ?? '08:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_127">
                      <span class="mx-2">-</span>
                      <label for="auto_128" class="sr-only">Bitiş</label>
                      <input type="time" name="friday_end" value="<?php echo htmlspecialchars(($business['working_hours']['friday']['end'] ?? $business['working_hours']['friday_end'] ?? '20:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_128">
                    </div>

                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Cumartesi:</span>
                      <label for="auto_129" class="sr-only">Başlangıç</label>
                      <input type="time" name="saturday_start" value="<?php echo htmlspecialchars(($business['working_hours']['saturday']['start'] ?? $business['working_hours']['saturday_start'] ?? '09:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_129">
                      <span class="mx-2">-</span>
                      <label for="auto_130" class="sr-only">Bitiş</label>
                      <input type="time" name="saturday_end" value="<?php echo htmlspecialchars(($business['working_hours']['saturday']['end'] ?? $business['working_hours']['saturday_end'] ?? '18:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_130">
                    </div>

                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Pazar:</span>
                      <label for="auto_131" class="sr-only">Başlangıç</label>
                      <input type="time" name="sunday_start" value="<?php echo htmlspecialchars(($business['working_hours']['sunday']['start'] ?? $business['working_hours']['sunday_start'] ?? '09:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_131">
                      <span class="mx-2">-</span>
                      <label for="auto_132" class="sr-only">Bitiş</label>
                      <input type="time" name="sunday_end" value="<?php echo htmlspecialchars(($business['working_hours']['sunday']['end'] ?? $business['working_hours']['sunday_end'] ?? '18:00')); ?>" class="w-24 px-3 py-2 border rounded-lg" id="auto_132">
                    </div>
                  </div>
                </div>

                <!-- Postal code (mapped to DB column `postal_code`) -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Posta Kodu</label>
                  <label for="auto_133" class="sr-only">Postal Code</label>
                  <input type="text" name="postal_code" id="auto_133" value="<?php echo htmlspecialchars($business['postal_code'] ?? $_SESSION['postal_code'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <!-- Certificate Upload Section -->
                <div class="mb-6">
                  <h4 class="text-lg font-bold mb-4 text-gray-800">
                    <i class="fas fa-certificate mr-2 text-yellow-500"></i>Sertifika Yükle (İsteğe Bağlı)
                  </h4>
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Sertifika Dosyası</label>
                    <p class="text-sm text-gray-600 mb-3">İşletmenizle ilgili sertifikaları yükleyebilirsiniz (PDF, Word, veya resim formatında)</p>
                    <input type="file" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" accept=".pdf,.doc,.docx,.jpg,.png" id="certificateUpload">
                    <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Maksimum dosya boyutu: 5MB</p>
                  </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3">
                  <button type="button" onclick="toggleBusinessEdit(false)" class="w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors">
                    İptal
                  </button>
                  <button type="submit" class="w-full sm:w-auto px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>Bilgileri Güncelle</span>
                  </button>
                </div>
              </form>
          </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="section-content hidden">
            <div class="mb-8 flex justify-between items-center">
            <div>
              <h2 class="text-3xl font-bold text-gray-800 mb-2">Profil Ayarları</h2>
            </div>
            <button 
              id="editProfileBtn"
              onclick="toggleProfileEdit()"
              class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center gap-2"
            >
              <i class="fas fa-edit"></i>
              <span>Düzenle</span>
            </button>
          </div>

          <!-- VIEW MODE: Display Profile Info -->
          <div id="profileViewMode" class="bg-white rounded-2xl shadow-lg p-8">
            <div class="space-y-6">
              <p class="text-sm text-gray-600">İşletme profil bilgilerinizi yönetin</p>
              <!-- Profile Header -->
              <div class="flex items-center gap-6 pb-6 border-b border-gray-200">
                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-blue-100 bg-gray-100">
                  <img 
                    id="profileViewImage"
                    src="<?php echo htmlspecialchars($_SESSION['profile_image'] ?? '/carwash_project/frontend/images/default-avatar.svg'); ?>" 
                    alt="Profil Fotoğrafı" 
                    class="w-full h-full object-cover"
                  >
                </div>
                <div>
                  <h3 id="profileViewName" class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Kullanıcı Adı'); ?></h3>
                  <p id="profileViewEmail" class="text-gray-600 mt-1"><?php echo htmlspecialchars($_SESSION['email'] ?? 'email@example.com'); ?></p>
                </div>
              </div>

              <!-- Profile Details -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-500">İsim</label>
                  <p class="text-base text-gray-900"><?php echo htmlspecialchars($_SESSION['name'] ?? '-'); ?></p>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-500">E-posta</label>
                  <p class="text-base text-gray-900"><?php echo htmlspecialchars($_SESSION['email'] ?? '-'); ?></p>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-500">Telefon</label>
                  <p class="text-base text-gray-900"><?php echo htmlspecialchars($_SESSION['phone'] ?? '-'); ?></p>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-500">Kullanıcı Adı</label>
                  <p class="text-base text-gray-900"><?php echo htmlspecialchars($_SESSION['username'] ?? '-'); ?></p>
                </div>
                <div class="space-y-2 md:col-span-2">
                  <label class="text-sm font-semibold text-gray-500">Rol</label>
                  <?php
                    $role_raw = $_SESSION['role'] ?? 'carwash';
                    $role_display = ($role_raw === 'carwash') ? 'İşletme' : (($role_raw === 'customer') ? 'Müşteri' : ucfirst($role_raw));
                  ?>
                  <p class="text-base text-gray-900"><?php echo htmlspecialchars($role_display); ?></p>
                </div>
              </div>
            </div>
          </div>
              <div class="flex justify-end pt-6 border-t border-gray-200">
                <button id="editProfileBtnBottom" type="button" onclick="toggleProfileEdit(true)" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center gap-2">
                  <i class="fas fa-edit"></i>
                  <span>Düzenle</span>
                </button>
              </div>

          <!-- EDIT MODE: Profile Form -->
          <div id="profileEditMode" class="bg-white rounded-2xl shadow-lg p-8 hidden">
            <form id="profileForm" class="space-y-6" enctype="multipart/form-data">
              <!-- Profile Image Upload -->
              <div class="mb-6 pb-6 border-b border-gray-200">
                <h4 class="text-lg font-bold text-gray-900 mb-4">Profil Fotoğrafı</h4>
                <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                  <div class="flex-shrink-0">
                    <div class="relative w-32 h-32 rounded-full overflow-hidden border-4 border-gray-200 bg-gray-100">
                      <img 
                        id="profileEditImagePreview"
                        src="<?php echo htmlspecialchars($_SESSION['profile_image'] ?? '/carwash_project/frontend/images/default-avatar.svg'); ?>" 
                        alt="Profil Önizleme" 
                        class="w-full h-full object-cover"
                      >
                    </div>
                  </div>
                  <div class="flex-1">
                    <label for="profile_image" class="block text-sm font-bold text-gray-700 mb-2">
                      Yeni Fotoğraf Yükle
                    </label>
                    <input 
                      type="file" 
                      id="profile_image" 
                      name="profile_image" 
                      accept="image/jpeg,image/png,image/jpg,image/webp"
                      onchange="previewProfileImage(event)"
                      class="block w-full text-sm text-gray-900 border-2 border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    >
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG veya WEBP formatında. Maksimum 3MB.</p>
                  </div>
                </div>
              </div>

              <!-- Form Fields -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Display Name -->
                <div>
                  <label for="profile_display_name" class="block text-sm font-bold text-gray-700 mb-2">
                    İsim (Görünen) <span class="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    id="profile_display_name"
                    name="name"
                    value="<?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['business_name'] ?? ''); ?>"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    placeholder="Adınız"
                  >
                </div>

                <!-- Display Name (user) remains above -->

                <!-- Email -->
                <div>
                  <label for="profile_email" class="block text-sm font-bold text-gray-700 mb-2">
                    E-posta <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="email"
                    id="profile_email"
                    name="email"
                    value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    placeholder="email@example.com"
                  >
                </div>

                <!-- Phone -->
                <div>
                  <label for="profile_phone" class="block text-sm font-bold text-gray-700 mb-2">Telefon</label>
                  <input 
                    type="tel"
                    id="profile_phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    placeholder="+90 555 123 45 67"
                  >
                </div>

                <!-- Username -->
                <div>
                  <label for="profile_username" class="block text-sm font-bold text-gray-700 mb-2">
                    Kullanıcı Adı <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="text"
                    id="profile_username"
                    name="username"
                    value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    placeholder="kullanici_adi"
                  >
                </div>
              </div>

              <!-- Password Change Section -->
              <div class="pt-6 border-t border-gray-200">
                <h4 class="text-lg font-bold text-gray-900 mb-4">Şifre Değiştir</h4>
                <p class="text-sm text-gray-600 mb-4">Şifrenizi değiştirmek istemiyorsanız bu alanları boş bırakın.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Current Password -->
                  <div>
                    <label for="current_password" class="block text-sm font-bold text-gray-700 mb-2">Mevcut Şifre</label>
                    <input 
                      type="password"
                      id="current_password"
                      name="current_password"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                      placeholder="••••••••"
                    >
                  </div>

                  <!-- New Password -->
                  <div>
                    <label for="new_password" class="block text-sm font-bold text-gray-700 mb-2">Yeni Şifre</label>
                    <input 
                      type="password"
                      id="new_password"
                      name="new_password"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                      placeholder="••••••••"
                    >
                  </div>

                  <!-- Confirm Password -->
                  <div class="md:col-span-2">
                    <label for="confirm_password" class="block text-sm font-bold text-gray-700 mb-2">Yeni Şifre (Tekrar)</label>
                    <input 
                      type="password"
                      id="confirm_password"
                      name="confirm_password"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                      placeholder="••••••••"
                    >
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
                <button 
                  type="button"
                  onclick="toggleProfileEdit()"
                  class="w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors"
                >
                  İptal
                </button>
                <button 
                  type="submit"
                  class="w-full sm:w-auto px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center justify-center gap-2"
                >
                  <i class="fas fa-save"></i>
                  <span>Kaydet</span>
                </button>
              </div>
            </form>
          </div>
        </section>

        <!-- Settings -->
        <section id="settings" class="section-content hidden">
          <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Ayarlar</h2>
            <p class="text-gray-600">Sistem ayarlarınızı yönetin</p>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
              <h3 class="text-xl font-bold mb-6">Sistem Ayarları</h3>
              <div class="space-y-4">
                <label class="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <h4 class="font-bold">Otomatik Fatural<label for="auto_138" class="sr-only">Input</label><input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_138">n sonra otomatik fatura oluştur</p>
                  </div>
                  <label for="auto_139" class="sr-only">Input</label><input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_139">
                </label>

                <label class="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <h4 class<label for="auto_140" class="sr-only">Input</label><input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_140">Müşterilere SMS ile hatırlatma gönder</p>
                  </div>
                  <label for="auto_141" class="sr-only">Input</label><input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_141">
                </label>

                <label class="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <h4 cl<label for="auto_142" class="sr-only">Input</label><input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_142">y-600">Rezervasyon onayları için e-posta gönder</p>
                  </div>
                  <label for="auto_143" class="sr-only">Input</label><input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_143">
                </label>

                <label class="flex items-center justify-between p-4 border rounded-lg">
                  <div>
    <label for="auto_144" class="sr-only">Input</label><input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_144">  <p class="text-sm text-gray-600">Verileri günlük olarak yedekle</p>
                  </div>
                  <label for="auto_145" class="sr-only">Input</label><input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_145">
                </label>

                <div class="pt-4 border-t">
                  <h4 class="font-bold mb-4">Veri Yönetimi</h4>
                  <div class="space-y-2">
                    <button class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                      <i class="fas fa-download mr-2"></i>Verileri Dışa Aktar
                    </button>
                    <button class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                      <i class="fas fa-upload mr-2"></i>Verileri İçe Aktar
                    </button>
                    <button class="w-full text-left p-3 border rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                      <i class="fas fa-trash mr-2"></i>Tüm Verileri Sil
                    </button>
                  </div>
                </div>
              </div>
          </div>
        </section>
      </main>

    <!-- Notification Panel -->
    <!-- Farsça: پنل اعلان‌ها. -->
    <!-- Türkçe: Bildirim Paneli. -->
    <!-- English: Notification Panel. -->
    <div id="notificationPanel" class="fixed right-4 w-80 bg-white rounded-2xl shadow-2xl hidden" style="top: calc(var(--header-height) + 1rem); z-index:1250;">
      <div class="p-4 border-b">
        <div class="flex justify-between items-center">
          <h3 class="font-bold">Bildirimler</h3>
          <button onclick="closeNotifications()" class="text-gray-400 hover:text-gray-600" title="Bildirimleri kapat" aria-label="Bildirimleri kapat">
            <i class="fas fa-times" aria-hidden="true"></i>
            <span class="sr-only">Bildirimleri kapat</span>
          </button>
        </div>
      </div>
      <div class="max-h-96 overflow-y-auto">
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Yeni rezervasyon alındı - Premium paket</p>
          <p class="text-xs text-gray-500">5 dakika önce</p>
        </div>
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Ödeme tamamlandı - ₺200</p>
          <p class="text-xs text-gray-500">15 dakika önce</p>
        </div>
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Müşteri yorumu - 5 yıldız</p>
          <p class="text-xs text-gray-500">1 ساعت önce</p>
        </div>
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Stok uyarısı - Şampuan azaldı</p>
          <p class="text-xs text-gray-500">2 saat önce</p>
        </div>
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Personel bildirimi - Ali Yılmaz izin istedi</p>
          <p class="text-xs text-gray-500">3 saat önce</p>
        </div>
      </div>
    </div>

    <!-- Farsça: مودال رزرو دستی. -->
    <!-- Türkçe: Manuel Rezervasyon Modalı. -->
    <!-- English: Manual Reservation Modal. -->
    <div id="manualReservationModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
      <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Müşteri Bilgileri</h3>
        <div class="space-y-4">
          <div>
            <label for="auto_146" class="sr-only">Müşteri adını girin</label>
            <input type="text" placeholder="Müşteri adını girin" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_146">
          </div>
          <div>
            <label for="auto_147" class="sr-only">Müşteri telefonu</label>
            <input type="text" placeholder="Müşteri telefonu" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_147">
          </div>
          <div>
            <label for="auto_148" class="sr-only">Alternatif telefon</label>
            <input type="tel" placeholder="05XX XXX XX XX" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_148">
          </div>
        </div>
      </div>
          <div>
            <label for="manualServiceSelect" class="block text-sm font-bold text-gray-700 mb-2">Hizmet Seçin</label>
            <select id="manualServiceSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" title="Hizmet seçin" aria-label="Hizmet seçin">
              <option>Dış Yıkama + İç Temizlik</option>
              <option>Tam Detaylandırma</option>
            </select>

            <div class="mt-4">
              <label class="block text-sm font-bold text-gray-700 mb-2">Araç Plakası</label>
              <label for="auto_149" class="sr-only">Araç Plakası</label>
              <input type="text" placeholder="34 ABC 123" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_149">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Tarih</label>
                <label for="auto_151" class="sr-only">Tarih</label>
                <input type="date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_151">
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Saat</label>
                <label for="auto_152" class="sr-only">Saat</label>
                <input type="time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_152">
              </div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Notlar (İsteğe Bağlı)</label>
            <label for="auto_153" class="sr-only">Input</label>
            <textarea rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" id="auto_153"></textarea>
          </div>
          <div class="flex space-x-3">
            <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Rezervasyon Oluştur</button>
            <button type="button" onclick="closeManualReservationModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Farsça: مودال افزودن مشتری. -->
    <!-- Türkçe: Müşteri Ekle Modalı. -->
    <!-- English: Customer Add Modal. -->
    <div id="customerModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Yeni Müşteri Ekle</h3>
        <form id="customerForm" class="space-y-4">
          <div>
            <label for="auto_154" class="sr-only">Müşteri Adı Soyadı</label>
            <input type="text" id="auto_154" placeholder="Müşteri Adı Soyadı" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label for="auto_155" class="sr-only">E-posta</label>
            <input type="email" id="auto_155" placeholder="email@example.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label for="auto_156" class="sr-only">Telefon</label>
            <input type="tel" id="auto_156" placeholder="05XX XXX XX XX" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label for="auto_158" class="sr-only">Adres</label>
            <textarea id="auto_158" rows="2" placeholder="Müşteri Adresi" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
          </div>
          <div class="flex space-x-3">
            <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Müşteri Ekle</button>
            <button type="button" onclick="closeCustomerModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Farsça: مودال خدمات. -->
    <!-- Türkçe: Hizmet Modalı. -->
    <!-- English: Service Modal. -->
    <div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Yeni Hizmet Ekle</h3>
        <form id="serviceForm" class="space-y-4">
          <div>
            <label for="auto_160" class="sr-only">Hizmet Adı</label>
            <input type="text" id="auto_160" placeholder="Hizmet Adı" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label for="auto_161" class="sr-only">Hizmet Açıklaması</label>
            <textarea id="auto_161" rows="3" placeholder="Hizmet açıklaması" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="auto_162" class="sr-only">Süre (dk)</label>
              <input type="number" id="auto_162" placeholder="150" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="auto_163" class="sr-only">Fiyat (₺)</label>
              <input type="number" id="auto_163" placeholder="150" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
          </div>
          <div class="flex space-x-3">
            <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Ekle</button>
            <button type="button" onclick="closeServiceModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Farsça: مودال پرسنل. -->
    <!-- Türkçe: Personel Modalı. -->
    <!-- English: Staff Modal. -->
    <div id="staffModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Personel Ekle</h3>
        <form id="staffForm" class="space-y-4">
          <div>
            <label for="auto_165" class="sr-only">Ad Soyad</label>
            <input type="text" id="auto_165" placeholder="Ad Soyad" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label for="staffPositionSelect" class="block text-sm font-bold text-gray-700 mb-2">Pozisyon</label>
            <select id="staffPositionSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" title="Pozisyon seçin" aria-label="Pozisyon seçin">
              <option>Teknisyen</option>
              <option>Senior Teknisyen</option>
              <option>Çırak</option>
              <option>Resepsiyonist</option>
              <option>Yönetici</option>
            </select>
          </div>
          <div>
            <label for="auto_166" class="sr-only">Telefon</label>
            <input type="tel" id="auto_166" placeholder="0555 123 4567" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label for="auto_167" class="sr-only">E-posta</label>
            <input type="email" id="auto_167" placeholder="email@domain.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div class="flex space-x-3">
            <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Ekle</button>
            <button type="button" onclick="closeStaffModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
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

      // Farsça: تابع برای نمایش بخش‌های مختلف داشبورد.
      // Türkçe: Kontrol panelinin farklı bölümlerini göstermek için fonksiyon.
      // English: Function to show different sections of the dashboard.
      function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.section-content').forEach(section => {
          section.classList.add('hidden');
        });

        // Show selected section
        document.getElementById(sectionId).classList.remove('hidden');

        // Update sidebar active state for both mobile and desktop
        document.querySelectorAll('aside a').forEach(link => {
          link.classList.remove('bg-white', 'bg-opacity-20');
          if (link.getAttribute('href') === '#' + sectionId) {
            link.classList.add('bg-white', 'bg-opacity-20');
          }
        });

        // Close mobile sidebar after selection
        if (window.innerWidth < 1024) {
          closeMobileSidebar();
        }
      }

      // Farsça: بارگذاری اولیه: نمایش داشبورد.
      // Türkçe: İlk yükleme: kontrol panelini göster.
      // English: Initial load: show dashboard.
      document.addEventListener('DOMContentLoaded', () => {
        showSection('dashboard');
        // Set initial toggle state based on localStorage or default
        // Farsça: وضعیت اولیه سوئیچ را بر اساس localStorage یا پیش‌فرض تنظیم کنید.
        // Türkçe: Başlangıçtaki geçiş durumunu localStorage veya varsayılan değere göre ayarla.
        // English: Set initial toggle state based on localStorage or default.
        const status = localStorage.getItem('workplaceStatus');
        const toggle = document.getElementById('workplaceStatus');
        if (status === 'off') {
          toggle.checked = false;
        } else {
          toggle.checked = true; // Default to On
        }
        toggleWorkplaceStatus(); // Apply initial styling
      });

      // Farsça: توابع پنل اعلان.
      // Türkçe: Bildirim Paneli fonksiyonları.
      // English: Notification Panel functions.
      function toggleNotifications() {
        const panel = document.getElementById('notificationPanel');
        panel.classList.toggle('hidden');
      }

      function closeNotifications() {
        document.getElementById('notificationPanel').classList.add('hidden');
      }

      // Farsça: توابع مودال خدمات.
      // Türkçe: Hizmet Modalı fonksiyonları.
      // English: Service Modal functions.
      function openServiceModal() {
        document.getElementById('serviceModal').classList.remove('hidden');
      }

      function closeServiceModal() {
        document.getElementById('serviceModal').classList.add('hidden');
      }

      // Farsça: توابع مودال پرسنل.
      // Türkçe: Personel Modalı fonksiyonları.
      // English: Staff Modal functions.
      function openStaffModal() {
        document.getElementById('staffModal').classList.remove('hidden');
      }

      function closeStaffModal() {
        document.getElementById('staffModal').classList.add('hidden');
      }

      // Farsça: تابع تغییر وضعیت محل کار.
      // Türkçe: İşyeri Durumu Geçiş Fonksiyonu.
      // English: Workplace Status Toggle Function.
      function toggleWorkplaceStatus() {
        const toggle = document.getElementById('workplaceStatus');
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');
        const toggleLabel = document.getElementById('toggleLabel');
        
        if (toggle.checked) {
          localStorage.setItem('workplaceStatus', 'on');
          if (statusIndicator) {
            statusIndicator.className = 'status-indicator status-open';
            statusText.textContent = 'Açık';
          }
          if (toggleLabel) {
            toggleLabel.textContent = 'İşletme Açık';
          }
          console.log('Workplace is now OPEN (Green)');
        } else {
          localStorage.setItem('workplaceStatus', 'off');
          if (statusIndicator) {
            statusIndicator.className = 'status-indicator status-closed';
            statusText.textContent = 'Kapalı';
          }
          if (toggleLabel) {
            toggleLabel.textContent = 'İşletme Kapalı';
          }
          console.log('Workplace is now CLOSED (Red)');
        }
      }

      // Farsça: توابع مودال رزرو دستی.
      // Türkçe: Manuel Rezervasyon Modalı fonksiyonları.
      // English: Manual Reservation Modal functions.
      function openManualReservationModal() {
        document.getElementById('manualReservationModal').classList.remove('hidden');
      }

      function closeManualReservationModal() {
        document.getElementById('manualReservationModal').classList.add('hidden');
      }

      // Farsça: توابع مودال افزودن مشتری.
      // Türkçe: Müşteri Ekle Modalı fonksiyonları.
      // English: Customer Add Modal functions.
      function openCustomerModal() {
        const modal = document.getElementById('customerModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }

      function closeCustomerModal() {
        const modal = document.getElementById('customerModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }

      // Profile Section Functions
      function toggleProfileEdit() {
        const viewMode = document.getElementById('profileViewMode');
        const editMode = document.getElementById('profileEditMode');
        const editBtn = document.getElementById('editProfileBtn');
        
        if (editMode.classList.contains('hidden')) {
          // Switch to edit mode
          viewMode.classList.add('hidden');
          editMode.classList.remove('hidden');
          editBtn.style.display = 'none';
        } else {
          // Switch to view mode
          editMode.classList.add('hidden');
          viewMode.classList.remove('hidden');
          editBtn.style.display = 'inline-flex';
        }
      }

      // Business Section Functions
      function toggleBusinessEdit(openEdit = false) {
        const viewMode = document.getElementById('businessViewMode');
        const editForm = document.getElementById('businessInfoForm');
        const editBtn = document.getElementById('editBusinessBtn');
        if (!viewMode || !editForm) return;

        if (openEdit) {
          // show form and load authoritative values from server
          fetch('/carwash_project/backend/api/get_business_info.php')
            .then(r => r.json())
            .then(bres => {
              if (bres && bres.success === true && bres.data) {
                const b = bres.data;
                // Populate form fields if present
                const byId = (id) => document.getElementById(id);
                if (byId('auto_114')) byId('auto_114').value = b.business_name || '';
                if (byId('auto_115')) byId('auto_115').value = b.address || '';
                if (byId('auto_116')) byId('auto_116').value = b.phone || b.contact_phone || '';
                if (byId('auto_117')) byId('auto_117').value = b.mobile_phone || '';
                if (byId('auto_118')) byId('auto_118').value = b.email || b.contact_email || '';
                if (byId('auto_133')) byId('auto_133').value = b.postal_code || '';
                // Working hours may be an object
                const wh = b.working_hours || {};
                const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                days.forEach(day => {
                  const startEl = byId('auto_' + ({'monday':119,'tuesday':121,'wednesday':123,'thursday':125,'friday':127,'saturday':129,'sunday':131}[day]));
                  const endEl = byId('auto_' + ({'monday':120,'tuesday':122,'wednesday':124,'thursday':126,'friday':128,'saturday':130,'sunday':132}[day]));
                  if (startEl) startEl.value = (wh[day] && wh[day].start) ? wh[day].start : (wh[day + '_start'] || startEl.value);
                  if (endEl) endEl.value = (wh[day] && wh[day].end) ? wh[day].end : (wh[day + '_end'] || endEl.value);
                });
                // Logo preview
                if (b.logo_path) {
                  const cur = document.getElementById('currentLogo');
                  if (cur) cur.src = b.logo_path;
                }
              }
            })
            .catch(e => console.warn('Failed to load business info for edit mode', e))
            .finally(() => {
              viewMode.classList.add('hidden');
              editForm.classList.remove('hidden');
              if (editBtn) editBtn.style.display = 'none';
            });
        } else {
          // show view
          editForm.classList.add('hidden');
          viewMode.classList.remove('hidden');
          if (editBtn) editBtn.style.display = '';
        }
      }

      function previewProfileImage(event) {
        const file = event.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
            document.getElementById('profileEditImagePreview').src = e.target.result;
          };
          reader.readAsDataURL(file);
        }
      }

      // Farsça: تنظیمات - پیش‌نمایش بارگذاری لوگو.
      // Türkçe: Ayarlar - Logo Yükleme Önizlemesi.
      // English: Settings - Logo Upload Preview.
      function previewLogo(event) {
        const reader = new FileReader();
        reader.onload = function() {
          // Show preview in the form
          const output = document.getElementById('currentLogo');
          output.src = reader.result;
          
          // Update ONLY sidebar logos (not header logo)
          document.querySelectorAll('#mobileSidebarLogo, #desktopSidebarLogo').forEach(function(img) {
            img.src = reader.result;
          });
          
          // Header logo stays as MyCar logo (fixed branding)
        };
        reader.readAsDataURL(event.target.files[0]);
      }

      // Business Info Form Submit Handler
      document.addEventListener('DOMContentLoaded', function() {
        const businessForm = document.getElementById('businessInfoForm');
        
        if (businessForm) {
          businessForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            const logoFile = document.getElementById('logoUpload').files[0];
            const businessName = document.getElementById('auto_114').value;
            
            // Add logo file if selected
            if (logoFile) {
              formData.append('logo', logoFile);
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Kaydediliyor...';
            
            // Send to backend API
            fetch('/carwash_project/backend/api/update_business_info.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success === true) {
                // Update business name in sidebars
                document.getElementById('mobileSidebarBusinessName').textContent = businessName;
                document.getElementById('desktopSidebarBusinessName').textContent = businessName;
                
                // Update ONLY sidebar logos if a new logo was uploaded
                if (data.data && data.data.logo_path) {
                  // Append a timestamp to bust browser cache after upload
                  const ts = Date.now();
                  const logoUrl = data.data.logo_path + (data.data.logo_path.indexOf('?') === -1 ? ('?ts=' + ts) : ('&ts=' + ts));
                  document.querySelectorAll('#mobileSidebarLogo, #desktopSidebarLogo').forEach(function(img) {
                    img.src = logoUrl;
                  });
                }

                // Reload the authoritative business data from the server and update the VIEW section
                fetch('/carwash_project/backend/api/get_business_info.php')
                  .then(r => r.json())
                  .then(bres => {
                    if (bres && bres.success === true && bres.data) {
                      const b = bres.data;
                      // Update sidebars with server-returned name if present
                      if (b.business_name) {
                        const name = b.business_name;
                        const mobileName = document.getElementById('mobileSidebarBusinessName');
                        const desktopName = document.getElementById('desktopSidebarBusinessName');
                        if (mobileName) mobileName.textContent = name;
                        if (desktopName) desktopName.textContent = name;
                      }

                      // Update view elements
                      const viewNameEl = document.getElementById('businessViewName');
                      if (viewNameEl && b.business_name) viewNameEl.textContent = b.business_name;
                      const viewEmailEl = document.getElementById('businessViewEmail');
                      if (viewEmailEl && b.email) viewEmailEl.textContent = b.email;
                      const viewPhoneEl = document.getElementById('businessViewPhone');
                      if (viewPhoneEl && (b.phone || b.contact_phone)) viewPhoneEl.textContent = b.phone || b.contact_phone;
                      const viewMobileEl = document.getElementById('businessViewMobile');
                      if (viewMobileEl && b.mobile_phone) viewMobileEl.textContent = b.mobile_phone;
                      const viewAddressEl = document.getElementById('businessViewAddress');
                      if (viewAddressEl && b.address) viewAddressEl.textContent = b.address;
                      const viewPostalEl = document.getElementById('businessViewPostal');
                      if (viewPostalEl && b.postal_code) viewPostalEl.textContent = b.postal_code;
                      if (b.logo_path) {
                        const viewLogo = document.getElementById('businessViewLogo');
                        if (viewLogo) viewLogo.src = b.logo_path;
                      }
                    }
                  })
                  .catch(err => console.warn('Failed to reload business info', err));

                // Switch back to view mode
                toggleBusinessEdit(false);

                showNotification(data.message || 'İşletme bilgileri başarıyla güncellendi!', 'success');
              } else {
                // Ensure error messages start with 'Error:' per UI requirement
                let errMsg = data.message || 'Bir hata oluştu';
                if (!/^Error[:\s]/i.test(errMsg)) errMsg = 'Error: ' + errMsg;
                showNotification(errMsg, 'error');
              }
              
              // Reset button
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalBtnText;
            })
            .catch(error => {
              console.error('API Error:', error);
              showNotification('Error: Bağlantı hatası. Lütfen tekrar deneyin.', 'error');
              
              // Reset button
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalBtnText;
            });
          });
        }
      });

      // Profile Form Submit Handler
      document.addEventListener('DOMContentLoaded', function() {
        const profileForm = document.getElementById('profileForm');
        
        if (profileForm) {
          profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate password fields if any are filled
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword || confirmPassword) {
              if (!currentPassword) {
                showNotification('Yeni şifre belirlemek için mevcut şifrenizi girmelisiniz', 'error');
                return;
              }
              if (newPassword !== confirmPassword) {
                showNotification('Yeni şifreler eşleşmiyor', 'error');
                return;
              }
              if (newPassword.length < 6) {
                showNotification('Yeni şifre en az 6 karakter olmalıdır', 'error');
                return;
              }
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Kaydediliyor...';
            
            // Send to backend API
            fetch('/carwash_project/backend/api/update_profile.php', {
              method: 'POST',
              body: formData
            })
            .then(res => res.json())
            .then(data => {
              if (data.success === true) {
                showNotification(data.message || 'Profil başarıyla güncellendi!', 'success');
                // Update header name and avatar (and mobile header) and explicitly close edit mode
                try {
                  if (data.data && data.data.name) {
                    const headerNameEl = document.getElementById('headerUserNameDisplay');
                    if (headerNameEl) headerNameEl.textContent = data.data.name;
                    const mobileNameEl = document.getElementById('mobileMenuUserName');
                    if (mobileNameEl) mobileNameEl.textContent = data.data.name;
                  }
                  if (data.data && data.data.profile_image) {
                    // Append timestamp to bust caches and keep both headers in sync via localStorage
                    const ts = Date.now();
                    const imageUrl = data.data.profile_image + (data.data.profile_image.indexOf('?') === -1 ? ('?ts=' + ts) : ('&ts=' + ts));

                    const avatarEl = document.getElementById('userAvatarTop');
                    if (avatarEl) {
                      avatarEl.src = imageUrl;
                      avatarEl.style.display = '';
                    }
                    const mobileAvatar = document.getElementById('mobileMenuAvatar');
                    if (mobileAvatar) mobileAvatar.src = imageUrl;

                    try {
                      localStorage.setItem('carwash_profile_image', imageUrl);
                      localStorage.setItem('carwash_profile_image_ts', ts.toString());
                    } catch (e) {
                      // ignore storage errors (e.g., private mode)
                    }
                  }
                } catch (e) {
                  console.warn('Failed to update header after profile save', e);
                }

                // Ensure edit mode is closed and view mode shown
                const editMode = document.getElementById('profileEditMode');
                const viewMode = document.getElementById('profileViewMode');
                const editBtn = document.getElementById('editProfileBtn');
                if (editMode && viewMode) {
                  editMode.classList.add('hidden');
                  viewMode.classList.remove('hidden');
                  if (editBtn) editBtn.style.display = 'inline-flex';
                }
                // Update view mode values (user fields)
                const displayName = document.getElementById('profile_display_name').value;
                const email = document.getElementById('profile_email').value;
                const viewName = document.getElementById('profileViewName');
                if (viewName) viewName.textContent = (data.data && data.data.name) ? data.data.name : displayName;
                const viewEmail = document.getElementById('profileViewEmail');
                if (viewEmail) viewEmail.textContent = (data.data && data.data.email) ? data.data.email : email;
                const viewImage = document.getElementById('profileViewImage');
                if (viewImage) {
                  if (data.data && data.data.profile_image) {
                    viewImage.src = data.data.profile_image;
                  } else {
                    const preview = document.getElementById('profileEditImagePreview');
                    if (preview) viewImage.src = preview.src;
                  }
                }
              } else {
                let errMsg = data.message || 'Profil güncellenemedi';
                if (!/^Error[:\s]/i.test(errMsg)) errMsg = 'Error: ' + errMsg;
                showNotification(errMsg, 'error');
              }

              submitBtn.disabled = false;
              submitBtn.innerHTML = originalBtnText;
            })
            .catch(err => {
              console.error(err);
              showNotification('Error: Sunucu hatası oluştu. Lütfen tekrar deneyin.', 'error');
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalBtnText;
            });
          });
        }
      });
      
      // Notification System
      function showNotification(message, type = 'success') {
        // Remove existing notification if any
        const existingNotification = document.getElementById('notification');
        if (existingNotification) {
          existingNotification.remove();
        }
        
          // Create notification element and position it below the header
          const notification = document.createElement('div');
          notification.id = 'notification';
          notification.className = 'fixed right-4 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full';
          // Position under header using CSS var --header-height and ensure it appears above header
          notification.style.top = 'calc(var(--header-height) + 1rem)';
          notification.style.zIndex = '1250';

          // Set style based on type
          if (type === 'success') {
            notification.classList.add('bg-green-500', 'text-white');
            notification.innerHTML = `
              <div class="flex items-center space-x-3">
                <i class="fas fa-check-circle text-2xl"></i>
                <div>
                  <p class="font-bold">Başarılı!</p>
                  <p class="text-sm">${message}</p>
                </div>
              </div>
            `;
          } else if (type === 'error') {
            notification.classList.add('bg-red-500', 'text-white');
            notification.innerHTML = `
              <div class="flex items-center space-x-3">
                <i class="fas fa-exclamation-circle text-2xl"></i>
                <div>
                  <p class="font-bold">Hata!</p>
                  <p class="text-sm">${message}</p>
                </div>
              </div>
            `;
          }

          // Add to document
          document.body.appendChild(notification);

          // Animate in
          setTimeout(() => {
            notification.classList.remove('translate-x-full');
          }, 10);

          // Auto remove after 4 seconds
          setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
              notification.remove();
            }, 300);
          }, 4000);
      }

      // Handle window resize for responsive behavior
      window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
          // Desktop view - close mobile sidebar if open
          closeMobileSidebar();
        }
      });

      // Close modals when clicking outside
      window.onclick = function(event) {
        const serviceModal = document.getElementById('serviceModal');
        const staffModal = document.getElementById('staffModal');
        const manualReservationModal = document.getElementById('manualReservationModal');
        const customerModal = document.getElementById('customerModal');

        if (event.target == serviceModal) {
          serviceModal.classList.add('hidden');
        }
        if (event.target == staffModal) {
          staffModal.classList.add('hidden');
        }
        if (event.target == manualReservationModal) {
          manualReservationModal.classList.add('hidden');
        }
        if (event.target == customerModal) {
          customerModal.classList.add('hidden');
        }
      }
    </script>
</div> <!-- End Dashboard Layout -->

<?php 
// Include the universal footer
include '../includes/footer.php'; 
?>
