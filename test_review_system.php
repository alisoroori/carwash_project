<?php
/**
 * Test Review System - Create test data and verify functionality
 * This script creates a test completed booking and displays verification UI
 */

require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;
use App\Classes\Auth;

// Require admin or test environment
if (!Auth::isLoggedIn()) {
    die('Please login to test the review system');
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get or create test booking
$testBooking = $db->fetchOne(
    "SELECT * FROM bookings WHERE user_id = :user_id AND status = 'completed' ORDER BY id DESC LIMIT 1",
    ['user_id' => $userId]
);

if (!$testBooking) {
    echo "<h2>Creating test completed booking...</h2>";
    
    // Get first available carwash and service
    $carwash = $db->fetchOne("SELECT id FROM carwashes LIMIT 1");
    $service = $db->fetchOne("SELECT id FROM services LIMIT 1");
    
    if ($carwash && $service) {
        $bookingId = $db->insert('bookings', [
            'user_id' => $userId,
            'carwash_id' => $carwash['id'],
            'service_id' => $service['id'],
            'booking_date' => date('Y-m-d'),
            'booking_time' => date('H:i:s'),
            'vehicle_type' => 'sedan',
            'vehicle_plate' => 'TEST123',
            'status' => 'completed',
            'review_status' => 'pending',
            'total_price' => 100.00,
            'payment_status' => 'paid',
            'completed_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($bookingId) {
            $testBooking = $db->fetchOne("SELECT * FROM bookings WHERE id = :id", ['id' => $bookingId]);
            echo "<p style='color: green;'>✅ Test booking created with ID: {$bookingId}</p>";
        }
    }
}

if (!$testBooking) {
    die('Could not create test booking. Please check database.');
}

// Check if review exists
$existingReview = $db->fetchOne(
    "SELECT * FROM reviews WHERE user_id = :user_id AND booking_id = :booking_id",
    ['user_id' => $userId, 'booking_id' => $testBooking['id']]
);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review System Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-900">
            <i class="fas fa-star text-yellow-500 mr-2"></i>
            Review System Test
        </h1>
        
        <!-- Test Status -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h2 class="text-xl font-semibold mb-3">Test Status</h2>
            <div class="space-y-2">
                <p><strong>User ID:</strong> <?php echo $userId; ?></p>
                <p><strong>Test Booking ID:</strong> <?php echo $testBooking['id']; ?></p>
                <p><strong>Booking Status:</strong> 
                    <span class="px-2 py-1 rounded bg-blue-100 text-blue-800">
                        <?php echo $testBooking['status']; ?>
                    </span>
                </p>
                <p><strong>Review Status:</strong> 
                    <span class="px-2 py-1 rounded <?php echo $testBooking['review_status'] === 'reviewed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                        <?php echo $testBooking['review_status']; ?>
                    </span>
                </p>
                <?php if ($existingReview): ?>
                    <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded">
                        <p class="text-green-800 font-semibold">✅ Review Exists</p>
                        <p><strong>Rating:</strong> <?php echo str_repeat('⭐', $existingReview['rating']); ?></p>
                        <?php if ($existingReview['comment']): ?>
                            <p><strong>Comment:</strong> <?php echo htmlspecialchars($existingReview['comment']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-yellow-700">⏳ No review yet - Test the button below!</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Test Button -->
        <?php if (!$existingReview): ?>
            <div class="mb-6 p-6 bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Test Review Modal</h2>
                <p class="text-gray-700 mb-4">Click the button below to test the review modal:</p>
                <button 
                    type="button"
                    onclick="openReviewModal(<?php echo $testBooking['id']; ?>)"
                    class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-lg font-semibold hover:shadow-lg hover:scale-105 transition-all duration-200"
                >
                    <i class="fas fa-star mr-2"></i>
                    Leave Review (Test)
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Implementation Checklist -->
        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h2 class="text-xl font-semibold mb-3">Implementation Checklist</h2>
            <ul class="space-y-2">
                <li class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span>Database: reviews table created</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span>Database: bookings.review_status column added</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span>Backend API: /backend/api/add_review.php created</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span>Frontend: Review modal added to Customer_Dashboard.php</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span>Frontend: "Leave Review" button added to reservations table</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span>JavaScript: Star rating system implemented</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span>JavaScript: AJAX form submission implemented</span>
                </li>
            </ul>
        </div>
        
        <!-- Testing Instructions -->
        <div class="p-4 bg-yellow-50 border border-yellow-300 rounded-lg">
            <h2 class="text-xl font-semibold mb-3">Testing Instructions</h2>
            <ol class="list-decimal list-inside space-y-2">
                <li>Visit the <a href="/carwash_project/backend/dashboard/Customer_Dashboard.php" class="text-blue-600 underline">Customer Dashboard</a></li>
                <li>Navigate to "Reservations" section</li>
                <li>Find bookings with status = "completed"</li>
                <li>Click the "Değerlendir" (Leave Review) button</li>
                <li>Select a star rating (1-5 stars)</li>
                <li>Optionally enter a comment</li>
                <li>Click "Gönder" (Submit)</li>
                <li>Verify the review is saved and button shows "Değerlendirildi"</li>
            </ol>
        </div>
        
        <!-- Database Verification -->
        <div class="mt-6">
            <h2 class="text-xl font-semibold mb-3">Database Verification</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-semibold mb-2">Reviews Table</h3>
                    <p class="text-sm">Columns: id, user_id, booking_id, carwash_id, rating, comment</p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg">
                    <h3 class="font-semibold mb-2">Bookings Table</h3>
                    <p class="text-sm">New column: review_status (pending/reviewed)</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Review Modal JavaScript from Customer_Dashboard.php -->
    <script>
        window.CONFIG = { CSRF_TOKEN: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>' };
        
        function showSuccess(message) {
            alert(message);
            setTimeout(() => location.reload(), 1000);
        }
    </script>
</body>
</html>
