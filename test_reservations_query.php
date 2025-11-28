<?php
require_once 'backend/includes/bootstrap.php';
use App\Classes\Database;

try {
    $db = Database::getInstance();
    $userId = 1;

    echo "Testing database query...\n";

    // Test the exact query from get_reservations.php
    $bookings = $db->fetchAll("
        SELECT
            b.id,
            b.booking_number,
            b.booking_date,
            b.booking_time,
            b.end_time,
            b.status,
            b.total_amount,
            b.discount_amount,
            b.special_requests,
            b.created_at,
            b.updated_at,
            GROUP_CONCAT(s.name SEPARATOR ', ') as service_names,
            GROUP_CONCAT(s.duration_minutes) as service_durations,
            cp.business_name as carwash_name,
            cp.address as carwash_address,
            cp.city as carwash_city,
            cp.state as carwash_state,
            v.brand as vehicle_brand,
            v.model as vehicle_model,
            v.license_plate as vehicle_plate,
            v.year as vehicle_year,
            v.color as vehicle_color
        FROM bookings b
        LEFT JOIN booking_services bs ON b.id = bs.booking_id
        LEFT JOIN services s ON bs.service_id = s.id
        LEFT JOIN carwash_profiles cp ON b.carwash_id = cp.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.user_id = :user_id
        AND b.status = 'completed'
        GROUP BY b.id
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ", ['user_id' => $userId]);

    echo "Query successful, found " . count($bookings) . " bookings\n";

} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>