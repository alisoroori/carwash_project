<?php
session_start();
require_once '../../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total logs count
$total_logs = $conn->query("SELECT COUNT(*) as count FROM admin_logs")->fetch_assoc()['count'];
$total_pages = ceil($total_logs / $per_page);

// Get logs with pagination
$stmt = $conn->prepare("
    SELECT 
        al.*,
        u.name as admin_name,
        u.email as admin_email
    FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$logs = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logs - AquaTR</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-xl font-bold text-blue-600">
                    <i class="fas fa-arrow-left"></i> Panele DÃ¶n
                </a>
                <div class="flex items-center space-x-4">
                    <button onclick="exportLogs()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-download"></i> DÄ±ÅŸa Aktar
                    </button>
                    <button onclick="exportLogs('csv')" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button onclick="exportLogs('pdf')" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Sistem LoglarÄ±</h1>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <form id="filterForm" class="flex flex-wrap gap-4">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Ara..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="w-48">
                    <select name="action" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">TÃ¼m Ä°ÅŸlemler</option>
                        <option value="update_settings">Ayar GÃ¼ncelleme</option>
                        <option value="clear_cache">Ã–nbellek Temizleme</option>
                        <option value="backup_db">VeritabanÄ± Yedekleme</option>
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
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Ä°ÅŸlem</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Detay</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($log['admin_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($log['admin_email']); ?>
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
        function exportLogs(format) {
            const exportUrl = format === 'pdf' ? 'export_logs_pdf.php' : 'export_logs.php';
            window.location.href = exportUrl;
        }

        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            window.location.href = '?' + params.toString();
        });
    </script>

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
                return 'Ayar GÃ¼ncelleme';
            case 'clear_cache':
                return 'Ã–nbellek Temizleme';
            case 'backup_db':
                return 'VeritabanÄ± Yedekleme';
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
            $output[] = ucfirst($key) . ': ' . $value;
        }

        return implode('<br>', $output);
    }
    ?>
</body>

</html>

