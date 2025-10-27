<?php
require_once '../../backend/includes/auth_required.php';
require_once '../../backend/includes/auth_check.php';
require_once '../../backend/includes/db.php';

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    header('Location: /carwash_project/frontend/dashboard/user_dashboard.php');
    exit();
}

// Check if booking belongs to user
$stmt = $conn->prepare("
    SELECT b.*, c.name as carwash_name 
    FROM bookings b
    JOIN carwash c ON b.carwash_id = c.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param('ii', $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: /carwash_project/frontend/dashboard/user_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Add Review - <?= htmlspecialchars($booking['carwash_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">Rate Your Experience</h1>

                <div class="mb-6">
                    <h2 class="text-lg font-semibold"><?= htmlspecialchars($booking['carwash_name']) ?></h2>
                    <p class="text-gray-600">
                        Visit Date: <?= date('d/m/Y', strtotime($booking['appointment_date'])) ?>
                    </p>
                </div>

                <form id="reviewForm" class="space-y-6">
                    <input type="hidden" name="booking_id" value="<?= $booking_id ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rating</label>
                        <div class="flex items-center space-x-2 mt-2" id="ratingStars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" data-rating="<?= $i ?>"
                                    class="rating-star text-gray-300 text-2xl hover:text-yellow-400">
                                    <i class="fas fa-star"></i>
                                </button>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Your Review</label>
                        <textarea name="comment" rows="4" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700">
                        Submit Review
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/customer/reviews/review-submission.js"></script>
</body>

</html>
