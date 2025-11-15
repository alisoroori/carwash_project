<?php
session_start();
require_once '../../includes/db.php';
// Escaping helpers
if (file_exists(__DIR__ . '/../../includes/escape.php')) {
    require_once __DIR__ . '/../../includes/escape.php';
}

// Ensure language helper is available and use it for this page
if (file_exists(__DIR__ . '/../../includes/lang_helper.php')) {
    require_once __DIR__ . '/../../includes/lang_helper.php';
}

// Pagination settings
require_once '../../includes/paginator.php';
use App\Includes\Paginator;

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$p = new Paginator($page, $per_page);
$offset = $p->getOffset();
$limit = $p->getLimit();

// Get total logs count and set paginator total
$total_logs = (int)$conn->query("SELECT COUNT(*) as count FROM admin_logs")->fetch_assoc()['count'];
$p->setTotal($total_logs);
$total_pages = $p->getTotalPages();

// Get logs with pagination
$stmt = $conn->prepare("SELECT al.*, u.name as admin_name, u.email as admin_email FROM admin_logs al JOIN users u ON al.admin_id = u.id ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$logs = $stmt->get_result();

// If request is AJAX (XHR), return JSON with meta + data
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $rows = $logs->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['meta' => $p->getMeta(), 'data' => $rows]);
    exit;
}
?>

<!DOCTYPE html>
<html <?php echo (function_exists('get_lang_dir_attrs_for_file') ? get_lang_dir_attrs_for_file(__FILE__) : 'lang="en"'); ?> >

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logs - AquaTR</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-xl font-bold text-blue-600">
                    <i class="fas fa-arrow-left"></i> Panele Dön
                </a>
                <div class="flex items-center space-x-4">
                    <button type="button" class="export-logs-btn bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" data-format="" aria-label="Dışa Aktar">
                        <i class="fas fa-download"></i> Dışa Aktar
                    </button>
                    <button type="button" class="export-logs-btn bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" data-format="csv" aria-label="CSV">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button type="button" class="export-logs-btn bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700" data-format="pdf" aria-label="PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Sistem Logları</h1>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <form id="filterForm" class="flex flex-wrap gap-4">
                <div class="flex-1">
                    <label for="auto_label_78" class="sr-only">Search</label>
                    <input type="text" name="search" placeholder="Ara..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" id="auto_label_78">
                </div>
                <div class="w-48">
                    <label for="auto_label_77" class="sr-only">Action</label>
                    <select name="action" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" id="auto_label_77">
                        <option value="">Tüm İşlemler</option>
                        <option value="update_settings">Ayar Güncelleme</option>
                        <option value="clear_cache">Önbellek Temizleme</option>
                        <option value="backup_db">Veritabanı Yedekleme</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-filter"></i> Filtrele
                </button>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Tarih/Saat</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">İşlem</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Detay</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo e_html(date('d.m.Y H:i:s', strtotime($log['created_at']))); ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo e_html($log['admin_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo e_html($log['admin_email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                           <?php echo getActionClass($log['action']); ?>">
                                    <?php echo getActionText($log['action']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php
                                $details = json_decode($log['details'], true);
                                echo formatDetails($details);
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <div class="flex space-x-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>"
                            class="px-4 py-2 border rounded-md <?php echo $page === $i ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Keep the existing filter behavior; export actions are handled by frontend/js/dashboard-events.js
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            window.location.href = '?' + params.toString();
        });
    </script>

    <script src="/carwash_project/frontend/js/dashboard-events.js"></script>

    <?php
    function getActionClass($action)
    {
        switch ($action) {
            case 'update_settings':
                return 'bg-blue-100 text-blue-800';
            case 'clear_cache':
                return 'bg-yellow-100 text-yellow-800';
            case 'backup_db':
                return 'bg-green-100 text-green-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    function getActionText($action)
    {
        switch ($action) {
            case 'update_settings':
                return 'Ayar Güncelleme';
            case 'clear_cache':
                return 'Önbellek Temizleme';
            case 'backup_db':
                return 'Veritabanı Yedekleme';
            default:
                return ucfirst(str_replace('_', ' ', $action));
        }
    }

    function formatDetails($details)
    {
        if (!is_array($details)) return 'N/A';

        $output = [];
        foreach ($details as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $output[] = e_html(ucfirst($key)) . ': ' . e_html($value);
        }

        return implode('<br>', $output);
    }
    ?>
</body>

</html>





