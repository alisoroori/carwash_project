<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;

// Only admin users should access analytics
Auth::requireRole('admin');

try {
    $db = Database::getInstance();

    // Total users - defensive is_array check
    $totalUsersRow = $db->fetchOne('SELECT COUNT(*) AS total FROM users');
    $total_users = ($totalUsersRow && is_array($totalUsersRow) && isset($totalUsersRow['total'])) ? (int)$totalUsersRow['total'] : 0;

    // Active users in last 30 days (fallback to last_login column if present)
    $activeUsersRow = $db->fetchOne("SELECT COUNT(*) AS active FROM users WHERE COALESCE(last_login, '1970-01-01') >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $active_users = ($activeUsersRow && is_array($activeUsersRow) && isset($activeUsersRow['active'])) ? (int)$activeUsersRow['active'] : 0;

    // Orders / bookings counts
    $totalOrdersRow = $db->fetchOne('SELECT COUNT(*) AS total FROM bookings');
    $total_orders = ($totalOrdersRow && is_array($totalOrdersRow) && isset($totalOrdersRow['total'])) ? (int)$totalOrdersRow['total'] : 0;

    $completedOrdersRow = $db->fetchOne("SELECT COUNT(*) AS completed FROM bookings WHERE status = 'completed'");
    $completed_orders = ($completedOrdersRow && is_array($completedOrdersRow) && isset($completedOrdersRow['completed'])) ? (int)$completedOrdersRow['completed'] : 0;

    $pendingOrdersRow = $db->fetchOne("SELECT COUNT(*) AS pending FROM bookings WHERE status IN ('pending','processing')");
    $pending_orders = ($pendingOrdersRow && is_array($pendingOrdersRow) && isset($pendingOrdersRow['pending'])) ? (int)$pendingOrdersRow['pending'] : 0;

    // Revenue (sum of paid bookings)
    $revenueRow = $db->fetchOne("SELECT COALESCE(SUM(CAST(total_price AS DECIMAL(10,2))),0) AS revenue FROM bookings WHERE payment_status = 'paid'");
    $revenue = ($revenueRow && is_array($revenueRow) && isset($revenueRow['revenue'])) ? (float)$revenueRow['revenue'] : 0.0;

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
