<?php
/**
 * Secure File Upload Form
 */
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Session;

// Start session and check authentication
Session::start();
$auth = new Auth();
$auth->requireRole(['admin', 'car_wash_manager']);

// Generate CSRF token
$csrf_token = Session::generateCsrfToken();

// Get service list
$db = \App\Classes\Database::getInstance();
$services = $db->fetchAll("SELECT id, name, category FROM services ORDER BY name");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ø¢Ù¾Ù„ÙˆØ¯ ØªØµÙˆÛŒØ± Ø®Ø¯Ù…Ø§Øª</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/style.css">
</head>
<body class="admin-panel">
    <div class="container">
        <div class="panel">
            <h1>Ø¢Ù¾Ù„ÙˆØ¯ ØªØµÙˆÛŒØ± Ø®Ø¯Ù…Ø§Øª</h1>
            
            <div class="upload-form">
                <form id="uploadForm" action="service_image_upload.php" method="post" enctype="multipart/form-data">
                    <!-- CSRF Protection -->
                    <label for="auto_label_96" class="sr-only">Csrf token</label><label for="auto_label_96" class="sr-only">Csrf token</label><input type="hidden" name="csrf_token" value="<?= $csrf_token "\>" id="auto_label_96">">
                    
                    <div class="form-group">
                        <label for="service_id">Ø§Ù†ØªØ®Ø§Ø¨ Ø®Ø¯Ù…Øª:</label>
                        <select name="service_id" id="service_id" required>
                            <option value="\>Ø§Ù†ØªØ®Ø§Ø¨ Ú©Ù†ÛŒØ¯</option>
                            <?php foreach ($services as $service): ?>
                            <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Ø¯Ø³ØªÙ‡â€ŒØ¨Ù†Ø¯ÛŒ:</label>
                        <select name="category" id="category">
                            <option value="general">Ø¹Ù…ÙˆÙ…ÛŒ</option>
                            <option value="exterior">Ø®Ø¯Ù…Ø§Øª Ø®Ø§Ø±Ø¬ÛŒ</option>
                            <option value="interior">Ø®Ø¯Ù…Ø§Øª Ø¯Ø§Ø®Ù„ÛŒ</option>
                            <option value="premium">Ø®Ø¯Ù…Ø§Øª ÙˆÛŒÚ˜Ù‡</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_image">Ø§Ù†ØªØ®Ø§Ø¨ ØªØµÙˆÛŒØ±:</label>
                        <input type="file" name="service_image" id="service_image" required accept="image/jpeg,image/png,image/webp">
                        <p class="hint">ÙØ±Ù…Øªâ€ŒÙ‡Ø§ÛŒ Ù…Ø¬Ø§Ø²: JPG, PNG, WEBP - Ø­Ø¯Ø§Ú©Ø«Ø± Ø­Ø¬Ù…: 5MB</p>
                    </div>
                    
                    <div class="preview-container">
                        <img id="imagePreview" src="#" alt="Ù¾ÛŒØ´â€ŒÙ†Ù…Ø§ÛŒØ´" style="display: none; max-width: 300px;">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Ø¢Ù¾Ù„ÙˆØ¯ ØªØµÙˆÛŒØ±</button>
                        <a href="services.php" class="btn btn-secondary">Ø¨Ø§Ø²Ú¯Ø´Øª</a>
                    </div>
                </form>
                
                <div id="uploadStatus" class="alert" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <script>
    // Image preview functionality
    document.getElementById('service_image').addEventListener('change', function(e) {
        const file = this.files[0];
        const preview = document.getElementById('imagePreview');
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        // Reset
        preview.style.display = 'none';
        document.getElementById('uploadStatus').style.display = 'none';
        
        // Validate file type
        if (file && allowedTypes.includes(file.type)) {
            // Validate file size
            if (file.size <= maxSize) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                showError('Ø­Ø¬Ù… ÙØ§ÛŒÙ„ Ø¨ÛŒØ´ØªØ± Ø§Ø² 5 Ù…Ú¯Ø§Ø¨Ø§ÛŒØª Ø§Ø³Øª');
            }
        } else {
            showError('Ù„Ø·ÙØ§Ù‹ ÛŒÚ© ØªØµÙˆÛŒØ± Ù…Ø¹ØªØ¨Ø± Ø¨Ø§ ÙØ±Ù…Øª JPGØŒ PNG ÛŒØ§ WEBP Ø§Ù†ØªØ®Ø§Ø¨ Ú©Ù†ÛŒØ¯');
        }
    });
    
    // Form submission
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const statusElement = document.getElementById('uploadStatus');
        
        statusElement.className = 'alert';
        statusElement.textContent = 'Ø¯Ø± Ø­Ø§Ù„ Ø¢Ù¾Ù„ÙˆØ¯...';
        statusElement.style.display = 'block';
        
        fetch('service_image_upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusElement.className = 'alert success';
                statusElement.textContent = data.message;
                
                // Reset form after successful upload
                setTimeout(() => {
                    document.getElementById('uploadForm').reset();
                    document.getElementById('imagePreview').style.display = 'none';
                }, 2000);
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            showError('Ø®Ø·Ø§ Ø¯Ø± Ø§Ø±ØªØ¨Ø§Ø· Ø¨Ø§ Ø³Ø±ÙˆØ±');
            console.error('Upload error:', error);
        });
    });
    
    function showError(message) {
        const statusElement = document.getElementById('uploadStatus');
        statusElement.className = 'alert error';
        statusElement.textContent = message;
        statusElement.style.display = 'block';
    }
    </script>
</body>
</html>



