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
// Support three sources: session-stored, reservations table (legacy), or bookings table (canonical)
if (strpos($id, 's_') === 0) {
    // session-stored
    if (!isset($_SESSION['reservations'][$id])) {
        echo "<p>Rezervasyon bulunamadı (session).</p>"; exit;
    }
    $reservation = $_SESSION['reservations'][$id];
    $reservation['id'] = $id;
} else {
    // Try reservations table first (older flow)
    try {
        $reservation = $db->fetchOne('SELECT * FROM reservations WHERE id = :id', ['id' => $id]);
    } catch (Exception $e) {
        // If the legacy `reservations` table does not exist or query fails,
        // log and continue to fall back to the canonical `bookings` table.
        if (class_exists('App\\Classes\\Logger')) {
            \App\Classes\Logger::error('invoice.php: reservations lookup failed, falling back to bookings', ['exception' => $e->getMessage()]);
        } else {
            error_log('invoice.php: reservations lookup failed: ' . $e->getMessage());
        }
        $reservation = null;
    }
    if (!$reservation) {
        // Fall back to bookings table (new canonical bookings)
        // Prefer canonical `carwashes` for name; fall back to business_profiles if necessary
        $b = $db->fetchOne('SELECT b.*, s.name as service_name, c.name as carwash_name FROM bookings b LEFT JOIN services s ON s.id = b.service_id LEFT JOIN carwashes c ON c.id = b.carwash_id WHERE b.id = :id', ['id' => $id]);
        if ($b) {
            // Normalize to expected reservation keys used in template
            $reservation = [
                'id' => $b['id'],
                'location' => $b['carwash_name'] ?? ($b['location'] ?? ''),
                'location_id' => $b['carwash_id'] ?? null,
                'service' => $b['service_name'] ?? ($b['service_type'] ?? ''),
                'vehicle' => $b['vehicle'] ?? '',
                'date' => $b['booking_date'] ?? $b['date'] ?? '',
                'time' => $b['booking_time'] ?? $b['time'] ?? '',
                'notes' => $b['notes'] ?? '',
                'price' => $b['total_price'] ?? $b['price'] ?? 0,
                'status' => $b['status'] ?? ''
            ];
        } else {
            echo "<p>Rezervasyon bulunamadı.</p>"; exit;
        }
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
    <style>
        /* Print styles: only invoice content */
        @media print {
            body * { visibility: hidden; }
            #invoiceContent, #invoiceContent * { visibility: visible; }
            #invoiceContent { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }

        /* Small adjustments for PDF generation consistency */
        .invoice-box { border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; background: #fff; }
        .invoice-header { border-bottom: 1px solid #eef2f7; padding-bottom: 16px; margin-bottom: 18px; }
        .company-meta { text-align: right; }
        @media (max-width: 640px) {
            .company-meta { text-align: left; margin-top: 12px; }
        }
    </style>
</head>
<body class="bg-gray-50 p-4 sm:p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-4 no-print">
            <div class="flex items-center gap-4">
                <?php
                    // Attempt to get carwash details if location_id exists
                    $cw_name = htmlspecialchars($reservation['location'] ?? '');
                    $cw_address = 'Adres bilgisi yok';
                    $cw_phone = '';
                    $cw_email = '';
                    if (!empty($reservation['location_id'])) {
                        $lid = $reservation['location_id'];
                        if (is_numeric($lid)) {
                            $cw = $db->fetchOne('SELECT * FROM carwashes WHERE id = :id', ['id' => $lid]);
                            if ($cw) {
                                $cw_name = htmlspecialchars($cw['name'] ?? $cw_name);
                                $cw_address = htmlspecialchars($cw['address'] ?? $cw_address);
                                $cw_phone = htmlspecialchars($cw['phone'] ?? '');
                                $cw_email = htmlspecialchars($cw['email'] ?? '');
                            }
                        }
                    }
                ?>
                <div class="flex items-center gap-3">
                    <?php
                        // Choose a logo path robustly to avoid 404s in different installs.
                        $candidate1 = __DIR__ . '/../../frontend/images/logo.png';
                        $candidate2 = __DIR__ . '/../logo01.png';
                        $candidate3 = __DIR__ . '/../../frontend/assets/img/default-user.png';

                        if (file_exists($candidate1)) {
                            $logo_url = $base . '/frontend/images/logo.png';
                        } elseif (file_exists($candidate2)) {
                            $logo_url = $base . '/backend/logo01.png';
                        } elseif (file_exists($candidate3)) {
                            $logo_url = $base . '/frontend/assets/img/default-user.png';
                        } else {
                            // Last resort: use a data URI 1x1 transparent GIF to avoid broken image icon
                            $logo_url = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo" class="w-20 h-20 object-contain">
                    <div>
                        <div class="text-lg font-bold text-gray-900"><?php echo $cw_name; ?></div>
                        <div class="text-sm text-gray-600"><?php echo $cw_address; ?></div>
                    </div>
                </div>
            </div>

            <div class="company-meta text-sm text-gray-700">
                <div><strong>Fatura No:</strong> <?php echo htmlspecialchars(is_string($reservation['id']) ? $reservation['id'] : ('INV-' . $reservation['id'] . '-' . date('YmdHis'))); ?></div>
                <div><strong>Tarih:</strong> <?php echo htmlspecialchars(date('Y-m-d')); ?></div>
                <?php if ($cw_phone): ?><div><strong>Tel:</strong> <?php echo $cw_phone; ?></div><?php endif; ?>
                <?php if ($cw_email): ?><div><strong>Email:</strong> <?php echo $cw_email; ?></div><?php endif; ?>
            </div>
        </div>

        <div id="invoiceContent" class="invoice-box p-6">
            <div class="invoice-header flex flex-col sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-1">Rezervasyon Faturası</h2>
                    <div class="text-sm text-gray-600">Lütfen bilgilerinizi kontrol edin ve ödemeye devam edin.</div>
                </div>
                <div class="mt-4 sm:mt-0 text-sm text-gray-700">
                    <div><strong>Müşteri:</strong> <?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['full_name'] ?? ''); ?></div>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6 text-sm text-gray-800">
                <div class="space-y-2">
                    <div class="font-semibold text-gray-700">Hizmet Bilgileri</div>
                    <div><strong>Konum / Yıkama:</strong> <?php echo htmlspecialchars($reservation['location'] ?? ''); ?></div>
                    <div><strong>Hizmet:</strong> <?php echo htmlspecialchars($reservation['service'] ?? ''); ?></div>
                    <div><strong>Araç:</strong> <?php echo htmlspecialchars($reservation['vehicle'] ?? ''); ?></div>
                </div>
                <div class="space-y-2">
                    <div class="font-semibold text-gray-700">Zamanlama</div>
                    <div><strong>Tarih:</strong> <?php echo htmlspecialchars($reservation['date'] ?? ''); ?></div>
                    <div><strong>Saat:</strong> <?php echo htmlspecialchars($reservation['time'] ?? ''); ?></div>
                </div>
            </div>

            <div class="mt-6">
                <div class="font-semibold text-gray-700 mb-2">Notlar</div>
                <div class="text-sm text-gray-700 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($reservation['notes'] ?? '')); ?></div>
            </div>

            <div class="mt-6 flex justify-end">
                <div class="w-full sm:w-1/3 bg-gray-50 p-4 rounded border">
                    <div class="flex justify-between text-sm text-gray-700"><div>Ara Toplam</div><div><?php echo number_format((float)($reservation['price'] ?? 0), 2); ?> TL</div></div>
                    <div class="flex justify-between text-sm text-gray-700 mt-2"><div>Toplam</div><div class="font-bold text-lg"><?php echo number_format((float)($reservation['price'] ?? 0), 2); ?> TL</div></div>
                </div>
            </div>

            <div class="mt-6 text-sm text-gray-600">Ödeme yapılırken lütfen güvenliğinizi sağlayın. Bu sayfa hassas ödeme bilgilerini saklamaz.</div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3 flex-col sm:flex-row no-print">
            <div class="flex gap-2">
                <a href="javascript:history.back()" class="px-4 py-2 border rounded bg-white">Geri</a>
            </div>

            <div class="flex gap-2">
                <button id="printBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Yazdır</button>
                <button id="pdfBtn" class="px-4 py-2 bg-blue-600 text-white rounded">PDF İndir</button>

                <form method="post" action="pay.php" class="inline">
                    <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Ödemeye Geç</button>
                </form>
            </div>
        </div>
    </div>

    <!-- html2pdf for client-side PDF generation (keeps styling) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
    <script>
        (function(){
            const printBtn = document.getElementById('printBtn');
            const pdfBtn = document.getElementById('pdfBtn');
            const invoice = document.getElementById('invoiceContent');

            if (printBtn) printBtn.addEventListener('click', function(){ window.print(); });

            if (pdfBtn && invoice) pdfBtn.addEventListener('click', function(){
                const opt = {
                    margin:       0.4,
                    filename:     'invoice-<?php echo preg_replace('/[^A-Za-z0-9_-]/','',htmlspecialchars($reservation['id'])); ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true },
                    jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
                };
                // clone to avoid modifying live node
                const element = invoice.cloneNode(true);
                element.style.width = '100%';
                document.body.appendChild(element);
                html2pdf().set(opt).from(element).save().then(() => { document.body.removeChild(element); }).catch(err => { console.error(err); alert('PDF oluşturulamadı.'); });
            });
        })();
    </script>
</body>
</html>
