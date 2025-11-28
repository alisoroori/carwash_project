<?php
/**
 * Generate Test Bookings Dataset for Customer Dashboard
 * Creates a complete set of realistic bookings for testing
 */

require_once __DIR__ . '/backend/includes/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "========================================\n";
    echo "CARWASH TEST BOOKINGS GENERATOR\n";
    echo "========================================\n\n";
    
    // Step 1: Detect valid existing data
    echo "Step 1: Detecting existing valid data...\n";
    
    // Get a valid customer user
    $user = $pdo->query("SELECT id, full_name FROM users WHERE role = 'customer' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("❌ ERROR: No customer users found. Please create a customer user first.\n");
    }
    echo "✓ Found user: {$user['full_name']} (ID: {$user['id']})\n";
    
    // Get valid services with their carwash_id and price
    $services = $pdo->query("
        SELECT s.id, s.name, s.carwash_id, s.price, c.name as carwash_name
        FROM services s
        JOIN carwashes c ON s.carwash_id = c.id
        WHERE s.status = 'active'
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($services) < 3) {
        die("❌ ERROR: Need at least 3 active services in database.\n");
    }
    echo "✓ Found " . count($services) . " active services\n";
    
    // Get valid carwashes
    $carwashes = $pdo->query("SELECT id, name FROM carwashes LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    if (count($carwashes) < 1) {
        die("❌ ERROR: No carwashes found in database.\n");
    }
    echo "✓ Found " . count($carwashes) . " carwashes\n\n";
    
    // Step 2: Check for required tables and columns
    echo "Step 2: Checking database schema...\n";
    
    $bookingsColumns = $pdo->query("DESCRIBE bookings")->fetchAll(PDO::FETCH_ASSOC);
    $requiredColumns = ['id', 'user_id', 'carwash_id', 'service_id', 'booking_date', 'booking_time', 
                        'vehicle_type', 'vehicle_plate', 'vehicle_model', 'status', 'total_price', 
                        'payment_status', 'payment_method', 'completed_at', 'cancellation_reason'];
    
    $existingColumns = array_column($bookingsColumns, 'Field');
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (!empty($missingColumns)) {
        echo "⚠ Warning: Missing columns: " . implode(', ', $missingColumns) . "\n";
        echo "Proceeding with available columns only.\n";
    } else {
        echo "✓ All required columns exist\n";
    }
    echo "\n";
    
    // Step 3: Prepare vehicle data for variety
    $vehicles = [
        ['type' => 'sedan', 'plate' => '34ABC123', 'model' => 'Honda Civic', 'color' => 'Blue'],
        ['type' => 'suv', 'plate' => '06XYZ456', 'model' => 'Toyota RAV4', 'color' => 'White'],
        ['type' => 'sedan', 'plate' => '35DEF789', 'model' => 'BMW 3 Series', 'color' => 'Black'],
        ['type' => 'truck', 'plate' => '16GHI321', 'model' => 'Ford F-150', 'color' => 'Red'],
        ['type' => 'van', 'plate' => '41JKL654', 'model' => 'Mercedes Sprinter', 'color' => 'Silver'],
        ['type' => 'suv', 'plate' => '34MNO987', 'model' => 'Jeep Wrangler', 'color' => 'Green'],
        ['type' => 'sedan', 'plate' => '34PQR135', 'model' => 'Tesla Model 3', 'color' => 'White'],
        ['type' => 'sedan', 'plate' => '06STU246', 'model' => 'Audi A4', 'color' => 'Gray'],
        ['type' => 'suv', 'plate' => '35VWX357', 'model' => 'Volvo XC90', 'color' => 'Blue'],
    ];
    
    $paymentMethods = ['cash', 'card', 'online'];
    $notes = [
        'Please use eco-friendly products',
        'Interior cleaning needed',
        'Extra attention to wheels',
        'First time customer',
        'Regular customer - monthly service',
        null, // Some bookings have no notes
        'VIP service requested',
        'Express service needed',
    ];
    
    $cancellationReasons = [
        'Customer changed plans',
        'Weather conditions',
        'Vehicle unavailable',
        'Scheduling conflict',
        'Customer request',
    ];
    
    echo "Step 3: Creating test bookings...\n\n";
    
    $createdBookings = [];
    $bookingIndex = 0;
    
    // ==========================================
    // CREATE 3 ACTIVE BOOKINGS
    // ==========================================
    echo "--- Creating 3 ACTIVE Bookings ---\n";
    
    $activeStatuses = ['pending', 'confirmed', 'in_progress'];
    
    for ($i = 0; $i < 3; $i++) {
        $service = $services[$i % count($services)];
        $vehicle = $vehicles[$bookingIndex % count($vehicles)];
        $status = $activeStatuses[$i];
        
        // Future dates for active bookings
        $daysAhead = ($i + 1) * 2; // 2, 4, 6 days ahead
        $bookingDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        $bookingTime = sprintf('%02d:00:00', 9 + ($i * 3)); // 09:00, 12:00, 15:00
        
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                user_id, 
                carwash_id, 
                service_id, 
                booking_date, 
                booking_time,
                vehicle_type,
                vehicle_plate,
                vehicle_model,
                vehicle_color,
                status,
                total_price,
                payment_status,
                payment_method,
                notes,
                created_at
            ) VALUES (
                :user_id,
                :carwash_id,
                :service_id,
                :booking_date,
                :booking_time,
                :vehicle_type,
                :vehicle_plate,
                :vehicle_model,
                :vehicle_color,
                :status,
                :total_price,
                :payment_status,
                :payment_method,
                :notes,
                NOW()
            )
        ");
        
        $paymentStatus = ($status === 'pending') ? 'pending' : 'paid';
        
        $stmt->execute([
            'user_id' => $user['id'],
            'carwash_id' => $service['carwash_id'],
            'service_id' => $service['id'],
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'vehicle_type' => $vehicle['type'],
            'vehicle_plate' => $vehicle['plate'],
            'vehicle_model' => $vehicle['model'],
            'vehicle_color' => $vehicle['color'],
            'status' => $status,
            'total_price' => $service['price'],
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethods[$i % count($paymentMethods)],
            'notes' => $notes[$i % count($notes)],
        ]);
        
        $bookingId = $pdo->lastInsertId();
        $createdBookings[] = [
            'id' => $bookingId,
            'type' => 'ACTIVE',
            'status' => $status,
            'date' => $bookingDate,
            'time' => $bookingTime,
            'service' => $service['name'],
            'carwash' => $service['carwash_name'],
            'vehicle' => $vehicle['model'] . ' (' . $vehicle['plate'] . ')',
            'price' => $service['price'],
        ];
        
        echo "✓ Created {$status} booking #{$bookingId}: {$service['name']} on {$bookingDate} {$bookingTime}\n";
        echo "  Vehicle: {$vehicle['model']} ({$vehicle['plate']})\n";
        echo "  Carwash: {$service['carwash_name']}\n";
        echo "  Price: {$service['price']} TL\n\n";
        
        $bookingIndex++;
    }
    
    // ==========================================
    // CREATE 2 CANCELLED BOOKINGS
    // ==========================================
    echo "--- Creating 2 CANCELLED Bookings ---\n";
    
    for ($i = 0; $i < 2; $i++) {
        $service = $services[($i + 3) % count($services)];
        $vehicle = $vehicles[$bookingIndex % count($vehicles)];
        
        // Past dates for cancelled bookings
        $daysAgo = ($i + 1) * 3; // 3, 6 days ago
        $bookingDate = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $bookingTime = sprintf('%02d:00:00', 10 + ($i * 4)); // 10:00, 14:00
        
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                user_id, 
                carwash_id, 
                service_id, 
                booking_date, 
                booking_time,
                vehicle_type,
                vehicle_plate,
                vehicle_model,
                vehicle_color,
                status,
                total_price,
                payment_status,
                payment_method,
                notes,
                cancellation_reason,
                created_at
            ) VALUES (
                :user_id,
                :carwash_id,
                :service_id,
                :booking_date,
                :booking_time,
                :vehicle_type,
                :vehicle_plate,
                :vehicle_model,
                :vehicle_color,
                'cancelled',
                :total_price,
                'refunded',
                :payment_method,
                :notes,
                :cancellation_reason,
                NOW()
            )
        ");
        
        $cancellationReason = $cancellationReasons[$i % count($cancellationReasons)];
        
        $stmt->execute([
            'user_id' => $user['id'],
            'carwash_id' => $service['carwash_id'],
            'service_id' => $service['id'],
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'vehicle_type' => $vehicle['type'],
            'vehicle_plate' => $vehicle['plate'],
            'vehicle_model' => $vehicle['model'],
            'vehicle_color' => $vehicle['color'],
            'total_price' => $service['price'],
            'payment_method' => $paymentMethods[$i % count($paymentMethods)],
            'notes' => $notes[($i + 5) % count($notes)],
            'cancellation_reason' => $cancellationReason,
        ]);
        
        $bookingId = $pdo->lastInsertId();
        $createdBookings[] = [
            'id' => $bookingId,
            'type' => 'CANCELLED',
            'status' => 'cancelled',
            'date' => $bookingDate,
            'time' => $bookingTime,
            'service' => $service['name'],
            'carwash' => $service['carwash_name'],
            'vehicle' => $vehicle['model'] . ' (' . $vehicle['plate'] . ')',
            'price' => $service['price'],
            'reason' => $cancellationReason,
        ];
        
        echo "✓ Created cancelled booking #{$bookingId}: {$service['name']} on {$bookingDate} {$bookingTime}\n";
        echo "  Reason: {$cancellationReason}\n";
        echo "  Vehicle: {$vehicle['model']} ({$vehicle['plate']})\n";
        echo "  Carwash: {$service['carwash_name']}\n\n";
        
        $bookingIndex++;
    }
    
    // ==========================================
    // CREATE 4 COMPLETED BOOKINGS
    // ==========================================
    echo "--- Creating 4 COMPLETED Bookings ---\n";
    
    for ($i = 0; $i < 4; $i++) {
        $service = $services[$i % count($services)];
        $vehicle = $vehicles[$bookingIndex % count($vehicles)];
        
        // Past dates for completed bookings (1-4 weeks ago)
        $weeksAgo = $i + 1;
        $daysAgo = $weeksAgo * 7;
        $bookingDate = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $bookingTime = sprintf('%02d:00:00', 9 + ($i * 2)); // 09:00, 11:00, 13:00, 15:00
        
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                user_id, 
                carwash_id, 
                service_id, 
                booking_date, 
                booking_time,
                vehicle_type,
                vehicle_plate,
                vehicle_model,
                vehicle_color,
                status,
                total_price,
                payment_status,
                payment_method,
                notes,
                completed_at,
                created_at
            ) VALUES (
                :user_id,
                :carwash_id,
                :service_id,
                :booking_date,
                :booking_time,
                :vehicle_type,
                :vehicle_plate,
                :vehicle_model,
                :vehicle_color,
                'completed',
                :total_price,
                'paid',
                :payment_method,
                :notes,
                :completed_at,
                NOW()
            )
        ");
        
        // Set completed_at to same day as booking_date but later time
        $completedAt = $bookingDate . ' ' . sprintf('%02d:30:00', 9 + ($i * 2));
        
        $stmt->execute([
            'user_id' => $user['id'],
            'carwash_id' => $service['carwash_id'],
            'service_id' => $service['id'],
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'vehicle_type' => $vehicle['type'],
            'vehicle_plate' => $vehicle['plate'],
            'vehicle_model' => $vehicle['model'],
            'vehicle_color' => $vehicle['color'],
            'total_price' => $service['price'],
            'payment_method' => $paymentMethods[$i % count($paymentMethods)],
            'notes' => $notes[($i + 3) % count($notes)],
            'completed_at' => $completedAt,
        ]);
        
        $bookingId = $pdo->lastInsertId();
        $createdBookings[] = [
            'id' => $bookingId,
            'type' => 'COMPLETED',
            'status' => 'completed',
            'date' => $bookingDate,
            'time' => $bookingTime,
            'service' => $service['name'],
            'carwash' => $service['carwash_name'],
            'vehicle' => $vehicle['model'] . ' (' . $vehicle['plate'] . ')',
            'price' => $service['price'],
            'completed_at' => $completedAt,
        ];
        
        echo "✓ Created completed booking #{$bookingId}: {$service['name']} on {$bookingDate} {$bookingTime}\n";
        echo "  Completed at: {$completedAt}\n";
        echo "  Vehicle: {$vehicle['model']} ({$vehicle['plate']})\n";
        echo "  Carwash: {$service['carwash_name']}\n";
        echo "  Price: {$service['price']} TL\n\n";
        
        $bookingIndex++;
    }
    
    // ==========================================
    // FINAL SUMMARY
    // ==========================================
    echo "\n========================================\n";
    echo "GENERATION COMPLETE!\n";
    echo "========================================\n\n";
    
    echo "📊 Summary:\n";
    echo "   User: {$user['full_name']} (ID: {$user['id']})\n";
    echo "   Total bookings created: " . count($createdBookings) . "\n\n";
    
    // Group by type
    $byType = [];
    foreach ($createdBookings as $booking) {
        $type = $booking['type'];
        if (!isset($byType[$type])) {
            $byType[$type] = [];
        }
        $byType[$type][] = $booking;
    }
    
    foreach ($byType as $type => $bookings) {
        echo "--- {$type} Bookings (" . count($bookings) . ") ---\n";
        foreach ($bookings as $b) {
            echo "  #{$b['id']}: {$b['status']} - {$b['service']} ({$b['date']} {$b['time']})\n";
            echo "           {$b['vehicle']} at {$b['carwash']}\n";
            echo "           Price: {$b['price']} TL\n";
            if (isset($b['reason'])) {
                echo "           Reason: {$b['reason']}\n";
            }
            if (isset($b['completed_at'])) {
                echo "           Completed: {$b['completed_at']}\n";
            }
        }
        echo "\n";
    }
    
    // Verify data in database
    echo "========================================\n";
    echo "DATABASE VERIFICATION\n";
    echo "========================================\n\n";
    
    $counts = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM bookings 
        WHERE user_id = {$user['id']}
        GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Bookings by status:\n";
    foreach ($counts as $row) {
        echo "  {$row['status']}: {$row['count']}\n";
    }
    
    echo "\n✅ All test bookings successfully created!\n";
    echo "\n📝 Next steps:\n";
    echo "   1. Login as: {$user['full_name']}\n";
    echo "   2. Navigate to Customer Dashboard\n";
    echo "   3. Check 'Rezervasyonlarım' for active bookings\n";
    echo "   4. Check 'Geçmiş' for completed bookings\n";
    echo "   5. Verify all data displays correctly\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ DATABASE ERROR: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}
