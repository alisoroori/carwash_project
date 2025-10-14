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
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Farsça: انیمیشن برای ظاهر شدن تدریجی عناصر از پایین به بالا. */
    /* Türkçe: Öğelerin aşağıdan yukarıya doğru yavaşça görünmesi için animasyon. */
    /* English: Animation for elements to fade in from bottom to top. */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Farsça: انیمیشن برای ورود تدریجی عناصر از چپ به راست. */
    /* Türkçe: Öğelerin soldan sağa doğru yavaşça kayarak gelmesi için animasyon. */
    /* English: Animation for elements to slide in from left to right. */
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-50px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* Farsça: انیمیشن برای پالس زدن (تپش). */
    /* Türkçe: Nabız atışı için animasyon. */
    /* English: Animation for pulsing. */
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    /* Farsça: اعمال انیمیشن fadeInUp. */
    /* Türkçe: fadeInUp animasyonunu uygular. */
    /* English: Applies the fadeInUp animation. */
    .animate-fade-in-up {
      animation: fadeInUp 0.8s ease-out forwards;
    }

    /* Farsça: اعمال انیمیشن slideIn. */
    /* Türkçe: slideIn animasyonunu uygular. */
    /* English: Applies the slideIn animation. */
    .animate-slide-in {
      animation: slideIn 0.6s ease-out forwards;
    }

    /* Farsça: اعمال انیمیشن pulse با سرعت آهسته. */
    /* Türkçe: Yavaş hızda nabız animasyonunu uygular. */
    /* English: Applies the pulse animation with slow speed. */
    .animate-pulse-slow {
      animation: pulse 3s ease-in-out infinite;
    }

    /* Farsça: پس‌زمینه گرادیانت برای بخش قهرمان. */
    /* Türkçe: Kahraman bölümü için gradyan arka plan. */
    /* English: Gradient background for the hero section. */
    .hero-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Farsça: استایل هاور برای کارت‌ها. */
    /* Türkçe: Kartlar için üzerine gelme stili. */
    /* English: Hover style for cards. */
    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    /* Farsça: گرادیانت برای متن. */
    /* Türkçe: Metin için gradyan. */
    /* English: Gradient for text. */
    .text-gradient {
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
  </style>
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
          <i class="fas fa-car text-3xl text-blue-600"></i>
          <h1 class="text-2xl font-bold text-gradient">CarWash</h1>
        </div>

        <nav class="hidden md:flex space-x-8">
          <a href="#home" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Ana Sayfa</a>
          <a href="#services" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Hizmetler</a>
          <a href="#about" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Hakkımızda</a>
          <a href="#contact" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">İletişim</a>
          <a href="#register" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Kayıt Ol</a>
        </nav>

        <div class="flex space-x-4">
          <a href="../backend/auth/login.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
            Giriş
          </a>
          <a href="#register" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all font-medium">
            Kayıt Ol
          </a>
        </div>

        <!-- Mobile menu button -->
        <!-- Farsça: دکمه منوی موبایل. -->
        <!-- Türkçe: Mobil menü düğmesi. -->
        <!-- English: Mobile menu button. -->
        <button class="md:hidden text-gray-700" onclick="toggleMobileMenu()">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>

      <!-- Mobile menu -->
      <!-- Farsça: منوی موبایل. -->
      <!-- Türkçe: Mobil menü. -->
      <!-- English: Mobile menu. -->
      <div id="mobileMenu" class="hidden md:hidden pb-4">
        <div class="flex flex-col space-y-2">
          <a href="#home" class="text-gray-700 hover:text-blue-600 py-2">Ana Sayfa</a>
          <a href="#services" class="text-gray-700 hover:text-blue-600 py-2">Hizmetler</a>
          <a href="#about" class="text-gray-700 hover:text-blue-600 py-2">Hakkımızda</a>
          <a href="#contact" class="text-gray-700 hover:text-blue-600 py-2">İletişim</a>
          <a href="#register" class="text-gray-700 hover:text-blue-600 py-2">Kayıt Ol</a>
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
    <div class="container mx-auto px-4 py-20 relative z-10">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
        <div class="animate-fade-in-up text-center lg:text-left">
          <h2 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold mb-4 lg:mb-6 leading-tight">
            Online Araç Yıkama
            <span class="text-gradient">Rezervasyonu</span>
          </h2>
          <p class="text-lg sm:text-xl mb-6 lg:mb-8 text-gray-200 leading-relaxed">
            Hızlı • Güvenilir • Profesyonel<br>
            Yakınınızdaki En İyi Araç Yıkama Hizmetleri
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
            <a href="../backend/auth/Customer_Registration.php" class="bg-white text-blue-600 px-6 sm:px-8 py-3 sm:py-4 rounded-lg font-bold hover:bg-gray-100 transition-all text-center animate-pulse-slow">
              <i class="fas fa-user-plus mr-2"></i>
              Müşteri Olarak Kayıt Ol
            </a>
            <a href="../backend/auth/Car_Wash_Registration.php" class="border-2 border-white text-white px-6 sm:px-8 py-3 sm:py-4 rounded-lg font-bold hover:bg-white hover:text-blue-600 transition-all text-center">
              <i class="fas fa-store mr-2"></i>
              Hizmet Sağlayıcı Olarak Kayıt Ol
            </a>
          </div>
        </div>

        <div class="animate-slide-in">
          <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-2xl">
            <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6 text-center">Neden CarWash?</h3>
            <div class="space-y-3 sm:space-y-4">
              <div class="flex items-center">
                <i class="fas fa-clock text-blue-600 text-xl sm:text-2xl mr-3 sm:mr-4"></i>
                <div>
                  <h4 class="font-bold text-gray-800 text-sm sm:text-base">Yüksek Hız</h4>
                  <p class="text-gray-600 text-xs sm:text-sm">2 Dakikadan Az Sürede Rezervasyon</p>
                </div>
              </div>
              <div class="flex items-center">
                <i class="fas fa-shield-alt text-blue-600 text-xl sm:text-2xl mr-3 sm:mr-4"></i>
                <div>
                  <h4 class="font-bold text-gray-800 text-sm sm:text-base">Tam Güvenlik</h4>
                  <p class="text-gray-600 text-xs sm:text-sm">Güvenli ve Garantili Ödeme</p>
                </div>
              </div>
              <div class="flex items-center">
                <i class="fas fa-star text-blue-600 text-xl sm:text-2xl mr-3 sm:mr-4"></i>
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
    <div class="absolute top-20 left-10 text-white opacity-10 animate-pulse">
      <i class="fas fa-car text-6xl"></i>
    </div>
    <div class="absolute bottom-20 right-10 text-white opacity-10 animate-pulse">
      <i class="fas fa-water text-6xl"></i>
    </div>
  </section>

  <!-- Statistics Section -->
  <!-- Farsça: بخش آمار. -->
  <!-- Türkçe: İstatistik Bölümü. -->
  <!-- English: Statistics Section. -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8 text-center">
        <div class="animate-fade-in-up">
          <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">1000+</div>
          <div class="text-xs sm:text-sm lg:text-base text-gray-600">Memnun Müşteri</div>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.2s">
          <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">500+</div>
          <div class="text-xs sm:text-sm lg:text-base text-gray-600">Aktif Hizmet Sağlayıcı</div>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.4s">
          <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">10000+</div>
          <div class="text-xs sm:text-sm lg:text-base text-gray-600">Tamamlanan Hizmet</div>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.6s">
          <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">4.9★</div>
          <div class="text-xs sm:text-sm lg:text-base text-gray-600">Müşteri Memnuniyeti</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <!-- Farsça: بخش خدمات. -->
  <!-- Türkçe: Hizmetler Bölümü. -->
  <!-- English: Services Section. -->
  <section id="services" class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12 lg:mb-16">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-800 mb-3 sm:mb-4">Hizmetlerimiz</h2>
        <p class="text-lg sm:text-xl text-gray-600">En İyi Kalitede Çeşitli Araç Yıkama Hizmetleri</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        <div class="bg-white rounded-2xl p-6 lg:p-8 card-hover shadow-lg">
          <div class="w-12 h-12 sm:w-16 sm:h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4 sm:mb-6">
            <i class="fas fa-car text-xl sm:text-2xl text-blue-600"></i>
          </div>
          <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-3 sm:mb-4">Dış Yıkama</h3>
          <p class="text-gray-600 mb-4 sm:mb-6 text-sm sm:text-base">Modern Ekipman ve En İyi Temizlik Malzemeleri ile Tam Gövde Yıkama</p>
          <div class="text-xl sm:text-2xl font-bold text-blue-600">₺50</div>
        </div>

        <div class="bg-white rounded-2xl p-6 lg:p-8 card-hover shadow-lg">
          <div class="w-12 h-12 sm:w-16 sm:h-16 bg-green-100 rounded-full flex items-center justify-center mb-4 sm:mb-6">
            <i class="fas fa-chair text-xl sm:text-2xl text-green-600"></i>
          </div>
          <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-3 sm:mb-4">İç Temizlik</h3>
          <p class="text-gray-600 mb-4 sm:mb-6 text-sm sm:text-base">Otomobilin İç Mekanı, Koltuklar, Torpido ve Konsolunun Tam Temizliği</p>
          <div class="text-xl sm:text-2xl font-bold text-green-600">₺80</div>
        </div>

        <div class="bg-white rounded-2xl p-6 lg:p-8 card-hover shadow-lg">
          <div class="w-12 h-12 sm:w-16 sm:h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4 sm:mb-6">
            <i class="fas fa-gem text-xl sm:text-2xl text-purple-600"></i>
          </div>
          <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-3 sm:mb-4">Tam Detaylandırma</h3>
          <p class="text-gray-600 mb-4 sm:mb-6 text-sm sm:text-base">Dış Yıkama, İç Temizlik ve Gövde Cilası Dahil Tam Hizmet</p>
          <div class="text-xl sm:text-2xl font-bold text-purple-600">₺150</div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Us Section -->
  <!-- Farsça: بخش درباره ما. -->
  <!-- Türkçe: Hakkımızda Bölümü. -->
  <!-- English: About Us Section. -->
  <section id="about" class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <section class="text-center mb-16 animate-fade-in-up">
        <h2 class="text-5xl font-bold text-gray-800 mb-6">
          <span class="text-gradient">CarWash</span> Hakkında
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          Türkiye'nin en iyi online araç yıkama rezervasyon platformu olarak, araç bakımını kolay, hızlı ve güvenilir hale getiriyoruz.
        </p>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
        <div class="animate-fade-in-up">
          <img src="../backend/auth/uploads/pic04.jpg" alt="Our Mission" class="rounded-2xl shadow-xl w-full h-auto object-cover">
        </div>
        <div class="space-y-6 animate-fade-in-up" style="animation-delay: 0.2s;">
          <h3 class="text-3xl font-bold text-gray-800">Misyonumuz</h3>
          <p class="text-lg text-gray-700 leading-relaxed">
            Müşterilerimize en yüksek kalitede araç yıkama ve detaylandırma hizmetlerini, yenilikçi bir online rezervasyon deneyimiyle sunmaktır. Zamanınızın değerli olduğunu biliyor, bu yüzden hızlı, güvenilir ve sorunsuz bir hizmet vaat ediyoruz.
          </p>
          <p class="text-lg text-gray-700 leading-relaxed">
            Çevreye duyarlı yaklaşımlarımızla, su ve enerji tasarrufu sağlayan yöntemleri benimseyerek sürdürülebilir bir gelecek için çalışıyoruz.
          </p>
        </div>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
        <div class="space-y-6 animate-fade-in-up" style="animation-delay: 0.4s;">
          <h3 class="text-3xl font-bold text-gray-800">Değerlerimiz</h3>
          <ul class="space-y-3 text-lg text-gray-700">
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Müşteri Memnuniyeti</li>
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Kalite ve Güvenilirlik</li>
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Yenilikçilik</li>
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Çevreye Duyarlılık</li>
            <li class="flex items-center"><i class="fas fa-check-circle text-blue-600 mr-3"></i> Profesyonellik</li>
          </ul>
        </div>
        <div class="animate-fade-in-up" style="animation-delay: 0.6s;">
          <img src="../backend/auth/uploads/pic02.jpg" alt="Our Values" class="rounded-2xl shadow-xl w-full h-auto object-cover">
        </div>
      </section>

      <section class="text-center py-16 hero-gradient text-white rounded-2xl shadow-xl animate-fade-in-up" style="animation-delay: 0.8s;">
        <h3 class="text-4xl font-bold mb-4">Neden CarWash'ı Seçmelisiniz?</h3>
        <p class="text-xl text-gray-200 max-w-3xl mx-auto mb-8">
          CarWash, size sadece bir araç yıkama hizmeti sunmakla kalmaz, aynı zamanda zamanınızı ve enerjinizi koruyan bir deneyim sunar.
        </p>
        <div class="flex flex-wrap justify-center gap-8">
          <div class="flex flex-col items-center">
            <i class="fas fa-clock text-5xl mb-3"></i>
            <p class="font-bold text-lg">Hızlı Rezervasyon</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-shield-alt text-5xl mb-3"></i>
            <p class="font-bold text-lg">Güvenli Ödeme</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-star text-5xl mb-3"></i>
            <p class="font-bold text-lg">Üstün Kalite</p>
          </div>
          <div class="flex flex-col items-center">
            <i class="fas fa-map-marker-alt text-5xl mb-3"></i>
            <p class="font-bold text-lg">Yaygın Ağ</p>
          </div>
        </div>
      </section>
    </div>
  </section>

  <!-- Testimonials Section -->
  <!-- Farsça: بخش نظرات مشتریان. -->
  <!-- Türkçe: Müşteri Yorumları Bölümü. -->
  <!-- English: Testimonials Section. -->
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-gray-800 mb-4">Müşteri Yorumları</h2>
        <p class="text-xl text-gray-600">CarWash Hizmetlerinden Müşterilerimizin Deneyimleri</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white rounded-2xl p-8 shadow-lg">
          <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
              A
            </div>
            <div class="ml-4">
              <h4 class="font-bold text-gray-800">Ali Yılmaz</h4>
              <div class="flex text-yellow-400">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600">"Mükemmel hizmet ve hız. Profesyonel ekip ve uygun fiyat. Kesinlikle tekrar kullanacağım."</p>
        </div>

        <div class="bg-white rounded-2xl p-8 shadow-lg">
          <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold">
              M
            </div>
            <div class="ml-4">
              <h4 class="font-bold text-gray-800">Merve Kaya</h4>
              <div class="flex text-yellow-400">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600">"İlk kez online araç yıkama hizmetini kullandım. İş kalitesinden gerçekten memnunum."</p>
        </div>

        <div class="bg-white rounded-2xl p-8 shadow-lg">
          <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center text-white font-bold">
              R
            </div>
            <div class="ml-4">
              <h4 class="font-bold text-gray-800">Recep Demir</h4>
              <div class="flex text-yellow-400">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600">"Araç yıkama rezervasyonu için en iyi platform. Kolay, hızlı ve kaliteli. Tavsiye ederim."</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <!-- Farsça: بخش تماس. -->
  <!-- Türkçe: İletişim Bölümü. -->
  <!-- English: Contact Section. -->
  <section id="contact" class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <section class="text-center mb-16 animate-fade-in-up">
        <h2 class="text-5xl font-bold text-gray-800 mb-6">
          Bize <span class="text-gradient">Ulaşın</span>
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          Sorularınız, geri bildirimleriniz veya destek talepleriniz için bizimle iletişime geçmekten çekinmeyin.
        </p>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
        <!-- Contact Information -->
        <!-- Farsça: اطلاعات تماس. -->
        <!-- Türkçe: İletişim Bilgileri. -->
        <!-- English: Contact Information. -->
        <div class="bg-gray-50 rounded-2xl p-8 shadow-lg animate-fade-in-up">
          <h3 class="text-3xl font-bold text-gray-800 mb-6">İletişim Bilgileri</h3>
          <div class="space-y-6">
            <div class="flex items-center">
              <i class="fas fa-map-marker-alt text-blue-600 text-2xl mr-4"></i>
              <div>
                <h4 class="font-bold text-gray-800">Adres</h4>
                <p class="text-gray-600">Örnek Mah. Örnek Cad. No: 123, İstanbul, Türkiye</p>
              </div>
            </div>
            <div class="flex items-center">
              <i class="fas fa-phone text-blue-600 text-2xl mr-4"></i>
              <div>
                <h4 class="font-bold text-gray-800">Telefon</h4>
                <p class="text-gray-600">0212-12345678</p>
              </div>
            </div>
            <div class="flex items-center">
              <i class="fas fa-envelope text-blue-600 text-2xl mr-4"></i>
              <div>
                <h4 class="font-bold text-gray-800">E-posta</h4>
                <p class="text-gray-600">info@carwash.com</p>
              </div>
            </div>
            <div class="flex items-center">
              <i class="fas fa-clock text-blue-600 text-2xl mr-4"></i>
              <div>
                <h4 class="font-bold text-gray-800">Çalışma Saatleri</h4>
                <p class="text-gray-600">Pazartesi - Cumartesi: 09:00 - 18:00</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Form -->
        <!-- Farsça: فرم تماس. -->
        <!-- Türkçe: İletişim Formu. -->
        <!-- English: Contact Form. -->
        <div class="bg-gray-50 rounded-2xl p-8 shadow-lg animate-fade-in-up" style="animation-delay: 0.2s;">
          <h3 class="text-3xl font-bold text-gray-800 mb-6">Mesaj Gönderin</h3>
          <form class="space-y-6">
            <div>
              <label for="contactName" class="block text-sm font-bold text-gray-700 mb-2">Adınız Soyadınız</label>
              <input type="text" id="contactName" placeholder="Adınız Soyadınız" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="contactEmail" class="block text-sm font-bold text-gray-700 mb-2">E-posta Adresiniz</label>
              <input type="email" id="contactEmail" placeholder="email@example.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="contactSubject" class="block text-sm font-bold text-gray-700 mb-2">Konu</label>
              <input type="text" id="contactSubject" placeholder="Konu" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="contactMessage" class="block text-sm font-bold text-gray-700 mb-2">Mesajınız</label>
              <textarea id="contactMessage" rows="5" placeholder="Mesajınızı buraya yazın..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:from-purple-700 hover:to-blue-700 transition-all">
              <i class="fas fa-paper-plane mr-2"></i>Mesajı Gönder
            </button>
          </form>
        </div>
      </section>

      <!-- Map Section -->
      <!-- Farsça: بخش نقشه. -->
      <!-- Türkçe: Harita Bölümü. -->
      <!-- English: Map Section. -->
      <section class="mb-16 animate-fade-in-up" style="animation-delay: 0.4s;">
        <h3 class="text-3xl font-bold text-gray-800 mb-6 text-center">Konumumuz</h3>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3010.7600000000007!2d28.97835891526708!3d41.00823797929982!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cac24e2b2b2b2b%3A0x123456789abcdef!2sIstanbul%2C%20Turkey!5e0!3m2!1sen!2sus!4v1678901234567!5m2!1sen!2sus"
            width="100%"
            height="450"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            class="rounded-2xl"
          ></iframe>
        </div>
      </section>
    </div>
  </section>

  <!-- Register Section -->
  <!-- Farsça: بخش ثبت نام. -->
  <!-- Türkçe: Kayıt Bölümü. -->
  <!-- English: Register Section. -->
  <section id="register" class="py-20 hero-gradient text-white">
    <div class="container mx-auto px-4">
      <section class="text-center mb-16 animate-fade-in-up">
        <h2 class="text-5xl font-bold mb-6">
          <span class="text-white">CarWash'a</span> Kayıt Olun
        </h2>
        <p class="text-xl text-gray-200 max-w-3xl mx-auto">
          Hizmetlerimizden yararlanmak veya hizmet sağlayıcımız olmak için hemen kayıt olun!
        </p>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Customer Registration -->
        <!-- Farsça: ثبت نام مشتری. -->
        <!-- Türkçe: Müşteri Kaydı. -->
        <!-- English: Customer Registration. -->
        <div class="bg-white rounded-2xl p-8 shadow-lg animate-fade-in-up">
          <div class="flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mx-auto mb-6">
            <i class="fas fa-user-plus text-4xl text-blue-600"></i>
          </div>
          <h3 class="text-3xl font-bold text-gray-800 mb-4 text-center">Müşteri Olarak Kayıt Ol</h3>
          <p class="text-gray-600 mb-8 text-center">
            Aracınızı kolayca yıkatmak için hemen bir müşteri hesabı oluşturun.
          </p>
          <form class="space-y-6">
            <div>
              <label for="customerName" class="block text-sm font-bold text-gray-700 mb-2">Adınız Soyadınız</label>
              <input type="text" id="customerName" placeholder="Adınız Soyadınız" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="customerEmail" class="block text-sm font-bold text-gray-700 mb-2">E-posta Adresiniz</label>
              <input type="email" id="customerEmail" placeholder="email@example.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="customerPassword" class="block text-sm font-bold text-gray-700 mb-2">Şifre</label>
              <input type="password" id="customerPassword" placeholder="Şifreniz" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="customerPasswordConfirm" class="block text-sm font-bold text-gray-700 mb-2">Şifre Tekrar</label>
              <input type="password" id="customerPasswordConfirm" placeholder="Şifrenizi tekrar girin" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition-colors">
              <i class="fas fa-user-plus mr-2"></i>Müşteri Kaydı Yap
            </button>
          </form>
          <p class="text-center text-gray-600 mt-6">
            Zaten hesabınız var mı? <a href="../backend/auth/login.php" class="text-blue-600 hover:underline font-medium">Giriş Yapın</a>
          </p>
        </div>

        <!-- Service Provider Registration -->
        <!-- Farsça: ثبت نام ارائه‌دهنده خدمات. -->
        <!-- Türkçe: Hizmet Sağlayıcı Kaydı. -->
        <!-- English: Service Provider Registration. -->
        <div class="bg-white rounded-2xl p-8 shadow-lg animate-fade-in-up" style="animation-delay: 0.2s;">
          <div class="flex items-center justify-center w-20 h-20 bg-purple-100 rounded-full mx-auto mb-6">
            <i class="fas fa-store text-4xl text-purple-600"></i>
          </div>
          <h3 class="text-3xl font-bold text-gray-800 mb-4 text-center">Hizmet Sağlayıcı Olarak Kayıt Ol</h3>
          <p class="text-gray-600 mb-8 text-center">
            İşletmenizi büyütmek için CarWash ağına katılın.
          </p>
          <form class="space-y-6">
            <div>
              <label for="providerCompanyName" class="block text-sm font-bold text-gray-700 mb-2">Şirket Adı</label>
              <input type="text" id="providerCompanyName" placeholder="Şirketinizin Adı" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="providerContactName" class="block text-sm font-bold text-gray-700 mb-2">Yetkili Adı Soyadı</label>
              <input type="text" id="providerContactName" placeholder="Yetkili Adı Soyadı" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="providerEmail" class="block text-sm font-bold text-gray-700 mb-2">E-posta Adresi</label>
              <input type="email" id="providerEmail" placeholder="email@company.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="providerPhone" class="block text-sm font-bold text-gray-700 mb-2">Telefon Numarası</label>
              <input type="tel" id="providerPhone" placeholder="05XX XXX XX XX" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="providerPassword" class="block text-sm font-bold text-gray-700 mb-2">Şifre</label>
              <input type="password" id="providerPassword" placeholder="Şifreniz" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label for="providerPasswordConfirm" class="block text-sm font-bold text-gray-700 mb-2">Şifre Tekrar</label>
              <input type="password" id="providerPasswordConfirm" placeholder="Şifrenizi tekrar girin" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:from-purple-700 hover:to-blue-700 transition-all">
              <i class="fas fa-store mr-2"></i>Hizmet Sağlayıcı Kaydı Yap
            </button>
          </form>
          <p class="text-center text-gray-600 mt-6">
            Zaten hesabınız var mı? <a href="../backend/auth/login.php" class="text-blue-600 hover:underline font-medium">Giriş Yapın</a>
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <!-- Farsça: پاورقی. -->
  <!-- Türkçe: Altbilgi. -->
  <!-- English: Footer. -->
  <footer class="bg-gray-800 text-white py-12">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
          <div class="flex items-center space-x-2 mb-4">
            <i class="fas fa-car text-2xl text-blue-400"></i>
            <h3 class="text-xl font-bold">CarWash</h3>
          </div>
          <p class="text-gray-300">Türkiye'nin En İyi Online Araç Yıkama Rezervasyon Platformu</p>
        </div>

        <div>
          <h4 class="font-bold mb-4">Hizmetler</h4>
          <ul class="space-y-2 text-gray-300">
            <li><a href="#services" class="hover:text-white transition-colors">Dış Yıkama</a></li>
            <li><a href="#services" class="hover:text-white transition-colors">İç Temizlik</a></li>
            <li><a href="#services" class="hover:text-white transition-colors">Tam Detaylandırma</a></li>
          </ul>
        </div>

        <div>
          <h4 class="font-bold mb-4">Destek</h4>
          <ul class="space-y-2 text-gray-300">
            <li><a href="#" class="hover:text-white transition-colors">Sık Sorulan Sorular</a></li>
            <li><a href="#contact" class="hover:text-white transition-colors">Bize Ulaşın</a></li>
            <li><a href="#" class="hover:text-white transition-colors">Kullanım Kılavuzu</a></li>
          </ul>
        </div>

        <div>
          <h4 class="font-bold mb-4">İletişim</h4>
          <div class="space-y-2 text-gray-300">
            <p><i class="fas fa-phone mr-2"></i> 0212-12345678</p>
            <p><i class="fas fa-envelope mr-2"></i> info@carwash.com</p>
            <p><i class="fas fa-map-marker-alt mr-2"></i> İstanbul, Türkiye</p>
          </div>
        </div>
      </div>

      <div class="border-t border-gray-700 mt-8 pt-8 text-center">
        <p class="text-gray-300">&copy; 2024 CarWash. Tüm Hakları Saklıdır.</p>
      </div>
    </div>
  </footer>

  <!-- Scroll to Top Button -->
  <!-- Farsça: دکمه اسکرول به بالا. -->
  <!-- Türkçe: Yukarı kaydırma düğmesi. -->
  <!-- English: Scroll to top button. -->
  <button id="scrollToTop" class="fixed bottom-4 right-4 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-all opacity-0 invisible transform scale-0 hover:scale-110 z-50">
    <i class="fas fa-chevron-up text-lg"></i>
  </button>

  <script>
    // Farsça: تابع برای تغییر وضعیت منوی موبایل.
    // Türkçe: Mobil menüyü açıp kapatmak için fonksiyon.
    // English: Function to toggle mobile menu.
    function toggleMobileMenu() {
      const menu = document.getElementById('mobileMenu');
      menu.classList.toggle('hidden');
    }

    // Scroll to Top Button Functionality
    // Farsça: عملکرد دکمه اسکرول به بالا.
    // Türkçe: Yukarı kaydırma düğmesi işlevselliği.
    // English: Scroll to top button functionality.
    const scrollToTopBtn = document.getElementById('scrollToTop');
    
    // Show/hide button based on scroll position
    // Farsça: نمایش/پنهان کردن دکمه بر اساس موقعیت اسکرول.
    // Türkçe: Kaydırma konumuna göre düğmeyi göster/gizle.
    // English: Show/hide button based on scroll position.
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        scrollToTopBtn.classList.remove('opacity-0', 'invisible', 'scale-0');
        scrollToTopBtn.classList.add('opacity-70');
      } else {
        scrollToTopBtn.classList.add('opacity-0', 'invisible', 'scale-0');
        scrollToTopBtn.classList.remove('opacity-70');
      }
    });

    // Scroll to top when button is clicked
    // Farsça: هنگام کلیک روی دکمه، به بالا اسکرول کن.
    // Türkçe: Düğmeye tıklandığında yukarı kaydır.
    // English: Scroll to top when button is clicked.
    scrollToTopBtn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
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
    });
  </script>

</body>
</html>
