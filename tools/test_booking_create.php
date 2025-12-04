<?php
// Simple CLI test harness for bookings/create.php
chdir(__DIR__ . '/..');
// Ensure session works in CLI by setting an explicit save path
ini_set('session.save_path', sys_get_temp_dir());
session_start();
// Provide fake authenticated user
$_SESSION['user_id'] = 1;
$_SESSION['carwash_id'] = 1;
// Provide a CSRF token for the API fallback check
$_SESSION['csrf_token'] = 'testtoken123';
// Simulate POST
$_SERVER['REQUEST_METHOD'] = 'POST';
// Example payload - adjust IDs to match your local DB
$_POST = [
    'service_id' => 1,
    'vehicle_id' => 1,
    'date' => date('Y-m-d', strtotime('+1 day')),
    'time' => '10:00',
    'notes' => 'Test booking from CLI',
    'csrf_token' => 'testtoken123'
];
// Allow output capture
ob_start();
try {
    // Include the API endpoint within a try/catch so we can capture exceptions
    require __DIR__ . '/../backend/api/bookings/create.php';
} catch (Throwable $e) {
    $trace = $e->getTraceAsString();
    $msg = $e->getMessage();
    $payload = json_encode(['success' => false, 'exception' => $msg, 'trace' => $trace], JSON_UNESCAPED_UNICODE);
    file_put_contents(__DIR__ . '/booking_test_output.json', $payload);
    echo $payload;
    exit(1);
}
$out = ob_get_clean();
file_put_contents(__DIR__ . '/booking_test_output.json', $out);
echo "Output written to tools/booking_test_output.json\n";
echo $out;
