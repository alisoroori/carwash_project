<?php
// Farsça: این فایل شامل کدهای HTML داشبورد مدیریت کارواش است.
// Türkçe: Bu dosya, araç yıkama yönetim paneli HTML kodlarını içermektedir.
// English: This file contains the HTML code for the car wash management dashboard.
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - İşletme Paneli</title>
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

    /* Farsça: گرادیانت برای نوار کناری. */
    /* Türkçe: Kenar çubuğu için gradyan. */
    /* English: Gradient for the sidebar. */
    .sidebar-gradient {
      background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    }

    /* Farsça: استایل کارت‌ها هنگام هاور: بزرگنمایی و سایه. */
    /* Türkçe: Kartların üzerine gelindiğinde stili: büyütme ve gölge. */
    /* English: Card style on hover: scale and shadow. */
    .card-hover {
      transition: all 0.3s ease;
    }
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    /* Farsça: استایل وضعیت "در انتظار". */
    /* Türkçe: "Bekliyor" durumu stili. */
    /* English: "Pending" status style. */
    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }

    /* Farsça: استایل وضعیت "تایید شده". */
    /* Türkçe: "Onaylandı" durumu stili. */
    /* English: "Confirmed" status style. */
    .status-confirmed {
      background: #d1fae5;
      color: #065f46;
    }

    /* Farsça: استایل وضعیت "در حال انجام". */
    /* Türkçe: "Devam Ediyor" durumu stili. */
    /* English: "In Progress" status style. */
    .status-in-progress {
      background: #dbeafe;
      color: #1e40af;
    }

    /* Farsça: استایل وضعیت "تکمیل شده". */
    /* Türkçe: "Tamamlandı" durumu stili. */
    /* English: "Completed" status style. */
    .status-completed {
      background: #e0e7ff;
      color: #3730a3;
    }

    /* Farsça: استایل وضعیت "لغو شده". */
    /* Türkçe: "İptal Edildi" durumu stili. */
    /* English: "Cancelled" status style. */
    .status-cancelled {
      background: #fecaca;
      color: #991b1b;
    }

    /* Farsça: استایل اولویت "بالا". */
    /* Türkçe: "Yüksek" öncelik stili. */
    /* English: "High" priority style. */
    .priority-high {
      background: #fee2e2;
      color: #dc2626;
    }

    /* Farsça: استایل اولویت "متوسط". */
    /* Türkçe: "Orta" öncelik stili. */
    /* English: "Medium" priority style. */
    .priority-medium {
      background: #fef3c7;
      color: #d97706;
    }

    /* Farsça: استایل اولویت "پایین". */
    /* Türkçe: "Düşük" öncelik stili. */
    /* English: "Low" priority style. */
    .priority-low {
      background: #d1fae5;
      color: #059669;
    }

    /* Farsça: استایل‌های سوئیچ تغییر وضعیت. */
    /* Türkçe: Geçiş anahtarı stilleri. */
    /* English: Toggle Switch Styles. */
    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 34px;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 34px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 26px;
      width: 26px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: #34c759; /* Green for On */
    }

    input:checked + .slider:before {
      transform: translateX(26px);
    }

    /* Farsça: استایل خاص برای زمانی که چک‌باکس انتخاب نشده است (وضعیت خاموش). */
    /* Türkçe: Onay kutusu işaretlenmediğinde (Kapalı durumu) özel stil. */
    /* English: Specific style for when checkbox is unchecked (Off state). */
    #workplaceStatus:not(:checked) + .slider {
      background-color: #ff3b30; /* Red for Off */
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

  <!-- Header -->
  <!-- Farsça: این بخش سربرگ صفحه را شامل می‌شود. -->
  <!-- Türkçe: Bu bölüm sayfa başlığını içerir. -->
  <!-- English: This section includes the page header. -->
  <header class="bg-white shadow-lg sticky top-0 z-50">
    <div class="container mx-auto px-4">
      <div class="flex justify-between items-center py-4">
        <div class="flex items-center space-x-4">
          <i class="fas fa-car text-3xl text-blue-600"></i>
          <h1 class="text-2xl font-bold text-blue-600">CarWash Pro</h1>
        </div>

        <div class="flex items-center space-x-4">
          <div class="hidden md:flex items-center space-x-2">
            <i class="fas fa-building text-blue-600"></i>
            <span class="text-gray-700 font-medium">CarWash Merkez</span>
          </div>
          <div class="flex space-x-2 items-center">
            <button onclick="toggleNotifications()" class="relative p-2 text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-bell text-xl"></i>
              <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">5</span>
            </button>
            <a href="../index.php" class="p-2 text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-home text-xl"></i>
            </a>
            <!-- Farsça: دکمه تغییر وضعیت روشن/خاموش. -->
            <!-- Türkçe: Açma/Kapama Geçiş Düğmesi. -->
            <!-- English: On/Off Toggle Button. -->
            <label class="toggle-switch">
              <input type="checkbox" id="workplaceStatus" checked onchange="toggleWorkplaceStatus()">
              <span class="slider"></span>
            </label>
            <a href="../auth/logout.php" class="p-2 text-gray-600 hover:text-red-600 transition-colors">
              <i class="fas fa-sign-out-alt text-xl"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <!-- Farsça: نوار کناری شامل لینک‌های ناوبری. -->
    <!-- Türkçe: Gezinme bağlantılarını içeren kenar çubuğu. -->
    <!-- English: Sidebar containing navigation links. -->
    <aside class="w-64 sidebar-gradient text-white sticky top-20 h-fit">
      <div class="p-6">
        <div class="text-center mb-8">
          <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-building text-3xl"></i>
          </div>
          <h3 class="text-xl font-bold">CarWash Merkez</h3>
          <p class="text-sm opacity-75">Premium İşletme</p>
        </div>

        <nav class="space-y-2">
          <a href="#dashboard" onclick="showSection('dashboard')" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Genel Bakış
          </a>
          <a href="#reservations" onclick="showSection('reservations')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-calendar-check mr-3"></i>
            Rezervasyonlar
          </a>
          <a href="#customers" onclick="showSection('customers')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-users mr-3"></i>
            Müşteriler
          </a>
          <a href="#services" onclick="showSection('services')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-wrench mr-3"></i>
            Hizmetler
          </a>
          <a href="#staff" onclick="showSection('staff')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-user-tie mr-3"></i>
            Personel
          </a>
          <a href="#financial" onclick="showSection('financial')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-chart-line mr-3"></i>
            Finansal
          </a>
          <a href="#reports" onclick="showSection('reports')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-chart-bar mr-3"></i>
            Raporlar
          </a>
          <a href="#invoices" onclick="showSection('invoices')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-file-invoice mr-3"></i>
            Faturalar
          </a>
          <a href="#settings" onclick="showSection('settings')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-cog mr-3"></i>
            Ayarlar
          </a>
        </nav>
      </div>
    </aside>

    <!-- Main Content -->
    <!-- Farsça: محتوای اصلی داشبورد. -->
    <!-- Türkçe: Ana kontrol paneli içeriği. -->
    <!-- English: Main dashboard content. -->
    <main class="flex-1 p-8">
      <!-- Dashboard Overview -->
      <!-- Farsça: بخش نمای کلی داشبورد. -->
      <!-- Türkçe: Kontrol paneli genel bakış bölümü. -->
      <!-- English: Dashboard Overview section. -->
      <section id="dashboard" class="section-content">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Genel Bakış</h2>
          <p class="text-gray-600">İşletmenizin günlük özeti ve performans metrikleri</p>
        </div>

        <!-- Key Metrics -->
        <!-- Farsça: معیارهای کلیدی عملکرد. -->
        <!-- Türkçe: Temel performans metrikleri. -->
        <!-- English: Key Metrics. -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Bugünkü Rezervasyon</p>
                <p class="text-3xl font-bold text-blue-600">12</p>
                <p class="text-sm text-green-600 mt-1">+3 önceki güne göre</p>
              </div>
              <i class="fas fa-calendar-check text-4xl text-blue-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Aylık Gelir</p>
                <p class="text-3xl font-bold text-green-600">₺15,420</p>
                <p class="text-sm text-green-600 mt-1">+12% artış</p>
              </div>
              <i class="fas fa-money-bill-wave text-4xl text-green-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Aktif Müşteri</p>
                <p class="text-3xl font-bold text-purple-600">156</p>
                <p class="text-sm text-purple-600 mt-1">+8 yeni müşteri</p>
              </div>
              <i class="fas fa-users text-4xl text-purple-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Ortalama Puan</p>
                <p class="text-3xl font-bold text-yellow-600">4.8★</p>
                <p class="text-sm text-yellow-600 mt-1">95% memnuniyet</p>
              </div>
              <i class="fas fa-star text-4xl text-yellow-600 opacity-20"></i>
            </div>
          </div>
        </div>

        <!-- Today's Schedule & Recent Activity -->
        <!-- Farsça: برنامه امروز و فعالیت‌های اخیر. -->
        <!-- Türkçe: Bugünün Programı ve Son Aktiviteler. -->
        <!-- English: Today's Schedule & Recent Activity. -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-clock text-blue-600 mr-2"></i>
              Bugünün Programı
            </h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-car text-blue-600"></i>
                  </div>
                  <div>
                    <h4 class="font-bold">Dış Yıkama + İç Temizlik</h4>
                    <p class="text-sm text-gray-600">Ahmet Yılmaz - 34 ABC 123</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-blue-600">10:00</p>
                  <span class="status-confirmed px-2 py-1 rounded-full text-xs">Onaylandı</span>
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-car text-green-600"></i>
                  </div>
                  <div>
                    <h4 class="font-bold">Tam Detaylandırma</h4>
                    <p class="text-sm text-gray-600">Fatma Kaya - 34 XYZ 789</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-green-600">14:00</p>
                  <span class="status-in-progress px-2 py-1 rounded-full text-xs">Devam Ediyor</span>
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-car text-purple-600"></i>
                  </div>
                  <div>
                    <h4 class="font-bold">Premium Paket</h4>
                    <p class="text-sm text-gray-600">Mehmet Demir - 34 DEF 456</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-purple-600">16:00</p>
                  <span class="status-pending px-2 py-1 rounded-full text-xs">Bekliyor</span>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-bell text-blue-600 mr-2"></i>
              Son Aktiviteler
            </h3>
            <div class="space-y-4">
              <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg">
                <i class="fas fa-plus-circle text-blue-600 mt-1"></i>
                <div>
                  <p class="text-sm font-medium">Yeni rezervasyon alındı</p>
                  <p class="text-xs text-gray-600">Premium paket - 2 saat önce</p>
                </div>
              </div>

              <div class="flex items-start space-x-3 p-3 bg-green-50 rounded-lg">
                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                <div>
                  <p class="text-sm font-medium">Ödeme tamamlandı</p>
                  <p class="text-xs text-gray-600">₺180 - 3 saat önce</p>
                </div>
              </div>

              <div class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg">
                <i class="fas fa-star text-yellow-600 mt-1"></i>
                <div>
                  <p class="text-sm font-medium">Yeni yorum alındı</p>
                  <p class="text-xs text-gray-600">5 yıldız - 5 saat önce</p>
                </div>
              </div>

              <div class="flex items-start space-x-3 p-3 bg-purple-50 rounded-lg">
                <i class="fas fa-chart-line text-purple-600 mt-1"></i>
                <div>
                  <p class="text-sm font-medium">Aylık hedef aşıldı</p>
                  <p class="text-xs text-gray-600">₺15,420 / ₺15,000 - Bugün</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Reservations Management -->
      <!-- Farsça: بخش مدیریت رزروها. -->
      <!-- Türkçe: Rezervasyon Yönetimi bölümü. -->
      <!-- English: Reservations Management section. -->
      <section id="reservations" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Rezervasyon Yönetimi</h2>
          <p class="text-gray-600">Tüm rezervasyonları görüntüleyin, onaylayın ve yönetin</p>
        </div>

        <!-- Filters and Actions -->
        <!-- Farsça: فیلترها و اقدامات. -->
        <!-- Türkçe: Filtreler ve Eylemler. -->
        <!-- English: Filters and Actions. -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
          <div class="flex flex-wrap justify-between items-center gap-4">
            <div class="flex space-x-4">
              <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option>Tüm Durumlar</option>
                <option>Bekliyor</option>
                <option>Onaylandı</option>
                <option>Devam Ediyor</option>
                <option>Tamamlandı</option>
                <option>İptal Edildi</option>
              </select>

              <input type="date" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>

            <div class="flex space-x-2">
              <button onclick="openManualReservationModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                <i class="fas fa-plus mr-2"></i>Manuel Rezervasyon
              </button>
              <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-all">
                <i class="fas fa-download mr-2"></i>Export
              </button>
            </div>
          </div>
        </div>

        <!-- Reservations Table -->
        <!-- Farsça: جدول رزروها. -->
        <!-- Türkçe: Rezervasyon Tablosu. -->
        <!-- English: Reservations Table. -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Müşteri</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Araç</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih/Saat</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Öncelik</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div>
                      <div class="font-medium">Ahmet Yılmaz</div>
                      <div class="text-sm text-gray-500">0555 123 4567</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm">Dış Yıkama + İç Temizlik</td>
                  <td class="px-6 py-4 text-sm">Toyota Corolla<br>34 ABC 123</td>
                  <td class="px-6 py-4 text-sm">15.12.2024<br>10:00</td>
                  <td class="px-6 py-4"><span class="status-confirmed px-2 py-1 rounded-full text-xs">Onaylandı</span></td>
                  <td class="px-6 py-4 font-medium">₺130</td>
                  <td class="px-6 py-4"><span class="priority-medium px-2 py-1 rounded-full text-xs">Orta</span></td>
                  <td class="px-6 py-4 text-sm">
                    <button class="text-blue-600 hover:text-blue-900 mr-2">Düzenle</button>
                    <button class="text-green-600 hover:text-green-900 mr-2">Tamamla</button>
                    <button class="text-red-600 hover:text-red-900">İptal</button>
                  </td>
                </tr>

                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div>
                      <div class="font-medium">Fatma Kaya</div>
                      <div class="text-sm text-gray-500">0555 987 6543</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm">Tam Detaylandırma</td>
                  <td class="px-6 py-4 text-sm">Honda Civic<br>34 XYZ 789</td>
                  <td class="px-6 py-4 text-sm">15.12.2024<br>14:00</td>
                  <td class="px-6 py-4"><span class="status-in-progress px-2 py-1 rounded-full text-xs">Devam Ediyor</span></td>
                  <td class="px-6 py-4 font-medium">₺200</td>
                  <td class="px-6 py-4"><span class="priority-high px-2 py-1 rounded-full text-xs">Yüksek</span></td>
                  <td class="px-6 py-4 text-sm">
                    <button class="text-blue-600 hover:text-blue-900 mr-2">Detay</button>
                    <button class="text-green-600 hover:text-green-900 mr-2">Tamamla</button>
                  </td>
                </tr>

                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div>
                      <div class="font-medium">Mehmet Demir</div>
                      <div class="text-sm text-gray-500">0555 456 7890</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm">Premium Paket</td>
                  <td class="px-6 py-4 text-sm">BMW 3 Serisi<br>34 DEF 456</td>
                  <td class="px-6 py-4 text-sm">15.12.2024<br>16:00</td>
                  <td class="px-6 py-4"><span class="status-pending px-2 py-1 rounded-full text-xs">Bekliyor</span></td>
                  <td class="px-6 py-4 font-medium">₺250</td>
                  <td class="px-6 py-4"><span class="priority-low px-2 py-1 rounded-full text-xs">Düşük</span></td>
                  <td class="px-6 py-4 text-sm">
                    <button class="text-green-600 hover:text-green-900 mr-2">Onayla</button>
                    <button class="text-yellow-600 hover:text-yellow-900 mr-2">Yeniden Planla</button>
                    <button class="text-red-600 hover:text-red-900">Reddet</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Customer Management -->
      <!-- Farsça: بخش مدیریت مشتریان. -->
      <!-- Türkçe: Müşteri Yönetimi bölümü. -->
      <!-- English: Customer Management section. -->
      <section id="customers" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Müşteri Yönetimi</h2>
          <p class="text-gray-600">Müşteri bilgilerini yönetin ve geçmişlerini görüntüleyin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                  <h3 class="text-xl font-bold">Müşteri Listesi</h3>
                  <button onclick="openCustomerModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                    <i class="fas fa-plus mr-2"></i>Müşteri Ekle
                  </button>
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Müşteri</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İletişim</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Toplam Harcama</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Son Ziyaret</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="font-bold text-blue-600">AY</span>
                          </div>
                          <div>
                            <div class="font-medium">Ahmet Yılmaz</div>
                            <div class="text-sm text-gray-500">Premium Müşteri</div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm">
                        <div>ali.yilmaz@email.com</div>
                        <div class="text-gray-500">0555 123 4567</div>
                      </td>
                      <td class="px-6 py-4 font-medium">₺2,450</td>
                      <td class="px-6 py-4 text-sm">12.12.2024</td>
                      <td class="px-6 py-4"><span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Aktif</span></td>
                      <td class="px-6 py-4 text-sm">
                        <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                        <button class="text-yellow-600 hover:text-yellow-900">Düzenle</button>
                      </td>
                    </tr>

                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="font-bold text-green-600">FK</span>
                          </div>
                          <div>
                            <div class="font-medium">Fatma Kaya</div>
                            <div class="text-sm text-gray-500">Standart Müşteri</div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm">
                        <div>fatma.kaya@email.com</div>
                        <div class="text-gray-500">0555 987 6543</div>
                      </td>
                      <td class="px-6 py-4 font-medium">₺890</td>
                      <td class="px-6 py-4 text-sm">10.12.2024</td>
                      <td class="px-6 py-4"><span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Aktif</span></td>
                      <td class="px-6 py-4 text-sm">
                        <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                        <button class="text-yellow-600 hover:text-yellow-900">Düzenle</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Müşteri İstatistikleri</h3>
            <div class="space-y-6">
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">156</div>
                <div class="text-sm text-gray-600">Toplam Müşteri</div>
              </div>

              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span class="text-sm">Premium</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-20 bg-gray-200 rounded-full h-2">
                      <div class="bg-blue-600 h-2 rounded-full" style="width: 35%"></div>
                    </div>
                    <span class="text-sm font-medium">35%</span>
                  </div>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm">Standart</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-20 bg-gray-200 rounded-full h-2">
                      <div class="bg-green-600 h-2 rounded-full" style="width: 50%"></div>
                    </div>
                    <span class="text-sm font-medium">50%</span>
                  </div>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-sm">Tek Seferlik</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-20 bg-gray-200 rounded-full h-2">
                      <div class="bg-yellow-600 h-2 rounded-full" style="width: 15%"></div>
                    </div>
                    <span class="text-sm font-medium">15%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Services Management -->
      <!-- Farsça: بخش مدیریت خدمات. -->
      <!-- Türkçe: Hizmet Yönetimi bölümü. -->
      <!-- English: Services Management section. -->
      <section id="services" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Hizmet Yönetimi</h2>
          <p class="text-gray-600">Hizmetlerinizi yönetin, fiyatları güncelleyin ve yeni hizmetler ekleyin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Hizmet Listesi</h3>
                <button onclick="openServiceModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                  <i class="fas fa-plus mr-2"></i>Hizmet Ekle
                </button>
              </div>

              <div class="space-y-4">
                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-car text-blue-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Dış Yıkama + İç Temizlik</h4>
                      <p class="text-sm text-gray-600">45 dakika - Premium kalite</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-lg">₺130</div>
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-scrubber text-green-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Tam Detaylandırma</h4>
                      <p class="text-sm text-gray-600">90 dakika - Profesyonel bakım</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-lg">₺200</div>
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-crown text-purple-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Premium Paket</h4>
                      <p class="text-sm text-gray-600">120 dakika - VIP hizmet</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-lg">₺250</div>
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Hizmet İstatistikleri</h3>
            <div class="space-y-6">
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">28</div>
                <div class="text-sm text-gray-600">Aktif Hizmet</div>
              </div>

              <div class="space-y-4">
                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>En Popüler</span>
                    <span>45%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 45%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Premium Paketler</span>
                    <span>30%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 30%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Temel Hizmetler</span>
                    <span>25%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 25%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Staff Management -->
      <!-- Farsça: بخش مدیریت پرسنل. -->
      <!-- Türkçe: Personel Yönetimi bölümü. -->
      <!-- English: Staff Management section. -->
      <section id="staff" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Personel Yönetimi</h2>
          <p class="text-gray-600">Personel bilgilerini yönetin ve performanslarını takip edin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Personel Listesi</h3>
                <button onclick="openStaffModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                  <i class="fas fa-plus mr-2"></i>Personel Ekle
                </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Ali Yılmaz</h4>
                      <p class="text-sm text-gray-600">Senior Teknisyen</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-green-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Fatma Kaya</h4>
                      <p class="text-sm text-gray-600">Teknisyen</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-purple-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Mehmet Demir</h4>
                      <p class="text-sm text-gray-600">Çırak</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-yellow-600"></i>
                    </div>
                    <div>
                      <h4 class="font-bold">Ayşe Şahin</h4>
                      <p class="text-sm text-gray-600">Resepsiyonist</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="flex space-x-2">
                      <button class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Personel Performansı</h3>
            <div class="space-y-6">
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">4</div>
                <div class="text-sm text-gray-600">Aktif Personel</div>
              </div>

              <div class="space-y-4">
                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Bu Ay Tamamlanan İş</span>
                    <span>85</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Müşteri Memnuniyeti</span>
                    <span>4.8/5</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 96%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Devamsızlık Oranı</span>
                    <span>2%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 2%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Financial Reports -->
      <!-- Farsça: بخش گزارشات مالی. -->
      <!-- Türkçe: Finansal Raporlar bölümü. -->
      <!-- English: Financial Reports section. -->
      <section id="financial" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Finansal Raporlar</h2>
          <p class="text-gray-600">Gelir, gider ve karlılık analizlerinizi görüntüleyin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Gelir-Gider Özeti</h3>
            <div class="space-y-4">
              <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
                <div>
                  <h4 class="font-bold text-green-800">Toplam Gelir</h4>
                  <p class="text-sm text-green-600">Bu ay</p>
                </div>
                <span class="text-2xl font-bold text-green-600">₺15,420</span>
              </div>

              <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg">
                <div>
                  <h4 class="font-bold text-red-800">Toplam Gider</h4>
                  <p class="text-sm text-red-600">Bu ay</p>
                </div>
                <span class="text-2xl font-bold text-red-600">₺8,750</span>
              </div>

              <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg">
                <div>
                  <h4 class="font-bold text-blue-800">Net Kar</h4>
                  <p class="text-sm text-blue-600">Bu ay</p>
                </div>
                <span class="text-2xl font-bold text-blue-600">₺6,670</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Hizmet Bazında Gelir</h3>
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <span class="text-sm">Premium Paket</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 40%"></div>
                  </div>
                  <span class="text-sm font-medium">₺6,168</span>
                </div>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-sm">Tam Detaylandırma</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 35%"></div>
                  </div>
                  <span class="text-sm font-medium">₺5,397</span>
                </div>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-sm">Dış Yıkama + İç</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 25%"></div>
                  </div>
                  <span class="text-sm font-medium">₺3,855</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
          <h3 class="text-xl font-bold mb-6">Aylık Trend</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">₺12,340</div>
              <div class="text-sm text-gray-600">Geçen Ay</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">₺15,420</div>
              <div class="text-sm text-gray-600">Bu Ay</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-purple-600">₺18,000</div>
              <div class="text-sm text-gray-600">Hedef</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Reports -->
      <!-- Farsça: بخش گزارشات و تحلیل‌ها. -->
      <!-- Türkçe: Raporlar ve Analitik bölümü. -->
      <!-- English: Reports and Analytics section. -->
      <section id="reports" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Raporlar ve Analitik</h2>
          <p class="text-gray-600">Detaylı raporlar ve iş zekası analizleri</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-6">Zaman Bazında Performans</h3>
            <div class="space-y-4">
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>Pazartesi</span>
                  <span>85%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>Salı</span>
                  <span>92%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-green-600 h-2 rounded-full" style="width: 92%"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>Çarşamba</span>
                  <span>78%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-yellow-600 h-2 rounded-full" style="width: 78%"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>Perşembe</span>
                  <span>88%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-purple-600 h-2 rounded-full" style="width: 88%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Cuma</span>
                    <span>95%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 95%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Cumartesi</span>
                    <span>90%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 90%"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-sm mb-1">
                    <span>Pazar</span>
                    <span>65%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-red-600 h-2 rounded-full" style="width: 65%"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-lg">
              <h3 class="text-xl font-bold mb-6">Müşteri Memnuniyeti</h3>
              <div class="space-y-6">
                <div class="text-center">
                  <div class="text-4xl font-bold text-yellow-600">4.8★</div>
                  <div class="text-sm text-gray-600">Ortalama Puan</div>
                </div>

                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                    </div>
                    <span class="text-sm font-medium">65%</span>
                  </div>

                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="far fa-star text-gray-300"></i>
                    </div>
                    <span class="text-sm font-medium">25%</span>
                  </div>

                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="far fa-star text-gray-300"></i>
                      <i class="far fa-star text-gray-300"></i>
                    </div>
                    <span class="text-sm font-medium">8%</span>
                  </div>

                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="far fa-star text-gray-300"></i>
                      <i class="far fa-star text-gray-300"></i>
                      <i class="far fa-star text-gray-300"></i>
                    </div>
                    <span class="text-sm font-medium">2%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Invoices -->
        <!-- Farsça: بخش فاکتورها. -->
        <!-- Türkçe: Faturalar bölümü. -->
        <!-- English: Invoices section. -->
        <section id="invoices" class="section-content hidden">
          <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Fatura Yönetimi</h2>
            <p class="text-gray-600">Otomatik faturalandırma ve fatura takibi</p>
          </div>

          <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center">
              <h3 class="text-xl font-bold">Otomatik Faturalandırma</h3>
              <div class="flex space-x-2">
                <button class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                  <i class="fas fa-plus mr-2"></i>Manuel Fatura
                </button>
                <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-all">
                  <i class="fas fa-cog mr-2"></i>Ayarlar
                </button>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fatura No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Müşteri</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tutar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">#INV-2024-001</td>
                    <td class="px-6 py-4">Ahmet Yılmaz</td>
                    <td class="px-6 py-4">Premium Paket</td>
                    <td class="px-6 py-4 font-medium">₺250</td>
                    <td class="px-6 py-4"><span class="status-completed px-2 py-1 rounded-full text-xs">Ödendi</span></td>
                    <td class="px-6 py-4 text-sm">15.12.2024</td>
                    <td class="px-6 py-4 text-sm">
                      <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                      <button class="text-green-600 hover:text-green-900 mr-2">Yazdır</button>
                      <button class="text-purple-600 hover:text-purple-900">E-posta</button>
                    </td>
                  </tr>

                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">#INV-2024-002</td>
                    <td class="px-6 py-4">Fatma Kaya</td>
                    <td class="px-6 py-4">Tam Detaylandırma</td>
                    <td class="px-6 py-4 font-medium">₺200</td>
                    <td class="px-6 py-4"><span class="status-pending px-2 py-1 rounded-full text-xs">Bekliyor</span></td>
                    <td class="px-6 py-4 text-sm">15.12.2024</td>
                    <td class="px-6 py-4 text-sm">
                      <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                      <button class="text-green-600 hover:text-green-900 mr-2">Gönder</button>
                      <button class="text-red-600 hover:text-red-900">İptal</button>
                    </td>
                  </tr>

                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium">#INV-2024-003</td>
                    <td class="px-6 py-4">Mehmet Demir</td>
                    <td class="px-6 py-4">Dış Yıkama + İç</td>
                    <td class="px-6 py-4 font-medium">₺130</td>
                    <td class="px-6 py-4"><span class="status-completed px-2 py-1 rounded-full text-xs">Ödendi</span></td>
                    <td class="px-6 py-4 text-sm">14.12.2024</td>
                    <td class="px-6 py-4 text-sm">
                      <button class="text-blue-600 hover:text-blue-900 mr-2">Görüntüle</button>
                      <button class="text-green-600 hover:text-green-900 mr-2">Yazdır</button>
                      <button class="text-purple-600 hover:text-purple-900">E-posta</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Settings -->
        <!-- Farsça: بخش تنظیمات. -->
        <!-- Türkçe: Ayarlar bölümü. -->
        <!-- English: Settings section. -->
        <section id="settings" class="section-content hidden">
          <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Ayarlar</h2>
            <p class="text-gray-600">İşletme ayarlarınızı yönetin</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-2xl p-6 shadow-lg">
              <h3 class="text-xl font-bold mb-6">İşletme Bilgileri</h3>
              <form class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">İşletme Adı</label>
                  <input type="text" value="CarWash Merkez" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Adres</label>
                  <textarea rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">İstanbul, Kadıköy, Moda Mahallesi, No: 123</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Telefon</label>
                    <input type="tel" value="0216 123 4567" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  </div>
                  <!-- Farsça: فیلد شماره تلفن همراه. -->
                  <!-- Türkçe: Cep Telefonu Numarası Alanı. -->
                  <!-- English: Mobile Phone Number Field. -->
                  <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Cep Telefonu</label>
                    <input type="tel" value="05XX XXX XX XX" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">E-posta</label>
                  <input type="email" value="info@carwashmerkez.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <!-- Farsça: گزینه بارگذاری لوگو. -->
                <!-- Türkçe: Logo Yükleme Seçeneği. -->
                <!-- English: Upload Logo Option. -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">İşletme Logosu</label>
                  <div class="flex items-center space-x-4">
                    <img id="currentLogo" src="https://via.placeholder.com/80x80?text=Logo" alt="Current Logo" class="w-20 h-20 rounded-lg object-cover border">
                    <input type="file" id="logoUpload" class="hidden" accept="image/*" onchange="previewLogo(event)">
                    <button type="button" onclick="document.getElementById('logoUpload').click()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                      <i class="fas fa-upload mr-2"></i>Logo Yükle
                    </button>
                  </div>
                </div>

                <!-- Farsça: ساعات کاری برای هر روز. -->
                <!-- Türkçe: Her Gün İçin Çalışma Saatleri. -->
                <!-- English: Working Hours for Each Day. -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Çalışma Saatleri</label>
                  <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Pazartesi:</span>
                      <input type="time" value="08:00" class="w-24 px-3 py-2 border rounded-lg">
                      <span>-</span>
                      <input type="time" value="20:00" class="w-24 px-3 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Salı:</span>
                      <input type="time" value="08:00" class="w-24 px-3 py-2 border rounded-lg">
                      <span>-</span>
                      <input type="time" value="20:00" class="w-24 px-3 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Çarşamba:</span>
                      <input type="time" value="08:00" class="w-24 px-3 py-2 border rounded-lg">
                      <span>-</span>
                      <input type="time" value="20:00" class="w-24 px-3 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Perşembe:</span>
                      <input type="time" value="08:00" class="w-24 px-3 py-2 border rounded-lg">
                      <span>-</span>
                      <input type="time" value="20:00" class="w-24 px-3 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Cuma:</span>
                      <input type="time" value="08:00" class="w-24 px-3 py-2 border rounded-lg">
                      <span>-</span>
                      <input type="time" value="20:00" class="w-24 px-3 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Cumartesi:</span>
                      <input type="time" value="09:00" class="w-24 px-3 py-2 border rounded-lg">
                      <span>-</span>
                      <input type="time" value="18:00" class="w-24 px-3 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="w-24 text-gray-600">Pazar:</span>
                      <input type="time" value="09:00" class="w-24 px-3 py-2 border rounded-lg">
                      <span>-</span>
                      <input type="time" value="18:00" class="w-24 px-3 py-2 border rounded-lg">
                    </div>
                  </div>
                </div>

                <button type="submit" class="w-full gradient-bg text-white py-3 rounded-lg font-bold hover:shadow-lg transition-all">
                  <i class="fas fa-save mr-2"></i>Bilgileri Güncelle
                </button>
              </form>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-lg">
              <h3 class="text-xl font-bold mb-6">Sistem Ayarları</h3>
              <div class="space-y-4">
                <label class="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <h4 class="font-bold">Otomatik Faturalandırma</h4>
                    <p class="text-sm text-gray-600">Hizmet tamamlandıktan sonra otomatik fatura oluştur</p>
                  </div>
                  <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                </label>

                <label class="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <h4 class="font-bold">SMS Bildirimleri</h4>
                    <p class="text-sm text-gray-600">Müşterilere SMS ile hatırlatma gönder</p>
                  </div>
                  <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                </label>

                <label class="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <h4 class="font-bold">E-posta Bildirimleri</h4>
                    <p class="text-sm text-gray-600">Rezervasyon onayları için e-posta gönder</p>
                  </div>
                  <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                </label>

                <label class="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <h4 class="font-bold">Otomatik Yedekleme</h4>
                    <p class="text-sm text-gray-600">Verileri günlük olarak yedekle</p>
                  </div>
                  <input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                </label>

                <div class="pt-4 border-t">
                  <h4 class="font-bold mb-4">Veri Yönetimi</h4>
                  <div class="space-y-2">
                    <button class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                      <i class="fas fa-download mr-2"></i>Verileri Dışa Aktar
                    </button>
                    <button class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                      <i class="fas fa-upload mr-2"></i>Verileri İçe Aktar
                    </button>
                    <button class="w-full text-left p-3 border rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                      <i class="fas fa-trash mr-2"></i>Tüm Verileri Sil
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Notification Panel -->
    <!-- Farsça: پنل اعلان‌ها. -->
    <!-- Türkçe: Bildirim Paneli. -->
    <!-- English: Notification Panel. -->
    <div id="notificationPanel" class="fixed top-20 right-4 w-80 bg-white rounded-2xl shadow-2xl z-50 hidden">
      <div class="p-4 border-b">
        <div class="flex justify-between items-center">
          <h3 class="font-bold">Bildirimler</h3>
          <button onclick="closeNotifications()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <div class="max-h-96 overflow-y-auto">
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Yeni rezervasyon alındı - Premium paket</p>
          <p class="text-xs text-gray-500">5 dakika önce</p>
        </div>
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Ödeme tamamlandı - ₺200</p>
          <p class="text-xs text-gray-500">15 dakika önce</p>
        </div>
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Müşteri yorumu - 5 yıldız</p>
          <p class="text-xs text-gray-500">1 ساعت önce</p>
        </div>
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Stok uyarısı - Şampuan azaldı</p>
          <p class="text-xs text-gray-500">2 saat önce</p>
        </div>
        <div class="p-4 border-b hover:bg-gray-50">
          <p class="text-sm">Personel bildirimi - Ali Yılmaz izin istedi</p>
          <p class="text-xs text-gray-500">3 saat önce</p>
        </div>
      </div>
    </div>

    <!-- Farsça: مودال رزرو دستی. -->
    <!-- Türkçe: Manuel Rezervasyon Modalı. -->
    <!-- English: Manual Reservation Modal. -->
    <div id="manualReservationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-6">Manuel Rezervasyon Oluştur</h3>
        <form class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Müşteri Adı Soyadı</label>
            <input type="text" placeholder="Müşteri adını girin" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Müşteri Telefonu</label>
            <input type="tel" placeholder="05XX XXX XX XX" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Hizmet Seçin</label>
            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              <option>Dış Yıkama + İç Temizlik</option>
              <option>Tam Detaylandırma</option>
              <option>Premium Paket</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Araç Plakası</label>
            <input type="text" placeholder="34 ABC 123" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Tarih</label>
              <input type="date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Saat</label>
              <input type="time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Notlar (İsteğe Bağlı)</label>
            <textarea rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
          </div>
          <div class="flex space-x-3">
            <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Rezervasyon Oluştur</button>
            <button type="button" onclick="closeManualReservationModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Farsça: مودال افزودن مشتری. -->
    <!-- Türkçe: Müşteri Ekle Modalı. -->
    <!-- English: Customer Add Modal. -->
    <div id="customerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-6">Yeni Müşteri Ekle</h3>
        <form class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Ad Soyad</label>
            <input type="text" placeholder="Müşteri Adı Soyadı" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">E-posta</label>
            <input type="email" placeholder="email@example.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Telefon</label>
            <input type="tel" placeholder="05XX XXX XX XX" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Adres (İsteğe Bağlı)</label>
            <textarea rows="2" placeholder="Müşteri Adresi" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
          </div>
          <div class="flex space-x-3">
            <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Müşteri Ekle</button>
            <button type="button" onclick="closeCustomerModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Farsça: مودال خدمات. -->
    <!-- Türkçe: Hizmet Modalı. -->
    <!-- English: Service Modal. -->
    <div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-6">Yeni Hizmet Ekle</h3>
        <form class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Hizmet Adı</label>
            <input type="text" placeholder="Hizmet adını girin" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Açıklama</label>
            <textarea rows="3" placeholder="Hizmet açıklaması" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Süre (dk)</label>
              <input type="number" placeholder="60" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Fiyat (₺)</label>
              <input type="number" placeholder="150" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
          </div>
          <div class="flex space-x-3">
            <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Ekle</button>
            <button type="button" onclick="closeServiceModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Farsça: مودال پرسنل. -->
    <!-- Türkçe: Personel Modalı. -->
    <!-- English: Staff Modal. -->
    <div id="staffModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-6">Personel Ekle</h3>
        <form class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Ad Soyad</label>
            <input type="text" placeholder="Ad soyad girin" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Pozisyon</label>
            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              <option>Teknisyen</option>
              <option>Senior Teknisyen</option>
              <option>Çırak</option>
              <option>Resepsiyonist</option>
              <option>Yönetici</option>
              <!-- Farsça: موقعیت راننده اضافه شد. -->
              <!-- Türkçe: Şoför Pozisyonu Eklendi. -->
              <!-- English: Driver Position Added. -->
              <option>Şoför</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Telefon</label>
            <input type="tel" placeholder="0555 123 4567" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">E-posta</label>
            <input type="email" placeholder="email@domain.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
          </div>
          <!-- Farsça: فیلد بارگذاری گواهی. -->
          <!-- Türkçe: Sertifika Yükleme Alanı. -->
          <!-- English: Upload Certificate Field. -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Sertifika Yükle (İsteğe Bağlı)</label>
            <input type="file" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" accept=".pdf,.doc,.docx,.jpg,.png">
          </div>
          <div class="flex space-x-3">
            <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Ekle</button>
            <button type="button" onclick="closeStaffModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      // Farsça: تابع برای نمایش بخش‌های مختلف داشبورد.
      // Türkçe: Kontrol panelinin farklı bölümlerini göstermek için fonksiyon.
      // English: Function to show different sections of the dashboard.
      function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.section-content').forEach(section => {
          section.classList.add('hidden');
        });

        // Show selected section
        document.getElementById(sectionId).classList.remove('hidden');

        // Update sidebar active state
        document.querySelectorAll('aside a').forEach(link => {
          link.classList.remove('bg-white', 'bg-opacity-20');
          if (link.getAttribute('href') === '#' + sectionId) {
            link.classList.add('bg-white', 'bg-opacity-20');
          }
        });
      }

      // Farsça: بارگذاری اولیه: نمایش داشبورد.
      // Türkçe: İlk yükleme: kontrol panelini göster.
      // English: Initial load: show dashboard.
      document.addEventListener('DOMContentLoaded', () => {
        showSection('dashboard');
        // Set initial toggle state based on localStorage or default
        // Farsça: وضعیت اولیه سوئیچ را بر اساس localStorage یا پیش‌فرض تنظیم کنید.
        // Türkçe: Başlangıçtaki geçiş durumunu localStorage veya varsayılan değere göre ayarla.
        // English: Set initial toggle state based on localStorage or default.
        const status = localStorage.getItem('workplaceStatus');
        const toggle = document.getElementById('workplaceStatus');
        if (status === 'off') {
          toggle.checked = false;
        } else {
          toggle.checked = true; // Default to On
        }
        toggleWorkplaceStatus(); // Apply initial styling
      });

      // Farsça: توابع پنل اعلان.
      // Türkçe: Bildirim Paneli fonksiyonları.
      // English: Notification Panel functions.
      function toggleNotifications() {
        const panel = document.getElementById('notificationPanel');
        panel.classList.toggle('hidden');
      }

      function closeNotifications() {
        document.getElementById('notificationPanel').classList.add('hidden');
      }

      // Farsça: توابع مودال خدمات.
      // Türkçe: Hizmet Modalı fonksiyonları.
      // English: Service Modal functions.
      function openServiceModal() {
        document.getElementById('serviceModal').classList.remove('hidden');
      }

      function closeServiceModal() {
        document.getElementById('serviceModal').classList.add('hidden');
      }

      // Farsça: توابع مودال پرسنل.
      // Türkçe: Personel Modalı fonksiyonları.
      // English: Staff Modal functions.
      function openStaffModal() {
        document.getElementById('staffModal').classList.remove('hidden');
      }

      function closeStaffModal() {
        document.getElementById('staffModal').classList.add('hidden');
      }

      // Farsça: تابع تغییر وضعیت محل کار.
      // Türkçe: İşyeri Durumu Geçiş Fonksiyonu.
      // English: Workplace Status Toggle Function.
      function toggleWorkplaceStatus() {
        const toggle = document.getElementById('workplaceStatus');
        if (toggle.checked) {
          localStorage.setItem('workplaceStatus', 'on');
          console.log('Workplace is now OPEN (Green)');
        } else {
          localStorage.setItem('workplaceStatus', 'off');
          console.log('Workplace is now CLOSED (Red)');
        }
      }

      // Farsça: توابع مودال رزرو دستی.
      // Türkçe: Manuel Rezervasyon Modalı fonksiyonları.
      // English: Manual Reservation Modal functions.
      function openManualReservationModal() {
        document.getElementById('manualReservationModal').classList.remove('hidden');
      }

      function closeManualReservationModal() {
        document.getElementById('manualReservationModal').classList.add('hidden');
      }

      // Farsça: توابع مودال افزودن مشتری.
      // Türkçe: Müşteri Ekle Modalı fonksiyonları.
      // English: Customer Add Modal functions.
      function openCustomerModal() {
        document.getElementById('customerModal').classList.remove('hidden');
      }

      function closeCustomerModal() {
        document.getElementById('customerModal').classList.add('hidden');
      }

      // Farsça: تنظیمات - پیش‌نمایش بارگذاری لوگو.
      // Türkçe: Ayarlar - Logo Yükleme Önizlemesi.
      // English: Settings - Logo Upload Preview.
      function previewLogo(event) {
        const reader = new FileReader();
        reader.onload = function() {
          const output = document.getElementById('currentLogo');
          output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
      }
    </script>
</body>
</html>
