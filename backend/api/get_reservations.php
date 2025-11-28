<?php

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;

Auth::requireAuth();

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];

    // Fetch past (completed) bookings with all required joins
    // Uses actual database structure: bookings, services, carwashes, user_vehicles
    // Note: COLLATE fixes collation mismatch between vehicle_plate and license_plate
    $bookings = $db->fetchAll("
        SELECT 
            b.id as booking_id,
            b.booking_date,
            b.booking_time,
            b.status,
            b.total_price,
            b.payment_status,
            b.payment_method,
            b.vehicle_plate,
            b.vehicle_model,
            b.vehicle_color,
            b.vehicle_type,
            b.notes,
            b.completed_at,
            b.created_at,
            s.name as service_name,
            s.description as service_description,
            s.price as service_price,
            s.category as service_category,
            s.duration as service_duration,
            c.name as carwash_name,
            c.address as carwash_address,
            c.city as carwash_city,
            c.phone as carwash_phone,
            uv.brand as vehicle_brand,
            uv.model as vehicle_model_full,
            uv.year as vehicle_year
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN carwashes c ON b.carwash_id = c.id
        LEFT JOIN user_vehicles uv ON b.vehicle_plate COLLATE utf8mb4_general_ci = uv.license_plate AND uv.user_id = b.user_id
        WHERE b.user_id = :user_id
        AND b.status = 'completed'
        ORDER BY b.completed_at DESC, b.created_at DESC
    ", ['user_id' => $userId]);

    // Format the response data
    $formattedBookings = [];
    foreach ($bookings as $booking) {
        $formattedBookings[] = [
            'booking_id' => $booking['booking_id'],
            'service_name' => $booking['service_name'] ?? 'Unknown Service',
            'service_category' => $booking['service_category'] ?? '',
            'service_description' => $booking['service_description'] ?? '',
            'service_duration' => $booking['service_duration'] ? $booking['service_duration'] . ' dk' : '',
            'carwash_name' => $booking['carwash_name'] ?? 'Unknown Carwash',
            'carwash_address' => $booking['carwash_address'] ?? '',
            'carwash_city' => $booking['carwash_city'] ?? '',
            'carwash_phone' => $booking['carwash_phone'] ?? '',
            'booking_date' => $booking['booking_date'],
            'booking_time' => $booking['booking_time'],
            'status' => $booking['status'],
            'total_price' => $booking['total_price'],
            'payment_status' => $booking['payment_status'],
            'payment_method' => $booking['payment_method'],
            'vehicle_plate' => $booking['vehicle_plate'] ?? '',
            'vehicle_brand' => $booking['vehicle_brand'] ?? '',
            'vehicle_model' => $booking['vehicle_model_full'] ?? $booking['vehicle_model'] ?? '',
            'vehicle_color' => $booking['vehicle_color'] ?? '',
            'vehicle_type' => $booking['vehicle_type'] ?? '',
            'vehicle_year' => $booking['vehicle_year'] ?? '',
            'vehicle_info' => trim(($booking['vehicle_brand'] ?? '') . ' ' . ($booking['vehicle_model_full'] ?? $booking['vehicle_model'] ?? '')),
            'notes' => $booking['notes'] ?? '',
            'completed_at' => $booking['completed_at'],
            'created_at' => $booking['created_at']
        ];
    }

    Response::success('Past bookings retrieved successfully', [
        'bookings' => $formattedBookings,
        'count' => count($formattedBookings)
    ]);

} catch (Exception $e) {
    Response::error('Failed to retrieve past bookings: ' . $e->getMessage());
}
?>
