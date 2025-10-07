<?php
// Farsça: این فایل شامل کدهای HTML صفحه ورود به سیستم است.
// Türkçe: Bu dosya, giriş sayfasının HTML kodlarını içermektedir.
// English: This file contains the HTML code for the login page.
?>
<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - Giriş Yap</title>
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
    /* Türkçe: Öğelerin soldan sağa doğru yavaşça kayarak gelmesi برای animasyon. */
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
    /* Türkçe: Şeffaf arka plan و bulanıklık filtresi ile form kapsayıcı stili. */
    /* English: Form container style with transparent background and blur filter. */
    .form-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
    }

    /* Farsça: استایل ورودی‌ها هنگام فوکوس: بزرگنمایی و سایه. */
    /* Türkçe: Odaklanıldığında girişlerin stili: büyütme و gölge. */
    /* English: Input style on focus: scale and shadow. */
    .input-focus:focus {
      transform: scale(1.02);
      box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

  <!-- Header -->
  <!-- Farsça: این بخش سربرگ صفحه را شامل می‌شود. -->
  <!-- Türkçe: Bu bölüm sayfa başlığını içerir. -->
  <!-- English: This section includes the page header. -->
  <header class="fixed top-0 left-0 right-0 bg-white shadow-lg z-50">
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

  <!-- Login Form -->
  <!-- Farsça: این بخش شامل فرم ورود به سیستم است. -->
  <!-- Türkçe: Bu bölüm giriş formunu içerir. -->
  <!-- English: This section contains the login form. -->
  <div class="w-full max-w-md mt-20">
    <div class="form-container rounded-2xl shadow-2xl p-8 animate-fade-in-up">
      <!-- Logo and Title -->
      <!-- Farsça: این بخش شامل لوگو و عنوان صفحه است. -->
      <!-- Türkçe: Bu bölüm logo و sayfa başlığını içerir. -->
      <!-- English: This section includes the logo and page title. -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4 animate-slide-in">
          <i class="fas fa-car text-3xl text-white"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">CarWash'e Hoş Geldiniz</h1>
        <p class="text-gray-600">Hesabınıza giriş yapın</p>
      </div>

      <!-- Login Form -->
      <!-- Farsça: این فرم برای ورود کاربران استفاده می‌شود. -->
      <!-- Türkçe: Bu form kullanıcı girişi için kullanılır. -->
      <!-- English: This form is used for user login. -->
      <form action="login.php" method="POST" class="space-y-6">
        <!-- Email Field -->
        <!-- Farsça: فیلد ورودی برای آدرس ایمیل. -->
        <!-- Türkçe: E-posta adresi için giriş alanı. -->
        <!-- English: Input field for email address. -->
        <div class="animate-slide-in" style="animation-delay: 0.1s">
          <label class="block text-sm font-bold text-gray-700 mb-2">
            <i class="fas fa-envelope mr-2 text-blue-600"></i>E-posta Adresi
          </label>
          <input
            type="email"
            name="email"
            placeholder="ornek@email.com"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
        </div>

        <!-- Password Field -->
        <!-- Farsça: فیلد ورودی برای رمز عبور. -->
        <!-- Türkçe: Şifre için giriş alanı. -->
        <!-- English: Input field for password. -->
        <div class="animate-slide-in" style="animation-delay: 0.2s">
          <label class="block text-sm font-bold text-gray-700 mb-2">
            <i class="fas fa-lock mr-2 text-blue-600"></i>Şifre
          </label>
          <div class="relative">
            <input
              type="password"
              name="password"
              id="password"
              placeholder="Şifrenizi girin"
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

        <!-- User Type Selection -->
        <!-- Farsça: انتخاب نوع کاربر: مشتری، ارائه‌دهنده خدمات یا مدیر. -->
        <!-- Türkçe: Kullanıcı tipi seçimi: Müşteri, Hizmet Sağlayıcı veya Yönetici. -->
        <!-- English: User type selection: Customer, Service Provider, or Admin. -->
        <div class="animate-slide-in" style="animation-delay: 0.3s">
          <label class="block text-sm font-bold text-gray-700 mb-2">
            <i class="fas fa-user mr-2 text-blue-600"></i>Kullanıcı Tipi
          </label>
          <select name="user_type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300">
            <option value="customer">Müşteri</option>
            <option value="carwash">Araç Yıkama</option>
            <option value="admin">Yönetici</option>
          </select>
        </div>

        <!-- Remember Me & Forgot Password -->
        <!-- Farsça: گزینه‌های "مرا به خاطر بسپار" و "رمز عبور را فراموش کرده‌ام". -->
        <!-- Türkçe: "Beni hatırla" و "Şifremi unuttum" seçenekleri. -->
        <!-- English: "Remember Me" and "Forgot Password" options. -->
        <div class="flex justify-between items-center animate-slide-in" style="animation-delay: 0.4s">
          <label class="flex items-center">
            <input type="checkbox" name="remember" class="mr-2 text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-gray-600">Beni hatırla</span>
          </label>
          <a href="../../backend/auth/forget_password.php" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
            Şifremi unuttum?
          </a>
        </div>

        <!-- Login Button -->
        <!-- Farsça: دکمه ورود به سیستم. -->
        <!-- Türkçe: Giriş yap butonu. -->
        <!-- English: Login button. -->
        <div class="animate-slide-in" style="animation-delay: 0.5s">
          <button
            type="submit"
            class="w-full gradient-bg text-white py-4 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
            <i class="fas fa-sign-in-alt mr-2"></i>Giriş Yap
          </button>
        </div>
      </form>

      <!-- Divider -->
      <!-- Farsça: جداکننده بین فرم ورود و گزینه‌های ثبت‌نام. -->
      <!-- Türkçe: Giriş formu ile kayıt seçenekleri arasındaki ayırıcı. -->
      <!-- English: Divider between login form and registration options. -->
      <div class="my-6 animate-slide-in" style="animation-delay: 0.6s">
        <div class="relative">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-gray-500">veya</span>
          </div>
        </div>
      </div>

      <!-- Register Link -->
      <!-- Farsça: لینک‌های ثبت‌نام برای مشتریان و ارائه‌دهندگان خدمات. -->
      <!-- Türkçe: Müşteriler و hizmet sağlayıcılar için kayıt bağlantıları. -->
      <!-- English: Registration links for customers and service providers. -->
      <div class="text-center animate-slide-in" style="animation-delay: 0.7s">
        <p class="text-gray-600 mb-4">Hesabınız yok mu?</p>
        <a
          href="Customer_Registration.php"
          class="inline-block w-full gradient-bg text-white py-3 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
          <i class="fas fa-user-plus mr-2"></i>Müşteri Olarak Kayıt Ol
        </a>
        <a
          href="Car_Wash_Registration.php"
          class="inline-block w-full mt-3 border-2 border-blue-600 text-blue-600 py-3 rounded-lg font-bold hover:bg-blue-600 hover:text-white transition-all duration-300">
          <i class="fas fa-store mr-2"></i>Hizmet Sağlayıcı Olarak Kayıt Ol
        </a>
      </div>

      <!-- Features -->
      <!-- Farsça: ویژگی‌های اصلی سرویس. -->
      <!-- Türkçe: Hizmetin ana özellikleri. -->
      <!-- English: Main features of the service. -->
      <div class="mt-8 pt-6 border-t border-gray-200 animate-slide-in" style="animation-delay: 0.8s">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <i class="fas fa-shield-alt text-blue-600 text-xl mb-1"></i>
            <p class="text-xs text-gray-600">Güvenli</p>
          </div>
          <div>
            <i class="fas fa-clock text-blue-600 text-xl mb-1"></i>
            <p class="text-xs text-gray-600">Hızlı</p>
          </div>
          <div>
            <i class="fas fa-star text-blue-600 text-xl mb-1"></i>
            <p class="text-xs text-gray-600">Kaliteli</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <!-- Farsça: پاورقی صفحه شامل لینک‌های شرایط استفاده و سیاست حفظ حریم خصوصی. -->
    <!-- Türkçe: Sayfa altbilgisi, kullanım şartları ve gizlilik politikası bağlantılarını içerir. -->
    <!-- English: Page footer including terms of use and privacy policy links. -->
    <div class="text-center mt-8 animate-fade-in-up">
      <p class="text-gray-500 text-sm">
        Giriş yaparak <a href="#" class="text-blue-600 hover:underline">Kullanım Şartları</a> ve
        <a href="#" class="text-blue-600 hover:underline">Gizlilik Politikası</a>'nı kabul etmiş olursunuz.
      </p>
    </div>
  </div>

  <!-- Farsça: اسکریپت جاوا اسکریپت برای تغییر دید رمز عبور. -->
  <!-- Türkçe: Şifre görünürlüğünü değiştirmek için JavaScript kodu. -->
  <!-- English: JavaScript for toggling password visibility. -->
  <script>
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
    document.querySelectorAll('input').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
      });

      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });
  </script>

</body>

</html>
<?php
require_once '../includes/db.php';
session_start();

class UserAuth {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("
            SELECT id, email, password_hash, role 
            FROM users 
            WHERE email = ?
        ");
        
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password, $result['password_hash'])) {
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['user_role'] = $result['role'];
            return true;
        }
        return false;
    }
}