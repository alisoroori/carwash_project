<?php
require_once __DIR__ . '/../includes/bootstrap.php';
use App\Classes\Auth;

// Simulated external bank gateway page (for dev/testing only).
// In production you'd redirect to the real bank payment URL.

$session = $_GET['session'] ?? '';
if (!$session || !isset($_SESSION['payment_sessions'][$session])) {
    echo '<p>Ödeme oturumu bulunamadı.</p>'; exit;
}
$ps = $_SESSION['payment_sessions'][$session];
$reservation_id = $ps['reservation_id'];
$amount = $ps['amount'];

// Simple page allowing user to simulate success or failure
$base = defined('BASE_URL') ? BASE_URL : (isset($base_url) ? $base_url : '/carwash_project');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bank Payment Gateway - Simulate</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/dist/output.css">
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow">
        <h2 class="text-xl font-bold mb-4">Banka Ödeme Sayfası (Simülasyon)</h2>
        <p>Rezervasyon: <strong><?php echo htmlspecialchars($reservation_id); ?></strong></p>
        <p>Tutar: <strong><?php echo number_format((float)$amount,2); ?> TL</strong></p>
        <div class="mt-6 flex gap-3">
            <form method="post" action="return.php">
                <input type="hidden" name="session" value="<?php echo htmlspecialchars($session); ?>">
                <input type="hidden" name="status" value="success">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Ödeme Başarılı (Simüle)</button>
            </form>

            <form method="post" action="return.php">
                <input type="hidden" name="session" value="<?php echo htmlspecialchars($session); ?>">
                <input type="hidden" name="status" value="failure">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">Ödeme Başarısız (Simüle)</button>
            </form>
        </div>
    </div>
</body>
</html>
