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

    // Check which table exists
    $tableCheck = $pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tbl");
    $tableCheck->execute(['tbl' => 'business_profiles']);
    $hasBusinessProfiles = (int)$tableCheck->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

    if ($hasBusinessProfiles) {
        $stmt = $pdo->prepare('SELECT id, user_id, business_name, address, postal_code, phone, mobile_phone, email, working_hours, logo_path, created_at, updated_at FROM business_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['working_hours'])) {
            $decoded = json_decode($row['working_hours'], true);
            $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
        }
    } else {
        $stmt = $pdo->prepare('SELECT id, user_id, business_name, address, postal_code, city, contact_phone AS phone, contact_email AS email, opening_hours AS working_hours, featured_image AS logo_path, social_media, created_at, updated_at FROM carwash_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (isset($row['working_hours'])) {
                $decoded = json_decode($row['working_hours'], true);
                $row['working_hours'] = $decoded === null ? $row['working_hours'] : $decoded;
            }

            // Extract mobile_phone from social_media JSON where present (fallback for legacy schema)
            $row['mobile_phone'] = $row['mobile_phone'] ?? null;
            if (isset($row['social_media']) && $row['social_media']) {
                $sm = json_decode($row['social_media'], true);
                if (is_array($sm)) {
                    // Common keys that might store a mobile number
                    foreach (['mobile_phone', 'mobile', 'phone', 'telephone', 'tel'] as $k) {
                        if (!empty($sm[$k])) {
                            $row['mobile_phone'] = $sm[$k];
                            break;
                        }
                    }

                    // Handle nested whatsapp entries like { "whatsapp": { "number": "..." } }
                    if (empty($row['mobile_phone']) && isset($sm['whatsapp'])) {
                        if (is_array($sm['whatsapp'])) {
                            $row['mobile_phone'] = $sm['whatsapp']['number'] ?? $sm['whatsapp']['phone'] ?? $row['mobile_phone'];
                        } elseif (is_string($sm['whatsapp'])) {
                            $row['mobile_phone'] = $sm['whatsapp'];
                        }
                    }
                }
            }

            // Don't expose raw social_media by default
            unset($row['social_media']);
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
