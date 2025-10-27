<?php
// Farsça: این فایل برای خروج کاربر از سیستم استفاده می‌شود.
// Türkçe: Bu dosya, kullanıcının sistemden çıkış yapması için kullanılır.
// English: This file is used for logging out the user from the system.

session_start(); // Farsça: شروع جلسه. Türkçe: Oturumu başlat. English: Start the session.
session_destroy(); // Farsça: از بین بردن تمام داده‌های جلسه. Türkçe: Tüm oturum verilerini yok et. English: Destroy all session data.

// Farsça: کاربر را پس از 2 ثانیه به صفحه ورود هدایت می‌کند.
// Türkçe: Kullanıcıyı 2 saniye sonra giriş sayfasına yönlendirir.
// English: Redirects the user to the login page after 2 seconds.
header("Refresh: 2; url=login.php");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - Çıkış Yapılıyor</title>
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

    /* Farsça: انیمیشن چرخش برای آیکون. */
    /* Türkçe: İkon için dönme animasyonu. */
    /* English: Spinning animation for the icon. */
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
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

    /* Farsça: اعمال انیمیشن چرخش. */
    /* Türkçe: Dönme animasyonunu uygular. */
    /* English: Applies the spin animation. */
    .animate-spin-slow {
      animation: spin 2s linear infinite;
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
    .logout-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
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

  <!-- Logout Confirmation -->
  <!-- Farsça: این بخش پیام خروج و انیمیشن را نمایش می‌دهد. -->
  <!-- Türkçe: Bu bölüm çıkış mesajını ve animasyonu gösterir. -->
  <!-- English: This section displays the logout message and animation. -->
  <div class="w-full max-w-md mt-20">
    <div class="logout-container rounded-2xl shadow-2xl p-8 text-center animate-fade-in-up">
      <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4 animate-spin-slow">
        <i class="fas fa-sign-out-alt text-3xl text-white"></i>
      </div>
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Çıkış Yapılıyor...</h1>
      <p class="text-gray-600 mb-4">Güvenli bir şekilde oturumunuz kapatılıyor.</p>
      <p class="text-gray-600 text-sm">
        Kısa süre içinde giriş sayfasına yönlendirileceksiniz.
      </p>
      <a href="../auth/login.php" class="text-blue-600 hover:underline mt-2 inline-block">
        Hemen giriş sayfasına gitmek için tıklayın.
      </a>
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

</body>
</html>
