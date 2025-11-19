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

    // `carwashes` is the authoritative source for business info in this app
    $tableCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'carwashes'");
    $tableCheck->execute();
    $hasCarwashes = (bool)$tableCheck->fetchColumn();

    if (!$hasCarwashes) {
        Response::error('Carwashes table not found on this installation.', 500);
    }

    $stmt = $pdo->prepare('SELECT id, user_id, name AS business_name, address, COALESCE(postal_code, zip_code) AS postal_code, phone, mobile_phone, email, COALESCE(working_hours, opening_hours) AS working_hours, COALESCE(logo_path, logo_image, profile_image, image) AS logo_path, COALESCE(license_number, "") AS license_number, COALESCE(tax_number, "") AS tax_number, COALESCE(city, "") AS city, COALESCE(district, "") AS district, social_media, services, COALESCE(certificate_path, "") AS certificate_path, created_at, updated_at FROM carwashes WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && isset($row['working_hours'])) {
        $decoded = json_decode($row['working_hours'], true);
        $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
    }

    // Normalize logo_path to full web URL if stored as filename
    if ($row && !empty($row['logo_path'])) {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';
        $lp = $row['logo_path'];
        if (preg_match('#^(?:https?://|/)#i', $lp)) {
            $row['logo_path'] = $lp;
        } else {
            $row['logo_path'] = $base_url . '/backend/uploads/business_logo/' . ltrim($lp, '/');
        }
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\/');
        if (!preg_match('#^(?:https?://|/)#i', $lp)) {
            $filePath = $docRoot . '/carwash_project/backend/uploads/business_logo/' . ltrim($lp, '/');
        } else {
            $filePath = $docRoot . parse_url($row['logo_path'], PHP_URL_PATH);
        }
        if (!file_exists($filePath)) {
            @file_put_contents(__DIR__ . '/../../logs/logo_missing.log', date('Y-m-d H:i:s') . " - get_business_info missing logo: {$filePath}\n", FILE_APPEND | LOCK_EX);
            $row['logo_path'] = $base_url . '/backend/logo01.png';
        }
    }

    if (!$row) {
        Response::error('İşletme kaydı bulunamadı', 404);
    }

    Response::success('OK', ['data' => $row]);

} catch (Exception $e) {
    error_log('Get business info error: ' . $e->getMessage());
    Response::error('Sunucu hatası: ' . $e->getMessage(), 500);
}
