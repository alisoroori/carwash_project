<?php
session_start();
require_once '../../includes/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'carwash') {
    header('Location: ../../auth/login.php');
    exit();
}

// Get carwash details
$stmt = $conn->prepare("SELECT id FROM carwash_profiles WHERE owner_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$carwash = $stmt->get_result()->fetch_assoc();

// Get working hours
$stmt = $conn->prepare("
    SELECT * FROM working_hours 
    WHERE carwash_id = ? 
    ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
");
$stmt->bind_param("i", $carwash['id']);
$stmt->execute();
$working_hours = $stmt->get_result();

// Convert to associative array
$hours = [];
while ($row = $working_hours->fetch_assoc()) {
    $hours[$row['day']] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çalışma Saatleri - AquaTR</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Çalışma Saatleri</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form id="workingHoursForm" action="process_working_hours.php" method="POST"
            class="bg-white rounded-lg shadow-md p-6">

            <?php
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $dayNames = [
                'Monday' => 'Pazartesi',
                'Tuesday' => 'Salı',
                'Wednesday' => 'Çarşamba',
                'Thursday' => 'Perşembe',
                'Friday' => 'Cuma',
                'Saturday' => 'Cumartesi',
                'Sunday' => 'Pazar'
            ];

            foreach ($days as $day):
                $isOpen = isset($hours[$day]) && $hours[$day]['is_open'];
                $openTime = isset($hours[$day]) ? $hours[$day]['open_time'] : '09:00';
                $closeTime = isset($hours[$day]) ? $hours[$day]['close_time'] : '18:00';
            ?>
                <div class="mb-6 border-b pb-4 last:border-0">
                    <div class="flex items-center justify-between mb-4">
                        <label class="text-lg font-medium text-gray-700">
                            <?php echo $dayNames[$day]; ?>
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_open[<?php echo $day; ?>]"
                                class="sr-only peer" <?php echo $isOpen ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 
                                     peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full 
                                     peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] 
                                     after:left-[2px] after:bg-white after:border-gray-300 after:border 
                                     after:rounded-full after:h-5 after:w-5 after:transition-all 
                                     peer-checked:bg-blue-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Açık</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Açılış</label>
                            <input type="time" name="open_time[<?php echo $day; ?>]"
                                value="<?php echo $openTime; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                          focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kapanış</label>
                            <input type="time" name="close_time[<?php echo $day; ?>]"
                                value="<?php echo $closeTime; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                          focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="mt-6">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md 
                                         hover:bg-blue-700 focus:outline-none focus:ring-2 
                                         focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-save mr-2"></i> Kaydet
                </button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('workingHoursForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('process_working_hours.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Bir hata oluştu.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu.');
                });
        });
    </script>
</body>

</html>
