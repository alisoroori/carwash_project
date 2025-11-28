<?php
include 'backend/includes/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    echo "=== CREATING TEST COMPLETED BOOKING ===\n\n";

    // Step 1: Identify existing data
    echo "1. Identifying existing data...\n";

    // Get customer user
    $stmt = $pdo->query('SELECT id, full_name, email FROM users WHERE role = \'customer\' LIMIT 1');
    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception('No customer user found');
    }
    $userId = $user['id'];
    echo "   ✓ Using customer: {$user['full_name']} (ID: $userId)\n";

    // Get carwash that has services
    $stmt = $pdo->query('SELECT cp.id, cp.business_name FROM carwash_profiles cp 
                        INNER JOIN services s ON cp.id = s.carwash_id 
                        LIMIT 1');
    $carwash = $stmt->fetch();
    if (!$carwash) {
        throw new Exception('No carwash with services found');
    }
    $carwashId = $carwash['id'];
    echo "   ✓ Using carwash: {$carwash['business_name']} (ID: $carwashId)\n";

    // Get service
    $stmt = $pdo->query('SELECT id, name, price FROM services WHERE carwash_id = '.$carwashId.' LIMIT 1');
    $service = $stmt->fetch();
    if (!$service) {
        throw new Exception('No service found for carwash');
    }
    $serviceId = $service['id'];
    $servicePrice = $service['price'];
    echo "   ✓ Using service: {$service['name']} (ID: $serviceId, Price: $servicePrice)\n";

    // Step 2: Ensure vehicle exists for user
    echo "\n2. Checking vehicle for user...\n";
    $stmt = $pdo->query('SELECT id, brand, model, license_plate FROM vehicles WHERE user_id = '.$userId.' LIMIT 1');
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        // Create a vehicle
        $vehicleStmt = $pdo->prepare("
            INSERT INTO vehicles (
                user_id, brand, model, year, color, license_plate, vehicle_type, created_at, updated_at
            ) VALUES (
                :user_id, :brand, :model, :year, :color, :license_plate, :vehicle_type, NOW(), NOW()
            )
        ");
        $vehicleStmt->execute([
            'user_id' => $userId,
            'brand' => 'Volkswagen',
            'model' => 'Passat',
            'year' => 2020,
            'color' => 'Siyah',
            'license_plate' => '34 TEST 123',
            'vehicle_type' => 'sedan'
        ]);
        $vehicleId = $pdo->lastInsertId();
        $vehicleBrand = 'Volkswagen';
        $vehicleModel = 'Passat';
        $vehiclePlate = '34 TEST 123';
        echo "   ✓ Created vehicle: $vehicleBrand $vehicleModel ($vehiclePlate)\n";
    } else {
        $vehicleId = $vehicle['id'];
        $vehicleBrand = $vehicle['brand'];
        $vehicleModel = $vehicle['model'];
        $vehiclePlate = $vehicle['license_plate'];
        echo "   ✓ Using existing vehicle: $vehicleBrand $vehicleModel ($vehiclePlate)\n";
    }

    // Step 3: Ensure time slot exists for carwash
    echo "\n3. Checking time slot for carwash...\n";
    $stmt = $pdo->query('SELECT id, start_time, end_time FROM time_slots WHERE carwash_id = '.$carwashId.' LIMIT 1');
    $timeSlot = $stmt->fetch();

    if (!$timeSlot) {
        // Create a time slot
        $timeSlotStmt = $pdo->prepare("
            INSERT INTO time_slots (carwash_id, day_of_week, start_time, end_time, capacity, is_active, created_at)
            VALUES (:carwash_id, :day_of_week, :start_time, :end_time, :capacity, :is_active, NOW())
        ");
        $timeSlotStmt->execute([
            'carwash_id' => $carwashId,
            'day_of_week' => 1, // Monday
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'capacity' => 1,
            'is_active' => 1
        ]);
        $timeSlotId = $pdo->lastInsertId();
        echo "   ✓ Created time slot: 14:00-15:00\n";
    } else {
        $timeSlotId = $timeSlot['id'];
        echo "   ✓ Using existing time slot: {$timeSlot['start_time']}-{$timeSlot['end_time']}\n";
    }

    // Step 4: Create the completed booking
    echo "\n4. Creating completed booking...\n";

    $bookingNumber = 'BK' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $pastDate = date('Y-m-d', strtotime('-1 week'));
    $bookingTime = '14:00:00';
    $endTime = '15:00:00';

    $bookingStmt = $pdo->prepare("
        INSERT INTO bookings (
            booking_number, user_id, vehicle_id, carwash_id, time_slot_id,
            booking_date, booking_time, end_time, status, total_amount,
            discount_amount, special_requests, created_at, updated_at
        ) VALUES (
            :booking_number, :user_id, :vehicle_id, :carwash_id, :time_slot_id,
            :booking_date, :booking_time, :end_time, :status, :total_amount,
            :discount_amount, :special_requests, NOW(), NOW()
        )
    ");

    $bookingStmt->execute([
        'booking_number' => $bookingNumber,
        'user_id' => $userId,
        'vehicle_id' => $vehicleId,
        'carwash_id' => $carwashId,
        'time_slot_id' => $timeSlotId,
        'booking_date' => $pastDate,
        'booking_time' => $bookingTime,
        'end_time' => $endTime,
        'status' => 'completed',
        'total_amount' => $servicePrice,
        'discount_amount' => 0.00,
        'special_requests' => 'Test completed booking'
    ]);

    $bookingId = $pdo->lastInsertId();
    echo "   ✓ Created booking: $bookingNumber (ID: $bookingId)\n";

    // Step 5: Create booking_services entry
    echo "\n5. Creating booking_services entry...\n";
    $bookingServiceStmt = $pdo->prepare("
        INSERT INTO booking_services (booking_id, service_id, price, created_at)
        VALUES (:booking_id, :service_id, :price, NOW())
    ");
    $bookingServiceStmt->execute([
        'booking_id' => $bookingId,
        'service_id' => $serviceId,
        'price' => $servicePrice
    ]);
    echo "   ✓ Created booking_services entry\n";

    // Step 6: Update booking with completed_at (if column exists)
    // Check if completed_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'completed_at'");
    $hasCompletedAt = $stmt->fetch();

    if (!$hasCompletedAt) {
        // Add completed_at column
        $pdo->exec("ALTER TABLE bookings ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
        echo "   ✓ Added completed_at column to bookings table\n";
    }

    // Update the booking with completed_at
    $pdo->exec("UPDATE bookings SET completed_at = NOW() WHERE id = $bookingId");
    echo "   ✓ Set completed_at timestamp\n";

    // Check for payment_status and payment_method columns
    $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'");
    $hasPaymentStatus = $stmt->fetch();

    if (!$hasPaymentStatus) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN payment_status ENUM('pending','paid','refunded') DEFAULT 'pending' AFTER total_amount");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER payment_status");
        echo "   ✓ Added payment_status and payment_method columns\n";
    }

    // Update payment fields
    $pdo->exec("UPDATE bookings SET payment_status = 'paid', payment_method = 'credit_card' WHERE id = $bookingId");
    echo "   ✓ Set payment_status to 'paid' and payment_method to 'credit_card'\n";

    $pdo->commit();

    echo "\n=== TEST COMPLETED BOOKING CREATED SUCCESSFULLY ===\n\n";

    echo "SUMMARY:\n";
    echo "- User: {$user['full_name']} (ID: $userId, Email: {$user['email']})\n";
    echo "- Carwash: {$carwash['business_name']} (ID: $carwashId)\n";
    echo "- Service: {$service['name']} (ID: $serviceId, Price: $servicePrice)\n";
    echo "- Vehicle: $vehicleBrand $vehicleModel ($vehiclePlate)\n";
    echo "- Booking ID: $bookingId\n";
    echo "- Booking Number: $bookingNumber\n";
    echo "- Status: completed\n";
    echo "- Date: $pastDate at $bookingTime\n";
    echo "- Total Price: $servicePrice\n";

    // Verify the booking appears in history API
    echo "\n=== VERIFYING BOOKING APPEARS IN HISTORY ===\n";

    // Simulate the history API query
    $historyStmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM bookings
        WHERE user_id = :user_id AND status = 'completed'
    ");
    $historyStmt->execute(['user_id' => $userId]);
    $historyCount = $historyStmt->fetch()['count'];

    echo "Bookings in history for user $userId: $historyCount\n";

    if ($historyCount > 0) {
        echo "✓ Booking should appear in Past Bookings / History section\n";
    } else {
        echo "✗ Booking may not appear - check API query\n";
    }

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo 'Stack trace: ' . $e->getTraceAsString() . PHP_EOL;
}
?>