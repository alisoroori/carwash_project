<?php
require __DIR__ . '/../backend/includes/db.php';
try {
    $c = getDBConnection();
    echo "OK" . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    // Also output previous exception if any
    if ($e->getPrevious()) {
        echo "PREV: " . $e->getPrevious()->getMessage() . PHP_EOL;
    }
}
