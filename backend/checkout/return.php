<?php
require_once __DIR__ . '/../includes/bootstrap.php';
use App\Classes\Auth;
use App\Classes\Database;

Auth::requireRole(['customer']);
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Invalid method'; exit;
}

$session = $_POST['session'] ?? '';
$status = $_POST['status'] ?? '';
if (!$session || !isset($_SESSION['payment_sessions'][$session])) { echo 'Invalid payment session'; exit; }

$ps = $_SESSION['payment_sessions'][$session];
$reservation_id = $ps['reservation_id'];

// Update reservation status depending on payment result
if ($status === 'success') {
    // mark as paid
    if (strpos($reservation_id, 's_') === 0) {
        if (isset($_SESSION['reservations'][$reservation_id])) {
            $_SESSION['reservations'][$reservation_id]['status'] = 'paid';
            $_SESSION['reservations'][$reservation_id]['paid_at'] = date('Y-m-d H:i:s');
        }
    } else {
        try {
            $db->update('reservations', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], ['id' => $reservation_id]);
        } catch (\Throwable $e) {
            // ignore but log in production
        }
    }
    $message = 'Ödeme başarıyla tamamlandı. Rezervasyonunuz oluşturuldu.';
} else {
    // failure
    if (strpos($reservation_id, 's_') === 0) {
        if (isset($_SESSION['reservations'][$reservation_id])) {
            $_SESSION['reservations'][$reservation_id]['status'] = 'failed';
        }
    } else {
        try {
            $db->update('reservations', ['status' => 'failed'], ['id' => $reservation_id]);
        } catch (\Throwable $e) {}
    }
    $message = 'Ödeme başarısız oldu. Lütfen tekrar deneyin.';
}

// Clean up payment session
unset($_SESSION['payment_sessions'][$session]);

$base = defined('BASE_URL') ? BASE_URL : (isset($base_url) ? $base_url : '/carwash_project');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ödeme Sonucu</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/dist/output.css">
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow">
        <h2 class="text-xl font-bold mb-4"><?php echo htmlspecialchars($message); ?></h2>
        <p>Rezervasyon ID: <strong><?php echo htmlspecialchars($reservation_id); ?></strong></p>
        <p class="mt-4"><a href="/carwash_project/backend/dashboard/Customer_Dashboard.php" class="px-4 py-2 bg-blue-600 text-white rounded">Panoya Dön</a></p>
    </div>
</body>
</html>
