<?php
// filepath: c:\xampp\htdocs\carwash_project\migrations\run_migrations.php

/**
 * CarWash Project Migration Runner
 * 
 * This script runs all migration files in sequential order
 */

// Define constants
define('MIGRATION_PATH', __DIR__);
define('MIGRATION_TRACKER', __DIR__ . '/migration_tracker.json');

// Database connection
require_once __DIR__ . '/../backend/includes/db.php';

// Functions
function getMigrationFiles(): array {
    $files = glob(MIGRATION_PATH . '/*.sql');
    $migrations = [];
    
    foreach ($files as $file) {
        $filename = basename($file);
        if (preg_match('/^(\d+)_(.+)\.sql$/', $filename, $matches)) {
            $number = (int)$matches[1];
            $migrations[$number] = [
                'number' => $number,
                'name' => $matches[2],
                'filename' => $filename,
                'path' => $file
            ];
        }
    }
    
    ksort($migrations);
    return $migrations;
}

function getCompletedMigrations(): array {
    if (!file_exists(MIGRATION_TRACKER)) {
        return [];
    }
    
    $data = json_decode(file_get_contents(MIGRATION_TRACKER), true);
    return is_array($data) ? $data : [];
}

function saveMigrationStatus(array $completed): void {
    file_put_contents(MIGRATION_TRACKER, json_encode($completed, JSON_PRETTY_PRINT));
}

function runMigration(array $migration, $conn): bool {
    echo "Running migration {$migration['number']}: {$migration['name']}... ";
    
    $sql = file_get_contents($migration['path']);
    $result = mysqli_multi_query($conn, $sql);
    
    if (!$result) {
        echo "FAILED: " . mysqli_error($conn) . PHP_EOL;
        return false;
    }
    
    // Clear results to allow next query
    while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
        // Consume and discard results
        $dummyResult = mysqli_store_result($conn);
        if ($dummyResult) {
            mysqli_free_result($dummyResult);
        }
    }
    
    echo "SUCCESS" . PHP_EOL;
    return true;
}

// Main execution
echo "=== CarWash Database Migration Tool ===" . PHP_EOL;
echo "Starting migrations..." . PHP_EOL;

$migrations = getMigrationFiles();
$completedMigrations = getCompletedMigrations();

if (empty($migrations)) {
    echo "No migration files found." . PHP_EOL;
    exit(1);
}

echo "Found " . count($migrations) . " migration file(s)." . PHP_EOL;

$anyFailed = false;
foreach ($migrations as $migration) {
    $migrationId = $migration['number'];
    
    // Skip if already completed
    if (isset($completedMigrations[$migrationId])) {
        echo "Migration {$migrationId}: {$migration['name']} already applied. Skipping." . PHP_EOL;
        continue;
    }
    
    // Run the migration
    $success = runMigration($migration, $conn);
    
    if ($success) {
        $completedMigrations[$migrationId] = [
            'number' => $migrationId,
            'name' => $migration['name'],
            'applied_at' => date('Y-m-d H:i:s')
        ];
        saveMigrationStatus($completedMigrations);
    } else {
        $anyFailed = true;
        break;
    }
}

if (!$anyFailed) {
    echo "All migrations completed successfully." . PHP_EOL;
} else {
    echo "Migration process stopped due to errors." . PHP_EOL;
}

mysqli_close($conn);
