<?php
// Database Schema Fix Script
require_once __DIR__ . '/../includes/db.php';

echo "<h2>🔧 Fixing Database Schema for Car Wash Registration</h2>";

try {
    $conn = getDBConnection();
    
    echo "<h3>Step 1: Checking current table structure...</h3>";
    
    // Check if carwash table exists
    try {
        $stmt = $conn->query("DESCRIBE carwash");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>✅ carwash table exists with columns: " . implode(', ', $columns) . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ carwash table does not exist</p>";
    }
    
    // Check if users table has required columns
    try {
        $stmt = $conn->query("DESCRIBE users");
        $user_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $has_username = false;
        $has_full_name = false;
        $has_password = false;
        
        foreach ($user_columns as $col) {
            if ($col['Field'] === 'username') $has_username = true;
            if ($col['Field'] === 'full_name') $has_full_name = true;
            if ($col['Field'] === 'password') $has_password = true;
        }
        
        echo "<h3>Step 2: Users table analysis:</h3>";
        echo "<ul>";
        echo "<li>username column: " . ($has_username ? "✅ exists" : "❌ missing") . "</li>";
        echo "<li>full_name column: " . ($has_full_name ? "✅ exists" : "❌ missing") . "</li>";
        echo "<li>password column: " . ($has_password ? "✅ exists" : "❌ missing") . "</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error checking users table: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Step 3: Applying schema fixes...</h3>";
    
    // Add missing columns to users table
    try {
        if (!$has_username) {
            $conn->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE");
            echo "<p>✅ Added username column to users table</p>";
        }
        
        if (!$has_full_name) {
            $conn->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(100)");
            echo "<p>✅ Added full_name column to users table</p>";
        }
        
        // Check if password column exists, if not rename password_hash
        if (!$has_password) {
            $conn->exec("ALTER TABLE users CHANGE COLUMN password_hash password VARCHAR(255)");
            echo "<p>✅ Renamed password_hash to password in users table</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>⚠️ Note: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Step 4: Checking carwash table columns...</h3>";
    
    // Check if carwash table has all required columns
    $required_carwash_columns = [
        'user_id', 'name', 'email', 'phone', 'tax_number', 'license_number',
        'owner_name', 'owner_id', 'owner_phone', 'birth_date', 'city', 'district',
        'address', 'exterior_price', 'interior_price', 'detailing_price',
        'opening_time', 'closing_time', 'capacity', 'description',
        'profile_image', 'logo_image', 'status'
    ];
    
    try {
        $stmt = $conn->query("DESCRIBE carwash");
        $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p><strong>Existing columns:</strong> " . implode(', ', $existing_columns) . "</p>";
        echo "<p><strong>Required columns:</strong> " . implode(', ', $required_carwash_columns) . "</p>";
        
        $missing_columns = array_diff($required_carwash_columns, $existing_columns);
        
        if (!empty($missing_columns)) {
            echo "<p>❌ Missing columns: " . implode(', ', $missing_columns) . "</p>";
            echo "<div style='background: #fbbf24; color: #92400e; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
            echo "<strong>⚠️ Action Required:</strong> The carwash table needs to be updated with additional columns.";
            echo "</div>";
            
            // Provide SQL to update the table
            echo "<h3>SQL to fix carwash table:</h3>";
            echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";
            echo "-- Add missing columns to carwash table\n";
            
            $column_definitions = [
                'user_id' => 'INT',
                'tax_number' => 'VARCHAR(50)',
                'license_number' => 'VARCHAR(50)',
                'owner_name' => 'VARCHAR(100)',
                'owner_id' => 'VARCHAR(11)',
                'owner_phone' => 'VARCHAR(20)',
                'birth_date' => 'DATE',
                'city' => 'VARCHAR(50)',
                'district' => 'VARCHAR(100)',
                'exterior_price' => 'DECIMAL(10,2) DEFAULT 0',
                'interior_price' => 'DECIMAL(10,2) DEFAULT 0',
                'detailing_price' => 'DECIMAL(10,2) DEFAULT 0',
                'opening_time' => 'TIME',
                'closing_time' => 'TIME',
                'capacity' => 'INT',
                'description' => 'TEXT',
                'profile_image' => 'VARCHAR(255)',
                'logo_image' => 'VARCHAR(255)'
            ];
            
            foreach ($missing_columns as $col) {
                if (isset($column_definitions[$col])) {
                    echo "ALTER TABLE carwash ADD COLUMN $col " . $column_definitions[$col] . ";\n";
                }
            }
            
            echo "-- Add foreign key constraint\n";
            echo "ALTER TABLE carwash ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;\n";
            echo "</textarea>";
            
        } else {
            echo "<p>✅ All required columns exist in carwash table</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error checking carwash table: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>✅ Schema Analysis Complete</h3>";
    echo "<p>If there are missing columns, you can either:</p>";
    echo "<ul>";
    echo "<li>1. Run the SQL commands shown above in phpMyAdmin</li>";
    echo "<li>2. Or <a href='apply_schema_fix.php'>click here to apply fixes automatically</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='Car_Wash_Registration.php'>← Back to Registration Form</a></p>";
?>
