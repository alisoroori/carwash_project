<?php
// Farsça: این فایل شامل کدهای HTML صفحه ثبت نام مشتری است.
// Türkçe: Bu dosya, müşteri kayıt sayfasının HTML kodlarını içermektedir.
// English: This file contains the HTML code for the customer registration page.

// Set page-specific variables
$page_title = 'Müşteri Kayıt - CarWash';
$current_page = 'register';
$show_login = false; // Don't show login button on registration page

// Add this to the top of your Customer_Registration.php file
session_start();

// Generate CSRF token for form security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle success and error messages following project patterns
$registration_success = $_SESSION['registration_success'] ?? false;
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Clear session messages after retrieving them
if (isset($_SESSION['registration_success'])) unset($_SESSION['registration_success']);
if (isset($_SESSION['success_message'])) unset($_SESSION['success_message']);
if (isset($_SESSION['error_message'])) unset($_SESSION['error_message']);

// Include header
include '../includes/universal_header.php';
?>

<!-- Fixed Customer Registration form spacing, alignment, and design issues -->
<!-- Additional CSS for registration page -->
<style>
  /* Fixed Customer Registration form spacing and alignment */
  /* Ensure the form has breathing room from header and footer and inputs have clearer padding */
  .registration-wrapper {
    margin-top: 3.5rem !important; /* add spacing from header */
    margin-bottom: 4rem !important; /* spacing from footer */
    padding-top: 1rem; /* ensure inner spacing */
  }

  /* Stronger base padding for the form container and inputs for better touch targets */
  .form-container {
    padding: 2rem !important;
    box-sizing: border-box;
  }

  /* Increase default padding for form controls and make focus / hover clearer */
  .form-container input,
  .form-container select,
  .form-container textarea {
    padding: 0.75rem 0.9rem !important;
    font-size: 15px !important;
    line-height: 1.35 !important;
    box-sizing: border-box;
  }

  /* Make checkbox and their labels vertically centered and consistent */
  .form-container label.flex.items-center {
    align-items: center; /* ensure vertical alignment */
    gap: 0.5rem; /* consistent horizontal spacing */
  }

  .form-container input[type="checkbox"] {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    vertical-align: middle;
  }

  .form-container .text-xs,
  .form-container .text-sm {
    vertical-align: middle;
  }

  /* Improve submit button spacing on wide screens */
  @media (min-width: 1024px) {
    .form-container {
      padding: 3rem !important;
    }
  }

  /* Make sure interactive elements have clear hover/active outlines for accessibility */
  .form-container input:focus, .form-container select:focus, .form-container textarea:focus {
    outline: 3px solid rgba(102,126,234,0.12) !important;
    outline-offset: 2px;
  }

  /* Ensure checkbox + label groups are easier to tap on mobile */
  .form-container .checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    cursor: pointer;
  }

  /* Slightly larger checkbox touch targets */
  .form-container input[type="checkbox"] {
    width: 1.15rem !important;
    height: 1.15rem !important;
  }

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

  /* Comprehensive Responsive Design - Mobile First */
  
  /* Base styles for all devices */
  body {
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }

  .registration-wrapper {
    flex: 1;
    padding: 1rem;
  }
  
  /* Ensure footer is always visible */
  footer {
    margin-top: auto;
  }

  /* Extra Small Mobile: 320px - 479px */
  @media (max-width: 479px) {
    .registration-wrapper {
      padding: 0.75rem;
    }

    .form-container {
      padding: 1.25rem !important;
      margin: 0.5rem 0;
    }

    .form-container h1 {
      font-size: 1.5rem !important;
      line-height: 2rem;
    }

    .form-container h2 {
      font-size: 1.125rem !important;
      line-height: 1.75rem;
    }

    .form-container p {
      font-size: 0.875rem;
    }

    .icon-header {
      width: 3rem !important;
      height: 3rem !important;
      margin-bottom: 0.75rem !important;
    }

    .icon-header i {
      font-size: 1.5rem !important;
    }

    input, select, textarea {
      font-size: 14px !important;
      padding: 0.625rem 0.75rem !important;
    }

    label {
      font-size: 0.813rem !important;
      margin-bottom: 0.375rem !important;
    }

    button[type="submit"] {
      padding: 0.875rem 1rem !important;
      font-size: 0.938rem !important;
    }

    .grid {
      gap: 1rem !important;
    }

    .space-y-8 > * + * {
      margin-top: 1.5rem !important;
    }
  }

  /* Small Mobile: 480px - 639px */
  @media (min-width: 480px) and (max-width: 639px) {
    .registration-wrapper {
      padding: 1rem;
    }

    .form-container {
      padding: 1.5rem !important;
    }

    .form-container h1 {
      font-size: 1.75rem !important;
    }

    .form-container h2 {
      font-size: 1.25rem !important;
    }

    input, select, textarea {
      font-size: 15px !important;
      padding: 0.75rem 0.875rem !important;
    }
  }

  /* Tablet Portrait: 640px - 767px */
  @media (min-width: 640px) and (max-width: 767px) {
    .registration-wrapper {
      padding: 1.5rem;
    }

    .form-container {
      padding: 2rem !important;
    }

    .form-container h1 {
      font-size: 2rem !important;
    }

    .form-container h2 {
      font-size: 1.375rem !important;
    }
  }

  /* Tablet Portrait/Landscape: 768px - 1023px */
  @media (min-width: 768px) and (max-width: 1023px) {
    .registration-wrapper {
      padding: 2rem;
      max-width: 900px;
      margin: 0 auto;
    }

    .form-container {
      padding: 2.5rem !important;
    }

    .grid.md\:grid-cols-2 {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  /* Desktop: 1024px+ */
  @media (min-width: 1024px) {
    .registration-wrapper {
      padding: 2rem;
    }

    .form-container {
      padding: 3rem !important;
    }

    .grid.md\:grid-cols-2 {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  /* Large Desktop: 1280px+ */
  @media (min-width: 1280px) {
    .registration-wrapper {
      max-width: 1200px;
      margin: 0 auto;
    }
  }

  /* Touch device optimizations */
  @media (hover: none) and (pointer: coarse) {
    input, select, textarea, button {
      min-height: 44px;
      font-size: 16px !important; /* Prevent zoom on iOS */
    }

    .input-focus:focus {
      transform: scale(1);
    }
  }

  /* Landscape phone optimization */
  @media (max-height: 500px) and (orientation: landscape) {
    .form-container {
      padding: 1.5rem !important;
    }

    .icon-header {
      width: 2.5rem !important;
      height: 2.5rem !important;
    }

    .form-container h1 {
      font-size: 1.5rem !important;
      margin-bottom: 0.5rem !important;
    }

    .form-container h2 {
      font-size: 1.125rem !important;
    }

    .space-y-8 > * + * {
      margin-top: 1.25rem !important;
    }
  }

  /* Reduced motion support */
  @media (prefers-reduced-motion: reduce) {
    .animate-fade-in-up,
    .animate-slide-in {
      animation: none;
      opacity: 1;
      transform: none;
    }

    .input-focus:focus {
      transform: none;
    }

    button:hover {
      transform: none !important;
    }
  }

  /* High contrast mode */
  @media (prefers-contrast: high) {
    .form-container {
      border: 2px solid #000;
    }

    input, select, textarea {
      border-width: 2px;
    }
  }

  /* Print styles */
  @media print {
    .animate-fade-in-up,
    .animate-slide-in {
      animation: none;
    }

    button[type="submit"] {
      display: none;
    }
  }

  /* Custom scrollbar for better UX */
  ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }

  ::-webkit-scrollbar-track {
    background: #f1f1f1;
  }

  ::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 4px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: #764ba2;
  }

  /* Footer override to ensure 4 columns display correctly */
  footer .grid {
    display: grid !important;
  }

  @media (min-width: 1024px) {
    footer .grid.lg\:grid-cols-4 {
      grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }
  }

  @media (min-width: 768px) and (max-width: 1023px) {
    footer .grid.md\:grid-cols-2 {
      grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
  }

  /* Ensure footer sections don't have conflicting styles */
  footer .lg\:col-span-1 {
    grid-column: span 1 / span 1;
  }
</style>

<!-- Registration Form -->
<div class="registration-wrapper relative z-10 mt-16 md:mt-20 lg:mt-24 pt-6">
  <div class="max-w-4xl mx-auto px-4">
    <div class="form-container rounded-2xl shadow-2xl p-8 animate-fade-in-up">
      <!-- Header -->
      <div class="text-center mb-6 sm:mb-8">
        <div class="icon-header w-16 h-16 sm:w-20 sm:h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4 animate-slide-in">
          <i class="fas fa-user-plus text-2xl sm:text-3xl text-white"></i>
        </div>
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-2 px-2">Müşteri Kayıt Formu</h1>
        <p class="text-sm sm:text-base text-gray-600 px-4">Hesabınızı oluşturun ve araç yıkama hizmetlerimizden yararlanın</p>
      </div>

  <!-- Fixed form action path and added CSRF token -->
  <form action="Customer_Registration_process.php" method="POST" class="space-y-8">
    <!-- Add CSRF Token -->
    <label for="auto_label_56" class="sr-only">Csrf token</label>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" id="auto_label_56">
        
        <!-- Personal Information Section -->
        <!-- Farsça: بخش اطلاعات شخصی. -->
        <!-- Türkçe: Kişisel Bilgiler bölümü. -->
        <!-- English: Personal Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.1s">
          <div class="flex items-center mb-4 sm:mb-6">
            <i class="fas fa-user text-blue-600 text-lg sm:text-xl mr-2 sm:mr-3"></i>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Kişisel Bilgiler</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_55">
                <i class="fas fa-signature mr-1 sm:mr-2 text-xs sm:text-sm"></i>Ad Soyad *
              </label>
              <label for="auto_label_55" class="sr-only">Full name</label><input type="text" name="full_name" placeholder="Adınızı ve soyadınızı girin" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_55">
            </div>

            <div>
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_54">
                <i class="fas fa-envelope mr-1 sm:mr-2 text-xs sm:text-sm"></i>E-posta Adresi *
              </label>
              <label for="auto_label_54" class="sr-only">Email</label><input type="email" name="email" placeholder="ornek@email.com" autocomplete="email" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_54">
            </div>

            <div>
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_53">
                <i class="fas fa-phone mr-1 sm:mr-2 text-xs sm:text-sm"></i>Telefon Numarası *
              </label>
              <label for="auto_label_53" class="sr-only">Phone</label><input type="tel" name="phone" placeholder="05XX XXX XX XX" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_53">
            </div>

            <div>
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="password">
                <i class="fas fa-lock mr-1 sm:mr-2 text-xs sm:text-sm"></i>Şifre *
              </label>
              <div class="relative">
                <label for="password" class="sr-only">Password</label><input type="password" name="password" id="password" placeholder="Güçlü bir şifre belirleyin" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&amp;*])(?=.{8,})" title="Şifreniz en az 8 karakter uzunluğunda olmalı ve en az bir büyük harf, bir küçük harf, bir rakam ve bir özel karakter içermelidir" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 pr-10 sm:pr-12 text-sm sm:text-base">
                <button type="button" onclick="togglePassword()" class="absolute right-2 sm:right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-blue-600 transition-colors p-2" aria-label="Toggle password visibility">
                  <i class="fas fa-eye text-sm sm:text-base" id="passwordToggle"></i>
                </button>
              </div>
              <!-- Password strength requirements display - MOVED BELOW the password field -->
              <div class="mt-2 text-xs text-gray-600">
                <p>Şifre en az aşağıdakileri içermelidir:</p>
                <ul class="list-disc ml-4 mt-1">
                  <li>8 karakter uzunluğunda</li>
                  <li>Bir büyük harf</li>
                  <li>Bir küçük harf</li>
                  <li>Bir rakam</li>
                  <li>Bir özel karakter</li>
                </ul>
              </div>
            </div>
            
            <!-- Add password confirmation field -->
            <div class="md:col-span-2">
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="password_confirm">
                <i class="fas fa-lock mr-1 sm:mr-2 text-xs sm:text-sm"></i>Şifre Tekrar *
              </label>
              <div class="relative">
                <label for="password_confirm" class="sr-only">Password confirm</label><input type="password" name="password_confirm" id="password_confirm" placeholder="Şifrenizi tekrar girin" autocomplete="new-password" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 pr-10 sm:pr-12 text-sm sm:text-base">
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden field for role -->
  <label for="auto_label_52" class="sr-only">Role</label><input type="hidden" name="role" value="customer" id="auto_label_52">

        <!-- Address Information -->
        <!-- Farsça: بخش اطلاعات آدرس. -->
        <!-- Türkçe: Adres Bilgileri bölümü. -->
        <!-- English: Address Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.2s">
          <div class="flex items-center mb-4 sm:mb-6">
            <i class="fas fa-map-marker-alt text-blue-600 text-lg sm:text-xl mr-2 sm:mr-3"></i>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Adres Bilgileri</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <div class="md:col-span-2">
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_51">
                <i class="fas fa-city mr-1 sm:mr-2 text-xs sm:text-sm"></i>Şehir *
              </label>
              <label for="auto_label_51" class="sr-only">City</label><select name="city" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_51">
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
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_50">
                <i class="fas fa-address-card mr-1 sm:mr-2 text-xs sm:text-sm"></i>Adres Detayları
              </label>
              <label for="auto_label_50" class="sr-only">Address</label><textarea name="address" rows="3" placeholder="Sokak, mahalle, apartman numarası vb." class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base resize-none" id="auto_label_50"></textarea>
            </div>
          </div>
        </div>

        <!-- Car Information Section -->
        <!-- Farsça: بخش اطلاعات خودرو. -->
        <!-- Türkçe: Araç Bilgileri bölümü. -->
        <!-- English: Car Information Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.3s">
          <div class="flex items-center mb-4 sm:mb-6">
            <i class="fas fa-car text-blue-600 text-lg sm:text-xl mr-2 sm:mr-3"></i>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Araç Bilgileri</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_49">
                <i class="fas fa-car-side mr-1 sm:mr-2 text-xs sm:text-sm"></i>Marka *
              </label>
              <label for="auto_label_49" class="sr-only">Car brand</label><select name="car_brand" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_49">
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
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_48">
                <i class="fas fa-car mr-1 sm:mr-2 text-xs sm:text-sm"></i>Model *
              </label>
              <label for="auto_label_48" class="sr-only">Car model</label><input type="text" name="car_model" placeholder="Örn: Corolla, Civic, Focus" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_48">
            </div>

            <div>
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_47">
                <i class="fas fa-calendar mr-1 sm:mr-2 text-xs sm:text-sm"></i>Model Yılı *
              </label>
              <label for="auto_label_47" class="sr-only">Car year</label><select name="car_year" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_47">
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
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_46">
                <i class="fas fa-palette mr-1 sm:mr-2 text-xs sm:text-sm"></i>Renk
              </label>
              <label for="auto_label_46" class="sr-only">Car color</label><input type="text" name="car_color" placeholder="Araç rengi" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_46">
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-2" for="auto_label_45">
                <i class="fas fa-id-card mr-1 sm:mr-2 text-xs sm:text-sm"></i>Plaka Numarası
              </label>
              <label for="auto_label_45" class="sr-only">License plate</label><input type="text" name="license_plate" placeholder="34 ABC 123" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 input-focus transition-all duration-300 text-sm sm:text-base" id="auto_label_45">
            </div>
          </div>
        </div>

        <!-- Preferences -->
        <!-- Farsça: بخش ترجیحات. -->
        <!-- Türkçe: Tercihler bölümü. -->
        <!-- English: Preferences Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.4s">
          <div class="flex items-center mb-4 sm:mb-6">
            <i class="fas fa-sliders-h text-blue-600 text-lg sm:text-xl mr-2 sm:mr-3"></i>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Tercihler</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <div>
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-3" for="auto_label_44">Bildirim Tercihleri</label>
              <div class="space-y-2 sm:space-y-3">
                <label class="flex items-center cursor-pointer py-1">
                  <label for="auto_label_44" class="sr-only">Notifications[]</label><input type="checkbox" name="notifications[]" value="email" checked class="mr-2 sm:mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2" id="auto_label_44">
                  <span class="text-xs sm:text-sm text-gray-600">E-posta bildirimleri</span>
                </label>
                <label class="flex items-center cursor-pointer py-1">
                  <label for="auto_label_43" class="sr-only">Notifications[]</label><input type="checkbox" name="notifications[]" value="sms" class="mr-2 sm:mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2" id="auto_label_43">
                  <span class="text-xs sm:text-sm text-gray-600">SMS bildirimleri</span>
                </label>
                <label class="flex items-center cursor-pointer py-1">
                  <label for="auto_label_42" class="sr-only">Notifications[]</label><input type="checkbox" name="notifications[]" value="push" class="mr-2 sm:mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2" id="auto_label_42">
                  <span class="text-xs sm:text-sm text-gray-600">Push bildirimleri</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-3" for="auto_label_41">Hangi hizmetleri tercih edersiniz?</label>
              <div class="space-y-2 sm:space-y-3">
                <label class="flex items-center cursor-pointer py-1">
                  <label for="auto_label_41" class="sr-only">Services[]</label><input type="checkbox" name="services[]" value="exterior" checked class="mr-2 sm:mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2" id="auto_label_41">
                  <span class="text-xs sm:text-sm text-gray-600">Dış yıkama</span>
                </label>
                <label class="flex items-center cursor-pointer py-1">
                  <label for="auto_label_40" class="sr-only">Services[]</label><input type="checkbox" name="services[]" value="interior" checked class="mr-2 sm:mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2" id="auto_label_40">
                  <span class="text-xs sm:text-sm text-gray-600">İç temizlik</span>
                </label>
                <label class="flex items-center cursor-pointer py-1">
                  <label for="auto_label_39" class="sr-only">Services[]</label><input type="checkbox" name="services[]" value="detailing" class="mr-2 sm:mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2" id="auto_label_39">
                  <span class="text-xs sm:text-sm text-gray-600">Tam detaylandırma</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Terms and Submit -->
        <!-- Farsça: بخش شرایط و ارسال فرم. -->
        <!-- Türkçe: Şartlar و Gönder bölümü. -->
        <!-- English: Terms and Submit Section. -->
        <div class="animate-slide-in" style="animation-delay: 0.5s">
          <div class="section-divider my-4 sm:my-6"></div>

          <label class="checkbox-label mb-4 sm:mb-6">
            <input id="terms" type="checkbox" name="terms" required class="mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2">
            <span class="text-xs sm:text-sm text-gray-600">
              <a href="#" class="text-blue-600 hover:underline font-medium">Kullanım Şartları</a> ve
              <a href="#" class="text-blue-600 hover:underline font-medium">Gizlilik Politikası</a>'nı
              okudum ve kabul ediyorum. *
            </span>
          </label>

          <button type="submit" class="w-full gradient-bg text-white py-3 sm:py-4 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300 text-sm sm:text-base">
            <i class="fas fa-user-plus mr-2"></i>Hesabımı Oluştur
          </button>
        </div>
      </form>

      <!-- Login Link -->
      <!-- Farsça: لینک ورود به سیستم. -->
      <!-- Türkçe: Giriş bağlantısı. -->
      <!-- English: Login Link. -->
      <div class="text-center mt-6 sm:mt-8 animate-slide-in" style="animation-delay: 0.6s">
        <p class="text-sm sm:text-base text-gray-600 mb-3 sm:mb-4">Zaten hesabınız var mı?</p>
        <a href="login.php" class="inline-block gradient-bg text-white px-6 sm:px-8 py-2.5 sm:py-3 rounded-lg font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300 text-sm sm:text-base">
          <i class="fas fa-sign-in-alt mr-2"></i>Giriş Yap
        </a>
      </div>
    </div>
  </div>
</div>

  <!-- Add this error/success message display section after your header -->
  <?php if ($registration_success): ?>
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 sm:p-8 max-w-md w-full mx-4 text-center animate-bounce">
        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
          <i class="fas fa-check text-white text-xl sm:text-2xl"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-green-600 mb-3 sm:mb-4">Başarılı!</h2>
        <p class="text-sm sm:text-base text-gray-700 mb-4 sm:mb-6"><?php echo htmlspecialchars($success_message); ?></p>

        <div class="mb-4 sm:mb-6">
          <div class="text-xs sm:text-sm text-gray-500 mb-2">Otomatik yönlendirme:</div>
          <div class="text-lg sm:text-xl font-bold text-blue-600" id="countdown">5</div>
        </div>

        <div class="space-y-2">
          <button onclick="redirectToDashboard()" class="w-full bg-blue-600 text-white py-2.5 sm:py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm sm:text-base font-medium">
            <i class="fas fa-tachometer-alt mr-2"></i>Şimdi Panele Git
          </button>
          <button onclick="closeModal()" class="w-full bg-gray-300 text-gray-700 py-2.5 sm:py-3 px-4 rounded-lg hover:bg-gray-400 transition-colors text-sm sm:text-base font-medium">
            Kapat
          </button>
        </div>
      </div>
    </div>

    <script>
      // Success modal functionality following project JS patterns
      let countdownTimer = 5;

      function updateCountdown() {
        document.getElementById('countdown').textContent = countdownTimer;
        if (countdownTimer <= 0) {
          redirectToDashboard();
          return;
        }
        countdownTimer--;
        setTimeout(updateCountdown, 1000);
      }

      function redirectToDashboard() {
        // Redirect to customer dashboard following project structure
        window.location.href = '../dashboard/Customer_Dashboard.php';
      }

      function closeModal() {
        document.getElementById('successModal').style.display = 'none';
      }

      // Start countdown when page loads
      document.addEventListener('DOMContentLoaded', function() {
        updateCountdown();
      });
    </script>
  <?php endif; ?>

  <!-- Add error message display following project patterns -->
  <?php if (!empty($error_message)): ?>
    <div class="max-w-4xl mx-auto mb-4 px-4">
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-md" role="alert">
        <div class="flex items-start">
          <i class="fas fa-exclamation-circle mr-3 mt-1 flex-shrink-0"></i>
          <div>
            <strong class="font-bold text-sm sm:text-base">Hata!</strong>
            <span class="block sm:inline text-xs sm:text-sm mt-1 sm:mt-0 sm:ml-2"><?php echo htmlspecialchars($error_message); ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

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
    
    // Enhanced password validation
    document.addEventListener('DOMContentLoaded', function() {
      const passwordInput = document.getElementById('password');
      const confirmInput = document.getElementById('password_confirm');
      const form = document.querySelector('form');
      
      form.addEventListener('submit', function(e) {
        // Check if passwords match
        if(passwordInput.value !== confirmInput.value) {
          e.preventDefault();
          alert('Şifreler eşleşmiyor. Lütfen kontrol edin.');
          return false;
        }
        
        // Check password strength
        const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{8,})/;
        if(!strongRegex.test(passwordInput.value)) {
          e.preventDefault();
          alert('Şifreniz gerekli güvenlik kriterlerini karşılamıyor.');
          return false;
        }
        
        return true;
      });
      
      // Real-time password matching feedback
      confirmInput.addEventListener('input', function() {
        if(passwordInput.value === confirmInput.value) {
          confirmInput.style.borderColor = 'green';
        } else {
          confirmInput.style.borderColor = '#e53e3e';
        }
      });
    });
  </script>

<?php include '../includes/footer.php'; ?>


