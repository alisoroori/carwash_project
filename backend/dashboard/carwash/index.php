<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Classes\Auth;

Auth::requireRole('carwash');

// Verify carwash owner authentication
session_start();

try {
    $pdo = getDBConnection();
    $carwash_id = $_SESSION['carwash_id'];

    // Get today's bookings
    $todayBookings = $pdo->prepare("
        SELECT b.*, u.name as customer_name, s.name as service_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN services s ON b.service_id = s.id
        WHERE b.carwash_id = ? 
        AND b.booking_date = CURDATE()
        ORDER BY b.booking_time ASC
    ");
    $todayBookings->execute([$carwash_id]);

    // Get services list
    $services = $pdo->prepare("
        SELECT * FROM services 
        WHERE carwash_id = ? 
        ORDER BY name ASC
    ");
    $services->execute([$carwash_id]);

    // Get working hours
    $hours = $pdo->prepare("
        SELECT * FROM working_hours 
        WHERE carwash_id = ? 
        ORDER BY day_of_week ASC
    ");
    $hours->execute([$carwash_id]);

    // Get unread messages count
    $unreadMessages = $pdo->prepare("
        SELECT COUNT(*) FROM messages 
        WHERE carwash_id = ? 
        AND is_read = 0
    ");
    $unreadMessages->execute([$carwash_id]);
} catch (PDOException $e) {
    error_log("Carwash dashboard error: " . $e->getMessage());
    $error = "Failed to load dashboard data";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CarWash Dashboard</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/admin.css">
    <link rel="stylesheet" href="/carwash_project/frontend/css/carwash.css">
</head>

<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h1>CarWash Dashboard</h1>
            <ul>
                <li><a href="#bookings">Today's Bookings</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#hours">Working Hours</a></li>
                <li><a href="#messages">Messages</a></li>
            </ul>
        </nav>

        <main class="admin-content">
            <!-- Today's Bookings -->
            <section id="bookings" class="dashboard-section">
                <h2>Today's Bookings</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $todayBookings->fetch()): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['booking_time']) ?></td>
                                <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                <td><?= htmlspecialchars($booking['service_name']) ?></td>
                                <td><?= htmlspecialchars($booking['status']) ?></td>
                                <td>
                                    <button class="btn-action" data-action="complete"
                                        data-id="<?= $booking['id'] ?>">Complete</button>
                                    <button class="btn-action" data-action="cancel"
                                        data-id="<?= $booking['id'] ?>">Cancel</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>

            <!-- Services Management -->
            <section id="services" class="dashboard-section">
                <h2>Services</h2>
                <button class="btn-primary" id="addServiceBtn">Add New Service</button>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $services->fetch()): ?>
                            <tr>
                                <td><?= htmlspecialchars($service['name']) ?></td>
                                <td>$<?= number_format($service['price'], 2) ?></td>
                                <td><?= $service['duration'] ?> min</td>
                                <td><?= htmlspecialchars($service['status']) ?></td>
                                <td>
                                    <button class="btn-action" data-action="edit"
                                        data-id="<?= $service['id'] ?>">Edit</button>
                                    <button class="btn-action" data-action="delete"
                                        data-id="<?= $service['id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add/Edit Service</h2>
            <form id="serviceForm">
                <input type="hidden" name="id" id="serviceId">
                <div class="form-group">
                    <label for="serviceName">Service Name</label>
                    <input type="text" id="serviceName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="servicePrice">Price</label>
                    <input type="number" id="servicePrice" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="serviceDuration">Duration (minutes)</label>
                    <input type="number" id="serviceDuration" name="duration" required>
                </div>
                <button type="submit" class="btn-primary">Save Service</button>
            </form>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/carwash/dashboard.js"></script>
</body>

</html>
