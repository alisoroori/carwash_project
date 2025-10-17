<?php
require_once __DIR__ . '/backend/includes/db.php';

try {
    $conn = getDBConnection();
    $stmt = $conn->query('DESCRIBE users');
    
    echo "Users Table Structure:\n";
    echo "====================\n\n";
    
    while($row = $stmt->fetch()) {
        echo "Field: " . $row['Field'] . "\n";
        echo "Type: " . $row['Type'] . "\n";
        echo "Null: " . $row['Null'] . "\n";
        echo "Key: " . $row['Key'] . "\n";
        echo "--------------------\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
