<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\classes\quick_schema_check.php

/**
 * Quick Schema Check for CarWash Project
 * 
 * This script verifies that all required tables and columns exist
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Database;

class SchemaChecker {
    private $db;
    private $errors = [];
    private $warnings = [];
    
    // Required tables and their essential columns
    private $requiredSchema = [
        'users' => [
            'id', 'email', 'password', 'full_name', 'role', 'status', 'created_at'
        ],
        'user_profiles' => [
            'id', 'user_id', 'address', 'city'
        ],
        'carwash_profiles' => [
            'id', 'user_id', 'business_name', 'address', 'city'
        ],
        'vehicles' => [
            'id', 'user_id', 'brand', 'model'
        ],
        'service_categories' => [
            'id', 'name', 'description'
        ],
        'services' => [
            'id', 'carwash_id', 'category_id', 'name', 'price', 'duration_minutes'
        ],
        'time_slots' => [
            'id', 'carwash_id', 'day_of_week', 'start_time', 'end_time'
        ],
        'bookings' => [
            'id', 'booking_number', 'user_id', 'carwash_id', 'booking_date', 'booking_time', 'status'
        ],
        'booking_services' => [
            'id', 'booking_id', 'service_id', 'price'
        ],
        'payments' => [
            'id', 'booking_id', 'amount', 'payment_method', 'status'
        ],
        'promotions' => [
            'id', 'carwash_id', 'name', 'discount_type', 'discount_value'
        ],
        'reviews' => [
            'id', 'booking_id', 'user_id', 'carwash_id', 'rating'
        ],
        'notifications' => [
            'id', 'user_id', 'title', 'message', 'type'
        ]
    ];
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function checkSchema() {
        // Get all tables
        $tables = $this->getTables();
        
        // Check each required table
        foreach ($this->requiredSchema as $tableName => $requiredColumns) {
            $this->checkTable($tableName, $requiredColumns, $tables);
        }
        
        // Check admin user
        $this->checkAdminUser();
        
        // Output results
        $this->outputResults();
    }
    
    private function getTables() {
        $query = "SHOW TABLES";
        $tables = $this->db->fetchAll($query);
        
        $tableList = [];
        foreach ($tables as $table) {
            $tableName = reset($table); // Get first (and only) value
            $tableList[] = $tableName;
        }
        
        return $tableList;
    }
    
    private function checkTable($tableName, $requiredColumns, $existingTables) {
        if (!in_array($tableName, $existingTables)) {
            $this->errors[] = "Table '{$tableName}' does not exist";
            return;
        }
        
        // Table exists, check columns
        $columns = $this->getColumns($tableName);
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                $this->errors[] = "Column '{$column}' missing from table '{$tableName}'";
            }
        }
    }
    
    private function getColumns($tableName) {
        $query = "SHOW COLUMNS FROM {$tableName}";
        $columns = $this->db->fetchAll($query);
        
        $columnList = [];
        foreach ($columns as $column) {
            $columnList[] = $column['Field'];
        }
        
        return $columnList;
    }
    
    private function checkAdminUser() {
        $query = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
        $result = $this->db->fetchOne($query);
        
        if (!$result || $result['admin_count'] == 0) {
            $this->warnings[] = "No admin user found. Please run migrations to create the default admin account.";
        }
    }
    
    private function outputResults() {
        echo "=== CarWash Database Schema Check ===\n\n";
        
        if (empty($this->errors) && empty($this->warnings)) {
            echo "✅ SUCCESS: Database schema is complete and valid.\n";
            echo "✅ All required tables and columns are present.\n";
            echo "✅ Admin user exists.\n";
        } else {
            if (!empty($this->errors)) {
                echo "❌ ERRORS: " . count($this->errors) . "\n";
                foreach ($this->errors as $index => $error) {
                    echo " {$index}. {$error}\n";
                }
                echo "\n";
            }
            
            if (!empty($this->warnings)) {
                echo "⚠️ WARNINGS: " . count($this->warnings) . "\n";
                foreach ($this->warnings as $index => $warning) {
                    echo " {$index}. {$warning}\n";
                }
                echo "\n";
            }
            
            if (!empty($this->errors)) {
                echo "Please run the migrations to fix these issues:\n";
                echo "php migrations/run_migrations.php\n";
            }
        }
        
        echo "\nCheck completed at: " . date('Y-m-d H:i:s') . "\n";
    }
}

// Run the check
$checker = new SchemaChecker();
$checker->checkSchema();