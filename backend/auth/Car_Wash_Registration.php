<?php
// Farsça: این فایل شامل کدهای HTML صفحه ثبت نام کارواش است.
// Türkçe: Bu dosya, araç yıkama işletmesi kayıt sayfasının HTML kodlarını içermektedir.
// English: This file contains the HTML code for the car wash business registration page.

// Set page-specific variables
$page_title = 'Car Wash İşletme Kayıt - CarWash';
$current_page = 'carwash_register';
$show_login = false; // Don't show login button on registration page

// Start session to check for error messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include header
include '../includes/header.php';
?>

<!-- Additional CSS for car wash registration page -->
<style>
  /* Custom animations for registration form */
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

  .form-container {
    background: rgba(255, 255, 255, 0.95);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
  }

  .input-focus:focus {
    transform: scale(1.02);
    box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
  }

  .section-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
  }
</style>

<!-- Registration Form -->
<div class="max-w-5xl mx-auto">
  <div class="form-container rounded-2xl shadow-2xl p-8 animate-fade-in-up">
    <!-- Header -->
    <div class="text-center mb-8">
      <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4 animate-slide-in">
        <i class="fas fa-store text-3xl text-white"></i>
      </div>
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Car Wash İşletme Kayıt Formu</h1>
      <p class="text-gray-600">İşletmenizi kaydedin ve hizmetlerinizi müşterilerimize sunun</p>
      
      <?php
      // Display error messages if any
      if (isset($_SESSION['error_message'])) {
          echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 mt-4">';
          echo '<strong>Hata:</strong> ' . $_SESSION['error_message'];
          echo '</div>';
          unset($_SESSION['error_message']); // Clear the message after displaying
      }
      
      // Display success messages if any
      if (isset($_SESSION['success_message'])) {
          echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 mt-4">';
          echo '<strong>Başarılı:</strong> ' . $_SESSION['success_message'];
          echo '</div>';
          unset($_SESSION['success_message']); // Clear the message after displaying
      }
      ?>
    </div>

  <form action="Car_Wash_Registration_process.php" method="POST" enctype="multipart/form-data" class="space-y-8">
  <label for="auto_label_33" class="sr-only">Csrf token</label><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" id="auto_label_33">
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
              <label for="auto_label_32" class="sr-only">Business name</label><input
                type="text"
                name="business_name"
                placeholder="İşletmenizin tam adını girin"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_32">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-envelope mr-2"></i>İşletme E-postası *
              </label>
              <label for="auto_label_31" class="sr-only">Email</label><input
                type="email"
                name="email"
                placeholder="ornek@isyeri.com"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_31">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-phone mr-2"></i>İşletme Telefonu *
              </label>
              <label for="auto_label_30" class="sr-only">Phone</label><input
                type="tel"
                name="phone"
                placeholder="05XX XXX XX XX"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_30">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-lock mr-2"></i>Şifre *
              </label>
              <div class="relative">
                <label for="password" class="sr-only">Password</label><input
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
                <label for="auto_label_29" class="sr-only">Tax number</label><input
                type="text"
                name="tax_number"
                placeholder="Vergi numaranızı girin"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_29">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-certificate mr-2"></i>Ruhsat Numarası *
              </label>
                <label for="auto_label_28" class="sr-only">License number</label><input
                type="text"
                name="license_number"
                placeholder="İşyeri ruhsat numarası"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_28">
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
              <label for="auto_label_27" class="sr-only">Owner name</label><input
                type="text"
                name="owner_name"
                placeholder="İşletme sahibinin tam adı"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_27">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-id-badge mr-2"></i>TC Kimlik Numarası *
              </label>
              <label for="auto_label_26" class="sr-only">Owner id</label><input
                type="text"
                name="owner_id"
                placeholder="11 haneli TC kimlik numarası"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_26">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-mobile-alt mr-2"></i>Cep Telefonu *
              </label>
              <label for="auto_label_25" class="sr-only">Owner phone</label><input
                type="tel"
                name="owner_phone"
                placeholder="05XX XXX XX XX"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_25">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-birthday-cake mr-2"></i>Doğum Tarihi
              </label>
              <label for="auto_label_24" class="sr-only">Birth date</label><input
                type="date"
                name="birth_date"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_24">
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
              <label for="auto_label_23" class="sr-only">City</label><select
                name="city"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_23">
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
              <label for="auto_label_22" class="sr-only">District</label><input
                type="text"
                name="district"
                placeholder="İlçe adı"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_22">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-address-card mr-2"></i>Adres Detayları *
              </label>
              <label for="auto_label_21" class="sr-only">Address</label><textarea
                name="address"
                rows="3"
                placeholder="Sokak, mahalle, apartman numarası, yakın yerler vb."
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_21"></textarea>
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
                <label for="auto_label_20" class="sr-only">Exterior price</label><input
                  type="number"
                  name="exterior_price"
                  placeholder="50"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
                 id="auto_label_20">
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <label for="auto_label_19" class="sr-only">Services[]</label><input type="checkbox" name="services[]" value="exterior" checked class="mr-2 text-blue-600 focus:ring-blue-500" id="auto_label_19">
                  <span class="text-sm text-gray-600">Sunuyorum</span>
                </label>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">İç Temizlik Fiyatı (₺)</label>
                <label for="auto_label_18" class="sr-only">Interior price</label><input
                  type="number"
                  name="interior_price"
                  placeholder="80"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
                 id="auto_label_18">
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <label for="auto_label_17" class="sr-only">Services[]</label><input type="checkbox" name="services[]" value="interior" checked class="mr-2 text-blue-600 focus:ring-blue-500" id="auto_label_17">
                  <span class="text-sm text-gray-600">Sunuyorum</span>
                </label>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Tam Detaylandırma Fiyatı (₺)</label>
                <label for="auto_label_16" class="sr-only">Detailing price</label><input
                  type="number"
                  name="detailing_price"
                  placeholder="150"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
                 id="auto_label_16">
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <label for="auto_label_15" class="sr-only">Services[]</label><input type="checkbox" name="services[]" value="detailing" class="mr-2 text-blue-600 focus:ring-blue-500" id="auto_label_15">
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
                <label for="auto_label_14" class="sr-only">Opening time</label><input
                  type="time"
                  name="opening_time"
                  placeholder="Açılış"
                  class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                 id="auto_label_14">
                <label for="auto_label_13" class="sr-only">Closing time</label><input
                  type="time"
                  name="closing_time"
                  placeholder="Kapanış"
                  class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                 id="auto_label_13">
              </div>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Kapasite (Günlük araç sayısı)</label>
              <label for="auto_label_12" class="sr-only">Capacity</label><input
                type="number"
                name="capacity"
                placeholder="20"
                min="1"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_12">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">Özel Hizmetler/Açıklama</label>
              <label for="auto_label_11" class="sr-only">Description</label><textarea
                name="description"
                rows="3"
                placeholder="İşletmeniz hakkında kısa bilgi, özel hizmetler, avantajlar vb."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
               id="auto_label_11"></textarea>
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
              <label for="auto_label_10" class="sr-only">Profile image</label><input
                type="file"
                name="profile_image"
                accept="image/*"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
               id="auto_label_10">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-building mr-2"></i>İşletme Logosu
              </label>
              <label for="auto_label_9" class="sr-only">Logo image</label><input
                type="file"
                name="logo_image"
                accept="image/*"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
               id="auto_label_9">
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
            <label for="auto_label_8" class="sr-only">Terms</label><input type="checkbox" name="terms" required class="mt-1 mr-3 text-blue-600 focus:ring-blue-500" id="auto_label_8">
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
          href="login.php"
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

<?php include '../includes/footer.php'; ?>


