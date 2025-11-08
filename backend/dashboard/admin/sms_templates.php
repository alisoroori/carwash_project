<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/sms_template_manager.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$templateManager = new SMSTemplateManager($conn);
$templates = $templateManager->getAllTemplates();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS ÅžablonlarÄ± - Admin Panel</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg mb-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <a href="index.php" class="flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Admin Panel
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold mb-6">SMS ÅžablonlarÄ±</h1>

        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Åžablon AdÄ±</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kod</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ä°Ã§erik</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td class="px-6 py-4"><?= htmlspecialchars($template['name']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($template['code']) ?></td>
                                    <td class="px-6 py-4">
                                        <pre class="text-sm"><?= htmlspecialchars($template['content']) ?></pre>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $template['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $template['is_active'] ? 'Aktif' : 'Pasif' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="editTemplate(<?= $template['id'] ?>)"
                                            class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Template Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Åžablon DÃ¼zenle</h3>
                <form id="templateForm">
                    <input type="hidden" id="templateId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Åžablon AdÄ±</label>
                        <input type="text" id="templateName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Ä°Ã§erik</label>
                        <textarea id="templateContent" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Durum</label>
                        <select id="templateStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="1">Aktif</option>
                            <option value="0">Pasif</option>
                        </select>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" onclick="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                            Ä°ptal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Template management JavaScript
        function editTemplate(id) {
            // Fetch template data and show modal
            fetch(`get_template.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('templateId').value = data.id;
                    document.getElementById('templateName').value = data.name;
                    document.getElementById('templateContent').value = data.content;
                    document.getElementById('templateStatus').value = data.is_active;
                    document.getElementById('editModal').classList.remove('hidden');
                });
        }

        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        document.getElementById('templateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('id', document.getElementById('templateId').value);
            formData.append('name', document.getElementById('templateName').value);
            formData.append('content', document.getElementById('templateContent').value);
            formData.append('is_active', document.getElementById('templateStatus').value);

            fetch('update_template.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error);
                    }
                });
        });
    </script>
</body>

</html>

