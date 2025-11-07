<?php
/**
 * Universal Footer Component for CarWash Website
 * Consistent footer that works across all pages
 * Includes dynamic copyright year and responsive design
 * 
 * Features:
 * - Responsive design (mobile single column, desktop multi-column)
 * - Dynamic copyright year
 * - Contact information
 * - Quick navigation links
 * - Social media links
 * - Professional, clean styling
 */

// Use the same URL variables from header if available
if (!isset($base_url)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/carwash_project';
    
    $home_url = $base_url . '/backend/index.php';
    $about_url = $base_url . '/backend/about.php';
    $contact_url = $base_url . '/backend/contact.php';
    $login_url = $base_url . '/backend/auth/login.php';
    $register_url = $base_url . '/backend/auth/register.php';
}

// Get current year for copyright
$current_year = date('Y');
?>

<!-- Footer -->
<footer class="bg-gray-900 text-white py-12 flex-none">
  <div class="container mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
      
      <!-- Brand Section -->
      <div class="lg:col-span-1">
        <div class="flex items-center space-x-3 mb-6">
          <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
            <i class="fas fa-car text-white text-xl"></i>
          </div>
          <h3 class="text-2xl font-bold">CarWash</h3>
        </div>
        <p class="text-gray-300 text-sm leading-relaxed mb-6">
          Türkiye'nin en güvenilir araç yıkama rezervasyon platformu. Kaliteli hizmet, güvenilir işletmeler ve kolay rezervasyon sistemi.
        </p>
        
        <!-- Social Media Links -->
        <div class="flex space-x-4">
          <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-blue-600 transition-colors">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-blue-400 transition-colors">
            <i class="fab fa-twitter"></i>
          </a>
          <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-pink-600 transition-colors">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
            <i class="fab fa-linkedin-in"></i>
          </a>
        </div>
      </div>
      
      <!-- Quick Links -->
      <div>
        <h4 class="text-lg font-bold mb-6 text-white">Hızlı Bağlantılar</h4>
        <ul class="space-y-3">
          <li>
            <a href="<?php echo $home_url; ?>" class="text-gray-300 hover:text-white transition-colors flex items-center">
              <i class="fas fa-home w-4 mr-2"></i>
              Ana Sayfa
            </a>
          </li>
          <li>
            <a href="<?php echo $about_url; ?>" class="text-gray-300 hover:text-white transition-colors flex items-center">
              <i class="fas fa-info-circle w-4 mr-2"></i>
              Hakkımızda
            </a>
          </li>
          <li>
            <a href="<?php echo $contact_url; ?>" class="text-gray-300 hover:text-white transition-colors flex items-center">
              <i class="fas fa-envelope w-4 mr-2"></i>
              İletişim
            </a>
          </li>
          <li>
            <a href="<?php echo $home_url; ?>#services" class="text-gray-300 hover:text-white transition-colors flex items-center">
              <i class="fas fa-cogs w-4 mr-2"></i>
              Hizmetlerimiz
            </a>
          </li>
          <li>
            <a href="<?php echo $home_url; ?>#pricing" class="text-gray-300 hover:text-white transition-colors flex items-center">
              <i class="fas fa-tags w-4 mr-2"></i>
              Fiyatlar
            </a>
          </li>
        </ul>
      </div>
      
      <!-- Services -->
      <div>
        <h4 class="text-lg font-bold mb-6 text-white">Hizmetlerimiz</h4>
        <ul class="space-y-3">
          <li>
            <span class="text-gray-300 flex items-center">
              <i class="fas fa-spray-can w-4 mr-2 text-blue-400"></i>
              Dış Yıkama
            </span>
          </li>
          <li>
            <span class="text-gray-300 flex items-center">
              <i class="fas fa-broom w-4 mr-2 text-green-400"></i>
              İç Temizlik
            </span>
          </li>
          <li>
            <span class="text-gray-300 flex items-center">
              <i class="fas fa-star w-4 mr-2 text-purple-400"></i>
              Tam Detaylandırma
            </span>
          </li>
          <li>
            <span class="text-gray-300 flex items-center">
              <i class="fas fa-wrench w-4 mr-2 text-orange-400"></i>
              Motor Temizliği
            </span>
          </li>
          <li>
            <span class="text-gray-300 flex items-center">
              <i class="fas fa-shield-alt w-4 mr-2 text-red-400"></i>
              Koruyucu Kaplama
            </span>
          </li>
        </ul>
      </div>
      
      <!-- Contact Info -->
      <div>
        <h4 class="text-lg font-bold mb-6 text-white">İletişim Bilgileri</h4>
        <div class="space-y-4">
          <div class="flex items-start space-x-3">
            <i class="fas fa-map-marker-alt text-blue-400 mt-1"></i>
            <div>
              <p class="text-gray-300 text-sm">
                Atatürk Mahallesi<br>
                İstiklal Caddesi No: 123<br>
                34000 İstanbul/Türkiye
              </p>
            </div>
          </div>
          
          <div class="flex items-center space-x-3">
            <i class="fas fa-phone text-green-400"></i>
            <a href="tel:+902123456789" class="text-gray-300 hover:text-white transition-colors">
              +90 (212) 345 67 89
            </a>
          </div>
          
          <div class="flex items-center space-x-3">
            <i class="fas fa-envelope text-purple-400"></i>
            <a href="mailto:info@carwash.com" class="text-gray-300 hover:text-white transition-colors">
              info@carwash.com
            </a>
          </div>
          
          <div class="flex items-center space-x-3">
            <i class="fas fa-clock text-orange-400"></i>
            <div>
              <p class="text-gray-300 text-sm">
                Pazartesi - Pazar<br>
                08:00 - 20:00
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Separator -->
    <div class="border-t border-gray-800 my-8"></div>
    
    <!-- Bottom Section -->
    <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
      <!-- Copyright -->
      <div class="text-center md:text-left">
        <p class="text-gray-400 text-sm">
          &copy; <?php echo $current_year; ?> CarWash. Tüm hakları saklıdır.
        </p>
        <p class="text-gray-500 text-xs mt-1">
          Güvenilir araç yıkama hizmetleri için tercih edilen platform.
        </p>
      </div>
      
      <!-- Legal Links -->
      <div class="flex flex-wrap justify-center md:justify-end space-x-6 text-sm">
        <a href="#" class="text-gray-400 hover:text-white transition-colors">
          Gizlilik Politikası
        </a>
        <a href="#" class="text-gray-400 hover:text-white transition-colors">
          Kullanım Şartları
        </a>
        <a href="#" class="text-gray-400 hover:text-white transition-colors">
          Çerez Politikası
        </a>
        <a href="#" class="text-gray-400 hover:text-white transition-colors">
          KVKK
        </a>
      </div>
    </div>
    
    <!-- Additional Info Bar -->
    <div class="mt-8 pt-6 border-t border-gray-800">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center md:text-left">
        <div class="flex items-center justify-center md:justify-start space-x-2">
          <i class="fas fa-shield-check text-green-400"></i>
          <span class="text-gray-300 text-sm">SSL Güvenlik</span>
        </div>
        <div class="flex items-center justify-center space-x-2">
          <i class="fas fa-mobile-alt text-blue-400"></i>
          <span class="text-gray-300 text-sm">Mobil Uyumlu</span>
        </div>
        <div class="flex items-center justify-center md:justify-end space-x-2">
          <i class="fas fa-headset text-purple-400"></i>
          <span class="text-gray-300 text-sm">7/24 Destek</span>
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="fixed bottom-6 right-6 w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 opacity-0 invisible z-40">
  <i class="fas fa-chevron-up"></i>
</button>

<style>
  /* Back to top button animation */
  #backToTop.show {
    opacity: 1;
    visibility: visible;
  }
  
  /* Smooth hover effects */
  footer a:hover {
    transform: translateX(2px);
  }
  
  /* Mobile optimizations */
  @media (max-width: 768px) {
    footer .grid {
      gap: 2rem;
    }
    
    footer h4 {
      font-size: 1.1rem;
    }
  }
</style>

<script>
  // Back to top button functionality
  window.addEventListener('scroll', function() {
    const backToTop = document.getElementById('backToTop');
    if (window.pageYOffset > 300) {
      backToTop.classList.add('show');
    } else {
      backToTop.classList.remove('show');
    }
  });
  
  document.getElementById('backToTop').addEventListener('click', function() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
</script>

<script>
  // Adjust any fixed sidebar so it stops above the footer instead of overlapping it.
  function adjustSidebarsToFooter() {
    const footer = document.querySelector('footer');
    if (!footer) return;
    const footerHeight = footer.offsetHeight || 0;
    document.querySelectorAll('.sidebar-fixed').forEach(el => {
      // apply inline bottom to override utility classes like bottom-0
      el.style.bottom = footerHeight + 'px';
    });
  }

  window.addEventListener('load', adjustSidebarsToFooter);
  window.addEventListener('resize', adjustSidebarsToFooter);
</script>

<?php 
// Include Universal JavaScript for entire website
include_once(__DIR__ . '/universal_scripts.php');
?>

</body>
</html>
