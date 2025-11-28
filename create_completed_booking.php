<?php
require_once __DIR__ . '/backend/includes/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating a completed booking for user_id=14..." . PHP_EOL . PHP_EOL;
    
    // Get a real service and carwash
    $service = $pdo->query("SELECT id, carwash_id, price FROM services LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $carwash = $pdo->query("SELECT id FROM carwashes WHERE id = " . $service['carwash_id'])->fetch(PDO::FETCH_ASSOC);
    
    if (!$service || !$carwash) {
        die("No services or carwashes found in database!" . PHP_EOL);
    }
    
    echo "Using service_id=" . $service['id'] . ", carwash_id=" . $carwash['id'] . PHP_EOL;
    
    // Create a completed booking
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
            completed_at,
            created_at
        ) VALUES (
            14,
            :carwash_id,
            :service_id,
            '2025-11-20',
            '14:00:00',
            'sedan',
            '34ABC123',
            'Honda Civic',
            'Blue',
            'completed',
            :price,
            'paid',
            'card',
            NOW(),
            NOW()
        )
    ");
    
    $stmt->execute([
        'carwash_id' => $carwash['id'],
        'service_id' => $service['id'],
        'price' => $service['price']
    ]);
    
    $bookingId = $pdo->lastInsertId();
    
    echo "✓ Created completed booking with ID: $bookingId" . PHP_EOL . PHP_EOL;
    
    // Verify it's retrievable
    echo "Testing get_reservations query..." . PHP_EOL;
    $stmt = $pdo->prepare("
        SELECT 
            b.id as booking_id,
            b.booking_date,
            b.booking_time,
            b.total_price,
            b.payment_status,
            s.name as service_name,
            c.name as carwash_name
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN carwashes c ON b.carwash_id = c.id
        WHERE b.user_id = 14
        AND b.status = 'completed'
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✓ Found " . count($results) . " completed bookings" . PHP_EOL;
    if (count($results) > 0) {
        echo "Latest booking:" . PHP_EOL;
        print_r($results[0]);
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
