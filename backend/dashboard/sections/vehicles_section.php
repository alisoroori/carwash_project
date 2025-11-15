<?php
// Fragment: vehicles_section.php
// Returns an HTML fragment listing vehicles for the current user.
// This file is intended to be fetched via AJAX and also included server-side as a <noscript> fallback.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Resolve bootstrap path robustly: this fragment lives in
// backend/dashboard/sections/, while bootstrap.php is in backend/includes/
$bootstrapPath = dirname(__DIR__, 2) . '/includes/bootstrap.php';
if (!file_exists($bootstrapPath)) {
    // Fallback for older PHP versions or unexpected layouts
    $bootstrapPath = __DIR__ . '/../../includes/bootstrap.php';
}
if (!file_exists($bootstrapPath)) {
    // If bootstrap cannot be found, emit a clear error and stop to avoid
    // fatal errors later when classes are referenced.
    http_response_code(500);
    echo '<div class="p-4 bg-red-50 border border-red-200 rounded">Server configuration error: bootstrap.php not found.</div>';
    exit;
}
require_once $bootstrapPath;
use App\Classes\Auth;
use App\Classes\Database;

// Require that the user is authenticated (for fragments we simply return 401 HTML when not authenticated)
try {
    Auth::requireAuth();
} catch (\Exception $e) {
    http_response_code(401);
    echo '<div class="p-6 bg-yellow-50 border border-yellow-200 rounded">You must be signed in to view this content.</div>';
    exit;
}

$base_url = defined('BASE_URL') ? BASE_URL : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';

$db = Database::getInstance();
$user_id = $_SESSION['user_id'] ?? null;

$vehicles = [];
if ($user_id) {
    // Basic vehicle list; keep lightweight (only needed columns)
    $vehicles = $db->fetchAll("SELECT id, brand, model, license_plate FROM vehicles WHERE user_id = :uid ORDER BY id DESC LIMIT 20", ['uid' => $user_id]);
}

?>
<div class="vehicles-fragment space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">Araçlarım</h3>
        <a href="#vehicles" class="text-sm text-blue-600 hover:underline">Tümünü Gör</a>
    </div>

    <?php if (empty($vehicles)): ?>
        <div class="p-4 bg-white rounded-md shadow-sm border border-gray-100">
            <p class="text-sm text-gray-600">Henüz araç kaydınız yok. <a href="#vehicles" class="text-blue-600 hover:underline">Araç ekleyin</a> to get started.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($vehicles as $v): ?>
                <div class="p-4 bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($v['brand'] . ' ' . $v['model'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($v['license_plate'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">#<?php echo (int)$v['id']; ?></div>
                    </div>
                    <div class="flex gap-2 mt-3">
                        <a href="<?php echo $base_url; ?>/backend/dashboard/vehicles_preview.html" class="px-3 py-2 rounded-md bg-blue-50 text-blue-700 text-sm">Detay</a>
                        <a href="#" class="px-3 py-2 rounded-md bg-gray-100 text-gray-700 text-sm">Düzenle</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
