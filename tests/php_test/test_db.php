<?php
require_once 'backend/includes/carwash_db.php';
require_once 'backend/includes/test_db.php';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>CarWash - Database Test</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Database Connection Test</h1>

        <div class="bg-white rounded-lg shadow-md p-6">
            <?php
            try {
                // Test basic connection
                $test_query = "SELECT NOW() as server_time";
                $result = query($test_query);

                if ($result) {
                    $time = $result->fetch_assoc()['server_time'];
                    echo '<div class="flex items-center mb-4 text-green-600">';
                    echo '<svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">';
                    echo '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
                    echo '</svg>';
                    echo '<span class="text-lg font-semibold">VeritabanÄ± baÄŸlantÄ±sÄ± baÅŸarÄ±lÄ±!</span>';
                    echo '</div>';
                    echo '<p class="text-gray-600 mb-6">Sunucu zamanÄ±: ' . $time . '</p>';

                    // Test required tables
                    $tables = ['users', 'carwash_profiles', 'services', 'bookings'];
                    echo '<div class="space-y-4">';
                    echo '<h2 class="text-xl font-semibold text-gray-700">Tablo KontrolÃ¼:</h2>';

                    foreach ($tables as $table) {
                        $table_check = query("SHOW TABLES LIKE ?", [$table]);
                        echo '<div class="flex items-center p-3 bg-gray-50 rounded">';
                        if ($table_check && $table_check->num_rows > 0) {
                            echo '<span class="text-green-500 mr-2">âœ“</span>';
                            echo '<span class="text-gray-700">' . $table . ' tablosu mevcut</span>';
                        } else {
                            echo '<span class="text-red-500 mr-2">âœ—</span>';
                            echo '<span class="text-gray-700">' . $table . ' tablosu bulunamadÄ±!</span>';
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="bg-red-50 border-l-4 border-red-500 p-4">';
                echo '<div class="flex items-center">';
                echo '<svg class="w-6 h-6 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">';
                echo '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
                echo '</svg>';
                echo '<div>';
                echo '<h3 class="text-lg font-medium text-red-700">BaÄŸlantÄ± hatasÄ±!</h3>';
                echo '<p class="text-red-600">' . $e->getMessage() . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            ?>

            <div class="mt-8 pt-6 border-t">
                <a href="../../index.php" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Ana Sayfaya DÃ¶n
                </a>
            </div>
        </div>
    </div>
</body>

</html>

