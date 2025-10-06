<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Çalışma Saatleri - CarWash Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Service Selection -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-4">Hizmet Seçimi</h2>
            <div class="flex flex-wrap gap-2" id="serviceSelection">
                <!-- Services will be loaded here -->
            </div>
        </div>

        <!-- Calendar View -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div id="availabilityCalendar"></div>
        </div>

        <!-- Batch Update Form -->
        <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Toplu Güncelleme</h3>
            <form id="batchUpdateForm" class="space-y-4">
                <!-- Form content -->
            </form>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="fixed bottom-4 right-4 space-x-2">
        <button id="undoBtn" class="px-4 py-2 bg-gray-600 text-white rounded-full hover:bg-gray-700">
            <i class="fas fa-undo"></i>
        </button>
        <button id="redoBtn" class="px-4 py-2 bg-gray-600 text-white rounded-full hover:bg-gray-700">
            <i class="fas fa-redo"></i>
        </button>
        <button id="optimizeBtn" class="px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700">
            <i class="fas fa-magic"></i> Optimize
        </button>
    </div>

    <script src="../../frontend/js/carwash/availability-calendar.js"></script>
    <script src="../../frontend/js/carwash/service-selector.js"></script>
    <script src="../../frontend/js/carwash/batch-updater.js"></script>
    <script src="../../frontend/js/carwash/conflict-visualizer.js"></script>
    <script src="../../frontend/js/carwash/enhanced-calendar.js"></script>
    <script src="../../frontend/js/carwash/schedule-clipboard.js"></script>
    <script src="../../frontend/js/carwash/schedule-templates.js"></script>
    <script src="../../frontend/js/carwash/schedule-history.js"></script>
    <script src="../../frontend/js/carwash/conflict-resolver.js"></script>
    <script src="../../frontend/js/carwash/schedule-optimizer.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const calendar = new AvailabilityCalendar('availabilityCalendar');
            const serviceSelector = new ServiceSelector('serviceSelection');
            const batchUpdater = new BatchUpdater('batchUpdateForm', serviceSelector, calendar);
            const conflictVisualizer = new ConflictVisualizer(calendar);
            const clipboard = new ScheduleClipboard(calendar);
            const templates = new ScheduleTemplates(calendar);
            const historyManager = new ScheduleHistoryManager(calendar);
            const conflictResolver = new ConflictResolver(calendar);
            const optimizer = new ScheduleOptimizer(calendar);

            // Connect components
            batchUpdater.onConflict = conflicts => conflictVisualizer.showConflicts(conflicts);

            // Handle newly created schedules
            calendar.addEventListener('scheduleCreated', (schedule) => {
                batchUpdater.addSchedule(schedule);
            });

            // Setup optimization button
            document.getElementById('optimizeBtn').addEventListener('click', async () => {
                const analysis = await optimizer.analyzeSchedule();
                showOptimizationDialog(analysis);
            });

            // Track schedule changes for undo/redo
            calendar.addEventListener('scheduleChanged', (schedules) => {
                historyManager.addToHistory(schedules);
            });

            // Handle conflicts
            calendar.addEventListener('conflictDetected', async (conflicts) => {
                const suggestions = await conflictResolver.suggestResolutions(conflicts);
                showConflictResolutionDialog(suggestions);
            });
        });
    </script>
</body>
</html>