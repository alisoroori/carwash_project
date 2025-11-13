<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check if user is a car wash owner
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'carwash') {
    header('Location: ../login.php');
    exit();
}

$carwashId = $_SESSION['carwash_id'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hizmet Paketleri - CarWash Panel</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Hizmet Paketleri</h1>
            <button onclick="showServiceModal()"
                class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700">
                <i class="fas fa-plus"></i> Yeni Paket Ekle
            </button>
        </div>

        <!-- Service Packages Grid -->
        <div id="servicesGrid" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Services will be loaded here -->
        </div>
    </div>

    <!-- Service Modal -->
    <div id="serviceModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium mb-4" id="modalTitle">Yeni Hizmet Paketi</h3>
                <form id="serviceForm" class="space-y-4">
                    <label for="serviceId" class="sr-only">Service Id</label><input type="hidden" name="serviceId" id="serviceId">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Paket Adı</label>
                        <label for="serviceName" class="sr-only">Name</label><input type="text" name="name" id="serviceName" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                        <label for="serviceDescription" class="sr-only">Description</label><textarea name="description" id="serviceDescription" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fiyat (TL)</label>
                        <label for="servicePrice" class="sr-only">Price</label><input type="number" name="price" id="servicePrice" required min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Süre (Dakika)</label>
                        <label for="serviceDuration" class="sr-only">Duration</label><input type="number" name="duration" id="serviceDuration" required min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeServiceModal()"
                            class="px-4 py-2 text-gray-500 hover:text-gray-700">
                            İptal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/carwash/services/service-manager.js"></script>
</body>

</html>




