<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/email_helper.php';
require_once '../includes/sms_helper.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Lütfen önce giriş yapın');
    }

    // Validate required fields
    $required_fields = ['service_type', 'service_price', 'vehicle_type', 'booking_date', 'booking_time'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception('Tüm alanları doldurun');
        }
    }

    // Start transaction
    $conn->begin_transaction();

    // Get customer details
    $stmt = $conn->prepare("
        SELECT name, email 
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();

    // Create booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (
            customer_id,
            service_type,
            vehicle_type,
            booking_date,
            booking_time,
            price,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->bind_param(
        'issssd',
        $_SESSION['user_id'],
        $_POST['service_type'],
        $_POST['vehicle_type'],
        $_POST['booking_date'],
        $_POST['booking_time'],
        $_POST['service_price']
    );

    if (!$stmt->execute()) {
        throw new Exception('Rezervasyon oluşturulamadı');
    }

    $booking_id = $conn->insert_id;

    // Get customer phone number
    $stmt = $conn->prepare("
        SELECT name, email, phone 
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();

    // Prepare booking data
    $booking_data = [
        'booking_id' => $booking_id,
        'customer_name' => $customer['name'],
        'customer_email' => $customer['email'],
        'customer_phone' => $customer['phone'],
        'booking_date' => $_POST['booking_date'],
        'booking_time' => $_POST['booking_time'],
        'service_type' => $_POST['service_type'],
        'vehicle_type' => $_POST['vehicle_type'],
        'price' => $_POST['service_price']
    ];

    // Send notifications
    $emailHelper = new EmailHelper();
    $smsHelper = new SMSHelper($conn);
    // Send customer notifications
    $emailHelper->sendBookingConfirmation($booking_data);
    if (!empty($customer['phone'])) {
        $smsHelper->sendBookingConfirmation($customer['phone'], $booking_data);
    }

    // Send carwash notifications
    $emailHelper->sendBookingNotificationToCarwash($booking_data);
    // Get carwash phone from settings/database
    $carwash_phone = getCarwashPhone(); // Implement this function
    if (!empty($carwash_phone)) {
        $smsHelper->sendBookingNotificationToCarwash($carwash_phone, $booking_data);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'message' => 'Rezervasyonunuz başarıyla oluşturuldu'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Booking error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getCarwashPhone()
{
    // Implement getting carwash phone from settings or database
    return '905555555555'; // Example
}
