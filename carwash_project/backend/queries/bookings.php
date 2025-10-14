<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\queries\bookings.php

require_once '../includes/db.php';

/**
 * Create a new booking
 */
function createBooking($userId, $carwashId, $serviceId, $bookingDate, $status = 'pending') {
    $conn = getDBConnection();
    $query = "INSERT INTO bookings (user_id, carwash_id, service_id, booking_date, status) VALUES (:user_id, :carwash_id, :service_id, :booking_date, :status)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $userId,
        ':carwash_id' => $carwashId,
        ':service_id' => $serviceId,
        ':booking_date' => $bookingDate,
        ':status' => $status
    ]);
    return $conn->lastInsertId();
}

/**
 * Retrieve all bookings for a specific user
 */
function getBookingsByUser($userId) {
    $conn = getDBConnection();
    $query = "SELECT * FROM bookings WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Retrieve a specific booking by ID
 */
function getBookingById($bookingId) {
    $conn = getDBConnection();
    $query = "SELECT * FROM bookings WHERE id = :booking_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':booking_id' => $bookingId]);
    return $stmt->fetch();
}

/**
 * Update a booking's status
 */
function updateBookingStatus($bookingId, $status) {
    $conn = getDBConnection();
    $query = "UPDATE bookings SET status = :status WHERE id = :booking_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':status' => $status,
        ':booking_id' => $bookingId
    ]);
    return $stmt->rowCount();
}

/**
 * Delete a booking by ID
 */
function deleteBooking($bookingId) {
    $conn = getDBConnection();
    $query = "DELETE FROM bookings WHERE id = :booking_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':booking_id' => $bookingId]);
    return $stmt->rowCount();
}
?>