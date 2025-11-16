<?php
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;

Auth::requireRole(['customer']);
$db = Database::getInstance();

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<p>Rezervasyon bulunamadı.</p>"; exit;
}

$reservation = null;
if (strpos($id, 's_') === 0) {
    // session-stored
    if (!isset($_SESSION['reservations'][$id])) {
        echo "<p>Rezervasyon bulunamadı (session).</p>"; exit;
    }
    $reservation = $_SESSION['reservations'][$id];
    $reservation['id'] = $id;
} else {
    $reservation = $db->fetchOne('SELECT * FROM reservations WHERE id = :id', ['id' => $id]);
    if (!$reservation) {
        echo "<p>Rezervasyon bulunamadı.</p>"; exit;
    }
}

// Basic HTML view - keep styling consistent with dashboard
$base = defined('BASE_URL') ? BASE_URL : (isset($base_url) ? $base_url : '/carwash_project');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fatura / Ödeme Onayı</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/dist/output.css">
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">
        <h1 class="text-2xl font-bold mb-4">Rezervasyon Detayları</h1>
        <dl class="grid grid-cols-1 gap-2 text-sm text-gray-700">
            <div><dt class="font-semibold">Konum / Yıkama</dt><dd><?php echo htmlspecialchars($reservation['location'] ?? ''); ?></dd></div>
            <div><dt class="font-semibold">Hizmet</dt><dd><?php echo htmlspecialchars($reservation['service'] ?? ''); ?></dd></div>
            <div><dt class="font-semibold">Araç</dt><dd><?php echo htmlspecialchars($reservation['vehicle'] ?? ''); ?></dd></div>
            <div><dt class="font-semibold">Tarih</dt><dd><?php echo htmlspecialchars($reservation['date'] ?? ''); ?></dd></div>
            <div><dt class="font-semibold">Saat</dt><dd><?php echo htmlspecialchars($reservation['time'] ?? ''); ?></dd></div>
            <div><dt class="font-semibold">Notlar</dt><dd><?php echo nl2br(htmlspecialchars($reservation['notes'] ?? '')); ?></dd></div>
            <div><dt class="font-semibold">Tutar</dt><dd><strong><?php echo number_format((float)($reservation['price'] ?? 0), 2); ?> TL</strong></dd></div>
        </dl>

        <div class="mt-6 flex justify-between">
            <a href="javascript:history.back()" class="px-4 py-2 border rounded">Geri</a>

            <form method="post" action="pay.php">
                <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Ödemeye Geç</button>
            </form>
        </div>
    </div>
</body>
</html>
