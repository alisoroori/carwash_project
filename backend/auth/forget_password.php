<?php
// Farsça: این فایل شامل کدهای HTML صفحه فراموشی رمز عبور است.
// Türkçe: Bu dosya, şifre sıfırlama sayfasının HTML kodlarını içermektedir.
// English: This file contains the HTML code for the forgot password page.

// Set page-specific variables
$page_title = 'Şifre Sıfırlama - CarWash';
$current_page = 'forgot_password';
$show_login = false; // Don't show login button on forgot password page

// Include header
include '../includes/header.php';
?>

<!-- Additional CSS for forgot password page -->
<style>
  /* Custom animations for forgot password form */
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

    /* Farsça: استایل تب فعال. */
    /* Türkçe: Aktif sekme stili. */
    /* English: Active tab style. */
    .tab-active {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    /* Farsça: استایل تب غیرفعال. */
    /* Türkçe: Pasif sekme stili. */
    /* English: Inactive tab style. */
    .tab-inactive {
      background: #f8fafc;
      color: #64748b;
    }
    /* Fixed spacing between E-posta and Telefon buttons to prevent overlap */
    /* Override for Tailwind's px-4 when needed on this page */
    .px-4 {
      margin: 0.1rem !important; /* ensures spacing between the buttons */
      padding-left: 1rem !important;
      padding-right: 1rem !important;
    }
    /* Layout & header-safe spacing */
    /* Add small gap on the tab row to prevent overlap on narrow screens */
    .tab-row { gap: 0.5rem; }
    .main-content { padding-top: calc(var(--site-header-height, 60px) + 1rem); padding-bottom: 2.5rem; min-height: calc(100vh - var(--site-header-height, 60px)); box-sizing: border-box; }
    .form-container { margin-top: 1rem; margin-bottom: 1.5rem; }
    @media (min-width: 1024px) { .form-container { margin-top: 2.5rem; margin-bottom: 3rem; } }
    </style>

<!-- Password Reset Form -->
<!-- Farsça: این بخش شامل فرم بازنشانی رمز عبور است. -->
<!-- Türkçe: Bu bölüm şifre sıfırlama formunu içerir. -->
  <!-- English: This section contains the password reset form. -->
  <main class="main-content">
    <div class="max-w-md mx-auto">
      <div class="form-container rounded-2xl shadow-2xl p-8 animate-fade-in-up">
      <!-- Header -->
      <!-- Farsça: سربرگ فرم بازنشانی رمز عبور. -->
      <!-- Türkçe: Şifre sıfırlama formunun başlığı. -->
      <!-- English: Header for the password reset form. -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4 animate-slide-in">
          <i class="fas fa-key text-3xl text-white"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Şifremi Unuttum</h1>
        <p class="text-gray-600">Şifrenizi sıfırlamak için aşağıdaki yöntemlerden birini seçin</p>
      </div>

      <!-- Tab Navigation -->
      <!-- Farsça: ناوبری تب برای انتخاب روش بازنشانی (ایمیل یا تلفن). -->
      <!-- Türkçe: Sıfırlama yöntemini seçmek için sekme navigasyonu (e-posta veya telefon). -->
      <!-- English: Tab navigation for selecting reset method (email or phone). -->
  <div class="flex tab-row mb-8 animate-slide-in" style="animation-delay: 0.1s">
        <button
          id="emailTab"
          type="button"
          class="flex-1 py-3 px-4 rounded-l-lg font-bold transition-all duration-300 tab-active"
        >
          <i class="fas fa-envelope mr-2"></i>E-posta
        </button>
        <button
          id="phoneTab"
          type="button"
          class="flex-1 py-3 px-4 rounded-r-lg font-bold transition-all duration-300 tab-inactive"
        >
          <i class="fas fa-mobile-alt mr-2"></i>Telefon
        </button>
      </div>

      <form id="resetForm" action="forgot_password.php" method="POST" class="space-y-6">
        <!-- Email Tab Content -->
        <!-- Farsça: محتوای تب ایمیل برای بازنشانی رمز عبور. -->
        <!-- Türkçe: Şifre sıfırlama için e-posta sekmesi içeriği. -->
        <!-- English: Email tab content for password reset. -->
        <div id="emailContent" class="animate-slide-in" style="animation-delay: 0.2s">
          <div class="text-center mb-6">
            <i class="fas fa-envelope text-4xl text-blue-600 mb-3"></i>
            <h3 class="text-lg font-bold text-gray-800 mb-2">E-posta ile Sıfırlama</h3>
            <p class="text-sm text-gray-600">Kayıtlı e-posta adresinize sıfırlama bağlantısı göndereceğiz</p>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-envelope mr-2 text-blue-600"></i>E-posta Adresi *
            </label>
            <input
              type="email"
              name="email"
              id="emailInput"
              placeholder="ornek@email.com"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300"
            >
          </div>
        </div>

        <!-- Phone Tab Content -->
        <!-- Farsça: محتوای تب تلفن برای بازنشانی رمز عبور. -->
        <!-- Türkçe: Şifre sıfırlama için telefon sekmesi içeriği. -->
        <!-- English: Phone tab content for password reset. -->
        <div id="phoneContent" class="hidden animate-slide-in" style="animation-delay: 0.2s">
          <div class="text-center mb-6">
            <i class="fas fa-mobile-alt text-4xl text-green-600 mb-3"></i>
            <h3 class="text-lg font-bold text-gray-800 mb-2">SMS ile Sıfırlama</h3>
            <p class="text-sm text-gray-600">Kayıtlı telefon numaranıza doğrulama kodu göndereceğiz</p>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-mobile-alt mr-2 text-green-600"></i>Telefon Numarası *
            </label>
            <input
              type="tel"
              name="phone"
              id="phoneInput"
              placeholder="05XX XXX XX XX"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 input-focus transition-all duration-300"
            >
          </div>
        </div>

        <!-- Submit Button -->
        <!-- Farsça: دکمه ارسال برای بازنشانی رمز عبور. -->
        <!-- Türkçe: Şifre sıfırlama için gönder butonu. -->
        <!-- English: Submit button for password reset. -->
        <div class="animate-slide-in" style="animation-delay: 0.3s">
          <button
            type="submit"
            id="submitBtn"
            class="w-full gradient-bg text-white py-4 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300"
          >
            <i class="fas fa-paper-plane mr-2"></i>Sıfırlama Bağlantısı Gönder
          </button>
        </div>
      </form>

      <!-- Alternative Options -->
      <!-- Farsça: گزینه‌های جایگزین مانند بازگشت به صفحه ورود. -->
      <!-- Türkçe: Giriş sayfasına dönme gibi alternatif seçenekler. -->
      <!-- English: Alternative options like returning to the login page. -->
      <div class="mt-8 pt-6 border-t border-gray-200 animate-slide-in" style="animation-delay: 0.4s">
        <div class="text-center space-y-3">
          <p class="text-sm text-gray-600">Veya diğer seçenekler:</p>
          <a
            href="../auth/login.php"
            class="inline-block w-full text-center py-3 px-4 border border-blue-600 text-blue-600 rounded-lg font-bold hover:bg-blue-600 hover:text-white transition-all duration-300"
          >
            <i class="fas fa-sign-in-alt mr-2"></i>Giriş Sayfasına Dön
          </a>
        </div>
      </div>

      <!-- Help Text -->
      <!-- Farsça: متن راهنما برای کاربران. -->
      <!-- Türkçe: Kullanıcılar için yardım metni. -->
      <!-- English: Help text for users. -->
      <div class="mt-6 p-4 bg-blue-50 rounded-lg animate-slide-in" style="animation-delay: 0.5s">
        <div class="flex items-start">
          <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
          <div class="text-sm text-blue-800">
            <p class="font-bold mb-1">Yardım:</p>
            <p>Sıfırlama bağlantısı 24 saat geçerlidir. Eğer e-posta/SMS alamıyorsanız, spam klasörünüzü kontrol edin.</p>
          </div>
        </div>
      </div>
    </div>
  </main>

    <!-- Success/Error Messages -->
    <!-- Farsça: کانتینر برای نمایش پیام‌های موفقیت یا خطا. -->
    <!-- Türkçe: Başarı veya hata mesajlarını göstermek için kapsayıcı. -->
    <!-- English: Container for displaying success or error messages. -->
    <div id="messageContainer" class="mt-4 hidden">
      <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg hidden">
        <div class="flex items-center">
          <i class="fas fa-check-circle mr-2"></i>
          <span id="successText"></span>
        </div>
      </div>

      <div id="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg hidden">
        <div class="flex items-center">
          <i class="fas fa-exclamation-circle mr-2"></i>
          <span id="errorText"></span>
        </div>
      </div>
    </div>
  </div>

  <script>
    <!-- Fixed ForgetPassword.php header overlap, form spacing, and Email/Phone tab display issues -->
    document.addEventListener('DOMContentLoaded', function() {
      function safeGet(id) { return document.getElementById(id) || null; }

      const emailTab = safeGet('emailTab');
      const phoneTab = safeGet('phoneTab');
      const emailContent = safeGet('emailContent');
      const phoneContent = safeGet('phoneContent');
      const resetForm = safeGet('resetForm');
      const submitBtn = safeGet('submitBtn');

      // Ensure header spacing: already handled via CSS .main-content

      // Tab switching - null-safe
      if (emailTab && phoneTab && emailContent && phoneContent) {
        emailTab.addEventListener('click', function() {
          emailContent.classList.remove('hidden');
          phoneContent.classList.add('hidden');
          emailTab.classList.add('tab-active'); emailTab.classList.remove('tab-inactive');
          phoneTab.classList.add('tab-inactive'); phoneTab.classList.remove('tab-active');
          if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Sıfırlama Bağlantısı Gönder';
        });
        phoneTab.addEventListener('click', function() {
          phoneContent.classList.remove('hidden');
          emailContent.classList.add('hidden');
          phoneTab.classList.add('tab-active'); phoneTab.classList.remove('tab-inactive');
          emailTab.classList.add('tab-inactive'); emailTab.classList.remove('tab-active');
          if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-sms mr-2"></i>Doğrulama Kodu Gönder';
        });
        // Initialize state
        emailContent.classList.remove('hidden'); phoneContent.classList.add('hidden');
      }

      // Form submission - null-safe
      if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
          e.preventDefault();
          if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Gönderiliyor...';
            submitBtn.disabled = true;
            setTimeout(function() {
              // showMessage success
              const messageContainer = safeGet('messageContainer');
              const successMessage = safeGet('successMessage');
              const successText = safeGet('successText');
              if (successText) successText.textContent = 'Sıfırlama bağlantısı e-posta adresinize gönderildi. Lütfen gelen kutunuzu kontrol edin.';
              if (successMessage) successMessage.classList.remove('hidden');
              if (messageContainer) messageContainer.classList.remove('hidden');
              if (submitBtn) { submitBtn.innerHTML = originalText; submitBtn.disabled = false; }
            }, 1200);
          }
        });
      }

      // Focus animations - null-safe
      const inputs = document.querySelectorAll('input');
      if (inputs && inputs.length) {
        inputs.forEach(function(input) {
          input.addEventListener('focus', function() { this.style.transform = 'scale(1.02)'; this.style.boxShadow = '0 0 20px rgba(102, 126, 234, 0.3)'; });
          input.addEventListener('blur', function() { this.style.transform = 'scale(1)'; this.style.boxShadow = 'none'; });
        });
      }
    });
  </script>

<?php include '../includes/footer.php'; ?>
