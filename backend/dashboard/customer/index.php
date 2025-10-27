<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;
use App\Classes\Session;

// Initialize session and require auth
Session::start();
Auth::requireAuth();

$user_id = Session::get('user_id') ?? ($_SESSION['user_id'] ?? null);
if (empty($user_id) || !Auth::hasRole('customer')) {
    header('Location: ../../auth/login.php');
    exit();
}

try {
    $db = Database::getInstance();

    // Get user's bookings
    $stmt = $db->prepare("SELECT b.*, c.name as carwash_name, s.name as service_name
        FROM bookings b
        LEFT JOIN carwash c ON b.carwash_id = c.id
        LEFT JOIN services s ON b.service_id = s.id
        WHERE b.user_id = :uid
        ORDER BY b.booking_date DESC, b.booking_time DESC
        LIMIT 10");
    $stmt->execute(['uid' => $user_id]);
    $bookings = $stmt->fetchAll();

    // Get user profile
    $profileStmt = $db->prepare("SELECT name, email, phone, created_at FROM users WHERE id = :uid");
    $profileStmt->execute(['uid' => $user_id]);
    $userProfile = $profileStmt->fetch();
} catch (\Throwable $e) {
    error_log("Customer dashboard error: " . $e->getMessage());
    $error = "Failed to load dashboard data";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/admin.css">
    <link rel="stylesheet" href="/carwash_project/frontend/css/customer.css">
    </head>

<body>
    <div class="dashboard-container">
        <nav class="dashboard-nav">
            <h1>My Dashboard</h1>
            <ul>
                <li><a href="#bookings">My Bookings</a></li>
                <li><a href="#profile">Profile</a></li>
                <li><a href="#reviews">My Reviews</a></li>
            </ul>
        </nav>

        <main class="dashboard-content">
            <!-- Bookings Section -->
            <section id="bookings" class="dashboard-section">
                <h2>My Bookings</h2>
                <div class="bookings-list">
                    <?php if (empty($bookings)): ?>
                        <div class="bg-white rounded-lg shadow-md p-6 text-center">
                            <p class="text-gray-600">You have no bookings yet.</p>
                            <a href="new_booking.php" class="inline-block mt-4 text-blue-600 hover:text-blue-800">
                                <i class="fas fa-plus"></i> Create a booking
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <h3><?= htmlspecialchars($booking['carwash_name'] ?? ($booking['business_name'] ?? '-')) ?></h3>
                                    <span class="booking-status <?= htmlspecialchars($booking['status'] ?? '') ?>">
                                        <?= ucfirst(htmlspecialchars($booking['status'] ?? '')) ?>
                                    </span>
                                </div>
                                <div class="booking-details">
                                    <p><strong>Service:</strong> <?= htmlspecialchars($booking['service_name'] ?? '') ?></p>
                                    <p><strong>Date:</strong> <?= htmlspecialchars($booking['booking_date'] ?? '') ?></p>
                                    <p><strong>Time:</strong> <?= htmlspecialchars($booking['booking_time'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile" class="dashboard-section">
                <h2>My Profile</h2>
                <form id="profileForm" class="profile-form">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($userProfile['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($userProfile['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($userProfile['phone'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-primary">Update Profile</button>
                </form>
            </section>
        </main>
    </div>

    <script src="/carwash_project/frontend/js/customer/dashboard.js"></script>
</body>

</html>