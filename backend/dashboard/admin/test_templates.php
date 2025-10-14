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
    <title>SMS Şablon Testi - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <nav class="bg-white shadow-lg mb-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="sms_templates.php" class="text-gray-700">
                        <i class="fas fa-arrow-left mr-2"></i> Şablonlara Dön
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold mb-6">SMS Şablon Test Aracı</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Template Selection and Variables -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Test Parametreleri</h2>

                <form id="testForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Şablon Seçin
                        </label>
                        <select id="templateSelect" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Şablon seçin...</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="variablesContainer" class="space-y-4">
                        <!-- Variables will be dynamically added here -->
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Test Telefon Numarası
                        </label>
                        <input type="tel" id="testPhone"
                            placeholder="5XX XXX XXXX"
                            class="w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div class="flex space-x-2">
                        <button type="button" onclick="previewTemplate()"
                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Önizle
                        </button>
                        <button type="button" onclick="sendTestSMS()"
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Test SMS Gönder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Panel -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Önizleme</h2>
                <div class="border rounded-lg p-4 bg-gray-50 min-h-[200px]">
                    <div id="previewContent" class="whitespace-pre-wrap"></div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    <div id="characterCount">Karakter sayısı: 0</div>
                    <div id="smsCount">SMS sayısı: 1</div>
                </div>
            </div>
        </div>

        <!-- Test History -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Test Geçmişi</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Şablon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                        </tr>
                    </thead>
                    <tbody id="testHistory" class="divide-y divide-gray-200">
                        <!-- Test history will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Template testing functionality
        let currentTemplate = null;

        document.getElementById('templateSelect').addEventListener('change', function() {
            const templateId = this.value;
            if (!templateId) return;

            fetch(`get_template.php?id=${templateId}`)
                .then(response => response.json())
                .then(template => {
                    currentTemplate = template;
                    renderVariableInputs(template);
                });
        });

        function renderVariableInputs(template) {
            const container = document.getElementById('variablesContainer');
            container.innerHTML = '';

            const variables = JSON.parse(template.variables);
            variables.forEach(variable => {
                const div = document.createElement('div');
                div.innerHTML = `
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ${variable}
                    </label>
                    <input type="text" name="${variable}"
                           class="w-full rounded-md border-gray-300 shadow-sm"
                           placeholder="Değer girin...">
                `;
                container.appendChild(div);
            });
        }

        function previewTemplate() {
            if (!currentTemplate) return;

            const variables = {};
            const inputs = document.querySelectorAll('#variablesContainer input');
            inputs.forEach(input => {
                variables[input.name] = input.value;
            });

            fetch('preview_template.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        template_id: currentTemplate.id,
                        variables: variables
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('previewContent').textContent = data.preview;
                        document.getElementById('characterCount').textContent =
                            `Karakter sayısı: ${data.preview.length}`;
                        document.getElementById('smsCount').textContent =
                            `SMS sayısı: ${Math.ceil(data.preview.length / 160)}`;
                    }
                });
        }

        function sendTestSMS() {
            const phone = document.getElementById('testPhone').value;
            if (!phone || !currentTemplate) return;

            const variables = {};
            const inputs = document.querySelectorAll('#variablesContainer input');
            inputs.forEach(input => {
                variables[input.name] = input.value;
            });

            fetch('send_test_sms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        template_id: currentTemplate.id,
                        variables: variables,
                        phone: phone
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Test SMS gönderildi!');
                        loadTestHistory();
                    } else {
                        alert(data.error || 'SMS gönderilemedi.');
                    }
                });
        }

        function loadTestHistory() {
            fetch('get_test_history.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('testHistory');
                    tbody.innerHTML = data.history.map(test => `
                        <tr>
                            <td class="px-6 py-4">${test.created_at}</td>
                            <td class="px-6 py-4">${test.template_name}</td>
                            <td class="px-6 py-4">${test.phone}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    ${test.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${test.status === 'success' ? 'Başarılı' : 'Başarısız'}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                });
        }

        // Load test history on page load
        loadTestHistory();
    </script>
</body>

</html>