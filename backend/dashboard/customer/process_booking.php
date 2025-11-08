<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Database;
use App\Classes\Session;
use App\Classes\Auth;

Session::start();
Auth::requireAuth();

$userId = Session::get('user_id') ?? ($_SESSION['user_id'] ?? null);
if (empty($userId) || !Auth::hasRole('customer')) {
    header('Location: ../../auth/login.php');
    exit();
}

// Get available carwashes via PSR-4 Database
$db = Database::getInstance();
$carwashes = $db->fetchAll("SELECT id, business_name, business_name AS name, address, contact_phone, contact_phone AS phone, average_rating as rating, verified
    FROM carwash_profiles WHERE 1");
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Randevu - AquaTR</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-xl font-semibold text-blue-600">
                    <i class="fas fa-arrow-left"></i> Panele DÃ¶n
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Yeni Randevu OluÅŸtur</h1>

        <form id="bookingForm" action="process_booking.php" method="POST" class="bg-white rounded-lg shadow-md p-6">
            <!-- Step 1: Select CarWash -->
            <div class="booking-step" id="step1">
                <h2 class="text-xl font-semibold mb-4">1. AraÃ§ YÄ±kama Merkezi SeÃ§in</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($carwashes as $carwash): ?>
                        <div class="border rounded p-4 hover:border-blue-500 cursor-pointer carwash-option">
                            <input type="radio" name="carwash_id" value="<?php echo htmlspecialchars($carwash['id']); ?>" class="hidden">
                            <h3 class="font-semibold"><?php echo htmlspecialchars($carwash['business_name'] ?? $carwash['name'] ?? ''); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($carwash['address'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-star text-yellow-400"></i>
                                <?php echo isset($carwash['rating']) ? number_format($carwash['rating'], 1) : '-'; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Step 2: Select Service -->
            <div class="booking-step hidden" id="step2">
                <h2 class="text-xl font-semibold mb-4">2. Hizmet SeÃ§in</h2>
                <div id="services-container" class="grid grid-cols-1 gap-4">
                    <!-- Services will be loaded dynamically -->
                </div>
            </div>

            <!-- Step 3: Select Date & Time -->
            <div class="booking-step hidden" id="step3">
                <h2 class="text-xl font-semibold mb-4">3. Tarih ve Saat SeÃ§in</h2>
                <div class="space-y-4">
                    <div>
                        <label for="booking_date_input" class="block text-sm font-medium text-gray-700">Tarih</label>
                        <input id="booking_date_input" type="date" name="booking_date" required
                            min="<?php echo date('Y-m-d'); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="booking_time_select" class="block text-sm font-medium text-gray-700">Saat</label>
                        <select id="booking_time_select" name="booking_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <!-- Time slots will be loaded dynamically -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-6 flex justify-between">
                <button type="button" id="prevBtn" class="hidden px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i> Geri
                </button>
                <button type="button" id="nextBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Ä°leri <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button type="submit" id="submitBtn" class="hidden px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Randevuyu Onayla <i class="fas fa-check ml-2"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;

        // Handle carwash selection
        document.querySelectorAll('.carwash-option').forEach(option => {
            option.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                document.querySelectorAll('.carwash-option').forEach(opt => {
                    opt.classList.remove('border-blue-500');
                });
                this.classList.add('border-blue-500');

                // Load services for selected carwash
                if (radio.checked) {
                    loadServices(radio.value);
                }
            });
        });

        // Load services for selected carwash
        function loadServices(carwashId) {
            fetch(`get_services.php?carwash_id=${carwashId}`)
                .then(response => response.json())
                .then(services => {
                    const container = document.getElementById('services-container');
                    container.innerHTML = '';
                    services.forEach(service => {
                        container.innerHTML += `
                            <div class="border rounded p-4 hover:border-blue-500 cursor-pointer service-option">
                                <input type="radio" name="service_id" value="${service.id}" class="hidden">
                                <h3 class="font-semibold">${service.service_name}</h3>
                                <p class="text-sm text-gray-600">${service.description}</p>
                                <p class="text-sm font-semibold text-blue-600">${service.price} TL</p>
                            </div>
                        `;
                    });

                    // Add click handlers for service options
                    document.querySelectorAll('.service-option').forEach(option => {
                        option.addEventListener('click', function() {
                            const radio = this.querySelector('input[type="radio"]');
                            radio.checked = true;
                            document.querySelectorAll('.service-option').forEach(opt => {
                                opt.classList.remove('border-blue-500');
                            });
                            this.classList.add('border-blue-500');
                        });
                    });
                });
        }

        // Navigation between steps
        document.getElementById('nextBtn').addEventListener('click', () => {
            if (validateCurrentStep()) {
                currentStep++;
                updateStepVisibility();
            }
        });

        document.getElementById('prevBtn').addEventListener('click', () => {
            currentStep--;
            updateStepVisibility();
        });

        function updateStepVisibility() {
            document.querySelectorAll('.booking-step').forEach((step, index) => {
                step.classList.toggle('hidden', index + 1 !== currentStep);
            });

            document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 1);
            document.getElementById('nextBtn').classList.toggle('hidden', currentStep === totalSteps);
            document.getElementById('submitBtn').classList.toggle('hidden', currentStep !== totalSteps);
        }

        function validateCurrentStep() {
            switch (currentStep) {
                case 1:
                    return document.querySelector('input[name="carwash_id"]:checked') !== null;
                case 2:
                    return document.querySelector('input[name="service_id"]:checked') !== null;
                default:
                    return true;
            }
        }

        // Form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateForm()) {
                return;
            }
            this.submit();
        });

        function validateForm() {
            const requiredFields = ['carwash_id', 'service_id', 'booking_date', 'booking_time'];
            return requiredFields.every(field => {
                const element = document.querySelector(`[name="${field}"]`);
                return element && element.value;
            });
        }
    </script>
</body>

</html>

