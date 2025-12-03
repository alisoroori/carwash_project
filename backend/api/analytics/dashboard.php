<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;

// Only admin users should access analytics
Auth::requireRole('admin');

try {
    $db = Database::getInstance();

    // Total users
    $totalUsersRow = $db->fetchOne('SELECT COUNT(*) AS total FROM users');
    $total_users = (int)($totalUsersRow['total'] ?? 0);

    // Active users in last 30 days (fallback to last_login column if present)
    $activeUsersRow = $db->fetchOne("SELECT COUNT(*) AS active FROM users WHERE COALESCE(last_login, '1970-01-01') >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $active_users = (int)($activeUsersRow['active'] ?? 0);

    // Orders / bookings counts
    $totalOrdersRow = $db->fetchOne('SELECT COUNT(*) AS total FROM bookings');
    $total_orders = (int)($totalOrdersRow['total'] ?? 0);

    $completedOrdersRow = $db->fetchOne("SELECT COUNT(*) AS completed FROM bookings WHERE status = 'completed'");
    $completed_orders = (int)($completedOrdersRow['completed'] ?? 0);

    $pendingOrdersRow = $db->fetchOne("SELECT COUNT(*) AS pending FROM bookings WHERE status IN ('pending','processing')");
    $pending_orders = (int)($pendingOrdersRow['pending'] ?? 0);

    // Revenue (sum of paid bookings)
    $revenueRow = $db->fetchOne("SELECT COALESCE(SUM(CAST(total_price AS DECIMAL(10,2))),0) AS revenue FROM bookings WHERE payment_status = 'paid'");
    $revenue = (float)($revenueRow['revenue'] ?? 0);

    Response::success('Dashboard stats retrieved', [
        'total_users' => $total_users,
        'active_users' => $active_users,
        'total_orders' => $total_orders,
        'completed_orders' => $completed_orders,
        'pending_orders' => $pending_orders,
        'revenue' => $revenue
    ]);

} catch (Exception $e) {
    Response::error('Failed to load dashboard analytics: ' . $e->getMessage());
}
