<?php
require_once '../includes/auth_required.php';
require_once '../includes/db.php';

requireLogin();

$carwash_id = $_GET['id'] ?? null;

if (!$carwash_id) {
    header('Location: /carwash_project/frontend/index.php');
    exit();
}

// Get carwash details
$stmt = $conn->prepare("
    SELECT * FROM carwash_profiles WHERE id = ?
");
$stmt->bind_param('i', $carwash_id);
$stmt->execute();
$carwash = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Wash Booking - <?= htmlspecialchars($carwash['business_name'] ?? $carwash['name'] ?? '') ?></title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="/carwash_project/frontend/css/style.css" rel="stylesheet">
    <link href="/carwash_project/frontend/css/booking.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6"><?= htmlspecialchars($carwash['business_name'] ?? $carwash['name'] ?? '') ?></h1>

                <!-- Service Selection -->
                <div id="serviceSelection" class="mb-8">
                    <h2 class="text-lg font-semibold mb-4">Select Services</h2>
                    <div id="servicesList" class="space-y-4">
                        <!-- Services will be loaded here -->
                    </div>
                </div>

                <!-- Date & Time Selection -->
                <div id="dateTimeSelection" class="mb-8">
                    <h2 class="text-lg font-semibold mb-4">Select Date & Time</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="appointmentDate" class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" id="appointmentDate" min="<?= date('Y-m-d') ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time</label>
                            <div id="timeSlots" class="mt-1 grid grid-cols-4 gap-2">
                                <!-- Time slots will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div id="bookingSummary" class="mb-8">
                    <h2 class="text-lg font-semibold mb-4">Booking Summary</h2>
                    <div class="bg-gray-50 p-4 rounded">
                        <div id="selectedServices" class="mb-4"></div>
                        <div class="text-xl font-bold">
                            Total: <span id="totalPrice">â‚º0</span>
                        </div>
                    </div>
                </div>

                <!-- Confirm Button -->
                <button id="confirmBooking"
                    class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700"
                    disabled>
                    Confirm Booking
                </button>
            </div>
        </div>
    </div>

    <script>
        const carwashId = <?= $carwash_id ?>;
        const userId = <?= $_SESSION['user_id'] ?>;
    </script>
    <!-- Update script paths to absolute -->
    <script src="/carwash_project/frontend/js/customer/booking/booking-wizard.js"></script>
    <script src="/carwash_project/frontend/js/shared/components/notifications.js"></script>

    <!-- If package booking is needed -->
    <script src="/carwash_project/frontend/js/customer/booking/package-booking.js"></script>

    <!-- Add shared utilities -->
    <script src="/carwash_project/frontend/js/shared/utils/form-validator.js"></script>
    <script src="/carwash_project/frontend/js/admin/dashboard.js"></script>
</body>

</html>

