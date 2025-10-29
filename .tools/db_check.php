<?php
require_once __DIR__ . '/../backend/includes/db.php';
try {
    $pdo = getDBConnection();
    echo "OK: PDO connected to " . DB_NAME . "\n";
    // print driver name
    echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}

// Check mysqli $conn if exists
if (isset($conn)) {
    if ($conn instanceof mysqli) {
        echo "OK: mysqli connected, host=" . DB_HOST . "\n";
    } else {
        echo "conn set but not mysqli\n";
    }
} else {
    echo "No \$conn variable set\n";
}
