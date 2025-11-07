<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Database;

try {
    $db = Database::getInstance();
    
    // Check if columns exist in users table
    $columnsToCheck = ['home_phone', 'national_id', 'driver_license', 'profile_image'];
    $existingColumns = [];
    $missingColumns = [];
    
    foreach ($columnsToCheck as $column) {
        $result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'users' 
             AND COLUMN_NAME = :column",
            ['column' => $column]
        );
        
        if ($result['count'] > 0) {
            $existingColumns[] = $column;
        } else {
            $missingColumns[] = $column;
        }
    }
    
    $allExist = count($missingColumns) === 0;
    
    echo json_encode([
        'success' => $allExist,
        'message' => $allExist ? 
            'All required columns exist in users table' : 
            'Missing columns: ' . implode(', ', $missingColumns),
        'data' => [
            'existing' => $existingColumns,
            'missing' => $missingColumns
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error checking database: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>