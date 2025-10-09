<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// Verify customer authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../../auth/login.php');
    exit();
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];

    // Get user's bookings
    $bookings = $pdo->prepare("
        SELECT b.*, c.name as carwash_name, s.name as service_name
        FROM bookings b
        JOIN carwash c ON b.carwash_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC, b.booking_time DESC
        LIMIT 10
    ");
    $bookings->execute([$user_id]);

    // Get user profile
    $profile = $pdo->prepare("
        SELECT name, email, phone, created_at
        FROM users
        WHERE id = ?
    ");
    $profile->execute([$user_id]);
    $userProfile = $profile->fetch();
} catch (PDOException $e) {
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
                    <?php while ($booking = $bookings->fetch()): ?>
                        <div class="booking-card">
                            <div class="booking-header">
                                <h3><?= htmlspecialchars($booking['carwash_name']) ?></h3>
                                <span class="booking-status <?= $booking['status'] ?>">
                                    <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                </span>
                            </div>
                            <div class="booking-details">
                                <p><strong>Service:</strong> <?= htmlspecialchars($booking['service_name']) ?></p>
                                <p><strong>Date:</strong> <?= date('F j, Y', strtotime($booking['booking_date'])) ?></p>
                                <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking['booking_time'])) ?></p>
                            </div>
                            <?php if ($booking['status'] === 'completed' && !$booking['review_id']): ?>
                                <button class="btn-review" data-booking-id="<?= $booking['id'] ?>">
                                    Leave Review
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile" class="dashboard-section">
                <h2>My Profile</h2>
                <form id="profileForm" class="profile-form">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name"
                            value="<?= htmlspecialchars($userProfile['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($userProfile['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone"
                            value="<?= htmlspecialchars($userProfile['phone']) ?>">
                    </div>
                    <button type="submit" class="btn-primary">Update Profile</button>
                </form>
            </section>
        </main>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Write a Review</h2>
            <form id="reviewForm">
                <input type="hidden" name="booking_id" id="bookingId">
                <div class="form-group">
                    <label for="rating">Rating</label>
                    <div class="rating-stars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>">
                            <label for="star<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="comment">Comment</label>
                    <textarea name="comment" id="comment" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-primary">Submit Review</button>
            </form>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/customer/dashboard.js"></script>
</body>

</html>