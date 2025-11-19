<?php
// Safe migration helper: ensure `carwashes` table has expected columns.
// Run from project root: php tools\migrations\add_carwashes_columns.php

require_once __DIR__ . '/../../backend/includes/bootstrap.php';
use App\Classes\Database;

echo "Starting carwashes schema fix...\n";
$db = Database::getInstance();
$pdo = $db->getPdo();
$schema = $pdo->query('SELECT DATABASE()')->fetchColumn();
if (!$schema) {
    echo "Could not determine database name. Check configuration.\n";
    exit(1);
}

// Check if carwashes table exists
$tblCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = 'carwashes'");
$tblCheck->execute(['db' => $schema]);
$hasCarwashes = (bool)$tblCheck->fetchColumn();

try {
    if (!$hasCarwashes) {
        echo "Table `carwashes` does not exist. Creating minimal `carwashes` table...\n";
        $createSql = <<<SQL
CREATE TABLE IF NOT EXISTS `carwashes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mobile_phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `social_media` json DEFAULT NULL,
  `working_hours` json DEFAULT NULL,
  `services` json DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL;
        $pdo->exec($createSql);
        echo "Created `carwashes` table.\n";
        $hasCarwashes = true;
    } else {
        echo "Table `carwashes` exists.\n";
    }

    // Columns we'd like to ensure exist
    $columnsToEnsure = [
        'logo_path' => "VARCHAR(255) DEFAULT NULL",
        'mobile_phone' => "VARCHAR(50) DEFAULT NULL",
        'social_media' => "JSON DEFAULT NULL",
        'working_hours' => "JSON DEFAULT NULL",
        'services' => "JSON DEFAULT NULL",
        'rating' => "DECIMAL(3,2) DEFAULT NULL",
        'status' => "VARCHAR(50) DEFAULT NULL"
    ];

    foreach ($columnsToEnsure as $col => $definition) {
        $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = :col");
        $colCheck->execute(['db' => $schema, 'col' => $col]);
        $exists = (bool)$colCheck->fetchColumn();
        if ($exists) {
            echo "Column `$col` already exists — skipping.\n";
            continue;
        }
        echo "Adding column `$col` ($definition) to `carwashes`...\n";
        $sql = "ALTER TABLE `carwashes` ADD COLUMN `$col` $definition";
        $pdo->exec($sql);
        echo "Added `$col`.\n";
    }

    // Ensure postal_code exists (some installs use zip_code) and certificate_path for uploads
    $extra = [
        'postal_code' => "VARCHAR(20) DEFAULT NULL",
        'certificate_path' => "VARCHAR(255) DEFAULT NULL"
    ];
    foreach ($extra as $col => $definition) {
        $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = :col");
        $colCheck->execute(['db' => $schema, 'col' => $col]);
        $exists = (bool)$colCheck->fetchColumn();
        if ($exists) {
            echo "Column `$col` already exists — skipping.\n";
            continue;
        }
        echo "Adding column `$col` ($definition) to `carwashes`...\n";
        $sql = "ALTER TABLE `carwashes` ADD COLUMN `$col` $definition";
        $pdo->exec($sql);
        echo "Added `$col`.\n";
    }

    // If zip_code exists, copy values into postal_code where postal_code is NULL
    $zipCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'carwashes' AND COLUMN_NAME = 'zip_code'");
    $zipCheck->execute(['db' => $schema]);
    if ((int)$zipCheck->fetchColumn() > 0) {
        echo "Copying zip_code -> postal_code where applicable...\n";
        $copy = $pdo->exec("UPDATE `carwashes` SET postal_code = zip_code WHERE postal_code IS NULL AND zip_code IS NOT NULL");
        echo "Copied rows: " . ($copy === false ? '0' : $copy) . "\n";
    }

    echo "Schema fix completed successfully.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;
