<?php
// Test page to verify database connectivity for customer dashboards
// Place in browser: /carwash_project/backend/dashboard/customer/test_db.php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;
use App\Classes\Session;

// Ensure bootstrap loaded and session started
Session::start();

// Require authentication
Auth::requireAuth();

// Enforce customer role for the test page (will redirect to login if unauthorized)
if (!Auth::hasRole('customer')) {
    // If role not present, try to populate then re-check
    $uid = Session::get('user_id') ?? $_SESSION['user_id'] ?? null;
    if ($uid) {
        // attempt to populate role
        // Auth::requireRole would exit if unauthorized; here just show message
        Auth::populateRoleFromDb((int)$uid);
    }
}

// After attempts, ensure the user is customer
if (!Auth::hasRole('customer')) {
    http_response_code(403);
    echo "403 Forbidden - you must be a customer to view this page.";
    exit;
}

// Fetch current user id from Session or legacy session
$userId = Session::get('user_id') ?? Session::get('user')['id'] ?? ($_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null));

if (empty($userId)) {
    echo "No user id found in session. Please log in as a customer to test.";
    exit;
}

$db = null;
try {
    $db = Database::getInstance();
} catch (\Throwable $e) {
    echo "Database connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}

// Simple queries for diagnostics and sample data
$results = [];
try {
    // Count user's bookings
    $countRow = $db->fetchOne('SELECT COUNT(*) AS cnt FROM bookings WHERE user_id = :uid', ['uid' => $userId]);
    $results['booking_count'] = $countRow['cnt'] ?? 0;

    // Fetch recent bookings with related carwash and service names (if tables exist)
    $bookings = $db->fetchAll(
        'SELECT b.id, b.booking_date, b.booking_time, b.status, c.business_name AS carwash_name, s.name AS service_name
         FROM bookings b
         LEFT JOIN carwash_profiles c ON b.carwash_id = c.id
         LEFT JOIN services s ON b.service_id = s.id
         WHERE b.user_id = :uid
         ORDER BY b.booking_date DESC, b.booking_time DESC
         LIMIT 25',
        ['uid' => $userId]
    );
    $results['bookings'] = $bookings;
} catch (\Throwable $e) {
    $results['error'] = 'Query failed: ' . $e->getMessage();
}

// Output a minimal HTML page with results
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Customer DB Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        table { border-collapse: collapse; width: 100%; max-width: 1000px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f5f5f5; }
        .muted { color: #666; font-size: 0.9em; }
        .error { color: #a00; }
    </style>
</head>
<body>
    <h1>Customer Dashboard — Database Test</h1>
    <p class="muted">Signed in user id: <?php echo htmlspecialchars((string)$userId); ?></p>

    <?php if (!empty($results['error'])): ?>
        <p class="error"><?php echo htmlspecialchars($results['error']); ?></p>
    <?php endif; ?>

    <h2>Bookings Summary</h2>
    <p>Total bookings found for this user: <strong><?php echo (int)($results['booking_count'] ?? 0); ?></strong></p>

    <?php if (!empty($results['bookings'])): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Carwash</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results['bookings'] as $b): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['id']); ?></td>
                        <td><?php echo htmlspecialchars($b['carwash_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($b['service_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                        <td><?php echo htmlspecialchars($b['booking_time']); ?></td>
                        <td><?php echo htmlspecialchars($b['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No bookings to display.</p>
    <?php endif; ?>

    <h2>Session Data (debug)</h2>
    <pre><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>

    <p class="muted">If this page shows bookings and no errors, database connectivity is working for customer dashboards.</p>
</body>
</html>
