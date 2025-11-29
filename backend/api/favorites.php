<?php
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;

// Require customer authentication
Auth::requireRole(['customer']);

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];
$carwash_id = $_POST['carwash_id'] ?? $_GET['carwash_id'] ?? null;

if (!$carwash_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Carwash ID required']);
    exit;
}

try {
    // For simplicity, we'll use a user preferences approach
    // Check if user has favorites stored in their profile or a simple table
    // For now, let's use a simple approach with a favorites field in user_profiles

    if ($method === 'POST') {
        // Toggle favorite
        $action = $_POST['action'] ?? 'toggle';

        // Get current user preferences stored on users table
        $profile = $db->fetchOne("SELECT id, preferences FROM users WHERE id = :user_id", ['user_id' => $user_id]);

        $favorites = [];
        if ($profile && !empty($profile['preferences'])) {
            $data = json_decode($profile['preferences'], true);
            $favorites = $data['favorites'] ?? [];
        }

        $is_favorite = in_array($carwash_id, $favorites);

        if ($action === 'add' || ($action === 'toggle' && !$is_favorite)) {
            if (!in_array($carwash_id, $favorites)) {
                $favorites[] = $carwash_id;
            }
            $is_favorite = true;
        } elseif ($action === 'remove' || ($action === 'toggle' && $is_favorite)) {
            $favorites = array_filter($favorites, function($id) use ($carwash_id) {
                return $id != $carwash_id;
            });
            $is_favorite = false;
        }

        // Update profile data
        $profile_data = ['favorites' => array_values($favorites)];

        // Persist preferences into users table
        $existing = $db->fetchOne('SELECT id FROM users WHERE id = :user_id', ['user_id' => $user_id]);
        if ($existing) {
            $db->update('users', ['preferences' => json_encode($profile_data)], ['id' => $user_id]);
        } else {
            // Unexpected: no users row
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'is_favorite' => $is_favorite,
            'message' => $is_favorite ? 'Added to favorites' : 'Removed from favorites'
        ]);

    } elseif ($method === 'GET') {
        // Get favorite status
        $profile = $db->fetchOne("SELECT preferences FROM users WHERE id = :user_id", ['user_id' => $user_id]);

        $favorites = [];
        if ($profile && !empty($profile['preferences'])) {
            $data = json_decode($profile['preferences'], true);
            $favorites = $data['favorites'] ?? [];
        }

        $is_favorite = in_array($carwash_id, $favorites);

        echo json_encode([
            'success' => true,
            'is_favorite' => $is_favorite
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log('Favorites API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>