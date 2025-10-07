<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İçerik Yönetimi - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/YOUR_TINY_MCE_KEY/tinymce/6/tinymce.min.js"></script>
</head>

<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-white w-64 shadow-lg">
            <div class="p-4 border-b">
                <h1 class="text-xl font-bold">İçerik Yönetimi</h1>
            </div>
            <nav class="p-4">
                <a href="#pages" class="block py-2 px-4 rounded hover:bg-blue-50" data-section="pages">
                    <i class="fas fa-file-alt mr-2"></i> Sayfalar
                </a>
                <a href="#announcements" class="block py-2 px-4 rounded hover:bg-blue-50" data-section="announcements">
                    <i class="fas fa-bullhorn mr-2"></i> Duyurular
                </a>
                <a href="#faqs" class="block py-2 px-4 rounded hover:bg-blue-50" data-section="faqs">
                    <i class="fas fa-question-circle mr-2"></i> SSS
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Content sections will be loaded here -->
            <div id="contentArea"></div>
        </div>
    </div>

    <!-- Add/Edit Page Modal -->
    <div id="pageModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-3/4 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold" id="modalTitle">Yeni Sayfa</h3>
                <button onclick="closeModal('pageModal')" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="pageForm" class="space-y-4">
                <input type="hidden" name="id" id="pageId">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Başlık</label>
                        <input type="text" name="title" id="pageTitle" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">URL</label>
                        <input type="text" name="slug" id="pageSlug" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">İçerik</label>
                    <textarea name="content" id="pageContent" rows="10"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('pageModal')"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        İptal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../../frontend/js/admin/content-manager.js"></script>
</body>

</html>