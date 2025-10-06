<?php
// Farsça: این فایل شامل کدهای HTML صفحه ثبت نام کارواش است.
// Türkçe: Bu dosya, araç yıkama işletmesi kayıt sayfasının HTML kodlarını içermektedir.
// English: This file contains the HTML code for the car wash business registration page.
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - Car Wash İşletme Kayıt</title>
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

    /* Farsça: استایل کانتینر فرم با پس‌زمینه شفاف و فیلتر بلور. */
    /* Türkçe: Şeffaf arka plan ve bulanıklık filtresi ile form kapsayıcı stili. */
    /* English: Form container style with transparent background and blur filter. */
    .form-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
    }

    /* Farsça: استایل ورودی‌ها هنگام فوکوس: بزرگنمایی و سایه. */
    /* Türkçe: Odaklanıldığında girişlerin stili: büyütme ve gölge. */
    /* English: Input style on focus: scale and shadow. */
    .input-focus:focus {
      transform: scale(1.02);
      box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
    }

    /* Farsça: جداکننده بخش‌ها. */
    /* Türkçe: Bölüm ayırıcı. */
    /* English: Section divider. */
    .section-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen p-4">

  <!-- Header -->
  <!-- Farsça: این بخش سربرگ صفحه را شامل می‌شود. -->
  <!-- Türkçe: Bu bölüm sayfa başlığını içerir. -->
  <!-- English: This section includes the page header. -->
  <header class="bg-white shadow-lg mb-8">
    <div class="container mx-auto px-4 py-3">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-2">
          <i class="fas fa-car text-2xl text-blue-600"></i>
          <h1 class="text-xl font-bold text-blue-600">CarWash</h1>
        </div>
        <a href="../index.php" class="text-gray-600 hover:text-blue-600 transition-colors">
          <i class="fas fa-home mr-2"></i>Ana Sayfa
        </a>
      </div>
    </div>
  </header>

  <!-- Registration Form -->
  <!-- Farsça: این بخش شامل فرم ثبت نام کارواش است. -->
  <!-- Türkçe: Bu bölüm araç yıkama işletmesi kayıt formunu içerir. -->
  <!-- English: This section contains the car wash business registration form. -->
  <div class="max-w-5xl mx-auto">
    <div class="form-container rounded-2xl shadow-2xl p-8 animate-fade-in-up">
      <!-- Header -->
      <!-- Farsça: سربرگ فرم ثبت نام. -->
      <!-- Türkçe: Kayıt formunun başlığı. -->
      <!-- English: Header for the registration form. -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4 animate-slide-in">
          <i class="fas fa-store text-3xl text-white"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Car Wash İşletme Kayıt Formu</h1>
        <p class="text-gray-600">İşletmenizi kaydedin ve hizmetlerinizi müşterilerimize sunun</p>
      </div>

      <form action="provider/register.php" method="POST" enctype="multipart/form-data" class="space-y-8">
        <!-- Business Information Section -->
        <!-- Farsça: بخش اطلاعات کسب و کار. -->
        <!-- Türkçe: İşletme Bilgileri bölümü. -->
        <!-- English: Business Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.1s">
          <div class="flex items-center mb-6">
            <i class="fas fa-building text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">İşletme Bilgileri</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-signature mr-2"></i>İşletme Adı *
              </label>
              <input
                type="text"
                name="business_name"
                placeholder="İşletmenizin tam adını girin"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-envelope mr-2"></i>İşletme E-postası *
              </label>
              <input
                type="email"
                name="email"
                placeholder="ornek@isyeri.com"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-phone mr-2"></i>İşletme Telefonu *
              </label>
              <input
                type="tel"
                name="phone"
                placeholder="05XX XXX XX XX"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-lock mr-2"></i>Şifre *
              </label>
              <div class="relative">
                <input
                  type="password"
                  name="password"
                  id="password"
                  placeholder="Güçlü bir şifre belirleyin"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 pr-12"
                >
                <button
                  type="button"
                  onclick="togglePassword()"
                  class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-blue-600 transition-colors"
                >
                  <i class="fas fa-eye" id="passwordToggle"></i>
                </button>
              </div>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-id-card mr-2"></i>Vergi Numarası *
              </label>
              <input
                type="text"
                name="tax_number"
                placeholder="Vergi numaranızı girin"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-certificate mr-2"></i>Ruhsat Numarası *
              </label>
              <input
                type="text"
                name="license_number"
                placeholder="İşyeri ruhsat numarası"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>
          </div>
        </div>

        <!-- Owner Information -->
        <!-- Farsça: بخش اطلاعات صاحب کسب و کار. -->
        <!-- Türkçe: İşletme Sahibi Bilgileri bölümü. -->
        <!-- English: Owner Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.2s">
          <div class="flex items-center mb-6">
            <i class="fas fa-user-tie text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">İşletme Sahibi Bilgileri</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-user mr-2"></i>Sahip Adı Soyadı *
              </label>
              <input
                type="text"
                name="owner_name"
                placeholder="İşletme sahibinin tam adı"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-id-badge mr-2"></i>TC Kimlik Numarası *
              </label>
              <input
                type="text"
                name="owner_id"
                placeholder="11 haneli TC kimlik numarası"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-mobile-alt mr-2"></i>Cep Telefonu *
              </label>
              <input
                type="tel"
                name="owner_phone"
                placeholder="05XX XXX XX XX"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-birthday-cake mr-2"></i>Doğum Tarihi
              </label>
              <input
                type="date"
                name="birth_date"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>
          </div>
        </div>

        <!-- Location Information -->
        <!-- Farsça: بخش اطلاعات مکان. -->
        <!-- Türkçe: Konum Bilgileri bölümü. -->
        <!-- English: Location Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.3s">
          <div class="flex items-center mb-6">
            <i class="fas fa-map-marker-alt text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Konum Bilgileri</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-city mr-2"></i>Şehir *
              </label>
              <select
                name="city"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
                <option value="">Şehir seçin</option>
                <option value="istanbul">İstanbul</option>
                <option value="ankara">Ankara</option>
                <option value="izmir">İzmir</option>
                <option value="bursa">Bursa</option>
                <option value="antalya">Antalya</option>
                <option value="adana">Adana</option>
                <option value="konya">Konya</option>
                <option value="gaziantep">Gaziantep</option>
                <option value="kocaeli">Kocaeli</option>
                <option value="mersin">Mersin</option>
                <option value="diger">Diğer</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-map mr-2"></i>İlçe *
              </label>
              <input
                type="text"
                name="district"
                placeholder="İlçe adı"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-address-card mr-2"></i>Adres Detayları *
              </label>
              <textarea
                name="address"
                rows="3"
                placeholder="Sokak, mahalle, apartman numarası, yakın yerler vb."
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Services and Pricing -->
        <!-- Farsça: بخش خدمات و قیمت‌گذاری. -->
        <!-- Türkçe: Hizmetler ve Fiyatlar bölümü. -->
        <!-- English: Services and Pricing Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.4s">
          <div class="flex items-center mb-6">
            <i class="fas fa-wrench text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Hizmetler ve Fiyatlar</h2>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Dış Yıkama Fiyatı (₺)</label>
                <input
                  type="number"
                  name="exterior_price"
                  placeholder="50"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
                >
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <input type="checkbox" name="services[]" value="exterior" checked class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">Sunuyorum</span>
                </label>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">İç Temizlik Fiyatı (₺)</label>
                <input
                  type="number"
                  name="interior_price"
                  placeholder="80"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
                >
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <input type="checkbox" name="services[]" value="interior" checked class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">Sunuyorum</span>
                </label>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Tam Detaylandırma Fiyatı (₺)</label>
                <input
                  type="number"
                  name="detailing_price"
                  placeholder="150"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
                >
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <input type="checkbox" name="services[]" value="detailing" class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">Sunuyorum</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Business Details -->
        <!-- Farsça: بخش جزئیات کسب و کار. -->
        <!-- Türkçe: İşletme Detayları bölümü. -->
        <!-- English: Business Details Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.5s">
          <div class="flex items-center mb-6">
            <i class="fas fa-info-circle text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">İşletme Detayları</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Çalışma Saatleri</label>
              <div class="grid grid-cols-2 gap-2">
                <input
                  type="time"
                  name="opening_time"
                  placeholder="Açılış"
                  class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                >
                <input
                  type="time"
                  name="closing_time"
                  placeholder="Kapanış"
                  class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                >
              </div>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Kapasite (Günlük araç sayısı)</label>
              <input
                type="number"
                name="capacity"
                placeholder="20"
                min="1"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              >
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">Özel Hizmetler/Açıklama</label>
              <textarea
                name="description"
                rows="3"
                placeholder="İşletmeniz hakkında kısa bilgi, özel hizmetler, avantajlar vb."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Images Upload -->
        <!-- Farsça: بخش بارگذاری تصاویر. -->
        <!-- Türkçe: Fotoğraf Yükleme bölümü. -->
        <!-- English: Images Upload Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.6s">
          <div class="flex items-center mb-6">
            <i class="fas fa-images text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Fotoğraflar</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-user-circle mr-2"></i>Profil Fotoğrafı
              </label>
              <input
                type="file"
                name="profile_image"
                accept="image/*"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              >
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-building mr-2"></i>İşletme Logosu
              </label>
              <input
                type="file"
                name="logo_image"
                accept="image/*"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              >
            </div>
          </div>
        </div>

        <!-- Terms and Submit -->
        <!-- Farsça: بخش شرایط و ارسال فرم. -->
        <!-- Türkçe: Şartlar ve Gönder bölümü. -->
        <!-- English: Terms and Submit Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.7s">
          <div class="section-divider my-6"></div>

          <div class="flex items-start mb-6">
            <input type="checkbox" name="terms" required class="mt-1 mr-3 text-blue-600 focus:ring-blue-500">
            <p class="text-sm text-gray-600">
              <a href="#" class="text-blue-600 hover:underline">Kullanım Şartları</a>,
              <a href="#" class="text-blue-600 hover:underline">Gizlilik Politikası</a> ve
              <a href="#" class="text-blue-600 hover:underline">İşletme Sözleşmesi</a>'ni
              okudum ve kabul ediyorum. *
            </p>
          </div>

          <button
            type="submit"
            class="w-full gradient-bg text-white py-4 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300"
          >
            <i class="fas fa-store mr-2"></i>İşletmemi Kaydet
          </button>
        </div>
      </form>

      <!-- Login Link -->
      <!-- Farsça: لینک ورود به سیستم. -->
      <!-- Türkçe: Giriş bağlantısı. -->
      <!-- English: Login Link. -->
      <div class="text-center mt-8 animate-slide-in" style="animation-delay: 0.8s">
        <p class="text-gray-600 mb-4">Zaten hesabınız var mı?</p>
        <a
          href="../auth/login.php"
          class="inline-block gradient-bg text-white px-8 py-3 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300"
        >
          <i class="fas fa-sign-in-alt mr-2"></i>Giriş Yap
        </a>
      </div>
    </div>
  </div>

  <script>
    // Farsça: تابع برای تغییر دید رمز عبور.
    // Türkçe: Şifre görünürlüğünü değiştirmek için fonksiyon. */
    // English: Function to toggle password visibility. */
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('passwordToggle');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }

    // Add focus animations
    // Farsça: اضافه کردن انیمیشن‌های فوکوس به فیلدهای ورودی.
    // Türkçe: Giriş alanlarına odaklanma animasyonları ekler.
    // English: Adds focus animations to input fields.
    document.querySelectorAll('input, select, textarea').forEach(input => {
      input.addEventListener('focus', function() {
        this.style.transform = 'scale(1.02)';
        this.style.boxShadow = '0 0 20px rgba(102, 126, 234, 0.3)';
      });

      input.addEventListener('blur', function() {
        this.style.transform = 'scale(1)';
        this.style.boxShadow = 'none';
      });
    });
  </script>

</body>
</html>
