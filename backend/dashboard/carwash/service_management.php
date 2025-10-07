<?php
session_start();
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['carwash_id'])) {
    header('Location: /carwash_project/frontend/auth/login.php');
    exit();
}

$carwash_id = $_SESSION['carwash_id'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Service Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Add Service Button -->
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Service Management</h1>
            <button id="addServiceBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add New Service
            </button>
        </div>

        <!-- Service Categories -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-2">Exterior Services</h3>
                <div id="exteriorServices" class="space-y-4">
                    <!-- Services will be loaded here -->
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-2">Interior Services</h3>
                <div id="interiorServices" class="space-y-4">
                    <!-- Services will be loaded here -->
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-2">Full Services</h3>
                <div id="fullServices" class="space-y-4">
                    <!-- Services will be loaded here -->
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-2">Special Services</h3>
                <div id="specialServices" class="space-y-4">
                    <!-- Services will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Service Modal -->
    <div id="serviceModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modalTitle">Add New Service</h3>
                <form id="serviceForm" class="mt-4">
                    <input type="hidden" id="serviceId" name="id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Service Name</label>
                        <input type="text" id="serviceName" name="name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="serviceDescription" name="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price (₺)</label>
                            <input type="number" id="servicePrice" name="price" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Duration (min)</label>
                            <input type="number" id="serviceDuration" name="duration" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select id="serviceCategory" name="category" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="exterior">Exterior</option>
                            <option value="interior">Interior</option>
                            <option value="full">Full Service</option>
                            <option value="special">Special</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal()"
                            class="px-4 py-2 text-gray-500 hover:text-gray-700">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/carwash/service-management.js"></script>
</body>

</html>