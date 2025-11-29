<?php
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;

// Enable comprehensive error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/favorites_error.log');
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't display errors in response (JSON only)

// Require customer authentication
Auth::requireRole(['customer']);

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();
$user_id = $_SESSION['user_id'] ?? null;

// Validate user_id exists
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// CSRF validation for POST requests
if ($method === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    
    if (empty($csrf_token) || empty($session_token) || !hash_equals($session_token, $csrf_token)) {
        error_log("CSRF validation failed for user_id={$user_id}, received token: " . substr($csrf_token, 0, 10) . '..., session token: ' . substr($session_token, 0, 10) . '...');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// Validate carwash_id
$carwash_id = null;
if ($method === 'POST') {
    $carwash_id = $_POST['carwash_id'] ?? null;
} elseif ($method === 'GET') {
    $carwash_id = $_GET['carwash_id'] ?? null;
}

if (!$carwash_id || !is_numeric($carwash_id)) {
    error_log("Invalid carwash_id provided for user_id={$user_id}");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid Carwash ID required']);
    exit;
}

$carwash_id = intval($carwash_id);


try {
    if ($method === 'POST') {
        // Validate all required POST parameters
        if (!isset($_POST['carwash_id']) || !isset($_POST['action'])) {
            error_log("Missing required POST parameters for user_id={$user_id}");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }
        
        // Validate action parameter
        $action = $_POST['action'] ?? 'toggle';
        if (!in_array($action, ['add', 'remove', 'toggle'])) {
            error_log("Invalid action '{$action}' for user_id={$user_id}");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
        }

        // Get current user preferences from user_profiles table (not users table)
        $profile = $db->fetchOne(
            "SELECT id, user_id, preferences FROM user_profiles WHERE user_id = :user_id", 
            ['user_id' => $user_id]
        );

        if (!$profile) {
            // Create user_profiles entry if it doesn't exist
            error_log("Creating user_profiles entry for user_id={$user_id}");
            $db->insert('user_profiles', [
                'user_id' => $user_id,
                'preferences' => json_encode(['favorites' => []])
            ]);
            $profile = ['id' => $db->lastInsertId(), 'user_id' => $user_id, 'preferences' => json_encode(['favorites' => []])];
        }

        $favorites = [];
        if (!empty($profile['preferences'])) {
            $data = json_decode($profile['preferences'], true);
            if (is_array($data) && isset($data['favorites']) && is_array($data['favorites'])) {
                $favorites = $data['favorites'];
            }
        }

        $is_favorite = in_array($carwash_id, $favorites, true);

        // Determine the new favorite status
        $new_is_favorite = $is_favorite;
        if ($action === 'add' || ($action === 'toggle' && !$is_favorite)) {
            if (!in_array($carwash_id, $favorites, true)) {
                $favorites[] = $carwash_id;
            }
            $new_is_favorite = true;
        } elseif ($action === 'remove' || ($action === 'toggle' && $is_favorite)) {
            $favorites = array_filter($favorites, function($id) use ($carwash_id) {
                return $id != $carwash_id;
            });
            $new_is_favorite = false;
        }

        // Prepare updated preferences
        $profile_data = ['favorites' => array_values($favorites)];

        // Update user_profiles table with new preferences (not users table)
        $updated = $db->update(
            'user_profiles', 
            ['preferences' => json_encode($profile_data)], 
            ['user_id' => $user_id]
        );

        if ($updated === false) {
            error_log("Failed to update favorites in user_profiles for user_id={$user_id}, carwash_id={$carwash_id}");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update favorites']);
            exit;
        }

        error_log("Favorites updated successfully for user_id={$user_id}, carwash_id={$carwash_id}, is_favorite={$new_is_favorite}");

        echo json_encode([
            'success' => true,
            'is_favorite' => $new_is_favorite,
            'message' => $new_is_favorite ? 'Added to favorites' : 'Removed from favorites'
        ]);
        exit;

    } elseif ($method === 'GET') {
        // Get favorite status from user_profiles table
        $profile = $db->fetchOne(
            "SELECT preferences FROM user_profiles WHERE user_id = :user_id", 
            ['user_id' => $user_id]
        );

        $favorites = [];
        if ($profile && !empty($profile['preferences'])) {
            $data = json_decode($profile['preferences'], true);
            if (is_array($data) && isset($data['favorites']) && is_array($data['favorites'])) {
                $favorites = $data['favorites'];
            }
        }

        $is_favorite = in_array($carwash_id, $favorites, true);

        echo json_encode([
            'success' => true,
            'is_favorite' => $is_favorite
        ]);
        exit;
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

} catch (Exception $e) {
    error_log('Favorites API error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred',
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
}
?>