<?php
// Farsça: این فایل شامل کدهای HTML صفحه ثبت نام مشتری است.
// Türkçe: Bu dosya, müşteri kayıt sayfasının HTML kodlarını içermektedir.
// English: This file contains the HTML code for the customer registration page.
?>
<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - Müşteri Kayıt</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Farsça: انیمیشن برای ظاهر شدن تدریجی عناصر از پایین به بالا. */
    /* Türkçe: Öğelerin aşağıdan yukarıya doğru yavaşça görünmesi için animasyon. */
    /* English: Animation for elements to fade in from bottom to top. */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Farsça: انیمیشن برای ورود تدریجی عناصر از چپ به راست. */
    /* Türkçe: Öğelerin soldan sağa doğru yavaşça kayarak gelmesi için animasyon. */
    /* English: Animation for elements to slide in from left to right. */
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
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
  <!-- Farsça: این بخش شامل فرم ثبت نام مشتری است. -->
  <!-- Türkçe: Bu bölüm müşteri kayıt formunu içerir. -->
  <!-- English: This section contains the customer registration form. -->
  <div class="max-w-4xl mx-auto">
    <div class="form-container rounded-2xl shadow-2xl p-8 animate-fade-in-up">
      <!-- Header -->
      <!-- Farsça: سربرگ فرم ثبت نام. -->
      <!-- Türkçe: Kayıt formunun başlığı. -->
      <!-- English: Header for the registration form. -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4 animate-slide-in">
          <i class="fas fa-user-plus text-3xl text-white"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Müşteri Kayıt Formu</h1>
        <p class="text-gray-600">Hesabınızı oluşturun ve araç yıkama hizmetlerimizden yararlanın</p>
      </div>

      <form action="customer/register.php" method="POST" class="space-y-8">
        <!-- Personal Information Section -->
        <!-- Farsça: بخش اطلاعات شخصی. -->
        <!-- Türkçe: Kişisel Bilgiler bölümü. -->
        <!-- English: Personal Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.1s">
          <div class="flex items-center mb-6">
            <i class="fas fa-user text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Kişisel Bilgiler</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-signature mr-2"></i>Ad Soyad *
              </label>
              <input
                type="text"
                name="full_name"
                placeholder="Adınızı ve soyadınızı girin"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-envelope mr-2"></i>E-posta Adresi *
              </label>
              <input
                type="email"
                name="email"
                placeholder="ornek@email.com"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-phone mr-2"></i>Telefon Numarası *
              </label>
              <input
                type="tel"
                name="phone"
                placeholder="05XX XXX XX XX"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
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
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 pr-12">
                <button
                  type="button"
                  onclick="togglePassword()"
                  class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-blue-600 transition-colors">
                  <i class="fas fa-eye" id="passwordToggle"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Address Information -->
        <!-- Farsça: بخش اطلاعات آدرس. -->
        <!-- Türkçe: Adres Bilgileri bölümü. -->
        <!-- English: Address Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.2s">
          <div class="flex items-center mb-6">
            <i class="fas fa-map-marker-alt text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Adres Bilgileri</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-city mr-2"></i>Şehir *
              </label>
              <select
                name="city"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
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

            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-address-card mr-2"></i>Adres Detayları
              </label>
              <textarea
                name="address"
                rows="3"
                placeholder="Sokak, mahalle, apartman numarası vb."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"></textarea>
            </div>
          </div>
        </div>

        <!-- Car Information Section -->
        <!-- Farsça: بخش اطلاعات خودرو. -->
        <!-- Türkçe: Araç Bilgileri bölümü. -->
        <!-- English: Car Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.3s">
          <div class="flex items-center mb-6">
            <i class="fas fa-car text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Araç Bilgileri</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-car-side mr-2"></i>Marka *
              </label>
              <select
                name="car_brand"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
                <option value="">Marka seçin</option>
                <option value="toyota">Toyota</option>
                <option value="honda">Honda</option>
                <option value="ford">Ford</option>
                <option value="volkswagen">Volkswagen</option>
                <option value="bmw">BMW</option>
                <option value="mercedes">Mercedes-Benz</option>
                <option value="audi">Audi</option>
                <option value="renault">Renault</option>
                <option value="fiat">Fiat</option>
                <option value="hyundai">Hyundai</option>
                <option value="nissan">Nissan</option>
                <option value="diger">Diğer</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-car mr-2"></i>Model *
              </label>
              <input
                type="text"
                name="car_model"
                placeholder="Örn: Corolla, Civic, Focus"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-calendar mr-2"></i>Model Yılı *
              </label>
              <select
                name="car_year"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
                <option value="">Yıl seçin</option>
                <option value="2024">2024</option>
                <option value="2023">2023</option>
                <option value="2022">2022</option>
                <option value="2021">2021</option>
                <option value="2020">2020</option>
                <option value="2019">2019</option>
                <option value="2018">2018</option>
                <option value="2017">2017</option>
                <option value="2016">2016</option>
                <option value="2015">2015</option>
                <option value="eski">2015'ten eski</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-palette mr-2"></i>Renk
              </label>
              <input
                type="text"
                name="car_color"
                placeholder="Araç rengi"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-id-card mr-2"></i>Plaka Numarası
              </label>
              <input
                type="text"
                name="license_plate"
                placeholder="34 ABC 123"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
            </div>
          </div>
        </div>

        <!-- Preferences -->
        <!-- Farsça: بخش ترجیحات. -->
        <!-- Türkçe: Tercihler bölümü. -->
        <!-- English: Preferences Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.4s">
          <div class="flex items-center mb-6">
            <i class="fas fa-sliders-h text-blue-600 text-xl mr-3"></i>
            <h2 class="text-2xl font-bold text-gray-800">Tercihler</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-3">Bildirim Tercihleri</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="checkbox" name="notifications[]" value="email" checked class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">E-posta bildirimleri</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="notifications[]" value="sms" class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">SMS bildirimleri</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="notifications[]" value="push" class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">Push bildirimleri</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-3">Hangi hizmetleri tercih edersiniz?</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="checkbox" name="services[]" value="exterior" checked class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">Dış yıkama</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="services[]" value="interior" checked class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">İç temizlik</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" name="services[]" value="detailing" class="mr-2 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600">Tam detaylandırma</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Terms and Submit -->
        <!-- Farsça: بخش شرایط و ارسال فرم. -->
        <!-- Türkçe: Şartlar ve Gönder bölümü. -->
        <!-- English: Terms and Submit Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.5s">
          <div class="section-divider my-6"></div>

          <div class="flex items-start mb-6">
            <input type="checkbox" name="terms" required class="mt-1 mr-3 text-blue-600 focus:ring-blue-500">
            <p class="text-sm text-gray-600">
              <a href="#" class="text-blue-600 hover:underline">Kullanım Şartları</a> ve
              <a href="#" class="text-blue-600 hover:underline">Gizlilik Politikası</a>'nı
              okudum ve kabul ediyorum. *
            </p>
          </div>

          <button
            type="submit"
            class="w-full gradient-bg text-white py-4 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
            <i class="fas fa-user-plus mr-2"></i>Hesabımı Oluştur
          </button>
        </div>
      </form>

      <!-- Login Link -->
      <!-- Farsça: لینک ورود به سیستم. -->
      <!-- Türkçe: Giriş bağlantısı. -->
      <!-- English: Login Link. -->
      <div class="text-center mt-8 animate-slide-in" style="animation-delay: 0.6s">
        <p class="text-gray-600 mb-4">Zaten hesabınız var mı?</p>
        <a
          href="../auth/login.php"
          class="inline-block gradient-bg text-white px-8 py-3 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
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