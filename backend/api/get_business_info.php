<?php
/**
 * Get Business Info API
 * Returns the authenticated user's business/carwash profile in a normalized shape
 */

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;
use App\Classes\Session;

Session::start();
Auth::requireAuth();

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        Response::unauthorized();
    }

    // Check which table exists and prefer `carwashes`, then `business_profiles` (legacy `carwash_profiles` removed)
    $tableCheck = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('carwashes','business_profiles')");
    $tableCheck->execute();
    $found = $tableCheck->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasCarwashes = in_array('carwashes', $found, true);
    $hasBusinessProfiles = in_array('business_profiles', $found, true);

    if ($hasCarwashes) {
        $stmt = $pdo->prepare('SELECT id, user_id, COALESCE(name,business_name) AS business_name, address, postal_code, COALESCE(phone,contact_phone) AS phone, COALESCE(mobile_phone,NULL) AS mobile_phone, COALESCE(email,contact_email) AS email, COALESCE(working_hours,opening_hours) AS working_hours, COALESCE(logo_path,featured_image) AS logo_path, social_media, created_at, updated_at FROM carwashes WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['working_hours'])) {
            $decoded = json_decode($row['working_hours'], true);
            $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
        }
    } elseif ($hasBusinessProfiles) {
        $stmt = $pdo->prepare('SELECT id, user_id, business_name, address, postal_code, phone, mobile_phone, email, working_hours, logo_path, created_at, updated_at FROM business_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['working_hours'])) {
            $decoded = json_decode($row['working_hours'], true);
            $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
        }
    } else {
        Response::error('İşletme kaydı için uygun tablo bulunamadı.', 404);
    }

    if (!$row) {
        Response::error('İşletme kaydı bulunamadı', 404);
    }

    Response::success('OK', ['data' => $row]);

} catch (Exception $e) {
    error_log('Get business info error: ' . $e->getMessage());
    Response::error('Sunucu hatası: ' . $e->getMessage(), 500);
}
