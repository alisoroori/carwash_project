<?php
// Database table structure checker
require_once __DIR__ . '/../includes/db.php';

try {
    $conn = getDBConnection();
    
    echo "<h2>🔍 Database Table Structure Analysis</h2>";
    
    // Check carwashes table structure
    echo "<h3>carwashes table columns:</h3>";
    $stmt = $conn->query("DESCRIBE carwashes");
    $carwash_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($carwash_columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>users table columns:</h3>";
    $stmt = $conn->query("DESCRIBE users");
    $user_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($user_columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if business_name exists in any form
    echo "<h3>Column name analysis:</h3>";
    $business_name_exists = false;
    $name_exists = false;
    
    foreach ($carwash_columns as $column) {
        if ($column['Field'] === 'business_name') {
            $business_name_exists = true;
        }
        if ($column['Field'] === 'name') {
            $name_exists = true;
        }
    }
    
    echo "<ul>";
    echo "<li>business_name column exists: " . ($business_name_exists ? "✅ YES" : "❌ NO") . "</li>";
    echo "<li>name column exists: " . ($name_exists ? "✅ YES" : "❌ NO") . "</li>";
    echo "</ul>";
    
    if (!$business_name_exists && $name_exists) {
        echo "<div style='background: #fbbf24; color: #92400e; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<strong>💡 Solution Found:</strong> The table uses 'name' column instead of 'business_name'";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
