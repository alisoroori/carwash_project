<?php
session_start();
require_once '../../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

// Get current settings
$stmt = $conn->prepare("SELECT * FROM system_settings");
$stmt->execute();
$settings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Convert to key-value array
$config = array_column($settings, 'value', 'key');
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - AquaTR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-xl font-bold text-blue-600">
                    <i class="fas fa-arrow-left"></i> Panele Dön
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Sistem Ayarları</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- General Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Genel Ayarlar</h2>
                <form action="update_settings.php" method="POST" class="space-y-6">
                    <input type="hidden" name="section" value="general">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Site Başlığı</label>
                        <input type="text" name="site_title"
                            value="<?php echo htmlspecialchars($config['site_title'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">İletişim Email</label>
                        <input type="email" name="contact_email"
                            value="<?php echo htmlspecialchars($config['contact_email'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">İletişim Telefon</label>
                        <input type="tel" name="contact_phone"
                            value="<?php echo htmlspecialchars($config['contact_phone'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                        Kaydet
                    </button>
                </form>
            </div>

            <!-- Booking Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Randevu Ayarları</h2>
                <form action="update_settings.php" method="POST" class="space-y-6">
                    <input type="hidden" name="section" value="booking">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Minimum Randevu Süresi (dakika)</label>
                        <input type="number" name="min_booking_duration"
                            value="<?php echo htmlspecialchars($config['min_booking_duration'] ?? '30'); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Maksimum Önceden Rezervasyon (gün)</label>
                        <input type="number" name="max_advance_booking_days"
                            value="<?php echo htmlspecialchars($config['max_advance_booking_days'] ?? '30'); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">İptal Süresi Limiti (saat)</label>
                        <input type="number" name="cancellation_limit_hours"
                            value="<?php echo htmlspecialchars($config['cancellation_limit_hours'] ?? '24'); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                        Kaydet
                    </button>
                </form>
            </div>

            <!-- Email Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Email Ayarları</h2>
                <form action="update_settings.php" method="POST" class="space-y-6">
                    <input type="hidden" name="section" value="email">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP Sunucu</label>
                        <input type="text" name="smtp_host"
                            value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP Port</label>
                        <input type="number" name="smtp_port"
                            value="<?php echo htmlspecialchars($config['smtp_port'] ?? '587'); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP Kullanıcı</label>
                        <input type="text" name="smtp_user"
                            value="<?php echo htmlspecialchars($config['smtp_user'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP Şifre</label>
                        <input type="password" name="smtp_pass"
                            value="<?php echo htmlspecialchars($config['smtp_pass'] ?? ''); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                        Kaydet
                    </button>
                </form>
            </div>

            <!-- System Maintenance -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Sistem Bakımı</h2>
                <div class="space-y-4">
                    <button onclick="clearCache()"
                        class="w-full bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700">
                        Önbelleği Temizle
                    </button>

                    <button onclick="backupDatabase()"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
                        Veritabanı Yedekle
                    </button>

                    <button onclick="confirmMaintenance()"
                        class="w-full bg-yellow-600 text-white py-2 px-4 rounded-md hover:bg-yellow-700">
                        Bakım Modu <?php echo ($config['maintenance_mode'] ?? 'off') === 'on' ? 'Kapat' : 'Aç'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function clearCache() {
            if (confirm('Önbelleği temizlemek istediğinize emin misiniz?')) {
                fetch('maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=clear_cache'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Önbellek temizlendi.');
                        } else {
                            alert(data.error || 'Bir hata oluştu.');
                        }
                    });
            }
        }

        function backupDatabase() {
            if (confirm('Veritabanı yedeklemesi başlatılsın mı?')) {
                fetch('maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=backup_db'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Yedekleme tamamlandı.');
                        } else {
                            alert(data.error || 'Bir hata oluştu.');
                        }
                    });
            }
        }

        function confirmMaintenance() {
            if (confirm('Bakım modunu değiştirmek istediğinize emin misiniz?')) {
                fetch('maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=toggle_maintenance'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.error || 'Bir hata oluştu.');
                        }
                    });
            }
        }
    </script>
</body>

</html>