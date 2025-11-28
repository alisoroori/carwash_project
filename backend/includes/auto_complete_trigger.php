<?php
/**
 * Internal Event-Based Auto-Completion Trigger
 * 
 * This is automatically included in critical pages to ensure bookings
 * get completed even without a proper cron setup.
 * 
 * It uses file-based locking to prevent concurrent executions and
 * only runs once every 5 minutes.
 */

// Only run in specific contexts to avoid performance overhead
if (!defined('ENABLE_AUTO_COMPLETION_TRIGGER')) {
    return;
}

$lockFile = __DIR__ . '/../../logs/auto_complete.lock';
$lockDir = dirname($lockFile);

// Create logs directory if it doesn't exist
if (!file_exists($lockDir)) {
    mkdir($lockDir, 0755, true);
}

// Check if we should run (throttle to once every 5 minutes)
$shouldRun = false;

if (!file_exists($lockFile)) {
    $shouldRun = true;
} else {
    $lastRun = filemtime($lockFile);
    $timeSinceLastRun = time() - $lastRun;
    
    // Run if more than 5 minutes have passed
    if ($timeSinceLastRun > 300) { // 300 seconds = 5 minutes
        $shouldRun = true;
    }
}

if (!$shouldRun) {
    return;
}

// Try to acquire lock
$lockHandle = fopen($lockFile, 'c+');
if (!$lockHandle) {
    return;
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    // Another process is running
    fclose($lockHandle);
    return;
}

// Update lock file timestamp
fwrite($lockHandle, date('Y-m-d H:i:s'));
fflush($lockHandle);

// Run the completion logic in the background (non-blocking)
try {
    require_once __DIR__ . '/../includes/config.php';
    
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Quick completion check (simplified for event-based execution)
    $cutoffTime = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    
    $updateQuery = "
        UPDATE bookings 
        SET 
            status = 'completed',
            completed_at = NOW(),
            updated_at = NOW()
        WHERE status IN ('pending', 'confirmed', 'in_progress')
        AND payment_status = 'paid'
        AND CONCAT(booking_date, ' ', booking_time) < :cutoff_time
    ";
    
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute(['cutoff_time' => $cutoffTime]);
    
    $completedCount = $stmt->rowCount();
    
    // Log result
    if ($completedCount > 0) {
        $logMsg = date('Y-m-d H:i:s') . " - Event trigger completed {$completedCount} bookings\n";
        file_put_contents(__DIR__ . '/../../logs/auto_complete.log', $logMsg, FILE_APPEND);
    }
    
} catch (Exception $e) {
    // Silently fail - don't interrupt the main application
    error_log("Auto-completion trigger error: " . $e->getMessage());
}

// Release lock
flock($lockHandle, LOCK_UN);
fclose($lockHandle);
