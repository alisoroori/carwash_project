<?php
session_start();
require_once '../../includes/db.php';
?>

<?php
// Provide language attributes and ensure proper HTML opening tag is present
if (file_exists(__DIR__ . '/../../includes/lang_helper.php')) {
    require_once __DIR__ . '/../../includes/lang_helper.php';
    $html_lang_attrs = get_lang_dir_attrs_for_file(__FILE__);
}
?>

<!DOCTYPE html>
<html <?php echo $html_lang_attrs ?? 'lang="en"'; ?> >

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ã‡alÄ±ÅŸma Saatleri - AquaTR</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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





    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ã‡alÄ±ÅŸma Saatleri - AquaTR</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">



    <!-- Navbar -->
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-xl font-bold text-blue-600">
                    <i class="fas fa-arrow-left"></i> Panele DÃ¶n
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Ã‡alÄ±ÅŸma Saatleri</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form id="workingHoursForm" action="process_working_hours.php" method="POST" class="bg-white rounded-lg shadow-md p-6">
            <!-- Fixed auto_label_101: ensured valid hidden CSRF input and matching label -->
            <label for="auto_label_101" class="sr-only">CSRF token</label>
            <input type="hidden" id="auto_label_101" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <?php
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $dayNames = [
                'Monday' => 'Pazartesi',
                'Tuesday' => 'SalÄ±',
                'Wednesday' => 'Ã‡arÅŸamba',
                'Thursday' => 'PerÅŸembe',
                'Friday' => 'Cuma',
                'Saturday' => 'Cumartesi',
                'Sunday' => 'Pazar'
            ];

            foreach ($days as $day):
                $isOpen = isset($hours[$day]) && $hours[$day]['is_open'];
                $openTime = isset($hours[$day]) ? $hours[$day]['open_time'] : '09:00';
                $closeTime = isset($hours[$day]) ? $hours[$day]['close_time'] : '18:00';
                // create safe, unique ids for inputs per day
                $dayKey = strtolower(str_replace(' ', '_', $day));
                $chkId = 'is_open_' . $dayKey;
                $openId = 'open_time_' . $dayKey;
                $closeId = 'close_time_' . $dayKey;
            ?>
                <div class="mb-6 border-b pb-4 last:border-0">
                    <div class="flex items-center justify-between mb-4">
                        <label class="text-lg font-medium text-gray-700" for="<?php echo $chkId; ?>">
                            <?php echo $dayNames[$day]; ?>
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <!-- Fixed auto_label_100: descriptive sr-only label and matching id -->
                            <label for="<?php echo $chkId; ?>" class="sr-only">Open toggle for <?php echo $dayNames[$day]; ?></label>
                            <input type="checkbox" id="<?php echo $chkId; ?>" name="is_open[<?php echo $day; ?>]" class="sr-only peer" __php_block_12__>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 
                                     peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full 
                                     peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] 
                                     after:left-[2px] after:bg-white after:border-gray-300 after:border 
                                     after:rounded-full after:h-5 after:w-5 after:transition-all 
                                     peer-checked:bg-blue-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">AÃ§Ä±k</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="<?php echo $openId; ?>">AÃ§Ä±lÄ±ÅŸ</label>
                            <!-- Fixed auto_label_99: open time input has matching id -->
                            <label for="<?php echo $openId; ?>" class="sr-only">Open time for <?php echo $dayNames[$day]; ?></label>
                            <input type="time" id="<?php echo $openId; ?>" name="open_time[<?php echo $day; ?>]" value="<?php echo $openTime; ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                          focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="<?php echo $closeId; ?>">KapanÄ±ÅŸ</label>
                            <!-- Fixed auto_label_98: close time input has matching id -->
                            <label for="<?php echo $closeId; ?>" class="sr-only">Close time for <?php echo $dayNames[$day]; ?></label>
                            <input type="time" id="<?php echo $closeId; ?>" name="close_time[<?php echo $day; ?>]" value="<?php echo $closeTime; ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
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
                        alert(data.error || 'Bir hata oluÅŸtu.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluÅŸtu.');
                });
        });
    </script>









