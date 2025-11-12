<?php
// CarWash - Main Page
// Set page-specific variables for the index header
$page_title = 'CarWash - En İyi Online Araç Yıkama Rezervasyon Platformu';
$current_page = 'home';
// Start session and ensure CSRF token exists (idempotent)
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
if (empty($_SESSION['csrf_token'])) {
  try {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  } catch (Exception $e) {
    // Fallback when random_bytes is not available
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
  }
}

// Handle contact form submission (POST) securely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__form']) && $_POST['__form'] === 'contact') {
  // Merge JSON body if necessary (idempotent)
  $raw = @file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (is_array($data)) {
    foreach ($data as $k => $v) {
      if (!isset($_POST[$k])) $_POST[$k] = $v;
    }
  }

  $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    $_SESSION['flash_error'] = 'CSRF doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }

  // Sanitize inputs
  $name = trim($_POST['contactName'] ?? '');
  $email = trim($_POST['contactEmail'] ?? '');
  $subject = trim($_POST['contactSubject'] ?? '');
  $message = trim($_POST['contactMessage'] ?? '');

  $errors = [];
  if ($name === '') $errors[] = 'Adınızı girin.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta adresi girin.';
  if ($subject === '') $errors[] = 'Konu girin.';
  if ($message === '') $errors[] = 'Mesajınızı yazın.';

  if (!empty($errors)) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }

  // At this point you would typically send an email or save to DB.
  // For now we store a success flash message.
  $_SESSION['flash_success'] = 'Mesajınız alındı. En kısa sürede sizinle iletişime geçeceğiz.';
  // Redirect to avoid form resubmission
  header('Location: ' . $_SERVER['REQUEST_URI']);
  exit;
}

// Include the specialized index header
include 'includes/index-header.php';
?>

<!-- Page-specific styles -->
<style>
    /* Animation for elements to fade in from bottom to top */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Animation for elements to slide in from left to right */
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-50px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* Animation for pulsing effect */
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    /* Applies the fadeInUp animation */
    .animate-fade-in-up {
      animation: fadeInUp 0.8s ease-out forwards;
    }

    /* Applies the slideIn animation */
    .animate-slide-in {
      animation: slideIn 0.6s ease-out forwards;
    }

    /* Applies the pulse animation with slow speed */
    .animate-pulse-slow {
      animation: pulse 3s ease-in-out infinite;
    }

    /* Gradient background for the hero section */
    .hero-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Hover style for cards */
    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    /* Gradient for text */
    .text-gradient {
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Enhanced responsive utilities */
    @media (max-width: 640px) {
      .hero-gradient {
        min-height: 100vh;
      }
      
      .card-hover:hover {
        transform: none;
      }
      
      .text-gradient {
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }
    }

    @media (min-width: 641px) and (max-width: 768px) {
      .hero-gradient {
        min-height: 90vh;
      }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
      .hero-gradient {
        min-height: 85vh;
      }
    }

    /* Touch-friendly interaction for mobile */
    @media (hover: none) and (pointer: coarse) {
      .card-hover:hover {
        transform: none;
      }
      
      .card-hover:active {
        transform: scale(0.98);
      }
    }
</style>

<!-- Hero Section -->
  <!-- Farsça: بخش قهرمان (Hero Section). -->
  <!-- Türkçe: Kahraman Bölümü. -->
  <!-- English: Hero Section. -->
  <section id="home" class="hero-gradient text-white relative overflow-hidden min-h-screen">
    <div class="absolute inset-0 bg-black bg-opacity-20"></div>
    <div class="container mx-auto px-4 py-12 sm:py-16 md:py-20 lg:py-24 relative z-10">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-10 lg:gap-12 items-center min-h-[calc(100vh-12rem)] sm:min-h-[calc(100vh-8rem)]">
        <div class="animate-fade-in-up text-center lg:text-left order-2 lg:order-1">
          <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold mb-3 sm:mb-4 md:mb-5 lg:mb-6 leading-tight">
            Online Araç Yıkama
            <span class="text-gradient block sm:inline">Rezervasyonu</span>
          </h2>
          <p class="text-base sm:text-lg md:text-xl mb-4 sm:mb-6 md:mb-7 lg:mb-8 text-gray-200 leading-relaxed max-w-xl mx-auto lg:mx-0">
            Hızlı • Güvenilir • Profesyonel<br>
            Yakınınızdaki En İyi Araç Yıkama Hizmetleri
          </p>
          <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center lg:justify-start max-w-lg mx-auto lg:mx-0">
            <a href="auth/Customer_Registration.php" class="bg-white text-blue-600 px-4 sm:px-6 md:px-8 py-3 sm:py-3 md:py-4 rounded-lg font-bold hover:bg-gray-100 transition-all text-center animate-pulse-slow text-sm sm:text-base" title="Müşteri Olarak Kayıt Ol" aria-label="Müşteri Olarak Kayıt Ol">
              <i class="fas fa-user-plus mr-2"></i>
              <span class="hidden sm:inline">Müşteri Olarak</span> Kayıt Ol
            </a>
            <a href="auth/Car_Wash_Registration.php" class="border-2 border-white text-white px-4 sm:px-6 md:px-8 py-3 sm:py-3 md:py-4 rounded-lg font-bold hover:bg-white hover:text-blue-600 transition-all text-center text-sm sm:text-base" title="Hizmet Sağlayıcı Olarak Kayıt Ol" aria-label="Hizmet Sağlayıcı Olarak Kayıt Ol">
              <i class="fas fa-store mr-2"></i>
              <span class="hidden sm:inline">Hizmet Sağlayıcı Olarak</span> Kayıt Ol
            </a>
          </div>
        </div>

        <div class="animate-slide-in order-1 lg:order-2">
          <div class="bg-white rounded-2xl p-4 sm:p-6 md:p-8 shadow-2xl max-w-md mx-auto lg:max-w-none">
            <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800 mb-3 sm:mb-4 md:mb-6 text-center">Neden CarWash?</h3>
            <div class="space-y-2 sm:space-y-3 md:space-y-4">
              <div class="flex items-center">
                <i class="fas fa-clock text-blue-600 text-lg sm:text-xl md:text-2xl mr-2 sm:mr-3 md:mr-4 flex-shrink-0"></i>
                <div>
                  <h4 class="font-bold text-gray-800 text-sm sm:text-base">Yüksek Hız</h4>
                  <p class="text-gray-600 text-xs sm:text-sm">2 Dakikadan Az Sürede Rezervasyon</p>
                </div>
              </div>
              <div class="flex items-center">
                <i class="fas fa-shield-alt text-blue-600 text-lg sm:text-xl md:text-2xl mr-2 sm:mr-3 md:mr-4 flex-shrink-0"></i>
                <div>
                  <h4 class="font-bold text-gray-800 text-sm sm:text-base">Tam Güvenlik</h4>
                  <p class="text-gray-600 text-xs sm:text-sm">Güvenli ve Garantili Ödeme</p>
                </div>
              </div>
              <div class="flex items-center">
                <i class="fas fa-star text-blue-600 text-lg sm:text-xl md:text-2xl mr-2 sm:mr-3 md:mr-4 flex-shrink-0"></i>
                <div>
                  <h4 class="font-bold text-gray-800 text-sm sm:text-base">Üstün Kalite</h4>
                  <p class="text-gray-600 text-xs sm:text-sm">Şehrin En İyi Uzmanları</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Decorative elements -->
    <!-- Farsça: عناصر تزئینی. -->
    <!-- Türkçe: Dekoratif öğeler. -->
    <!-- English: Decorative elements. -->
    <div class="absolute top-16 sm:top-20 left-4 sm:left-10 text-white opacity-10 animate-pulse">
      <i class="fas fa-car text-3xl sm:text-4xl md:text-5xl lg:text-6xl"></i>
    </div>
    <div class="absolute bottom-16 sm:bottom-20 right-4 sm:right-10 text-white opacity-10 animate-pulse">
      <i class="fas fa-water text-3xl sm:text-4xl md:text-5xl lg:text-6xl"></i>
    </div>
  </section>

  <!-- Statistics Section -->
  <!-- Farsça: بخش آمار. -->
  <!-- Türkçe: İstatistik Bölümü. -->
  <!-- English: Statistics Section. -->
  <section class="py-12 sm:py-16 bg-white">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 xs:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 md:gap-8 text-center">
        <div class="animate-fade-in-up bg-gray-50 p-4 sm:p-6 rounded-xl">
          <div class="text-2xl sm:text-3xl md:text-3xl lg:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">1000+</div>
          <div class="text-xs sm:text-sm md:text-sm lg:text-base text-gray-600">Memnun Müşteri</div>
        </div>
        <div class="animate-fade-in-up bg-gray-50 p-4 sm:p-6 rounded-xl" style="animation-delay: 0.2s">
          <div class="text-2xl sm:text-3xl md:text-3xl lg:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">500+</div>
          <div class="text-xs sm:text-sm md:text-sm lg:text-base text-gray-600">Aktif Hizmet Sağlayıcı</div>
        </div>
        <div class="animate-fade-in-up bg-gray-50 p-4 sm:p-6 rounded-xl" style="animation-delay: 0.4s">
          <div class="text-2xl sm:text-3xl md:text-3xl lg:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">10000+</div>
          <div class="text-xs sm:text-sm md:text-sm lg:text-base text-gray-600">Tamamlanan Hizmet</div>
        </div>
        <div class="animate-fade-in-up bg-gray-50 p-4 sm:p-6 rounded-xl" style="animation-delay: 0.6s">
          <div class="text-2xl sm:text-3xl md:text-3xl lg:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">4.9★</div>
          <div class="text-xs sm:text-sm md:text-sm lg:text-base text-gray-600">Müşteri Memnuniyeti</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <!-- Farsça: بخش خدمات. -->
  <!-- Türkçe: Hizmetler Bölümü. -->
  <!-- English: Services Section. -->
  <section id="services" class="py-16 sm:py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-10 sm:mb-12 lg:mb-16">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 mb-2 sm:mb-3 md:mb-4">Hizmetlerimiz</h2>
        <p class="text-base sm:text-lg md:text-xl text-gray-600 px-4">En İyi Kalitede Çeşitli Araç Yıkama Hizmetleri</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
        <div class="bg-white rounded-2xl p-4 sm:p-6 lg:p-8 card-hover shadow-lg h-full flex flex-col">
          <div class="w-10 h-10 sm:w-12 sm:h-12 md:w-16 md:h-16 bg-blue-100 rounded-full flex items-center justify-center mb-3 sm:mb-4 md:mb-6 mx-auto sm:mx-0">
            <i class="fas fa-car text-lg sm:text-xl md:text-2xl text-blue-600"></i>
          </div>
          <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800 mb-2 sm:mb-3 md:mb-4 text-center sm:text-left">Dış Yıkama</h3>
          <p class="text-gray-600 mb-3 sm:mb-4 md:mb-6 text-sm sm:text-base flex-grow text-center sm:text-left leading-relaxed">Modern Ekipman ve En İyi Temizlik Malzemeleri ile Tam Gövde Yıkama</p>
          <div class="text-xl sm:text-2xl font-bold text-blue-600 text-center sm:text-left">₺50</div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-6 lg:p-8 card-hover shadow-lg h-full flex flex-col">
          <div class="w-10 h-10 sm:w-12 sm:h-12 md:w-16 md:h-16 bg-green-100 rounded-full flex items-center justify-center mb-3 sm:mb-4 md:mb-6 mx-auto sm:mx-0">
            <i class="fas fa-chair text-lg sm:text-xl md:text-2xl text-green-600"></i>
          </div>
          <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800 mb-2 sm:mb-3 md:mb-4 text-center sm:text-left">İç Temizlik</h3>
          <p class="text-gray-600 mb-3 sm:mb-4 md:mb-6 text-sm sm:text-base flex-grow text-center sm:text-left leading-relaxed">Otomobilin İç Mekanı, Koltuklar, Torpido ve Konsolunun Tam Temizliği</p>
          <div class="text-xl sm:text-2xl font-bold text-green-600 text-center sm:text-left">₺80</div>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-6 lg:p-8 card-hover shadow-lg h-full flex flex-col sm:col-span-2 lg:col-span-1">
          <div class="w-10 h-10 sm:w-12 sm:h-12 md:w-16 md:h-16 bg-purple-100 rounded-full flex items-center justify-center mb-3 sm:mb-4 md:mb-6 mx-auto sm:mx-0">
            <i class="fas fa-gem text-lg sm:text-xl md:text-2xl text-purple-600"></i>
          </div>
          <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800 mb-2 sm:mb-3 md:mb-4 text-center sm:text-left">Tam Detaylandırma</h3>
          <p class="text-gray-600 mb-3 sm:mb-4 md:mb-6 text-sm sm:text-base flex-grow text-center sm:text-left leading-relaxed">Dış Yıkama, İç Temizlik ve Gövde Cilası Dahil Tam Hizmet</p>
          <div class="text-xl sm:text-2xl font-bold text-purple-600 text-center sm:text-left">₺150</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer will be included at the bottom of the page -->

  <!-- About Us Section -->
  <!-- Farsça: بخش درباره ما. -->
  <!-- Türkçe: Hakkımızda Bölümü. -->
  <!-- English: About Us Section. -->
  <section id="about" class="py-12 sm:py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4">
      <section class="text-center mb-8 sm:mb-12 md:mb-16 animate-fade-in-up">
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-800 mb-3 sm:mb-4 md:mb-6">
          <span class="text-gradient">CarWash</span> Hakkında
        </h2>
        <p class="text-base sm:text-lg md:text-xl text-gray-600 max-w-3xl mx-auto px-4 leading-relaxed">
          Türkiye'nin en iyi online araç yıkama rezervasyon platformu olarak, araç bakımını kolay, hızlı ve güvenilir hale getiriyoruz.
        </p>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-12 items-center mb-8 sm:mb-12 md:mb-16">
        <div class="animate-fade-in-up order-2 lg:order-1">
          <img src="auth/uploads/pic04.jpg" alt="Our Mission" class="rounded-2xl shadow-xl w-full h-48 sm:h-56 md:h-64 lg:h-80 xl:h-auto object-cover">
        </div>
        <div class="space-y-3 sm:space-y-4 md:space-y-6 animate-fade-in-up order-1 lg:order-2" style="animation-delay: 0.2s;">
          <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Misyonumuz</h3>
          <p class="text-sm sm:text-base md:text-lg text-gray-700 leading-relaxed">
            Müşterilerimize en yüksek kalitede araç yıkama ve detaylandırma hizmetlerini, yenilikçi bir online rezervasyon deneyimiyle sunmaktır. Zamanınızın değerli olduğunu biliyor, bu yüzden hızlı, güvenilir ve sorunsuz bir hizmet vaat ediyoruz.
          </p>
          <p class="text-sm sm:text-base md:text-lg text-gray-700 leading-relaxed">
            Çevreye duyarlı yaklaşımlarımızla, su ve enerji tasarrufu sağlayan yöntemleri benimseyerek sürdürülebilir bir gelecek için çalışıyoruz.
          </p>
        </div>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-12 items-center mb-8 sm:mb-12 md:mb-16">
        <div class="space-y-3 sm:space-y-4 md:space-y-6 animate-fade-in-up" style="animation-delay: 0.4s;">
          <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">Değerlerimiz</h3>
          <ul class="space-y-2 sm:space-y-3 text-sm sm:text-base md:text-lg text-gray-700">
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-2 sm:mr-3 text-base sm:text-lg md:text-xl flex-shrink-0"></i> Müşteri Memnuniyeti</li>
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-2 sm:mr-3 text-base sm:text-lg md:text-xl flex-shrink-0"></i> Kalite ve Güvenilirlik</li>
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-2 sm:mr-3 text-base sm:text-lg md:text-xl flex-shrink-0"></i> Yenilikçilik</li>
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-2 sm:mr-3 text-base sm:text-lg md:text-xl flex-shrink-0"></i> Çevreye Duyarlılık</li>
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-2 sm:mr-3 text-base sm:text-lg md:text-xl flex-shrink-0"></i> Profesyonellik</li>
          </ul>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.6s;">
          <img src="auth/uploads/pic02.jpg" alt="Our Values" class="rounded-2xl shadow-xl w-full h-48 sm:h-56 md:h-64 lg:h-80 xl:h-auto object-cover">
        </div>
      </section>

      <section class="text-center py-8 sm:py-12 md:py-16 hero-gradient text-white rounded-2xl shadow-xl animate-fade-in-up px-4 sm:px-6 md:px-8" style="animation-delay: 0.8s;">
        <h3 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold mb-3 sm:mb-4">Neden CarWash'ı Seçmelisiniz?</h3>
        <p class="text-sm sm:text-base md:text-lg lg:text-xl text-gray-200 max-w-3xl mx-auto mb-4 sm:mb-6 md:mb-8 leading-relaxed">
          CarWash, size sadece bir araç yıkama hizmeti sunmakla kalmaz, aynı zamanda zamanınızı ve enerjinizi koruyan bir deneyim sunar.
        </p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 md:gap-8">
          <div class="flex flex-col items-center">
            <i class="fas fa-clock text-2xl sm:text-3xl md:text-4xl lg:text-5xl mb-2 md:mb-3"></i>
            <p class="font-bold text-xs sm:text-sm md:text-base lg:text-lg">Hızlı Rezervasyon</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-shield-alt text-2xl sm:text-3xl md:text-4xl lg:text-5xl mb-2 md:mb-3"></i>
            <p class="font-bold text-xs sm:text-sm md:text-base lg:text-lg">Güvenli Ödeme</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-star text-2xl sm:text-3xl md:text-4xl lg:text-5xl mb-2 md:mb-3"></i>
            <p class="font-bold text-xs sm:text-sm md:text-base lg:text-lg">Üstün Kalite</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-map-marker-alt text-2xl sm:text-3xl md:text-4xl lg:text-5xl mb-2 md:mb-3"></i>
            <p class="font-bold text-xs sm:text-sm md:text-base lg:text-lg">Yaygın Ağ</p>
          </div>
        </div>
      </section>
    </div>
  </section>

  <!-- Testimonials Section -->
  <!-- Farsça: بخش نظرات مشتریان. -->
  <!-- Türkçe: Müşteri Yorumları Bölümü. -->
  <!-- English: Testimonials Section. -->
  <section class="py-12 sm:py-16 md:py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-8 sm:mb-12 md:mb-16">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 mb-2 sm:mb-3 md:mb-4">Müşteri Yorumları</h2>
        <p class="text-base sm:text-lg md:text-xl text-gray-600 px-4">CarWash Hizmetlerinden Müşterilerimizin Deneyimleri</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 md:gap-8">
        <div class="bg-white rounded-2xl p-4 sm:p-6 md:p-8 shadow-lg h-full flex flex-col">
          <div class="flex items-center mb-3 sm:mb-4">
            <div class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-xs sm:text-sm md:text-base flex-shrink-0">
              A
            </div>
            <div class="ml-2 sm:ml-3 md:ml-4">
              <h4 class="font-bold text-gray-800 text-sm sm:text-base">Ali Yılmaz</h4>
              <div class="flex text-yellow-400 text-xs sm:text-sm">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600 text-xs sm:text-sm md:text-base flex-grow leading-relaxed">"Mükemmel hizmet ve hız. Profesyonel ekip ve uygun fiyat. Kesinlikle tekrar kullanacağım."</p>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-6 md:p-8 shadow-lg h-full flex flex-col">
          <div class="flex items-center mb-3 sm:mb-4">
            <div class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-xs sm:text-sm md:text-base flex-shrink-0">
              M
            </div>
            <div class="ml-2 sm:ml-3 md:ml-4">
              <h4 class="font-bold text-gray-800 text-sm sm:text-base">Merve Kaya</h4>
              <div class="flex text-yellow-400 text-xs sm:text-sm">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600 text-xs sm:text-sm md:text-base flex-grow leading-relaxed">"İlk kez online araç yıkama hizmetini kullandım. İş kalitesinden gerçekten memnunum."</p>
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-6 md:p-8 shadow-lg h-full flex flex-col md:col-span-2 lg:col-span-1">
          <div class="flex items-center mb-3 sm:mb-4">
            <div class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-purple-600 rounded-full flex items-center justify-center text-white font-bold text-xs sm:text-sm md:text-base flex-shrink-0">
              R
            </div>
            <div class="ml-2 sm:ml-3 md:ml-4">
              <h4 class="font-bold text-gray-800 text-sm sm:text-base">Recep Demir</h4>
              <div class="flex text-yellow-400 text-xs sm:text-sm">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600 text-xs sm:text-sm md:text-base flex-grow leading-relaxed">"Araç yıkama rezervasyonu için en iyi platform. Kolay, hızlı ve kaliteli. Tavsiye ederim."</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <!-- Farsça: بخش تماس. -->
  <!-- Türkçe: İletişim Bölümü. -->
  <!-- English: Contact Section. -->
  <?php
  // Display flash messages set by form handlers (if any)
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (!empty($_SESSION['flash_success']) || !empty($_SESSION['flash_error'])):
  ?>
  <div class="container mx-auto px-4 mt-6">
    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <section id="contact" class="py-12 sm:py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4">
      <section class="text-center mb-8 sm:mb-12 md:mb-16 animate-fade-in-up">
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-800 mb-3 sm:mb-4 md:mb-6">
          Bize <span class="text-gradient">Ulaşın</span>
        </h2>
        <p class="text-base sm:text-lg md:text-xl text-gray-600 max-w-3xl mx-auto px-4 leading-relaxed">
          Sorularınız, geri bildirimleriniz veya destek talepleriniz için bizimle iletişime geçmekten çekinmeyin.
        </p>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-12 mb-8 sm:mb-12 md:mb-16">
        <!-- Contact Information -->
        <!-- Farsça: اطلاعات تماس. -->
        <!-- Türkçe: İletişim Bilgileri. -->
        <!-- English: Contact Information. -->
        <div class="bg-gray-50 rounded-2xl p-4 sm:p-6 md:p-8 shadow-lg animate-fade-in-up">
          <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 mb-3 sm:mb-4 md:mb-6">İletişim Bilgileri</h3>
          <div class="space-y-3 sm:space-y-4 md:space-y-6">
            <div class="flex items-start">
              <i class="fas fa-map-marker-alt text-blue-600 text-lg sm:text-xl md:text-2xl mr-2 sm:mr-3 md:mr-4 mt-1 flex-shrink-0"></i>
              <div>
                <h4 class="font-bold text-gray-800 text-sm sm:text-base">Adres</h4>
                <p class="text-gray-600 text-xs sm:text-sm md:text-base leading-relaxed">Örnek Mah. Örnek Cad. No: 123, İstanbul, Türkiye</p>
              </div>
            </div>
            <div class="flex items-center">
              <i class="fas fa-phone text-blue-600 text-lg sm:text-xl md:text-2xl mr-2 sm:mr-3 md:mr-4 flex-shrink-0"></i>
              <div>
                <h4 class="font-bold text-gray-800 text-sm sm:text-base">Telefon</h4>
                <p class="text-gray-600 text-xs sm:text-sm md:text-base">0212-12345678</p>
              </div>
            </div>
            <div class="flex items-center">
              <i class="fas fa-envelope text-blue-600 text-lg sm:text-xl md:text-2xl mr-2 sm:mr-3 md:mr-4 flex-shrink-0"></i>
              <div>
                <h4 class="font-bold text-gray-800 text-sm sm:text-base">E-posta</h4>
                <p class="text-gray-600 text-xs sm:text-sm md:text-base">info@carwash.com</p>
              </div>
            </div>
            <div class="flex items-start">
              <i class="fas fa-clock text-blue-600 text-lg sm:text-xl md:text-2xl mr-2 sm:mr-3 md:mr-4 mt-1 flex-shrink-0"></i>
              <div>
                <h4 class="font-bold text-gray-800 text-sm sm:text-base">Çalışma Saatleri</h4>
                <p class="text-gray-600 text-xs sm:text-sm md:text-base leading-relaxed">Pazartesi - Cumartesi: 09:00 - 18:00</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Form -->
        <!-- Farsça: فرم تماس. -->
        <!-- Türkçe: İletişim Formu. -->
        <!-- English: Contact Form. -->
  <div class="bg-gray-50 rounded-2xl p-4 sm:p-6 md:p-8 shadow-lg animate-fade-in-up">
          <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 mb-3 sm:mb-4 md:mb-6">Mesaj Gönderin</h3>
          <form class="space-y-3 sm:space-y-4 md:space-y-6" method="post" action="" name="contactForm">
            <label for="auto_label_120" class="sr-only">Form</label><label for="auto_label_120" class="sr-only">Form</label><input type="hidden" name="__form" value="contact" id="auto_label_120">
            <label for="auto_label_119" class="sr-only">Csrf token</label><label for="auto_label_119" class="sr-only">Csrf token</label><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" id="auto_label_119">
            <div>
              <label for="contactName" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1 sm:mb-2">Adınız Soyadınız</label>
              <input type="text" id="contactName" name="contactName" placeholder="Adınız Soyadınız" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm sm:text-base">
            </div>
            <div>
              <label for="contactEmail" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1 sm:mb-2">E-posta Adresiniz</label>
              <input type="email" id="contactEmail" name="contactEmail" placeholder="email@example.com" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm sm:text-base">
            </div>
            <div>
              <label for="contactSubject" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1 sm:mb-2">Konu</label>
              <input type="text" id="contactSubject" name="contactSubject" placeholder="Konu" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm sm:text-base">
            </div>
            <div>
              <label for="contactMessage" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1 sm:mb-2">Mesajınız</label>
              <textarea id="contactMessage" name="contactMessage" rows="3" placeholder="Mesajınızı buraya yazın..." class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm sm:text-base resize-none md:rows-4"></textarea>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-bold hover:from-purple-700 hover:to-blue-700 transition-all text-sm sm:text-base">
              <i class="fas fa-paper-plane mr-2"></i>Mesajı Gönder
            </button>
          </form>
        </div>
      </section>

      <!-- Map Section -->
      <!-- Farsça: بخش نقشه. -->
      <!-- Türkçe: Harita Bölümü. -->
      <!-- English: Map Section. -->
      <section class="mb-8 sm:mb-12 md:mb-16 animate-fade-in-up" style="animation-delay: 0.4s;">
        <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 mb-3 sm:mb-4 md:mb-6 text-center">Konumumuz</h3>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3010.7600000000007!2d28.97835891526708!3d41.00823797929982!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cac24e2b2b2b2b%3A0x123456789abcdef!2sIstanbul%2C%20Turkey!5e0!3m2!1sen!2sus!4v1678901234567!5m2!1sen!2sus"
            width="100%"
            height="250"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            class="rounded-2xl sm:h-80 md:h-96"
          ></iframe>
        </div>
      </section>
    </div>
  </section>

  <!-- Footer - Using standardized footer component -->
  <?php 
  // Include the universal footer
  include 'includes/footer.php'; 
  ?>


