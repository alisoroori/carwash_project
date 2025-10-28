<?php
require_once '../../backend/includes/auth_check.php';
require_once '../../backend/includes/db.php';

$package_id = $_GET['id'] ?? null;
if (!$package_id) {
    header('Location: /carwash_project/frontend/index.php');
    exit();
}

// Get package details
$stmt = $conn->prepare("
    SELECT p.*, c.name as carwash_name 
    FROM service_packages p
    JOIN carwash c ON p.carwash_id = c.id
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->bind_param('i', $package_id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Book Package - <?= htmlspecialchars($package['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <!-- Package Details -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($package['name']) ?></h1>
                <div class="text-gray-600 mb-4"><?= nl2br(htmlspecialchars($package['description'])) ?></div>
                <div class="text-2xl font-bold text-blue-600 mb-2">₺<?= number_format($package['price'], 2) ?></div>
                <?php if ($package['discount_percentage'] > 0): ?>
                    <div class="text-green-600">Save <?= $package['discount_percentage'] ?>%</div>
                <?php endif; ?>
            </div>

            <!-- Booking Form -->
            <form id="packageBookingForm" class="bg-white rounded-lg shadow-lg p-6">
                <input type="hidden" name="package_id" value="<?= $package_id ?>">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Select Date</label>
                    <input type="date" name="date" required min="<?= date('Y-m-d') ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div id="timeSlots" class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Available Times</label>
                    <div class="mt-1 grid grid-cols-4 gap-2">
                        <!-- Time slots will be loaded dynamically -->
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700">
                    Book Package
                </button>
            </form>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/package-booking.js"></script>
</body>

</html>
