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
echo '<label for="auto_label_68" class="sr-only">Business name</label>';
echo '<input type="text" name="business_name" value="Test Business" id="auto_label_68" placeholder="Business name">';
echo '<label for="auto_label_67" class="sr-only">Email</label>';
echo '<input type="email" name="email" value="test@example.com" id="auto_label_67" placeholder="Email">';
echo '<label for="auto_label_66" class="sr-only">Password</label>';
echo '<input type="password" name="password" value="test123" id="auto_label_66" placeholder="Password">';
echo '<label for="auto_label_65" class="sr-only">Phone</label>';
echo '<input type="tel" name="phone" value="05551234567" id="auto_label_65" placeholder="Phone">';
echo '<label for="auto_label_64" class="sr-only">Tax number</label>';
echo '<input type="text" name="tax_number" value="1234567890" id="auto_label_64" placeholder="Tax number">';
echo '<label for="auto_label_63" class="sr-only">License number</label>';
echo '<input type="text" name="license_number" value="LICENSE123" id="auto_label_63" placeholder="License number">';
echo '<label for="auto_label_62" class="sr-only">Owner name</label>';
echo '<input type="text" name="owner_name" value="John Doe" id="auto_label_62" placeholder="Owner name">';
echo '<label for="auto_label_61" class="sr-only">Owner id</label>';
echo '<input type="text" name="owner_id" value="12345678901" id="auto_label_61" placeholder="Owner id">';
echo '<label for="auto_label_60" class="sr-only">City</label>';
echo '<select name="city" id="auto_label_60"><option value="istanbul">İstanbul</option></select>';
echo '<label for="auto_label_59" class="sr-only">District</label>';
echo '<input type="text" name="district" value="Kadıköy" id="auto_label_59" placeholder="District">';
echo '<label for="auto_label_58" class="sr-only">Address</label>';
echo '<textarea name="address" id="auto_label_58">Test Address</textarea>';
echo '<label for="auto_label_57" class="sr-only">Terms</label>';
echo '<input type="checkbox" name="terms" checked id="auto_label_57" />';
echo '<button type="submit">Test Submit</button>';
echo '</form>';
?>


