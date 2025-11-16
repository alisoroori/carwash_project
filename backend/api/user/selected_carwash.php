<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;
use App\Classes\Response;

// Only customers may set preferred carwash
Auth::requireRole(['customer']);

$db = Database::getInstance();

// Accept JSON body or form-encoded
$input = json_decode(file_get_contents('php://input'), true);
$carwash_id = $input['carwash_id'] ?? $_POST['carwash_id'] ?? null;

if (empty($carwash_id)) {
    Response::error('Missing carwash_id', 400);
}

$user_id = $_SESSION['user_id'] ?? null;
if (empty($user_id)) {
    Response::unauthorized('Authentication required');
}

// verify carwash exists
$cw = $db->fetchOne("SELECT id FROM carwash_profiles WHERE id = :id", ['id' => $carwash_id]);
if (!$cw) {
    Response::notFound('Carwash not found');
}

// Upsert into user_profiles.preferred_carwash_id
$exists = $db->fetchOne("SELECT user_id FROM user_profiles WHERE user_id = :uid", ['uid' => $user_id]);
if ($exists) {
    $db->update('user_profiles', ['preferred_carwash_id' => $carwash_id], ['user_id' => $user_id]);
} else {
    // insert minimal profile row
    $db->insert('user_profiles', ['user_id' => $user_id, 'preferred_carwash_id' => $carwash_id]);
}

Response::success('Preferred carwash updated', ['carwash_id' => $carwash_id]);

?>
