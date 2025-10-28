<?php
// Automatic Schema Fix Application
require_once __DIR__ . '/../includes/db.php';

echo "<h2>🔧 Applying Database Schema Fixes</h2>";

try {
    $conn = getDBConnection();
    
    echo "<h3>Step 1: Adding missing columns to users table...</h3>";
    
    // Add username column if it doesn't exist
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE");
        echo "<p>✅ Added username column</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p>ℹ️ username column already exists</p>";
        } else {
            echo "<p>⚠️ " . $e->getMessage() . "</p>";
        }
    }
    
    // Add full_name column if it doesn't exist
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(100)");
        echo "<p>✅ Added full_name column</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p>ℹ️ full_name column already exists</p>";
        } else {
            echo "<p>⚠️ " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Step 2: Adding missing columns to carwash table...</h3>";
    
    $carwash_columns = [
        'user_id INT',
        'tax_number VARCHAR(50)',
        'license_number VARCHAR(50)',
        'owner_name VARCHAR(100)',
        'owner_id VARCHAR(11)',
        'owner_phone VARCHAR(20)',
        'birth_date DATE',
        'city VARCHAR(50)',
        'district VARCHAR(100)',
        'exterior_price DECIMAL(10,2) DEFAULT 0',
        'interior_price DECIMAL(10,2) DEFAULT 0',
        'detailing_price DECIMAL(10,2) DEFAULT 0',
        'opening_time TIME',
        'closing_time TIME',
        'capacity INT',
        'description TEXT',
        'profile_image VARCHAR(255)',
        'logo_image VARCHAR(255)'
    ];
    
    foreach ($carwash_columns as $column_def) {
        $column_name = explode(' ', $column_def)[0];
        try {
            $conn->exec("ALTER TABLE carwash ADD COLUMN $column_def");
            echo "<p>✅ Added $column_name column</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p>ℹ️ $column_name column already exists</p>";
            } else {
                echo "<p>⚠️ Error adding $column_name: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3>Step 3: Adding foreign key constraint...</h3>";
    
    try {
        $conn->exec("ALTER TABLE carwash ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
        echo "<p>✅ Added foreign key constraint</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p>ℹ️ Foreign key constraint already exists</p>";
        } else {
            echo "<p>⚠️ " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Step 4: Updating status column...</h3>";
    
    try {
        $conn->exec("ALTER TABLE carwash MODIFY COLUMN status ENUM('pending', 'active', 'inactive', 'suspended') DEFAULT 'pending'");
        echo "<p>✅ Updated status column to include 'pending' and 'suspended'</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>✅ Schema Fix Complete!</h3>";
    echo "<div style='background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<strong>Success!</strong> The database schema has been updated to support the full car wash registration form.";
    echo "</div>";
    
    echo "<h3>Testing the updated structure:</h3>";
    
    // Show the updated table structures
    echo "<h4>Users table columns:</h4>";
    $stmt = $conn->query("DESCRIBE users");
    $user_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($user_columns as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";
    
    echo "<h4>Carwash table columns:</h4>";
    $stmt = $conn->query("DESCRIBE carwash");
    $carwash_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($carwash_columns as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎯 Next Steps:</h3>";
echo "<p>1. <a href='Car_Wash_Registration.php'>Test the Car Wash Registration Form</a></p>";
echo "<p>2. <a href='Car_Wash_Registration_process_debug.php'>Use Debug Version</a> if you encounter issues</p>";
?>
