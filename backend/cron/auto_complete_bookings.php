<?php
/**
 * Auto-Complete Past Bookings Cron Job
 * 
 * Purpose: Automatically mark bookings as "completed" when their scheduled time has passed
 * Schedule: Run every 5 minutes via cron or task scheduler
 * 
 * Cron syntax (Linux/Mac):
 * Run: crontab -e
 * Add: (star)(star)/5 (star) (star) (star) (star) /usr/bin/php /path/to/carwash_project/backend/cron/auto_complete_bookings.php
 * (Replace (star) with actual asterisks)
 * 
 * Windows Task Scheduler:
 * Program: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\carwash_project\backend\cron\auto_complete_bookings.php
 * Trigger: Every 5 minutes
 */

// Only allow CLI execution for security
if (php_sapi_name() !== 'cli' && !defined('ALLOW_WEB_CRON')) {
    die('This script can only be run from command line or with ALLOW_WEB_CRON defined.');
}

require_once __DIR__ . '/../includes/config.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $logPrefix = '[AUTO-COMPLETE] ' . date('Y-m-d H:i:s') . ' - ';
    
    echo $logPrefix . "Starting automatic booking completion check...\n";
    
    // Calculate the cutoff datetime (bookings that should be completed by now)
    // Add a buffer of 30 minutes after the booking time to ensure service has finished
    $cutoffTime = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    
    echo $logPrefix . "Cutoff time: {$cutoffTime}\n";
    
    // Find bookings that should be completed
    // Conditions:
    // 1. Status is pending, confirmed, or in_progress
    // 2. Booking date + time + 30 minutes is in the past
    // 3. Payment is completed (paid)
    $query = "
        SELECT 
            b.id,
            b.user_id,
            b.booking_date,
            b.booking_time,
            b.status,
            s.duration,
            CONCAT(b.booking_date, ' ', b.booking_time) as booking_datetime
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        WHERE b.status IN ('pending', 'confirmed', 'in_progress')
        AND b.payment_status = 'paid'
        AND CONCAT(b.booking_date, ' ', b.booking_time) < :cutoff_time
        ORDER BY b.booking_date ASC, b.booking_time ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['cutoff_time' => $cutoffTime]);
    $bookingsToComplete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalFound = count($bookingsToComplete);
    echo $logPrefix . "Found {$totalFound} bookings to auto-complete\n";
    
    if ($totalFound === 0) {
        echo $logPrefix . "No bookings need completion at this time.\n";
        exit(0);
    }
    
    // Update bookings to completed status
    $updateQuery = "
        UPDATE bookings 
        SET 
            status = 'completed',
            completed_at = NOW(),
            updated_at = NOW()
        WHERE id = :booking_id
    ";
    
    $updateStmt = $pdo->prepare($updateQuery);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($bookingsToComplete as $booking) {
        try {
            $updateStmt->execute(['booking_id' => $booking['id']]);
            
            if ($updateStmt->rowCount() > 0) {
                $successCount++;
                echo $logPrefix . "✓ Completed booking #{$booking['id']} (user: {$booking['user_id']}, datetime: {$booking['booking_datetime']})\n";
                
                // Optional: Log to a completion history table if it exists
                try {
                    $logStmt = $pdo->prepare("
                        INSERT INTO booking_completion_log (booking_id, user_id, completed_by, completed_at)
                        VALUES (:booking_id, :user_id, 'auto-cron', NOW())
                    ");
                    $logStmt->execute([
                        'booking_id' => $booking['id'],
                        'user_id' => $booking['user_id']
                    ]);
                } catch (PDOException $e) {
                    // Ignore if log table doesn't exist - not critical
                }
            } else {
                echo $logPrefix . "⚠ Booking #{$booking['id']} was already updated\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            echo $logPrefix . "✗ Failed to complete booking #{$booking['id']}: " . $e->getMessage() . "\n";
        }
    }
    
    // Summary
    echo $logPrefix . "========================================\n";
    echo $logPrefix . "COMPLETION SUMMARY\n";
    echo $logPrefix . "========================================\n";
    echo $logPrefix . "Total found: {$totalFound}\n";
    echo $logPrefix . "Successfully completed: {$successCount}\n";
    echo $logPrefix . "Errors: {$errorCount}\n";
    echo $logPrefix . "========================================\n";
    
    // Return appropriate exit code
    exit($errorCount > 0 ? 1 : 0);
    
} catch (PDOException $e) {
    echo $logPrefix . "DATABASE ERROR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo $logPrefix . "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
