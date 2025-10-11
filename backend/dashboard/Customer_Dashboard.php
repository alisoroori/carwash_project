<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\dashboard\Customer_Dashboard.php

/**
 * Customer Dashboard for CarWash Web Application
 * Following project conventions: file-based routing, session management
 */

// Start session following project patterns
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Include database connection following project structure
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and has customer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
  header('Location: ../auth/login.php');
  exit();
}

try {
  $conn = getDBConnection();
  $user_id = $_SESSION['user_id'];

  // Get user information
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch();

  if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit();
  }

  // Get user statistics
  $stats = [
    'total_reservations' => 0,
    'monthly_reservations' => 0,
    'total_spent' => 0,
    'average_rating' => 0
  ];

  // Get reservations count
  try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['total_reservations'] = $result['total'] ?? 0;

    // Monthly reservations
    $stmt = $conn->prepare("SELECT COUNT(*) as monthly FROM reservations WHERE user_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE())");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['monthly_reservations'] = $result['monthly'] ?? 0;

    // Total spent
    $stmt = $conn->prepare("SELECT SUM(price) as total_spent FROM reservations WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['total_spent'] = $result['total_spent'] ?? 0;
  } catch (PDOException $e) {
    // Tables might not exist, continue with default values
    error_log("Customer dashboard stats error: " . $e->getMessage());
  }

  // Get active reservations
  $active_reservations = [];
  try {
    $stmt = $conn->prepare("
            SELECT r.*, c.business_name 
            FROM reservations r 
            LEFT JOIN carwashes c ON r.carwash_id = c.id 
            WHERE r.user_id = ? AND r.status IN ('pending', 'confirmed') 
            ORDER BY r.reservation_date ASC, r.reservation_time ASC 
            LIMIT 5
        ");
    $stmt->execute([$user_id]);
    $active_reservations = $stmt->fetchAll();
  } catch (PDOException $e) {
    error_log("Customer active reservations error: " . $e->getMessage());
  }

  // Get user vehicles
  $user_vehicles = [];
  try {
    $stmt = $conn->prepare("
            SELECT CONCAT(car_brand, ' ', car_model, ' - ', license_plate) as vehicle_display,
                   car_brand, car_model, license_plate, car_year, car_color
            FROM users 
            WHERE id = ? AND car_brand IS NOT NULL
        ");
    $stmt->execute([$user_id]);
    $user_vehicle = $stmt->fetch();
    if ($user_vehicle) {
      $user_vehicles[] = $user_vehicle;
    }
  } catch (PDOException $e) {
    error_log("Customer vehicles error: " . $e->getMessage());
  }

  // Get recent history
  $recent_history = [];
  try {
    $stmt = $conn->prepare("
            SELECT r.*, c.business_name 
            FROM reservations r 
            LEFT JOIN carwashes c ON r.carwash_id = c.id 
            WHERE r.user_id = ? AND r.status = 'completed' 
            ORDER BY r.reservation_date DESC 
            LIMIT 5
        ");
    $stmt->execute([$user_id]);
    $recent_history = $stmt->fetchAll();
  } catch (PDOException $e) {
    error_log("Customer history error: " . $e->getMessage());
  }
} catch (Exception $e) {
  error_log("Customer dashboard error: " . $e->getMessage());
  $error_message = "Dashboard yüklenirken bir hata oluştu.";
}

// Handle success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - Müşteri Paneli</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../frontend/css/style.css">
  <style>
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

    .animate-fade-in-up {
      animation: fadeInUp 0.6s ease-out forwards;
    }

    .animate-slide-in {
      animation: slideIn 0.5s ease-out forwards;
    }

    .gradient-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .sidebar-gradient {
      background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    }

    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }

    .status-confirmed {
      background: #d1fae5;
      color: #065f46;
    }

    .status-completed {
      background: #e0e7ff;
      color: #3730a3;
    }

    .status-cancelled {
      background: #fecaca;
      color: #991b1b;
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">

  <!-- Success/Error Messages -->
  <?php if (!empty($success_message)): ?>
    <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50" id="successMessage">
      <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
      <button onclick="document.getElementById('successMessage').remove()" class="float-right ml-4">×</button>
    </div>
  <?php endif; ?>

  <?php if (!empty($error_message)): ?>
    <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50" id="errorMessage">
      <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
      <button onclick="document.getElementById('errorMessage').remove()" class="float-right ml-4">×</button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <header class="bg-white shadow-lg sticky top-0 z-50">
    <div class="container mx-auto px-4">
      <div class="flex justify-between items-center py-4">
        <div class="flex items-center space-x-4">
          <i class="fas fa-car text-3xl text-blue-600"></i>
          <h1 class="text-2xl font-bold text-blue-600">CarWash</h1>
        </div>

        <div class="flex items-center space-x-4">
          <div class="hidden md:flex items-center space-x-2">
            <i class="fas fa-user text-blue-600"></i>
            <span class="text-gray-700 font-medium">Hoş Geldiniz, <?php echo htmlspecialchars($user['name']); ?></span>
          </div>
          <div class="flex space-x-2">
            <button onclick="toggleNotifications()" class="relative p-2 text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-bell text-xl"></i>
              <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
            </button>
            <a href="../../frontend/homes.html" class="p-2 text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-home text-xl"></i>
            </a>
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
    <aside class="w-64 sidebar-gradient text-white sticky top-20 h-fit">
      <div class="p-6">
        <div class="text-center mb-8">
          <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user text-3xl"></i>
          </div>
          <h3 class="text-xl font-bold"><?php echo htmlspecialchars($user['name']); ?></h3>
          <p class="text-sm opacity-75"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <nav class="space-y-2">
          <a href="#dashboard" onclick="showSection('dashboard')" class="flex items-center p-3 rounded-lg bg-white bg-opacity-20">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Genel Bakış
          </a>
          <a href="#carWashSelection" onclick="showSection('carWashSelection')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-hand-pointer mr-3"></i>
            Oto Yıkama Seçimi
          </a>
          <a href="#reservations" onclick="showSection('reservations')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-calendar-check mr-3"></i>
            Rezervasyonlarım
          </a>
          <a href="#profile" onclick="showSection('profile')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-user-edit mr-3"></i>
            Profil Yönetimi
          </a>
          <a href="#vehicles" onclick="showSection('vehicles')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-car mr-3"></i>
            Araçlarım
          </a>
          <a href="#history" onclick="showSection('history')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-history mr-3"></i>
            Geçmiş İşlemler
          </a>
          <a href="#support" onclick="showSection('support')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-headset mr-3"></i>
            Destek
          </a>
          <a href="#settings" onclick="showSection('settings')" class="flex items-center p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors">
            <i class="fas fa-cog mr-3"></i>
            Ayarlar
          </a>
        </nav>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
      <!-- Dashboard Overview -->
      <section id="dashboard" class="section-content">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Genel Bakış</h2>
          <p class="text-gray-600">Hesabınızın özeti ve son aktiviteleriniz</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Toplam Rezervasyon</p>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_reservations']; ?></p>
              </div>
              <i class="fas fa-calendar-check text-4xl text-blue-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Bu Ay</p>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['monthly_reservations']; ?></p>
              </div>
              <i class="fas fa-calendar-day text-4xl text-green-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Toplam Harcama</p>
                <p class="text-3xl font-bold text-purple-600">₺<?php echo number_format($stats['total_spent'], 0); ?></p>
              </div>
              <i class="fas fa-money-bill-wave text-4xl text-purple-600 opacity-20"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm">Ortalama Puan</p>
                <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($stats['average_rating'], 1); ?>★</p>
              </div>
              <i class="fas fa-star text-4xl text-yellow-600 opacity-20"></i>
            </div>
          </div>
        </div>

        <!-- Recent Reservations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-clock text-blue-600 mr-2"></i>
              Yaklaşan Rezervasyonlar
            </h3>
            <div class="space-y-4">
              <?php if (empty($active_reservations)): ?>
                <p class="text-gray-500 text-center py-4">Aktif rezervasyonunuz bulunmuyor.</p>
              <?php else: ?>
                <?php foreach ($active_reservations as $reservation): ?>
                  <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                      <h4 class="font-bold"><?php echo htmlspecialchars($reservation['service_type'] ?? 'Genel Hizmet'); ?></h4>
                      <p class="text-sm text-gray-600">
                        <?php echo date('d.m.Y', strtotime($reservation['reservation_date'])); ?>,
                        <?php echo date('H:i', strtotime($reservation['reservation_time'])); ?> -
                        <?php echo htmlspecialchars($reservation['business_name'] ?? 'CarWash'); ?>
                      </p>
                    </div>
                    <span class="status-<?php echo $reservation['status']; ?> px-3 py-1 rounded-full text-xs font-bold">
                      <?php
                      $status_text = [
                        'pending' => 'Bekliyor',
                        'confirmed' => 'Onaylandı',
                        'completed' => 'Tamamlandı',
                        'cancelled' => 'İptal'
                      ];
                      echo $status_text[$reservation['status']] ?? 'Bilinmiyor';
                      ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-bell text-blue-600 mr-2"></i>
              Bildirimler
            </h3>
            <div class="space-y-4">
              <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-600">
                <p class="text-sm">Hoş geldiniz! Dashboard'unuza başarıyla giriş yaptınız.</p>
                <p class="text-xs text-gray-500 mt-1">Şimdi</p>
              </div>

              <?php if (!empty($active_reservations)): ?>
                <div class="p-4 bg-green-50 rounded-lg border-l-4 border-green-600">
                  <p class="text-sm">Aktif rezervasyonlarınız bulunuyor. Detaylar için rezervasyonlar bölümüne bakın.</p>
                  <p class="text-xs text-gray-500 mt-1">Güncel</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <!-- Car Wash Selection Section -->
      <section id="carWashSelection" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Oto Yıkama Seçimi</h2>
          <p class="text-gray-600">Size en uygun oto yıkama merkezini bulun ve rezervasyon yapın.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Filtreleme Seçenekleri</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label for="cityFilter" class="block text-sm font-bold text-gray-700 mb-2">Şehir</label>
              <select id="cityFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">Tüm Şehirler</option>
                <option value="İstanbul">İstanbul</option>
                <option value="Ankara">Ankara</option>
                <option value="İzmir">İzmir</option>
              </select>
            </div>
            <div>
              <label for="districtFilter" class="block text-sm font-bold text-gray-700 mb-2">Mahalle</label>
              <select id="districtFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">Tüm Mahalleler</option>
              </select>
            </div>
            <div>
              <label for="carWashNameFilter" class="block text-sm font-bold text-gray-700 mb-2">CarWash Adı</label>
              <input type="text" id="carWashNameFilter" onkeyup="filterCarWashes()" placeholder="CarWash adı girin..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex items-end">
              <label for="favoriteFilter" class="flex items-center cursor-pointer">
                <input type="checkbox" id="favoriteFilter" onchange="filterCarWashes()" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                <span class="ml-2 text-sm font-bold text-gray-700">Favorilerim</span>
              </label>
            </div>
          </div>
        </div>

        <div id="carWashList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Car wash cards will be loaded here by JavaScript -->
        </div>
      </section>

      <!-- Reservations Section -->
      <section id="reservations" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Rezervasyonlarım</h2>
          <p class="text-gray-600">Tüm rezervasyonlarınızı görüntüleyin ve yönetin</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <!-- Reservation List View -->
          <div id="reservationListView">
            <div class="p-6 border-b">
              <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Aktif Rezervasyonlar</h3>
                <button onclick="showNewReservationForm()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                  <i class="fas fa-plus mr-2"></i>Yeni Rezervasyon
                </button>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih/Saat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Konum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <?php if (empty($active_reservations)): ?>
                    <tr>
                      <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        Aktif rezervasyonunuz bulunmuyor.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($active_reservations as $reservation): ?>
                      <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                          <div>
                            <div class="font-medium"><?php echo htmlspecialchars($reservation['service_type'] ?? 'Genel Hizmet'); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['car_brand'] . ' ' . $user['car_model'] . ' - ' . $user['license_plate']); ?></div>
                          </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                          <?php echo date('d.m.Y', strtotime($reservation['reservation_date'])); ?><br>
                          <?php echo date('H:i', strtotime($reservation['reservation_time'])); ?>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($reservation['business_name'] ?? 'CarWash'); ?></td>
                        <td class="px-6 py-4">
                          <span class="status-<?php echo $reservation['status']; ?> px-2 py-1 rounded-full text-xs">
                            <?php
                            $status_text = [
                              'pending' => 'Bekliyor',
                              'confirmed' => 'Onaylandı',
                              'completed' => 'Tamamlandı',
                              'cancelled' => 'İptal'
                            ];
                            echo $status_text[$reservation['status']] ?? 'Bilinmiyor';
                            ?>
                          </span>
                        </td>
                        <td class="px-6 py-4 font-medium">₺<?php echo number_format($reservation['price'] ?? 0, 0); ?></td>
                        <td class="px-6 py-4 text-sm">
                          <?php if ($reservation['status'] === 'pending'): ?>
                            <button onclick="editReservation(<?php echo $reservation['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Düzenle</button>
                            <button onclick="cancelReservation(<?php echo $reservation['id']; ?>)" class="text-red-600 hover:text-red-900">İptal</button>
                          <?php else: ?>
                            <span class="text-gray-400">-</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- New Reservation Form -->
          <div id="newReservationForm" class="p-6 hidden">
            <h3 class="text-xl font-bold mb-6">Yeni Rezervasyon Oluştur</h3>
            <form action="Customer_Dashboard_process.php" method="POST" class="space-y-6">
              <input type="hidden" name="action" value="create_reservation">

              <!-- Service Selection -->
              <div>
                <label for="service" class="block text-sm font-bold text-gray-700 mb-2">Hizmet Seçin</label>
                <select id="service" name="service_type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  <option value="">Hizmet Seçiniz</option>
                  <option value="Dış Yıkama">Dış Yıkama</option>
                  <option value="Dış Yıkama + İç Temizlik">Dış Yıkama + İç Temizlik</option>
                  <option value="Tam Detaylandırma">Tam Detaylandırma</option>
                  <option value="Motor Temizliği">Motor Temizliği</option>
                </select>
              </div>

              <!-- Date and Time -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="reservationDate" class="block text-sm font-bold text-gray-700 mb-2">Tarih</label>
                  <input type="date" id="reservationDate" name="reservation_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                  <label for="reservationTime" class="block text-sm font-bold text-gray-700 mb-2">Saat</label>
                  <input type="time" id="reservationTime" name="reservation_time" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
              </div>

              <!-- Location -->
              <div>
                <label for="location" class="block text-sm font-bold text-gray-700 mb-2">Konum</label>
                <select id="location" name="carwash_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  <option value="">Konum Seçiniz</option>
                  <!-- Options will be loaded dynamically -->
                </select>
              </div>

              <!-- Notes -->
              <div>
                <label for="notes" class="block text-sm font-bold text-gray-700 mb-2">Ek Notlar (İsteğe Bağlı)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Özel istekleriniz veya notlarınız..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
              </div>

              <div class="flex justify-end space-x-4">
                <button type="button" onclick="hideNewReservationForm()" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors">
                  Geri Dön
                </button>
                <button type="submit" class="gradient-bg text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg transition-all">
                  <i class="fas fa-calendar-plus mr-2"></i>Rezervasyon Yap
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>

      <!-- Profile Management Section -->
      <section id="profile" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Profil Yönetimi</h2>
          <p class="text-gray-600">Kişisel bilgilerinizi güncelleyin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg p-6">
              <h3 class="text-xl font-bold mb-6">Kişisel Bilgiler</h3>
              <form action="Customer_Dashboard_process.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_profile">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Ad Soyad</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  </div>
                  <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">E-posta</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Telefon</label>
                  <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Şehir</label>
                  <select name="city" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="">Şehir Seçin</option>
                    <option value="İstanbul" <?php echo ($user['city'] ?? '') === 'İstanbul' ? 'selected' : ''; ?>>İstanbul</option>
                    <option value="Ankara" <?php echo ($user['city'] ?? '') === 'Ankara' ? 'selected' : ''; ?>>Ankara</option>
                    <option value="İzmir" <?php echo ($user['city'] ?? '') === 'İzmir' ? 'selected' : ''; ?>>İzmir</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Adres</label>
                  <textarea name="address" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="gradient-bg text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg transition-all">
                  <i class="fas fa-save mr-2"></i>Bilgileri Güncelle
                </button>
              </form>
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-xl font-bold mb-6">Araç Bilgileri</h3>
            <form action="Customer_Dashboard_process.php" method="POST" class="space-y-4">
              <input type="hidden" name="action" value="update_vehicle">

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Araç Markası</label>
                <input type="text" name="car_brand" value="<?php echo htmlspecialchars($user['car_brand'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Model</label>
                <input type="text" name="car_model" value="<?php echo htmlspecialchars($user['car_model'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Plaka</label>
                <input type="text" name="license_plate" value="<?php echo htmlspecialchars($user['license_plate'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Yıl</label>
                <input type="number" name="car_year" value="<?php echo htmlspecialchars($user['car_year'] ?? ''); ?>" min="1990" max="<?php echo date('Y'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Renk</label>
                <input type="text" name="car_color" value="<?php echo htmlspecialchars($user['car_color'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>

              <button type="submit" class="w-full gradient-bg text-white py-3 rounded-lg font-bold hover:shadow-lg transition-all">
                <i class="fas fa-car mr-2"></i>Araç Bilgilerini Güncelle
              </button>
            </form>
          </div>
        </div>
      </section>

      <!-- Vehicles Section -->
      <section id="vehicles" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Araçlarım</h2>
          <p class="text-gray-600">Kayıtlı araçlarınızı yönetin</p>
        </div>

        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold">Kayıtlı Araçlar</h3>
          <button onclick="openVehicleModal()" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>Araç Ekle
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex justify-between items-start mb-4">
              <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-car text-2xl text-blue-600"></i>
              </div>
              <button class="text-red-500 hover:text-red-700">
                <i class="fas fa-trash"></i>
              </button>
            </div>
            <h4 class="font-bold text-lg mb-2">Toyota Corolla</h4>
            <div class="space-y-1 text-sm text-gray-600">
              <p><span class="font-medium">Plaka:</span> 34 ABC 123</p>
              <p><span class="font-medium">Model:</span> 2020</p>
              <p><span class="font-medium">Renk:</span> Beyaz</p>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 card-hover shadow-lg">
            <div class="flex justify-between items-start mb-4">
              <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-car text-2xl text-green-600"></i>
              </div>
              <button class="text-red-500 hover:text-red-700">
                <i class="fas fa-trash"></i>
              </button>
            </div>
            <h4 class="font-bold text-lg mb-2">Honda Civic</h4>
            <div class="space-y-1 text-sm text-gray-600">
              <p><span class="font-medium">Plaka:</span> 34 XYZ 789</p>
              <p><span class="font-medium">Model:</span> 2019</p>
              <p><span class="font-medium">Renk:</span> Siyah</p>
            </div>
          </div>
        </div>
      </section>

      <!-- History Section -->
      <section id="history" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Geçmiş İşlemler</h2>
          <p class="text-gray-600">Tamamlanan hizmetlerinizin geçmişi</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
          <div class="p-6 border-b">
            <h3 class="text-xl font-bold">İşlem Geçmişi</h3>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Araç</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Konum</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm">10.12.2024<br>15:30</td>
                  <td class="px-6 py-4 text-sm">Tam Detaylandırma</td>
                  <td class="px-6 py-4 text-sm">Toyota Corolla</td>
                  <td class="px-6 py-4 text-sm">CarWash Premium</td>
                  <td class="px-6 py-4 font-medium">₺180</td>
                  <td class="px-6 py-4"><span class="status-completed px-2 py-1 rounded-full text-xs">Tamamlandı</span></td>
                  <td class="px-6 py-4">
                    <div class="flex text-yellow-400">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                    </div>
                  </td>
                </tr>

                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm">05.12.2024<br>11:00</td>
                  <td class="px-6 py-4 text-sm">Dış Yıkama</td>
                  <td class="px-6 py-4 text-sm">Honda Civic</td>
                  <td class="px-6 py-4 text-sm">CarWash Express</td>
                  <td class="px-6 py-4 font-medium">₺50</td>
                  <td class="px-6 py-4"><span class="status-completed px-2 py-1 rounded-full text-xs">Tamamlandı</span></td>
                  <td class="px-6 py-4">
                    <div class="flex text-yellow-400">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="far fa-star"></i>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Support Section -->
      <section id="support" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Destek</h2>
          <p class="text-gray-600">Yardıma mı ihtiyacınız var? Size yardımcı olalım</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-4">
              <i class="fas fa-question-circle text-blue-600 mr-2"></i>
              Sık Sorulan Sorular
            </h3>
            <div class="space-y-4">
              <div>
                <h4 class="font-bold text-gray-800">Rezervasyonumu nasıl iptal edebilirim?</h4>
                <p class="text-sm text-gray-600 mt-1">Rezervasyonlar bölümünden iptal edebilirsiniz.</p>
              </div>
              <div>
                <h4 class="font-bold text-gray-800">Ödeme yöntemleri nelerdir?</h4>
                <p class="text-sm text-gray-600 mt-1">Nakit, kredi kartı ve mobil ödeme kabul ediyoruz.</p>
              </div>
              <div>
                <h4 class="font-bold text-gray-800">Hizmet kalitesi nasıl garanti ediliyor?</h4>
                <p class="text-sm text-gray-600 mt-1">Tüm hizmetlerimizde kalite garantisi veriyoruz.</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-xl font-bold mb-4">
              <i class="fas fa-headset text-blue-600 mr-2"></i>
              Bize Ulaşın
            </h3>
            <form class="space-y-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Konu</label>
                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  <option>Rezervasyon Sorunu</option>
                  <option>Ödeme Sorunu</option>
                  <option>Hizmet Kalitesi</option>
                  <option>Diğer</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Mesajınız</label>
                <textarea rows="4" placeholder="Sorunuzu veya şikayetinizi yazın..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
              </div>

              <button type="submit" class="w-full gradient-bg text-white py-3 rounded-lg font-bold hover:shadow-lg transition-all">
                <i class="fas fa-paper-plane mr-2"></i>Mesaj Gönder
              </button>
            </form>
          </div>
        </div>
      </section>

      <!-- Settings Section -->
      <section id="settings" class="section-content hidden">
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-800 mb-2">Ayarlar</h2>
          <p class="text-gray-600">Hesap ayarlarınızı yönetin</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
          <h3 class="text-xl font-bold mb-6">Bildirim Tercihleri</h3>
          <div class="space-y-4">
            <label class="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <h4 class="font-bold">E-posta Bildirimleri</h4>
                <p class="text-sm text-gray-600">Rezervasyon onayları ve güncellemeler</p>
              </div>
              <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
            </label>

            <label class="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <h4 class="font-bold">SMS Bildirimleri</h4>
                <p class="text-sm text-gray-600">Acil durumlar için SMS</p>
              </div>
              <input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
            </label>

            <label class="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <h4 class="font-bold">Promosyon Bildirimleri</h4>
                <p class="text-sm text-gray-600">İndirim ve kampanya duyuruları</p>
              </div>
              <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
            </label>
          </div>

          <div class="mt-8 pt-6 border-t">
            <h3 class="text-xl font-bold mb-6">Güvenlik</h3>
            <div class="space-y-4">
              <button class="w-full text-left p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                <h4 class="font-bold">Şifre Değiştir</h4>
                <p class="text-sm text-gray-600">Hesap güvenliğiniz için şifrenizi güncelleyin</p>
              </button>

              <button class="w-full text-left p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                <h4 class="font-bold">İki Faktörlü Doğrulama</h4>
                <p class="text-sm text-gray-600">Ek güvenlik katmanı ekleyin</p>
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- Notification Panel -->
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
        <p class="text-sm">Rezervasyonunuz onaylandı</p>
        <p class="text-xs text-gray-500">2 saat önce</p>
      </div>
      <div class="p-4 border-b hover:bg-gray-50">
        <p class="text-sm">Önceki hizmetiniz tamamlandı</p>
        <p class="text-xs text-gray-500">1 gün önce</p>
      </div>
      <div class="p-4 border-b hover:bg-gray-50">
        <p class="text-sm">Yeni kampanya başladı!</p>
        <p class="text-xs text-gray-500">2 gün önce</p>
      </div>
    </div>
  </div>

  <!-- Vehicle Modal -->
  <div id="vehicleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4">
      <h3 class="text-xl font-bold mb-6">Yeni Araç Ekle</h3>
      <form class="space-y-4">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Marka</label>
          <input type="text" placeholder="Toyota" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Model</label>
          <input type="text" placeholder="Corolla" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Plaka</label>
          <input type="text" placeholder="34 ABC 123" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Model Yılı</label>
          <input type="number" placeholder="2020" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Renk</label>
          <input type="text" placeholder="Beyaz" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div class="flex space-x-3">
          <button type="submit" class="flex-1 gradient-bg text-white py-3 rounded-lg font-bold">Ekle</button>
          <button type="button" onclick="closeVehicleModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg font-bold">İptal</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Sample data for car washes
    const allCarWashes = [{
        id: 1,
        name: 'CarWash Merkez',
        city: 'İstanbul',
        district: 'Kadıköy',
        rating: 4.8,
        isFavorite: true,
        services: ['Dış Yıkama', 'İç Temizlik']
      },
      {
        id: 2,
        name: 'CarWash Premium',
        city: 'İstanbul',
        district: 'Beşiktaş',
        rating: 4.9,
        isFavorite: false,
        services: ['Tam Detaylandırma', 'Motor Temizliği']
      },
      {
        id: 3,
        name: 'CarWash Express',
        city: 'İstanbul',
        district: 'Şişli',
        rating: 4.5,
        isFavorite: true,
        services: ['Dış Yıkama']
      },
      {
        id: 4,
        name: 'Ankara Oto Yıkama',
        city: 'Ankara',
        district: 'Çankaya',
        rating: 4.7,
        isFavorite: false,
        services: ['Dış Yıkama', 'İç Temizlik']
      },
      {
        id: 5,
        name: 'İzmir Hızlı Yıkama',
        city: 'İzmir',
        district: 'Bornova',
        rating: 4.6,
        isFavorite: true,
        services: ['Dış Yıkama']
      },
      {
        id: 6,
        name: 'Kadıköy Detay',
        city: 'İstanbul',
        district: 'Kadıköy',
        rating: 4.9,
        isFavorite: false,
        services: ['Tam Detaylandırma']
      },
    ];

    // Sample districts data (for dynamic loading)
    const districtsByCity = {
      'İstanbul': ['Kadıköy', 'Beşiktaş', 'Şişli', 'Fatih'],
      'Ankara': ['Çankaya', 'Kızılay', 'Yenimahalle'],
      'İzmir': ['Bornova', 'Konak', 'Karşıyaka']
    };

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
      });

      // Add active state to clicked link
      let targetLink = event.target;
      while (targetLink && targetLink.tagName !== 'A') {
        targetLink = targetLink.parentNode;
      }
      if (targetLink) {
        targetLink.classList.add('bg-white', 'bg-opacity-20');
      }

      // Special handling for carWashSelection to load list
      if (sectionId === 'carWashSelection') {
        loadDistrictOptions(); // Load districts for the default city or all
        filterCarWashes(); // Display all car washes initially
      }
      // Ensure reservation list is shown by default when navigating to reservations
      if (sectionId === 'reservations') {
        hideNewReservationForm(); // Ensure the list view is active
      }
    }

    function toggleNotifications() {
      const panel = document.getElementById('notificationPanel');
      panel.classList.toggle('hidden');
    }

    function closeNotifications() {
      document.getElementById('notificationPanel').classList.add('hidden');
    }

    function openVehicleModal() {
      document.getElementById('vehicleModal').classList.remove('hidden');
    }

    function closeVehicleModal() {
      document.getElementById('vehicleModal').classList.add('hidden');
    }

    // Functions for New Reservation Form
    function showNewReservationForm() {
      document.getElementById('reservationListView').classList.add('hidden');
      document.getElementById('newReservationForm').classList.remove('hidden');

      // Load available car washes
      loadCarWashes();
    }

    function hideNewReservationForm() {
      document.getElementById('newReservationForm').classList.add('hidden');
      document.getElementById('reservationListView').classList.remove('hidden');
    }

    function loadCarWashes() {
      // This would typically fetch from the server
      const locationSelect = document.getElementById('location');
      locationSelect.innerHTML = '<option value="">Konum Seçiniz</option>';

      // Sample data - in real implementation, this would come from PHP/AJAX
      const carWashes = [{
          id: 1,
          name: 'CarWash Merkez'
        },
        {
          id: 2,
          name: 'CarWash Premium'
        },
        {
          id: 3,
          name: 'CarWash Express'
        }
      ];

      carWashes.forEach(carWash => {
        const option = document.createElement('option');
        option.value = carWash.id;
        option.textContent = carWash.name;
        locationSelect.appendChild(option);
      });
    }

    function cancelReservation(reservationId) {
      if (confirm('Bu rezervasyonu iptal etmek istediğinizden emin misiniz?')) {
        // Create a form to submit the cancellation
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'Customer_Dashboard_process.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'cancel_reservation';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'reservation_id';
        idInput.value = reservationId;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
      }
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('vehicleModal');
      const notificationPanel = document.getElementById('notificationPanel');

      if (event.target == modal) {
        modal.classList.add('hidden');
      }

      if (!event.target.closest('#notificationPanel') && !event.target.closest('[onclick="toggleNotifications()"]')) {
        notificationPanel.classList.add('hidden');
      }
    }

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      showSection('dashboard');

      // Auto-hide messages after 5 seconds
      setTimeout(() => {
        const successMsg = document.getElementById('successMessage');
        const errorMsg = document.getElementById('errorMessage');
        if (successMsg) successMsg.remove();
        if (errorMsg) errorMsg.remove();
      }, 5000);
    });
  </script>

</body>

</html>