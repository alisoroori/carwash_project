<?php


require_once '../includes/api_bootstrap.php';


if (session_status() === PHP_SESSION_NONE) session_start();
if (file_exists($csrf_helper)) {
    require_once $csrf_helper;
    if (function_exists('require_valid_csrf')) {
        require_valid_csrf();
    }
} else {
    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (empty($_SESSION['csrf_token']) || empty($csrfToken) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrfToken)) {
        error_log('CSRF: missing or invalid token in create_booking.php');
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

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
            user_id,
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
    // Get carwash/profile phone from settings/database
    $profile_phone = getProfilePhone(); // Implement this function
    if (!empty($profile_phone)) {
        $smsHelper->sendBookingNotificationToCarwash($profile_phone, $booking_data);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'message' => 'Rezervasyonunuz başarıyla oluşturuldu'
    ]);
} catch (Throwable $e) {
    // Attempt rollback if possible
    if (isset($conn) && method_exists($conn, 'rollback')) {
        try { $conn->rollback(); } catch (Throwable $_e) { /* ignore */ }
    }
    // Use structured error response helper if available
    if (function_exists('send_structured_error_response')) {
        send_structured_error_response($e, 500);
    }

    // Fallback
    error_log("Booking error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_type' => get_class($e),
        'message' => $e->getMessage()
    ]);
    exit;
}

function getProfilePhone()
{
    // Implement getting carwash/profile phone from settings or database
    return '905555555555'; // Example
}
