<?php
// Set page-specific variables
$page_title = 'İletişim - CarWash';
$current_page = 'contact';

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8">
  <div class="max-w-7xl mx-auto">
    
    <!-- Page Header -->
    <div class="text-center mb-8 lg:mb-12">
      <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-800 mb-3 lg:mb-4">
        İletişim
      </h1>
      <p class="text-base md:text-lg lg:text-xl text-gray-600 max-w-2xl mx-auto px-4">
        Sorularınız mı var? Rezervasyon yapmak mı istiyorsunuz? 
        Bizimle iletişime geçmek için aşağıdaki bilgileri kullanabilirsiniz.
      </p>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
      
      <!-- Contact Information -->
      <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6 lg:mb-8">İletişim Bilgileri</h2>
        
        <!-- Contact Cards -->
        <div class="space-y-4 lg:space-y-6">
          <div class="bg-white p-6 rounded-lg shadow-lg border-l-4 border-blue-600">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
              </div>
              <h3 class="text-lg md:text-xl font-bold text-gray-800">Adres</h3>
            </div>
            <p class="text-sm md:text-base text-gray-600">
              Atatürk Mahallesi, Cumhuriyet Caddesi No: 123<br>
              Çankaya/Ankara 06100<br>
              Türkiye
            </p>
          </div>
          
          <div class="bg-white p-6 rounded-lg shadow-lg border-l-4 border-green-600">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-phone text-green-600 text-xl"></i>
              </div>
              <h3 class="text-lg md:text-xl font-bold text-gray-800">Telefon</h3>
            </div>
            <p class="text-sm md:text-base text-gray-600">
              <a href="tel:+902123456789" class="hover:text-green-600 transition-colors">
                0212 345 67 89
              </a><br>
              <a href="tel:+905551234567" class="hover:text-green-600 transition-colors">
                0555 123 45 67 (Mobil)
              </a>
            </p>
          </div>
          
          <div class="bg-white p-6 rounded-lg shadow-lg border-l-4 border-purple-600">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-envelope text-purple-600 text-xl"></i>
              </div>
              <h3 class="text-lg md:text-xl font-bold text-gray-800">E-posta</h3>
            </div>
            <p class="text-sm md:text-base text-gray-600">
              <a href="mailto:info@carwash.com" class="hover:text-purple-600 transition-colors">
                info@carwash.com
              </a><br>
              <a href="mailto:rezervasyon@carwash.com" class="hover:text-purple-600 transition-colors">
                rezervasyon@carwash.com
              </a>
            </p>
          </div>
          
          <div class="bg-white p-6 rounded-lg shadow-lg border-l-4 border-orange-600">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-clock text-orange-600 text-xl"></i>
              </div>
              <h3 class="text-lg md:text-xl font-bold text-gray-800">Çalışma Saatleri</h3>
            </div>
            <div class="text-sm md:text-base text-gray-600">
              <p class="mb-2"><strong>Pazartesi - Cumartesi:</strong> 08:00 - 18:00</p>
              <p class="mb-2"><strong>Pazar:</strong> 09:00 - 17:00</p>
              <p class="text-sm text-gray-500">Resmi tatillerde kapalıyız.</p>
            </div>
          </div>
        </div>
        
        <!-- Social Media -->
        <div class="mt-6 lg:mt-8">
          <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Sosyal Medya</h3>
          <div class="flex space-x-4">
            <a href="#" class="w-10 h-10 bg-blue-600 text-white rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="w-10 h-10 bg-blue-400 text-white rounded-lg flex items-center justify-center hover:bg-blue-500 transition-colors">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="w-10 h-10 bg-pink-600 text-white rounded-lg flex items-center justify-center hover:bg-pink-700 transition-colors">
              <i class="fab fa-instagram"></i>
            </a>
            <a href="#" class="w-10 h-10 bg-blue-800 text-white rounded-lg flex items-center justify-center hover:bg-blue-900 transition-colors">
              <i class="fab fa-linkedin-in"></i>
            </a>
          </div>
        </div>
      </div>
      
      <!-- Contact Form -->
      <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6 lg:mb-8">Bize Yazın</h2>
        
        <form action="#" method="POST" class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label for="name" class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-user mr-2 text-blue-600"></i>Ad Soyad
              </label>
              <input type="text" id="name" name="name" required
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>
            <div>
              <label for="email" class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-envelope mr-2 text-blue-600"></i>E-posta
              </label>
              <input type="email" id="email" name="email" required
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label for="phone" class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-phone mr-2 text-blue-600"></i>Telefon
              </label>
              <input type="tel" id="phone" name="phone"
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>
            <div>
              <label for="subject" class="block text-sm font-bold text-gray-700 mb-2">
                <i class="fas fa-tag mr-2 text-blue-600"></i>Konu
              </label>
              <select id="subject" name="subject" required
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                <option value="">Konu seçin...</option>
                <option value="rezervasyon">Rezervasyon</option>
                <option value="bilgi">Bilgi Almak</option>
                <option value="sikayet">Şikayet</option>
                <option value="oneri">Öneri</option>
                <option value="diger">Diğer</option>
              </select>
            </div>
          </div>
          
          <div class="mb-6">
            <label for="message" class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-comment mr-2 text-blue-600"></i>Mesaj
            </label>
            <textarea id="message" name="message" rows="6" required
                      placeholder="Mesajınızı buraya yazın..."
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-vertical"></textarea>
          </div>
          
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input type="checkbox" id="privacy" name="privacy" required
                     class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
              <label for="privacy" class="ml-2 text-sm text-gray-600">
                <a href="#" class="text-blue-600 hover:underline">Kişisel verilerin korunması</a> 
                politikasını okudum ve kabul ediyorum.
              </label>
            </div>
          </div>
          
          <div class="mt-6">
            <button type="submit" 
                    class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 font-medium flex items-center justify-center">
              <i class="fas fa-paper-plane mr-2"></i>
              Mesajı Gönder
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Map Section -->
    <div class="mt-12 lg:mt-16">
      <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6 lg:mb-8 text-center">Konum</h2>
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="relative w-full" style="height: 450px;">
          <!-- Google Maps Embed - Ankara/Çankaya Location -->
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3060.0827793676866!2d32.85384!3d39.91987!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14d34f190a9c6d33%3A0x5e8e8f0e8e8e8e8e!2s%C3%87ankaya%2C%20Ankara!5e0!3m2!1str!2str!4v1234567890123!5m2!1str!2str"
            style="border:0; display:block; margin:0; padding:0; position:absolute; top:0; left:0; width:100%; height:100%;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
          
          <!-- Fallback for browsers that don't support iframe -->
          <noscript>
            <div class="bg-gray-200 h-full flex items-center justify-center">
              <div class="text-center text-gray-600">
                <i class="fas fa-map-marked-alt text-4xl mb-4"></i>
                <p class="text-lg font-medium">Haritayı görüntülemek için JavaScript'i etkinleştirin</p>
              </div>
            </div>
          </noscript>
        </div>
        
        <!-- Map Info Card -->
        <div class="p-4 md:p-6 bg-gradient-to-r from-blue-50 to-blue-100 border-t-4 border-blue-600">
          <div class="flex items-start">
            <i class="fas fa-map-marker-alt text-blue-600 text-xl mt-1 mr-3 flex-shrink-0"></i>
            <div class="flex-1">
              <p class="text-sm md:text-base text-gray-800 font-bold mb-1">Atatürk Mahallesi, Cumhuriyet Caddesi No: 123</p>
              <p class="text-xs md:text-sm text-gray-600 mb-3">Çankaya/Ankara 06100, Türkiye</p>
              <a href="https://www.google.com/maps/dir//39.91987,32.85384" 
                 target="_blank"
                 class="inline-flex items-center px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors shadow-sm">
                <i class="fas fa-directions mr-2"></i>
                Yol Tarifi Al
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</main>

<?php include 'includes/footer.php'; ?>
