<?php
// Diagnostic script to check what's being output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PHP Include Test ===\n\n";

// Test 1: Check bootstrap.php
echo "Test 1: Loading bootstrap.php...\n";
ob_start();
try {
    require_once __DIR__ . '/backend/includes/bootstrap.php';
    $bootstrap_output = ob_get_clean();
    if (empty($bootstrap_output)) {
        echo "✅ bootstrap.php: No output\n";
    } else {
        echo "❌ bootstrap.php outputs: " . strlen($bootstrap_output) . " bytes\n";
        echo "First 200 chars: " . substr($bootstrap_output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ bootstrap.php error: " . $e->getMessage() . "\n";
}

// Test 2: Check db.php
echo "\nTest 2: Loading db.php...\n";
ob_start();
try {
    require_once __DIR__ . '/backend/includes/db.php';
    $db_output = ob_get_clean();
    if (empty($db_output)) {
        echo "✅ db.php: No output\n";
    } else {
        echo "❌ db.php outputs: " . strlen($db_output) . " bytes\n";
        echo "First 200 chars: " . substr($db_output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ db.php error: " . $e->getMessage() . "\n";
}

// Test 3: Check session start
echo "\nTest 3: Starting session...\n";
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$session_output = ob_get_clean();
if (empty($session_output)) {
    echo "✅ session_start(): No output\n";
} else {
    echo "❌ session_start() outputs: " . strlen($session_output) . " bytes\n";
}

// Test 4: Try to output JSON
echo "\nTest 4: JSON output test...\n";
ob_start();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'message' => 'Test successful']);
$json_output = ob_get_clean();
echo "JSON output: " . $json_output . "\n";

echo "\n=== All Tests Complete ===\n";
