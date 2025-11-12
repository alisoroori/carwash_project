<?php
/**
 * About Page - CarWash
 * Uses standard header similar to login page
 */

// Set page-specific variables
$page_title = 'Hakkımızda - CarWash';
$current_page = 'about';
$is_dashboard = false; // Bu sayfa bir kontrol paneli değil, standart bir sayfadır

// Build navigation URLs (relative paths from backend/)
$home_url = './index.php';
$about_url = './about.php';
$contact_url = './contact.php';
$login_url = './auth/login.php';
$register_url = './auth/register.php';

// Include standard header from the correct path
include __DIR__ . '/includes/header.php';
?>

<!-- Fixed About.php layout: MainContent spacing, full width, and text-center padding -->
<!-- Fixed MainContent overlap with Header -->
<style>
  /* Ensure main content sits below fixed header and works responsively */
  :root { --site-header-height: 60px; }
  /* mobile adjustments match header.php media adjustments */
  @media (max-width: 479px) { :root { --site-header-height: 56px; } }
  @media (min-width: 480px) and (max-width: 639px) { :root { --site-header-height: 58px; } }
  @media (min-width: 640px) and (max-width: 1023px) { :root { --site-header-height: 62px; } }

  /* main content padding ensures it's not hidden behind the fixed header */
  .main-content {
    padding-top: calc(var(--site-header-height) + 1rem);
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box;
  }

  /* Center inner content area and constrain for readability */
  .main-inner {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 1rem;
    padding-right: 1rem;
  }

  /* Make .text-center sections have internal padding and spacing below */
  .main-content .text-center {
    padding-left: 1rem;
    padding-right: 1rem;
    margin-bottom: 1.25rem; /* 20px */
  }

  /* Slightly more breathing room on larger screens */
  @media (min-width: 1024px) {
    .main-content { padding-top: calc(var(--site-header-height) + 1.5rem); }
  }

  /* If prefers-reduced-motion, avoid large visual shifts */
  @media (prefers-reduced-motion: reduce) {
    .main-content { transition: none; }
  }
</style>

<!-- Main Content -->
<main id="main-content" class="main-content container mx-auto px-4 py-8">
  <div class="max-w-4xl mx-auto">
    
    <!-- Page Header -->
    <div class="text-center mb-12">
      <h1 class="text-4xl lg:text-5xl font-bold text-gray-800 mb-4">
        Hakkımızda
      </h1>
      <p class="text-xl text-gray-600 max-w-2xl mx-auto">
        CarWash olarak, aracınızı en iyi şekilde temizleme konusunda uzmanız. 
        Modern teknoloji ve deneyimli ekibimizle hizmetinizdeyiz.
      </p>
    </div>
    
    <!-- About Section (full-width) -->
  </div> <!-- /.max-w container (closed to allow full-width section) -->

  <section class="w-full bg-white">
    <div class="max-w-screen-xl mx-auto px-4 lg:px-8 py-12">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16 items-center">
        <div>
          <h2 class="text-3xl font-bold text-gray-800 mb-6">Misyonumuz</h2>
          <p class="text-gray-600 mb-4 leading-relaxed">
            CarWash olarak amacımız, müşterilerimizin araçlarını en kaliteli şekilde temizleyerek 
            onlara güvenli ve konforlu bir sürüş deneyimi sunmaktır. Modern ekipmanlarımız ve 
            deneyimli personelimizle her zaman en iyi hizmeti vermeye odaklanıyoruz.
          </p>
          <p class="text-gray-600 leading-relaxed">
            Çevre dostu ürünler kullanarak doğaya zarar vermeden aracınızı temizliyoruz. 
            Su tasarrufu sağlayan teknolojilerimizle hem çevreyi koruyor hem de kaynaklarımızı 
            verimli kullanıyoruz.
          </p>
        </div>
        <div class="bg-blue-50 p-8 md:p-10 lg:p-12 rounded-lg shadow-sm">
          <h3 class="text-2xl font-bold text-blue-800 mb-4">Neden CarWash?</h3>
          <ul class="space-y-4 leading-relaxed">
            <li class="flex items-start">
              <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
              <span>7/24 online rezervasyon sistemi</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
              <span>Profesyonel ve deneyimli ekip</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
              <span>Çevre dostu temizlik ürünleri</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
              <span>Modern teknoloji ve ekipman</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
              <span>Uygun fiyat garantisi</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
              <span>Müşteri memnuniyeti odaklı hizmet</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <div class="max-w-4xl mx-auto"> <!-- reopen container for remaining content -->
    
    <!-- Team Section -->
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-800 mb-8">Ekibimiz</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <div class="w-20 h-20 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-tie text-2xl text-white"></i>
          </div>
          <h3 class="text-xl font-bold mb-2">Ahmet Yılmaz</h3>
          <p class="text-gray-600 text-sm mb-2">Genel Müdür</p>
          <p class="text-gray-500 text-sm">15 yıllık deneyim ile ekibimizi yönetiyor.</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <div class="w-20 h-20 bg-gradient-to-r from-green-600 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-tools text-2xl text-white"></i>
          </div>
          <h3 class="text-xl font-bold mb-2">Mehmet Demir</h3>
          <p class="text-gray-600 text-sm mb-2">Teknik Uzman</p>
          <p class="text-gray-500 text-sm">Ekipman bakımı ve kalite kontrol sorumlusu.</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <div class="w-20 h-20 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-headset text-2xl text-white"></i>
          </div>
          <h3 class="text-xl font-bold mb-2">Ayşe Kaya</h3>
          <p class="text-gray-600 text-sm mb-2">Müşteri Hizmetleri</p>
          <p class="text-gray-500 text-sm">Müşteri memnuniyeti ve destek hizmetleri.</p>
        </div>
      </div>
    </div>
    
  <!-- History Section -->
  <div class="bg-gray-50 p-8 rounded-lg mt-8 mb-12">
      <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Hikayemiz</h2>
      <div class="max-w-3xl mx-auto">
        <p class="text-gray-600 mb-4 leading-relaxed">
          CarWash, 2015 yılında küçük bir aile işletmesi olarak başladı. Kurucu ortaklarımız, 
          kaliteli araç yıkama hizmetinin şehrimizde eksik olduğunu fark ederek bu alanda 
          hizmet vermeye karar verdiler.
        </p>
        <p class="text-gray-600 mb-4 leading-relaxed">
          Başlangıçta sadece temel yıkama hizmetleri sunarken, müşteri taleplerine göre 
          hizmet yelpazemizi genişlettik. Bugün, en modern ekipmanlarla donatılmış 
          tesislerimizde profesyonel detay temizlik hizmetleri sunuyoruz.
        </p>
        <p class="text-gray-600 leading-relaxed">
          10 yılı aşkın tecrübemizle binlerce müşteriye hizmet verdik ve müşteri 
          memnuniyetinde %98 başarı oranına ulaştık. Geleceğe doğru büyümeye devam ederken, 
          kalitemizden hiç ödün vermiyoruz.
        </p>
      </div>
    </div>
    
    <!-- Call to Action -->
    <div class="text-center">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Hizmetlerimizi Deneyimleyin</h2>
      <p class="text-gray-600 mb-6">
        Aracınız için en iyi bakımı almaya hazır mısınız?
      </p>
      <a href="<?php echo $login_url; ?>" 
         class="inline-flex items-center bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium text-lg mr-4">
        <i class="fas fa-calendar-alt mr-2"></i>
        Rezervasyon Yap
      </a>
      <a href="<?php echo $contact_url; ?>" 
         class="inline-flex items-center border border-blue-600 text-blue-600 px-8 py-3 rounded-lg hover:bg-blue-50 transition-colors font-medium text-lg">
        <i class="fas fa-phone mr-2"></i>
        İletişime Geç
      </a>
    </div>
    
  </div>
</main>

<?php 
// Include footer
include __DIR__ . '/includes/footer.php'; 
?>
