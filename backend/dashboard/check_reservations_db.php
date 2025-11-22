<?php
/**
 * Database Structure Diagnostic Tool
 * Check tables and columns needed for reservations query
 */

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Database;

header('Content-Type: text/plain; charset=utf-8');

echo "=== RESERVATIONS DATABASE DIAGNOSTIC ===\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    // Check required tables
    $requiredTables = ['bookings', 'users', 'services', 'user_vehicles', 'carwashes'];
    
    echo "1. CHECKING TABLES:\n";
    echo str_repeat('-', 50) . "\n";
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt && $stmt->rowCount() > 0;
        echo sprintf("%-20s %s\n", $table, $exists ? '✓ EXISTS' : '✗ MISSING');
    }
    
    echo "\n2. CHECKING BOOKINGS TABLE STRUCTURE:\n";
    echo str_repeat('-', 50) . "\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE bookings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $requiredColumns = ['id', 'carwash_id', 'user_id', 'vehicle_id', 'service_id', 
                           'service_type', 'booking_date', 'booking_time', 'status', 'total_price'];
        
        $existingColumns = array_column($columns, 'Field');
        
        foreach ($requiredColumns as $col) {
            $exists = in_array($col, $existingColumns);
            echo sprintf("%-20s %s\n", $col, $exists ? '✓' : '✗');
        }
        
        echo "\nAll columns in bookings table:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n3. CHECKING USER_VEHICLES TABLE:\n";
    echo str_repeat('-', 50) . "\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE user_vehicles");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columns in user_vehicles table:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. CHECKING SERVICES TABLE:\n";
    echo str_repeat('-', 50) . "\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE services");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columns in services table:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n5. SAMPLE QUERY TEST:\n";
    echo str_repeat('-', 50) . "\n";
    
    // Try simplified query
    try {
        $testQuery = "SELECT 
            b.id AS booking_id,
            b.booking_date,
            b.booking_time,
            b.status
        FROM bookings b
        LIMIT 1";
        
        $stmt = $pdo->query($testQuery);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✓ Basic bookings query works\n";
        if ($result) {
            echo "Sample booking ID: " . $result['booking_id'] . "\n";
        } else {
            echo "No bookings found in database\n";
        }
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. COUNTING RECORDS:\n";
    echo str_repeat('-', 50) . "\n";
    
    $tables = ['bookings', 'users', 'services', 'user_vehicles', 'carwashes'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo sprintf("%-20s %d records\n", $table, $result['count']);
        } catch (Exception $e) {
            echo sprintf("%-20s ERROR: %s\n", $table, $e->getMessage());
        }
    }
    
    echo "\n=== DIAGNOSTIC COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
