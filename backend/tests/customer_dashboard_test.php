<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';

use App\Classes\Auth;
use App\Classes\Session;
use App\Classes\Database;

// Ensure the user is logged in as a customer
if (!isset($_SESSION['user_id']) || !Auth::hasRole('customer')) {
    // Attempt to log in a test user
    $testEmail = 'test_customer@example.com';
    $testPassword = 'password123';
    $auth = new Auth();
    $loginResult = $auth->login($testEmail, $testPassword);

    if (empty($loginResult['success'])) {
        die('Failed to log in test user. Please ensure the test user exists.');
    }
}

// Fetch CSRF token for form submissions
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// Fetch all vehicle images from the database
$db = Database::getInstance();
$vehicleImages = $db->fetchAll("SELECT id, image_path FROM user_vehicles");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard Test</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; padding: 20px; }
        .debug-panel { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .success { color: #16a34a; }
        .error { color: #dc2626; }
        .warning { color: #f59e0b; }
        .log { margin-bottom: 10px; }
        .log span { display: inline-block; min-width: 100px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Customer Dashboard Test</h1>
    <div class="debug-panel" id="debugPanel">
        <h2>Test Results</h2>
        <div id="results"></div>
    </div>

    <script>
        (async function() {
            const results = document.getElementById('results');

            // Helper to log results
            let totalTests = 0;
            let totalSuccesses = 0;
            let totalErrors = 0;
            let totalWarnings = 0;

            function logResult(type, message) {
                totalTests++;
                if (type === 'success') totalSuccesses++;
                if (type === 'error') totalErrors++;
                if (type === 'warning') totalWarnings++;

                const div = document.createElement('div');
                div.className = `log ${type}`;
                div.innerHTML = `<span>${type.toUpperCase()}:</span> ${message}`;
                results.appendChild(div);
            }

            // Test all forms
            async function testForms() {
                const forms = document.querySelectorAll('form');
                for (const form of forms) {
                    const formData = new FormData(form);
                    formData.append('csrf_token', '<?php echo $csrfToken; ?>');

                    // Test with valid data
                    try {
                        const response = await fetch(form.action, {
                            method: form.method || 'POST',
                            body: formData,
                            credentials: 'same-origin',
                        });
                        const result = await response.json();
                        if (result.success) {
                            logResult('success', `Form ${form.id || form.action} submitted successfully with valid data.`);
                        } else {
                            logResult('error', `Form ${form.id || form.action} failed with valid data: ${result.message || 'Unknown error'}`);
                        }
                    } catch (error) {
                        logResult('error', `Form ${form.id || form.action} submission error with valid data: ${error.message}`);
                    }

                    // Test with invalid data
                    const invalidFormData = new FormData(form);
                    invalidFormData.append('csrf_token', '<?php echo $csrfToken; ?>');
                    invalidFormData.append('invalid_field', 'invalid_value'); // Add an invalid field

                    try {
                        const response = await fetch(form.action, {
                            method: form.method || 'POST',
                            body: invalidFormData,
                            credentials: 'same-origin',
                        });
                        const result = await response.json();
                        if (result.success) {
                            logResult('warning', `Form ${form.id || form.action} unexpectedly succeeded with invalid data.`);
                        } else {
                            logResult('success', `Form ${form.id || form.action} correctly failed with invalid data: ${result.message || 'Validation error'}`);
                        }
                    } catch (error) {
                        logResult('error', `Form ${form.id || form.action} submission error with invalid data: ${error.message}`);
                    }
                }
            }

            // Test all buttons
            function testButtons() {
                const buttons = document.querySelectorAll('button');
                buttons.forEach(button => {
                    if (button.onclick) {
                        try {
                            button.onclick();
                            logResult('success', `Button ${button.innerText || button.id} clicked successfully.`);
                        } catch (error) {
                            logResult('error', `Button ${button.innerText || button.id} click error: ${error.message}`);
                        }
                    } else {
                        logResult('warning', `Button ${button.innerText || button.id} has no click handler.`);
                    }
                });
            }

            // Test all images
            async function testImages() {
                const images = document.querySelectorAll('img');
                for (const img of images) {
                    try {
                        const response = await fetch(img.src, { method: 'HEAD' });
                        if (response.ok) {
                            logResult('success', `Image ${img.src} loaded successfully.`);
                        } else {
                            logResult('error', `Image ${img.src} failed to load.`);
                        }
                    } catch (error) {
                        logResult('error', `Image ${img.src} error: ${error.message}`);
                    }
                }
            }

            // Test AJAX-loaded content
            async function testAjaxContent() {
                try {
                    const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php?action=list', {
                        method: 'GET',
                        credentials: 'same-origin',
                    });
                    const data = await response.json();
                    if (data.success) {
                        logResult('success', 'AJAX content loaded successfully.');
                    } else {
                        logResult('error', `AJAX content load failed: ${data.message || 'Unknown error'}`);
                    }
                } catch (error) {
                    logResult('error', `AJAX content load error: ${error.message}`);
                }
            }

            // Run all tests
            await testForms();
            testButtons();
            await testImages();
            await testAjaxContent();

            // Display overall summary
            const summary = document.createElement('div');
            summary.className = 'log';
            summary.innerHTML = `<strong>Summary:</strong> Total Tests: ${totalTests}, Successes: ${totalSuccesses}, Errors: ${totalErrors}, Warnings: ${totalWarnings}`;
            results.appendChild(summary);

            logResult('success', 'All tests completed.');
        })();
    </script>
</body>
</html>