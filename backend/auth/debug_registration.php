<?php
// Debug script for Car Wash Registration
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Debug Information</h2>";
echo "<p><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>POST Data:</strong></p>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<p><strong>Files Data:</strong></p>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><strong>Server Info:</strong></p>";
echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "</pre>";

// Test database connection
try {
    require_once __DIR__ . '/../includes/db.php';
    $conn = getDBConnection();
    echo "<p><strong>Database Connection:</strong> ✅ Success</p>";
} catch (Exception $e) {
    echo "<p><strong>Database Connection:</strong> ❌ Failed - " . $e->getMessage() . "</p>";
}

// Test if POST request would be processed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p><strong>✅ POST request detected - would be processed</strong></p>";
    
    // Check required fields
    $required_fields = ['business_name', 'email', 'password', 'phone', 'tax_number', 'license_number', 'owner_name', 'owner_id', 'city', 'district', 'address'];
    echo "<p><strong>Required Fields Check:</strong></p>";
    echo "<ul>";
    foreach ($required_fields as $field) {
        $status = !empty($_POST[$field]) ? "✅" : "❌";
        $value = $_POST[$field] ?? 'NOT SET';
        echo "<li>$field: $status $value</li>";
    }
    echo "</ul>";
} else {
    echo "<p><strong>❌ No POST request - this is a GET request</strong></p>";
}

echo "<hr>";
echo "<h3>Test Form</h3>";
echo '<form method="POST" action="debug_registration.php">';
echo '<input type="text" name="business_name" value="Test Business" />';
echo '<input type="email" name="email" value="test@example.com" />';
echo '<input type="password" name="password" value="test123" />';
echo '<input type="tel" name="phone" value="05551234567" />';
echo '<input type="text" name="tax_number" value="1234567890" />';
echo '<input type="text" name="license_number" value="LICENSE123" />';
echo '<input type="text" name="owner_name" value="John Doe" />';
echo '<input type="text" name="owner_id" value="12345678901" />';
echo '<select name="city"><option value="istanbul">İstanbul</option></select>';
echo '<input type="text" name="district" value="Kadıköy" />';
echo '<textarea name="address">Test Address</textarea>';
echo '<input type="checkbox" name="terms" checked />';
echo '<button type="submit">Test Submit</button>';
echo '</form>';
?>