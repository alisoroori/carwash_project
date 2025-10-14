<?php
// Farsça: این فایل شامل کدهای HTML صفحه اصلی است.
// Türkçe: Bu dosya, ana sayfanın HTML kodlarını içermektedir.
// English: This file contains the HTML code for the main page.
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - En İyi Online Araç Yıkama Rezervasyon Platformu</title>
  <!-- Optimized TailwindCSS with all utilities -->
  <link rel="stylesheet" href="../frontend/css/style.css?v=<?php echo time(); ?>">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

  <!-- Header -->
  <!-- Farsça: این بخش سربرگ صفحه را شامل می‌شود. -->
  <!-- Türkçe: Bu bölüm sayfa başlığını içerir. -->
  <!-- English: This section includes the page header. -->
  <header class="bg-white shadow-lg sticky top-0 z-50">
    <div class="container mx-auto px-4">
      <div class="flex justify-between items-center py-4">
        <div class="flex items-center space-x-2">
          <i class="fas fa-car text-2xl md:text-3xl text-blue-600"></i>
          <h1 class="text-xl md:text-2xl font-bold text-gradient">CarWash</h1>
        </div>

        <nav class="hidden md:flex space-x-8">
          <a href="#home" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Ana Sayfa</a>
          <a href="#services" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Hizmetler</a>
          <a href="#about" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Hakkımızda</a>
          <a href="#contact" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">İletişim</a>
          <a href="#register" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Kayıt Ol</a>
        </nav>

        <div class="hidden md:flex space-x-4">
          <a href="../backend/auth/login.php" class="bg-blue-600 text-white px-4 lg:px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm lg:text-base">
            Giriş
          </a>
          <a href="#register" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 lg:px-6 py-2 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all font-medium text-sm lg:text-base">
            Kayıt Ol
          </a>
        </div>

        <!-- Mobile menu button -->
        <!-- Farsça: دکمه منوی موبایل. -->
        <!-- Türkçe: Mobil menü düğmesi. -->
        <!-- English: Mobile menu button. -->
        <button class="md:hidden text-gray-700 p-2" onclick="toggleMobileMenu()">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>

      <!-- Mobile menu -->
      <!-- Farsça: منوی موبایل. -->
      <!-- Türkçe: Mobil menü. -->
      <!-- English: Mobile menu. -->
      <div id="mobileMenu" class="hidden md:hidden pb-4 border-t border-gray-200 mt-4 pt-4">
        <div class="flex flex-col space-y-3">
          <a href="#home" class="text-gray-700 hover:text-blue-600 py-2 px-4 rounded-lg hover:bg-gray-50 transition-all">Ana Sayfa</a>
          <a href="#services" class="text-gray-700 hover:text-blue-600 py-2 px-4 rounded-lg hover:bg-gray-50 transition-all">Hizmetler</a>
          <a href="#about" class="text-gray-700 hover:text-blue-600 py-2 px-4 rounded-lg hover:bg-gray-50 transition-all">Hakkımızda</a>
          <a href="#contact" class="text-gray-700 hover:text-blue-600 py-2 px-4 rounded-lg hover:bg-gray-50 transition-all">İletişim</a>
          <a href="#register" class="text-gray-700 hover:text-blue-600 py-2 px-4 rounded-lg hover:bg-gray-50 transition-all">Kayıt Ol</a>
          <div class="flex flex-col space-y-2 pt-2">
            <a href="../backend/auth/login.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium text-center">
              Giriş
            </a>
            <a href="#register" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-2 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all font-medium text-center">
              Kayıt Ol
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <!-- Farsça: بخش قهرمان (Hero Section). -->
  <!-- Türkçe: Kahraman Bölümü. -->
  <!-- English: Hero Section. -->
  <section id="home" class="hero-gradient text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-black bg-opacity-20"></div>
    <div class="container mx-auto px-4 py-12 md:py-20 relative z-10">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
        <div class="animate-fade-in-up text-center lg:text-left">
          <h2 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold mb-4 lg:mb-6 leading-tight">
            Online Araç Yıkama
            <span class="text-gradient block sm:inline">Rezervasyonu</span>
          </h2>
          <p class="text-lg sm:text-xl mb-6 lg:mb-8 text-gray-200 leading-relaxed">
            Hızlı • Güvenilir • Profesyonel<br>
            Yakınınızdaki En İyi Araç Yıkama Hizmetleri
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
            <a href="../backend/auth/Customer_Registration.php" class="bg-white text-blue-600 px-6 sm:px-8 py-3 sm:py-4 rounded-lg font-bold hover:bg-gray-100 transition-all text-center animate-pulse-slow">
              <i class="fas fa-user-plus mr-2"></i>
              <span class="hidden sm:inline">Müşteri Olarak</span> Kayıt Ol
            </a>
            <a href="../backend/auth/Car_Wash_Registration.php" class="border-2 border-white text-white px-6 sm:px-8 py-3 sm:py-4 rounded-lg font-bold hover:bg-white hover:text-blue-600 transition-all text-center">
              <i class="fas fa-store mr-2"></i>
              <span class="hidden sm:inline">Hizmet Sağlayıcı Olarak</span> <span class="sm:hidden">Sağlayıcı</span> Kayıt Ol
            </a>
          </div>
        </div>

        <div class="animate-slide-in mt-8 lg:mt-0">
          <div class="bg-white rounded-2xl p-6 lg:p-8 shadow-2xl">
            <h3 class="text-xl lg:text-2xl font-bold text-gray-800 mb-4 lg:mb-6 text-center">Neden CarWash?</h3>
            <div class="space-y-4">
              <div class="flex items-center">
                <i class="fas fa-clock text-blue-600 text-xl lg:text-2xl mr-3 lg:mr-4 flex-shrink-0"></i>
                <div>
                  <h4 class="font-bold text-gray-800 text-sm lg:text-base">Yüksek Hız</h4>
                  <p class="text-gray-600 text-sm lg:text-base">2 Dakikadan Az Sürede Rezervasyon</p>
                </div>
              </div>
              <div class="flex items-center">
                <i class="fas fa-shield-alt text-blue-600 text-xl lg:text-2xl mr-3 lg:mr-4 flex-shrink-0"></i>
                <div>
                  <h4 class="font-bold text-gray-800 text-sm lg:text-base">Tam Güvenlik</h4>
                  <p class="text-gray-600 text-sm lg:text-base">Güvenli ve Garantili Ödeme</p>
                </div>
              </div>
              <div class="flex items-center">
                <i class="fas fa-star text-blue-600 text-xl lg:text-2xl mr-3 lg:mr-4 flex-shrink-0"></i>
                <div>
                  <h4 class="font-bold text-gray-800 text-sm lg:text-base">Üstün Kalite</h4>
                  <p class="text-gray-600 text-sm lg:text-base">Şehrin En İyi Uzmanları</p>
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
    <div class="absolute top-20 left-10 text-white opacity-10 animate-pulse hidden lg:block">
      <i class="fas fa-car text-6xl"></i>
    </div>
    <div class="absolute bottom-20 right-10 text-white opacity-10 animate-pulse hidden lg:block">
      <i class="fas fa-water text-6xl"></i>
    </div>
  </section>

  <!-- Statistics Section -->
  <!-- Farsça: بخش آمار. -->
  <!-- Türkçe: İstatistik Bölümü. -->
  <!-- English: Statistics Section. -->
  <section class="py-12 md:py-16 bg-white">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-8 text-center">
        <div class="animate-fade-in-up">
          <div class="text-2xl md:text-4xl font-bold text-blue-600 mb-2">1000+</div>
          <div class="text-gray-600 text-sm md:text-base">Memnun Müşteri</div>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.2s">
          <div class="text-2xl md:text-4xl font-bold text-blue-600 mb-2">500+</div>
          <div class="text-gray-600 text-sm md:text-base">Aktif Hizmet Sağlayıcı</div>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.4s">
          <div class="text-2xl md:text-4xl font-bold text-blue-600 mb-2">10000+</div>
          <div class="text-gray-600 text-sm md:text-base">Tamamlanan Hizmet</div>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.6s">
          <div class="text-2xl md:text-4xl font-bold text-blue-600 mb-2">4.9★</div>
          <div class="text-gray-600 text-sm md:text-base">Müşteri Memnuniyeti</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <!-- Farsça: بخش خدمات. -->
  <!-- Türkçe: Hizmetler Bölümü. -->
  <!-- English: Services Section. -->
  <section id="services" class="py-12 md:py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12 md:mb-16">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Hizmetlerimiz</h2>
        <p class="text-lg md:text-xl text-gray-600">En İyi Kalitede Çeşitli Araç Yıkama Hizmetleri</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
        <div class="bg-white rounded-2xl p-6 md:p-8 card-hover shadow-lg">
          <div class="w-12 h-12 md:w-16 md:h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4 md:mb-6 mx-auto md:mx-0">
            <i class="fas fa-car text-xl md:text-2xl text-blue-600"></i>
          </div>
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-3 md:mb-4 text-center md:text-left">Dış Yıkama</h3>
          <p class="text-gray-600 mb-4 md:mb-6 text-center md:text-left text-sm md:text-base">Modern Ekipman ve En İyi Temizlik Malzemeleri ile Tam Gövde Yıkama</p>
          <div class="text-xl md:text-2xl font-bold text-blue-600 text-center md:text-left">₺50</div>
        </div>

        <div class="bg-white rounded-2xl p-6 md:p-8 card-hover shadow-lg">
          <div class="w-12 h-12 md:w-16 md:h-16 bg-green-100 rounded-full flex items-center justify-center mb-4 md:mb-6 mx-auto md:mx-0">
            <i class="fas fa-chair text-xl md:text-2xl text-green-600"></i>
          </div>
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-3 md:mb-4 text-center md:text-left">İç Temizlik</h3>
          <p class="text-gray-600 mb-4 md:mb-6 text-center md:text-left text-sm md:text-base">Otomobilin İç Mekanı, Koltuklar, Torpido ve Konsolunun Tam Temizliği</p>
          <div class="text-xl md:text-2xl font-bold text-green-600 text-center md:text-left">₺80</div>
        </div>

        <div class="bg-white rounded-2xl p-6 md:p-8 card-hover shadow-lg">
          <div class="w-12 h-12 md:w-16 md:h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4 md:mb-6 mx-auto md:mx-0">
            <i class="fas fa-gem text-xl md:text-2xl text-purple-600"></i>
          </div>
          <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-3 md:mb-4 text-center md:text-left">Tam Detaylandırma</h3>
          <p class="text-gray-600 mb-4 md:mb-6 text-center md:text-left text-sm md:text-base">Dış Yıkama, İç Temizlik ve Gövde Cilası Dahil Tam Hizmet</p>
          <div class="text-xl md:text-2xl font-bold text-purple-600 text-center md:text-left">₺150</div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Us Section -->
  <!-- Farsça: بخش درباره ما. -->
  <!-- Türkçe: Hakkımızda Bölümü. -->
  <!-- English: About Us Section. -->
  <section id="about" class="py-12 md:py-20 bg-white">
    <div class="container mx-auto px-4">
      <section class="text-center mb-12 md:mb-16 animate-fade-in-up">
        <h2 class="text-3xl md:text-5xl font-bold text-gray-800 mb-4 md:mb-6">
          <span class="text-gradient">CarWash</span> Hakkında
        </h2>
        <p class="text-lg md:text-xl text-gray-600 max-w-3xl mx-auto">
          Türkiye'nin en iyi online araç yıkama rezervasyon platformu olarak, araç bakımını kolay, hızlı ve güvenilir hale getiriyoruz.
        </p>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center mb-12 md:mb-16">
        <div class="animate-fade-in-up order-2 lg:order-1">
          <img src="../backend/auth/uploads/pic04.jpg" alt="Our Mission" class="rounded-2xl shadow-xl w-full h-auto object-cover">
        </div>
        <div class="space-y-4 md:space-y-6 animate-fade-in-up order-1 lg:order-2" style="animation-delay: 0.2s;">
          <h3 class="text-2xl md:text-3xl font-bold text-gray-800 text-center lg:text-left">Misyonumuz</h3>
          <p class="text-base md:text-lg text-gray-700 leading-relaxed text-center lg:text-left">
            Müşterilerimize en yüksek kalitede araç yıkama ve detaylandırma hizmetlerini, yenilikçi bir online rezervasyon deneyimiyle sunmaktır. Zamanınızın değerli olduğunu biliyor, bu yüzden hızlı, güvenilir ve sorunsuz bir hizmet vaat ediyoruz.
          </p>
          <p class="text-base md:text-lg text-gray-700 leading-relaxed text-center lg:text-left">
            Çevreye duyarlı yaklaşımlarımızla, su ve enerji tasarrufu sağlayan yöntemleri benimseyerek sürdürülebilir bir gelecek için çalışıyoruz.
          </p>
        </div>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center mb-12 md:mb-16">
        <div class="space-y-4 md:space-y-6 animate-fade-in-up" style="animation-delay: 0.4s;">
          <h3 class="text-2xl md:text-3xl font-bold text-gray-800 text-center lg:text-left">Değerlerimiz</h3>
          <ul class="space-y-3 text-base md:text-lg text-gray-700">
            <li class="flex items-center justify-center lg:justify-start"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Müşteri Memnuniyeti</li>
            <li class="flex items-center justify-center lg:justify-start"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Kalite ve Güvenilirlik</li>
            <li class="flex items-center justify-center lg:justify-start"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Yenilikçilik</li>
            <li class="flex items-center justify-center lg:justify-start"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Çevreye Duyarlılık</li>
            <li class="flex items-center justify-center lg:justify-start"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Profesyonellik</li>
          </ul>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.6s;">
          <img src="../backend/auth/uploads/pic02.jpg" alt="Our Values" class="rounded-2xl shadow-xl w-full h-auto object-cover">
        </div>
      </section>

      <section class="text-center py-12 md:py-16 hero-gradient text-white rounded-2xl shadow-xl animate-fade-in-up" style="animation-delay: 0.8s;">
        <h3 class="text-2xl md:text-4xl font-bold mb-4">Neden CarWash'ı Seçmelisiniz?</h3>
        <p class="text-lg md:text-xl text-gray-200 max-w-3xl mx-auto mb-6 md:mb-8 px-4">
          CarWash, size sadece bir araç yıkama hizmeti sunmakla kalmaz, aynı zamanda zamanınızı ve enerjinizi koruyan bir deneyim sunar.
        </p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-8 px-4">
          <div class="flex flex-col items-center">
            <i class="fas fa-clock text-3xl md:text-5xl mb-2 md:mb-3"></i>
            <p class="font-bold text-sm md:text-lg">Hızlı Rezervasyon</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-shield-alt text-3xl md:text-5xl mb-2 md:mb-3"></i>
            <p class="font-bold text-sm md:text-lg">Güvenli Ödeme</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-star text-3xl md:text-5xl mb-2 md:mb-3"></i>
            <p class="font-bold text-sm md:text-lg">Üstün Kalite</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-map-marker-alt text-3xl md:text-5xl mb-2 md:mb-3"></i>
            <p class="font-bold text-sm md:text-lg">Yaygın Ağ</p>
          </div>
        </div>
      </section>
    </div>
  </section>

  <!-- Testimonials Section -->
  <!-- Farsça: بخش نظرات مشتریان. -->
  <!-- Türkçe: Müşteri Yorumları Bölümü. -->
  <!-- English: Testimonials Section. -->
  <section class="py-12 md:py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12 md:mb-16">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Müşteri Yorumları</h2>
        <p class="text-lg md:text-xl text-gray-600">CarWash Hizmetlerinden Müşterilerimizin Deneyimleri</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
        <div class="bg-white rounded-2xl p-6 md:p-8 shadow-lg">
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm md:text-base">
              A
            </div>
            <div class="ml-3 md:ml-4">
              <h4 class="font-bold text-gray-800 text-sm md:text-base">Ali Yılmaz</h4>
              <div class="flex text-yellow-400 text-sm">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600 text-sm md:text-base">"Mükemmel hizmet ve hız. Profesyonel ekip ve uygun fiyat. Kesinlikle tekrar kullanacağım."</p>
        </div>

        <div class="bg-white rounded-2xl p-6 md:p-8 shadow-lg">
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm md:text-base">
              M
            </div>
            <div class="ml-3 md:ml-4">
              <h4 class="font-bold text-gray-800 text-sm md:text-base">Merve Kaya</h4>
              <div class="flex text-yellow-400 text-sm">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600 text-sm md:text-base">"İlk kez online araç yıkama hizmetini kullandım. İş kalitesinden gerçekten memnunum."</p>
        </div>

        <div class="bg-white rounded-2xl p-6 md:p-8 shadow-lg md:col-span-2 lg:col-span-1">
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm md:text-base">
              R
            </div>
            <div class="ml-3 md:ml-4">
              <h4 class="font-bold text-gray-800 text-sm md:text-base">Recep Demir</h4>
              <div class="flex text-yellow-400 text-sm">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600 text-sm md:text-base">"Araç yıkama rezervasyonu için en iyi platform. Kolay, hızlı ve kaliteli. Tavsiye ederim."</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <!-- Farsça: بخش تماس. -->
  <!-- Türkçe: İletişim Bölümü. -->
  <!-- English: Contact Section. -->
  <section id="contact" class="py-12 md:py-20 bg-white">
    <div class="container mx-auto px-4">
      <section class="text-center mb-12 md:mb-16 animate-fade-in-up">
        <h2 class="text-3xl md:text-5xl font-bold text-gray-800 mb-4 md:mb-6">
          Bize <span class="text-gradient">Ulaşın</span>
        </h2>
        <p class="text-lg md:text-xl text-gray-600 max-w-3xl mx-auto">
          Sorularınız, geri bildirimleriniz veya destek talepleriniz için bizimle iletişime geçmekten çekinmeyin.
        </p>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-12 md:mb-16">
        <!-- Contact Information -->
        <!-- Farsça: اطلاعات تماس. -->
        <!-- Türkçe: İletişim Bilgileri. -->
        <!-- English: Contact Information. -->
        <div class="bg-gray-50 rounded-2xl p-6 md:p-8 shadow-lg animate-fade-in-up">
          <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4 md:mb-6 text-center lg:text-left">İletişim Bilgileri</h3>
          <div class="space-y-4 md:space-y-6">
            <div class="flex items-center justify-center lg:justify-start">
              <i class="fas fa-map-marker-alt text-blue-600 text-xl md:text-2xl mr-3 md:mr-4 flex-shrink-0"></i>
              <div class="text-center lg:text-left">
                <h4 class="font-bold text-gray-800 text-sm md:text-base">Adres</h4>
                <p class="text-gray-600 text-sm md:text-base">Örnek Mah. Örnek Cad. No: 123, İstanbul, Türkiye</p>
              </div>
            </div>
            <div class="flex items-center justify-center lg:justify-start">
              <i class="fas fa-phone text-blue-600 text-xl md:text-2xl mr-3 md:mr-4 flex-shrink-0"></i>
              <div class="text-center lg:text-left">
                <h4 class="font-bold text-gray-800 text-sm md:text-base">Telefon</h4>
                <p class="text-gray-600 text-sm md:text-base">0212-12345678</p>
              </div>
            </div>
            <div class="flex items-center justify-center lg:justify-start">
              <i class="fas fa-envelope text-blue-600 text-xl md:text-2xl mr-3 md:mr-4 flex-shrink-0"></i>
              <div class="text-center lg:text-left">
                <h4 class="font-bold text-gray-800 text-sm md:text-base">E-posta</h4>
                <p class="text-gray-600 text-sm md:text-base">info@carwash.com</p>
              </div>
            </div>
            <div class="flex items-center justify-center lg:justify-start">
              <i class="fas fa-clock text-blue-600 text-xl md:text-2xl mr-3 md:mr-4 flex-shrink-0"></i>
              <div class="text-center lg:text-left">
                <h4 class="font-bold text-gray-800 text-sm md:text-base">Çalışma Saatleri</h4>
                <p class="text-gray-600 text-sm md:text-base">Pazartesi - Cumartesi: 09:00 - 18:00</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Form -->
        <!-- Farsça: فرم تماس. -->
        <!-- Türkçe: İletişim Formu. -->
        <!-- English: Contact Form. -->
        <div class="bg-gray-50 rounded-2xl p-6 md:p-8 shadow-lg animate-fade-in-up" style="animation-delay: 0.2s;">
          <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4 md:mb-6 text-center lg:text-left">Mesaj Gönderin</h3>
          <form class="space-y-4 md:space-y-6" method="POST" action="#contact" onsubmit="handleContactForm(event)">
            <div>
              <label for="contactName" class="block text-sm font-bold text-gray-700 mb-2">Adınız Soyadınız *</label>
              <input type="text" id="contactName" name="contactName" placeholder="Adınız Soyadınız" required class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm md:text-base">
            </div>
            <div>
              <label for="contactEmail" class="block text-sm font-bold text-gray-700 mb-2">E-posta Adresiniz *</label>
              <input type="email" id="contactEmail" name="contactEmail" placeholder="email@example.com" required class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm md:text-base">
            </div>
            <div>
              <label for="contactSubject" class="block text-sm font-bold text-gray-700 mb-2">Konu *</label>
              <input type="text" id="contactSubject" name="contactSubject" placeholder="Konu" required class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm md:text-base">
            </div>
            <div>
              <label for="contactMessage" class="block text-sm font-bold text-gray-700 mb-2">Mesajınız *</label>
              <textarea id="contactMessage" name="contactMessage" rows="4" placeholder="Mesajınızı buraya yazın..." required class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm md:text-base"></textarea>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg font-bold hover:from-purple-700 hover:to-blue-700 transition-all text-sm md:text-base">
              <i class="fas fa-paper-plane mr-2"></i>Mesajı Gönder
            </button>
          </form>
        </div>
      </section>

      <!-- Map Section -->
      <!-- Farsça: بخش نقشه. -->
      <!-- Türkçe: Harita Bölümü. -->
      <!-- English: Map Section. -->
      <section class="mb-12 md:mb-16 animate-fade-in-up" style="animation-delay: 0.4s;">
        <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4 md:mb-6 text-center">Konumumuz</h3>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3010.7600000000007!2d28.97835891526708!3d41.00823797929982!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cac24e2b2b2b2b%3A0x123456789abcdef!2sIstanbul%2C%20Turkey!5e0!3m2!1sen!2sus!4v1678901234567!5m2!1sen!2sus"
            width="100%"
            height="300"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            class="rounded-2xl md:h-[450px]"
          ></iframe>
        </div>
      </section>
    </div>
  </section>

  <!-- Register Section -->
  <!-- Farsça: بخش ثبت نام. -->
  <!-- Türkçe: Kayıt Bölümü. -->
  <!-- English: Register Section. -->
  <section id="register" class="py-12 md:py-20 hero-gradient text-white">
    <div class="container mx-auto px-4">
      <section class="text-center mb-12 md:mb-16 animate-fade-in-up">
        <h2 class="text-3xl md:text-5xl font-bold mb-4 md:mb-6">
          <span class="text-white">CarWash'a</span> Kayıt Olun
        </h2>
        <p class="text-lg md:text-xl text-gray-200 max-w-3xl mx-auto">
          Hizmetlerimizden yararlanmak veya hizmet sağlayıcımız olmak için hemen kayıt olun!
        </p>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
        <!-- Customer Registration -->
        <!-- Farsça: ثبت نام مشتری. -->
        <!-- Türkçe: Müşteri Kaydı. -->
        <!-- English: Customer Registration. -->
        <div class="bg-white rounded-2xl p-6 md:p-8 shadow-lg animate-fade-in-up">
          <div class="flex items-center justify-center w-16 h-16 md:w-20 md:h-20 bg-blue-100 rounded-full mx-auto mb-4 md:mb-6">
            <i class="fas fa-user-plus text-2xl md:text-4xl text-blue-600"></i>
          </div>
          <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3 md:mb-4 text-center">Müşteri Olarak Kayıt Ol</h3>
          <p class="text-gray-600 mb-6 md:mb-8 text-center text-sm md:text-base">
            Aracınızı kolayca yıkatmak için hemen bir müşteri hesabı oluşturun.
          </p>
          
          <!-- Direct link to customer registration -->
          <div class="text-center">
            <a href="../backend/auth/Customer_Registration.php" class="inline-block w-full bg-blue-600 text-white px-6 py-4 rounded-lg font-bold hover:bg-blue-700 transition-colors mb-4">
              <i class="fas fa-user-plus mr-2"></i>Müşteri Kaydı Sayfasına Git
            </a>
            <p class="text-gray-600 text-sm md:text-base">
              Zaten hesabınız var mı? <a href="../backend/auth/login.php" class="text-blue-600 hover:underline font-medium">Giriş Yapın</a>
            </p>
          </div>
        </div>

        <!-- Service Provider Registration -->
        <!-- Farsça: ثبت نام ارائه‌دهنده خدمات. -->
        <!-- Türkçe: Hizmet Sağlayıcı Kaydı. -->
        <!-- English: Service Provider Registration. -->
        <div class="bg-white rounded-2xl p-6 md:p-8 shadow-lg animate-fade-in-up" style="animation-delay: 0.2s;">
          <div class="flex items-center justify-center w-16 h-16 md:w-20 md:h-20 bg-purple-100 rounded-full mx-auto mb-4 md:mb-6">
            <i class="fas fa-store text-2xl md:text-4xl text-purple-600"></i>
          </div>
          <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3 md:mb-4 text-center">Hizmet Sağlayıcı Olarak Kayıt Ol</h3>
          <p class="text-gray-600 mb-6 md:mb-8 text-center text-sm md:text-base">
            İşletmenizi büyütmek için CarWash ağına katılın.
          </p>
          
          <!-- Direct link to car wash registration -->
          <div class="text-center">
            <a href="../backend/auth/Car_Wash_Registration.php" class="inline-block w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-4 rounded-lg font-bold hover:from-purple-700 hover:to-blue-700 transition-all mb-4">
              <i class="fas fa-store mr-2"></i>Hizmet Sağlayıcı Kaydı Sayfasına Git
            </a>
            <p class="text-gray-600 text-sm md:text-base">
              Zaten hesabınız var mı? <a href="../backend/auth/login.php" class="text-blue-600 hover:underline font-medium">Giriş Yapın</a>
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Registration Benefits -->
      <div class="mt-12 md:mt-16 text-center">
        <h3 class="text-2xl md:text-3xl font-bold mb-6 md:mb-8">Kayıt Avantajları</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
          <div class="bg-white bg-opacity-10 rounded-xl p-4 md:p-6">
            <i class="fas fa-lightning-bolt text-3xl md:text-4xl mb-3 md:mb-4"></i>
            <h4 class="font-bold mb-2 text-lg md:text-xl">Hızlı Rezervasyon</h4>
            <p class="text-sm md:text-base text-gray-200">Anında rezervasyon yapın</p>
          </div>
          <div class="bg-white bg-opacity-10 rounded-xl p-4 md:p-6">
            <i class="fas fa-gift text-3xl md:text-4xl mb-3 md:mb-4"></i>
            <h4 class="font-bold mb-2 text-lg md:text-xl">Özel İndirimler</h4>
            <p class="text-sm md:text-base text-gray-200">Üyelere özel fırsatlar</p>
          </div>
          <div class="bg-white bg-opacity-10 rounded-xl p-4 md:p-6">
            <i class="fas fa-history text-3xl md:text-4xl mb-3 md:mb-4"></i>
            <h4 class="font-bold mb-2 text-lg md:text-xl">Geçmiş Takibi</h4>
            <p class="text-sm md:text-base text-gray-200">Tüm hizmetlerinizi takip edin</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <!-- Farsça: پاورقی. -->
  <!-- Türkçe: Altbilgi. -->
  <!-- English: Footer. -->
  <footer class="bg-gray-800 text-white py-8 md:py-12">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
        <div class="text-center sm:text-left">
          <div class="flex items-center justify-center sm:justify-start space-x-2 mb-3 md:mb-4">
            <i class="fas fa-car text-xl md:text-2xl text-blue-400"></i>
            <h3 class="text-lg md:text-xl font-bold">CarWash</h3>
          </div>
          <p class="text-gray-300 text-sm md:text-base">Türkiye'nin En İyi Online Araç Yıkama Rezervasyon Platformu</p>
        </div>

        <div class="text-center sm:text-left">
          <h4 class="font-bold mb-3 md:mb-4 text-base md:text-lg">Hizmetler</h4>
          <ul class="space-y-2 text-gray-300 text-sm md:text-base">
            <li><a href="#services" class="hover:text-white transition-colors">Dış Yıkama</a></li>
            <li><a href="#services" class="hover:text-white transition-colors">İç Temizlik</a></li>
            <li><a href="#services" class="hover:text-white transition-colors">Tam Detaylandırma</a></li>
          </ul>
        </div>

        <div class="text-center sm:text-left">
          <h4 class="font-bold mb-3 md:mb-4 text-base md:text-lg">Destek</h4>
          <ul class="space-y-2 text-gray-300 text-sm md:text-base">
            <li><a href="#" class="hover:text-white transition-colors">Sık Sorulan Sorular</a></li>
            <li><a href="#contact" class="hover:text-white transition-colors">Bize Ulaşın</a></li>
            <li><a href="#" class="hover:text-white transition-colors">Kullanım Kılavuzu</a></li>
          </ul>
        </div>

        <div class="text-center sm:text-left">
          <h4 class="font-bold mb-3 md:mb-4 text-base md:text-lg">İletişim</h4>
          <div class="space-y-2 text-gray-300 text-sm md:text-base">
            <p><i class="fas fa-phone mr-2"></i> 0212-12345678</p>
            <p><i class="fas fa-envelope mr-2"></i> info@carwash.com</p>
            <p><i class="fas fa-map-marker-alt mr-2"></i> İstanbul, Türkiye</p>
          </div>
        </div>
      </div>

      <div class="border-t border-gray-700 mt-6 md:mt-8 pt-6 md:pt-8 text-center">
        <p class="text-gray-300 text-sm md:text-base">&copy; 2024 CarWash. Tüm Hakları Saklıdır.</p>
      </div>
    </div>
  </footer>

  <!-- Back to Top Button -->
  <!-- Farsça: دکمه بازگشت به بالا. -->
  <!-- Türkçe: Yukarı çık düğmesi. -->
  <!-- English: Back to top button. -->
  <button class="back-to-top" onclick="scrollToTop()" title="Yukarı Çık">
    <i class="fas fa-chevron-up"></i>
  </button>

  <script>
    // Farsça: تابع برای تغییر وضعیت منوی موبایل.
    // Türkçe: Mobil menüyü açıp kapatmak için fonksiyon.
    // English: Function to toggle mobile menu.
    function toggleMobileMenu() {
      const menu = document.getElementById('mobileMenu');
      menu.classList.toggle('hidden');
    }

    // Back to Top Button Functionality
    // Farsça: عملکرد دکمه بازگشت به بالا.
    // Türkçe: Yukarı çık düğmesi işlevselliği.
    // English: Back to top button functionality.
    function scrollToTop() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }

    // Contact Form Handler
    // Farsça: کنترل کننده فرم تماس.
    // Türkçe: İletişim formu işleyicisi.
    // English: Contact form handler.
    function handleContactForm(event) {
      event.preventDefault();
      
      const name = document.getElementById('contactName').value;
      const email = document.getElementById('contactEmail').value;
      const subject = document.getElementById('contactSubject').value;
      const message = document.getElementById('contactMessage').value;
      
      // Basic validation
      if (!name || !email || !subject || !message) {
        alert('Lütfen tüm alanları doldurun.');
        return false;
      }
      
      // Email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        alert('Lütfen geçerli bir e-posta adresi girin.');
        return false;
      }
      
      // Show success message
      alert('Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.');
      
      // Reset form
      document.querySelector('form').reset();
      
      return false;
    }

    // Show/hide back to top button based on scroll position
    // Farsça: نمایش/مخفی کردن دکمه بازگشت به بالا بر اساس موقعیت اسکرول.
    // Türkçe: Kaydırma konumuna göre yukarı çık düğmesini göster/gizle.
    // English: Show/hide back to top button based on scroll position.
    window.addEventListener('scroll', function() {
      const backToTopButton = document.querySelector('.back-to-top');
      if (window.pageYOffset > 300) {
        backToTopButton.classList.add('show');
      } else {
        backToTopButton.classList.remove('show');
      }
    });

    // Smooth scrolling for navigation links
    // Farsça: اسکرول نرم برای لینک‌های ناوبری.
    // Türkçe: Navigasyon bağlantıları için yumuşak kaydırma.
    // English: Smooth scrolling for navigation links.
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          // Close mobile menu if open
          const mobileMenu = document.getElementById('mobileMenu');
          if (!mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
          }
          
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    // Add animation delays to cards
    // Farsça: اضافه کردن تاخیر انیمیشن به کارت‌ها.
    // Türkçe: Kartlara animasyon gecikmeleri ekle.
    // English: Add animation delays to cards.
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.card-hover');
      cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.2}s`;
      });

      // Initialize back to top button
      const backToTopButton = document.querySelector('.back-to-top');
      if (window.pageYOffset > 300) {
        backToTopButton.classList.add('show');
      }
    });

    // Close mobile menu when clicking outside
    // Farsça: کلیک کردن خارج از منو، منوی موبایل را می‌بندد.
    // Türkçe: Menü dışına tıklandığında mobil menüyü kapat.
    // English: Close mobile menu when clicking outside.
    document.addEventListener('click', function(event) {
      const mobileMenu = document.getElementById('mobileMenu');
      const menuButton = event.target.closest('button[onclick="toggleMobileMenu()"]');
      
      if (!menuButton && !mobileMenu.contains(event.target)) {
        mobileMenu.classList.add('hidden');
      }
    });
  </script>

  <!-- Include main.js for common functionality -->
  <script src="../frontend/js/main.js"></script>

</body>
</html>
