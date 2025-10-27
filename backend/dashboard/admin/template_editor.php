<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Email Template Editor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
</head>

<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Email Template Editor</h1>

            <div class="grid grid-cols-2 gap-6">
                <!-- Template List -->
                <div class="border-r pr-6">
                    <h2 class="text-lg font-semibold mb-4">Available Templates</h2>
                    <div id="templateList" class="space-y-2">
                        <!-- Templates will be loaded here -->
                    </div>
                    <button id="newTemplateBtn" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        New Template
                    </button>
                </div>

                <!-- Editor -->
                <div>
                    <form id="templateForm" class="space-y-4">
                        <input type="hidden" id="templateId" name="id">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Template Name</label>
                            <input type="text" id="templateName" name="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subject</label>
                            <input type="text" id="templateSubject" name="subject" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Template Type</label>
                            <select id="templateType" name="type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="notification">Notification</option>
                                <option value="approval">Approval</option>
                                <option value="reminder">Reminder</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">HTML Content</label>
                            <textarea id="templateContent" name="content" rows="20"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Available Variables</label>
                            <div id="variables" class="mt-1 text-sm text-gray-500">
                                {{userName}}, {{date}}, {{content}}, {{actionUrl}}
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button type="button" id="previewBtn"
                                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                Preview
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Save Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-2xl w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Template Preview</h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="previewContent" class="border rounded p-4"></div>
        </div>
    </div>

    <script src="../../../frontend/js/admin/template-editor.js"></script>
</body>

</html>
