<?php
// FarsÃ§a: Ø§ÛŒÙ† ÙØ§ÛŒÙ„ Ø¨Ø±Ø§ÛŒ Ø®Ø±ÙˆØ¬ Ú©Ø§Ø±Ø¨Ø± Ø§Ø² Ø³ÛŒØ³ØªÙ… Ø§Ø³ØªÙØ§Ø¯Ù‡ Ù…ÛŒâ€ŒØ´ÙˆØ¯.
// TÃ¼rkÃ§e: Bu dosya, kullanÄ±cÄ±nÄ±n sistemden Ã§Ä±kÄ±ÅŸ yapmasÄ± iÃ§in kullanÄ±lÄ±r.
// English: This file is used for logging out the user from the system.

session_start(); // FarsÃ§a: Ø´Ø±ÙˆØ¹ Ø¬Ù„Ø³Ù‡. TÃ¼rkÃ§e: Oturumu baÅŸlat. English: Start the session.
session_destroy(); // FarsÃ§a: Ø§Ø² Ø¨ÛŒÙ† Ø¨Ø±Ø¯Ù† ØªÙ…Ø§Ù… Ø¯Ø§Ø¯Ù‡â€ŒÙ‡Ø§ÛŒ Ø¬Ù„Ø³Ù‡. TÃ¼rkÃ§e: TÃ¼m oturum verilerini yok et. English: Destroy all session data.

// FarsÃ§a: Ú©Ø§Ø±Ø¨Ø± Ø±Ø§ Ù¾Ø³ Ø§Ø² 2 Ø«Ø§Ù†ÛŒÙ‡ Ø¨Ù‡ ØµÙØ­Ù‡ ÙˆØ±ÙˆØ¯ Ù‡Ø¯Ø§ÛŒØª Ù…ÛŒâ€ŒÚ©Ù†Ø¯.
// TÃ¼rkÃ§e: KullanÄ±cÄ±yÄ± 2 saniye sonra giriÅŸ sayfasÄ±na yÃ¶nlendirir.
// English: Redirects the user to the login page after 2 seconds.
header("Refresh: 2; url=login.php");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - Ã‡Ä±kÄ±ÅŸ YapÄ±lÄ±yor</title>
  <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* FarsÃ§a: Ø§Ù†ÛŒÙ…ÛŒØ´Ù† Ø¨Ø±Ø§ÛŒ Ø¸Ø§Ù‡Ø± Ø´Ø¯Ù† ØªØ¯Ø±ÛŒØ¬ÛŒ Ø¹Ù†Ø§ØµØ± Ø§Ø² Ù¾Ø§ÛŒÛŒÙ† Ø¨Ù‡ Ø¨Ø§Ù„Ø§. */
    /* TÃ¼rkÃ§e: Ã–ÄŸelerin aÅŸaÄŸÄ±dan yukarÄ±ya doÄŸru yavaÅŸÃ§a gÃ¶rÃ¼nmesi iÃ§in animasyon. */
    /* English: Animation for elements to fade in from bottom to top. */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* FarsÃ§a: Ø§Ù†ÛŒÙ…ÛŒØ´Ù† Ø¨Ø±Ø§ÛŒ ÙˆØ±ÙˆØ¯ ØªØ¯Ø±ÛŒØ¬ÛŒ Ø¹Ù†Ø§ØµØ± Ø§Ø² Ú†Ù¾ Ø¨Ù‡ Ø±Ø§Ø³Øª. */
    /* TÃ¼rkÃ§e: Ã–ÄŸelerin soldan saÄŸa doÄŸru yavaÅŸÃ§a kayarak gelmesi iÃ§in animasyon. */
    /* English: Animation for elements to slide in from left to right. */
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-30px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* FarsÃ§a: Ø§Ù†ÛŒÙ…ÛŒØ´Ù† Ú†Ø±Ø®Ø´ Ø¨Ø±Ø§ÛŒ Ø¢ÛŒÚ©ÙˆÙ†. */
    /* TÃ¼rkÃ§e: Ä°kon iÃ§in dÃ¶nme animasyonu. */
    /* English: Spinning animation for the icon. */
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    /* FarsÃ§a: Ø§Ø¹Ù…Ø§Ù„ Ø§Ù†ÛŒÙ…ÛŒØ´Ù† fadeInUp. */
    /* TÃ¼rkÃ§e: fadeInUp animasyonunu uygular. */
    /* English: Applies the fadeInUp animation. */
    .animate-fade-in-up {
      animation: fadeInUp 0.6s ease-out forwards;
    }

    /* FarsÃ§a: Ø§Ø¹Ù…Ø§Ù„ Ø§Ù†ÛŒÙ…ÛŒØ´Ù† slideIn. */
    /* TÃ¼rkÃ§e: slideIn animasyonunu uygular. */
    /* English: Applies the slideIn animation. */
    .animate-slide-in {
      animation: slideIn 0.5s ease-out forwards;
    }

    /* FarsÃ§a: Ø§Ø¹Ù…Ø§Ù„ Ø§Ù†ÛŒÙ…ÛŒØ´Ù† Ú†Ø±Ø®Ø´. */
    /* TÃ¼rkÃ§e: DÃ¶nme animasyonunu uygular. */
    /* English: Applies the spin animation. */
    .animate-spin-slow {
      animation: spin 2s linear infinite;
    }

    /* FarsÃ§a: Ù¾Ø³â€ŒØ²Ù…ÛŒÙ†Ù‡ Ú¯Ø±Ø§Ø¯ÛŒØ§Ù†Øª Ø¨Ø±Ø§ÛŒ Ø¹Ù†Ø§ØµØ±. */
    /* TÃ¼rkÃ§e: Ã–ÄŸeler iÃ§in gradyan arka plan. */
    /* English: Gradient background for elements. */
    .gradient-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* FarsÃ§a: Ø§Ø³ØªØ§ÛŒÙ„ Ú©Ø§Ù†ØªÛŒÙ†Ø± ÙØ±Ù… Ø¨Ø§ Ù¾Ø³â€ŒØ²Ù…ÛŒÙ†Ù‡ Ø´ÙØ§Ù Ùˆ ÙÛŒÙ„ØªØ± Ø¨Ù„ÙˆØ±. */
    /* TÃ¼rkÃ§e: Åžeffaf arka plan ve bulanÄ±klÄ±k filtresi ile form kapsayÄ±cÄ± stili. */
    /* English: Form container style with transparent background and blur filter. */
    .logout-container {
      background: rgba(255, 255, 255, 0.95);
      /* Added -webkit-backdrop-filter for Safari support */
      -webkit-backdrop-filter: blur(10px);
      backdrop-filter: blur(10px);
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

  <!-- Header -->
  <!-- FarsÃ§a: Ø§ÛŒÙ† Ø¨Ø®Ø´ Ø³Ø±Ø¨Ø±Ú¯ ØµÙØ­Ù‡ Ø±Ø§ Ø´Ø§Ù…Ù„ Ù…ÛŒâ€ŒØ´ÙˆØ¯. -->
  <!-- TÃ¼rkÃ§e: Bu bÃ¶lÃ¼m sayfa baÅŸlÄ±ÄŸÄ±nÄ± iÃ§erir. -->
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
  <!-- FarsÃ§a: Ø§ÛŒÙ† Ø¨Ø®Ø´ Ù¾ÛŒØ§Ù… Ø®Ø±ÙˆØ¬ Ùˆ Ø§Ù†ÛŒÙ…ÛŒØ´Ù† Ø±Ø§ Ù†Ù…Ø§ÛŒØ´ Ù…ÛŒâ€ŒØ¯Ù‡Ø¯. -->
  <!-- TÃ¼rkÃ§e: Bu bÃ¶lÃ¼m Ã§Ä±kÄ±ÅŸ mesajÄ±nÄ± ve animasyonu gÃ¶sterir. -->
  <!-- English: This section displays the logout message and animation. -->
  <div class="w-full max-w-md mt-20">
    <div class="logout-container rounded-2xl shadow-2xl p-8 text-center animate-fade-in-up">
      <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4 animate-spin-slow">
        <i class="fas fa-sign-out-alt text-3xl text-white"></i>
      </div>
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Ã‡Ä±kÄ±ÅŸ YapÄ±lÄ±yor...</h1>
      <p class="text-gray-600 mb-4">GÃ¼venli bir ÅŸekilde oturumunuz kapatÄ±lÄ±yor.</p>
      <p class="text-gray-600 text-sm">
        KÄ±sa sÃ¼re iÃ§inde giriÅŸ sayfasÄ±na yÃ¶nlendirileceksiniz.
      </p>
      <a href="../auth/login.php" class="text-blue-600 hover:underline mt-2 inline-block">
        Hemen giriÅŸ sayfasÄ±na gitmek iÃ§in tÄ±klayÄ±n.
      </a>
    </div>

    <!-- Footer -->
    <!-- FarsÃ§a: Ù¾Ø§ÙˆØ±Ù‚ÛŒ ØµÙØ­Ù‡ Ø´Ø§Ù…Ù„ Ù„ÛŒÙ†Ú©â€ŒÙ‡Ø§ÛŒ Ø´Ø±Ø§ÛŒØ· Ø§Ø³ØªÙØ§Ø¯Ù‡ Ùˆ Ø³ÛŒØ§Ø³Øª Ø­ÙØ¸ Ø­Ø±ÛŒÙ… Ø®ØµÙˆØµÛŒ. -->
    <!-- TÃ¼rkÃ§e: Sayfa altbilgisi, kullanÄ±m ÅŸartlarÄ± ve gizlilik politikasÄ± baÄŸlantÄ±larÄ±nÄ± iÃ§erir. -->
    <!-- English: Page footer including terms of use and privacy policy links. -->
    <div class="text-center mt-8 animate-fade-in-up">
      <p class="text-gray-500 text-sm">
        GiriÅŸ yaparak <a href="#" class="text-blue-600 hover:underline">KullanÄ±m ÅžartlarÄ±</a> ve
        <a href="#" class="text-blue-600 hover:underline">Gizlilik PolitikasÄ±</a>'nÄ± kabul etmiÅŸ olursunuz.
      </p>
    </div>
  </div>

</body>
</html>



