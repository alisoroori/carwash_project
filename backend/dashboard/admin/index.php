<?php
// Ensure autoloading and RBAC
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Classes\Auth;

Auth::requireRole('admin');

// Verify admin authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

try {
    $pdo = getDBConnection();

    // Get financial statistics
    $financialStats = $pdo->query("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_booking_value
        FROM bookings 
        WHERE status = 'completed'
    ")->fetch();

    // Get user statistics
    $userStats = $pdo->query("
        SELECT 
            role,
            COUNT(*) as count
        FROM users 
        GROUP BY role
    ")->fetchAll();

    // Get recent bookings
    $recentBookings = $pdo->query("
        SELECT b.*, u.name as customer_name, c.business_name as carwash_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN carwash_profiles c ON b.carwash_id = c.id
        ORDER BY b.created_at DESC
        LIMIT 10
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $error = "Failed to load dashboard data";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - CarWash Management</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/admin.css">
</head>

<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h1>Admin Dashboard</h1>
            <ul>
                <li><a href="#statistics">Statistics</a></li>
                <li><a href="#users">User Management</a></li>
                <li><a href="#carwashes">CarWash Management</a></li>
                <li><a href="#reports">Reports</a></li>
            </ul>
        </nav>

        <main class="admin-content">
            <!-- Statistics Section -->
            <section id="statistics" class="dashboard-section">
                <h2>Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Revenue</h3>
                        <p><?= number_format($financialStats['total_revenue'], 2) ?> USD</p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Bookings</h3>
                        <p><?= $financialStats['total_bookings'] ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Avg. Booking Value</h3>
                        <p><?= number_format($financialStats['avg_booking_value'], 2) ?> USD</p>
                    </div>
                </div>
            </section>

            <!-- Recent Bookings -->
            <section id="recent-bookings" class="dashboard-section">
                <h2>Recent Bookings</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>CarWash</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['id']) ?></td>
                                <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                <td><?= htmlspecialchars($booking['carwash_name']) ?></td>
                                <td><?= htmlspecialchars($booking['booking_date']) ?></td>
                                <td><?= htmlspecialchars($booking['status']) ?></td>
                                <td><?= number_format($booking['total_amount'] ?? $booking['total_price'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script src="/carwash_project/frontend/js/admin/dashboard.js"></script>
</body>

</html>
