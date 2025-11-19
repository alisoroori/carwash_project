<?php
/**
 * Test Service Loading Diagnostic
 * Simulates a carwash user session and calls get.php to verify services load
 */

session_start();

// Simulate a logged-in carwash user with user_id = 27 (from our DB check)
$_SESSION['user_id'] = 27;
$_SESSION['role'] = 'carwash';
// Note: we don't set carwash_id directly; let get.php resolve it from user_id

echo "<h2>Service Loading Diagnostic Test</h2>\n";
echo "<h3>Step 1: Session State</h3>\n";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "carwash_id: " . ($_SESSION['carwash_id'] ?? 'NOT SET (will be resolved)') . "\n";
echo "</pre>\n";

echo "<h3>Step 2: Call get.php via include (server-side)</h3>\n";
echo "<pre>";

// Capture output from get.php
ob_start();
include __DIR__ . '/backend/api/services/get.php';
$output = ob_get_clean();

echo "Raw output from get.php:\n";
echo htmlspecialchars($output);
echo "\n\n";

$json = json_decode($output, true);
if ($json) {
    echo "Parsed JSON:\n";
    print_r($json);
    
    if (isset($json['success']) && $json['success'] === true) {
        echo "\n✅ SUCCESS: get.php returned success=true\n";
        echo "Service count: " . count($json['data'] ?? []) . "\n";
        if (!empty($json['data'])) {
            echo "\nFirst service:\n";
            print_r($json['data'][0]);
        }
    } else {
        echo "\n❌ FAILED: get.php returned success=false or missing\n";
        echo "Error: " . ($json['error'] ?? 'unknown') . "\n";
    }
} else {
    echo "❌ JSON parsing failed. Check raw output above.\n";
}

echo "</pre>\n";

echo "<h3>Step 3: Check PHP error log</h3>\n";
echo "<p>Check <code>logs/app.log</code> or PHP error log for entries from services/get.php</p>\n";

echo "<h3>Step 4: Frontend Test</h3>\n";
echo "<p>Open browser console (F12) when viewing Car_Wash_Dashboard.php and look for '[loadServices]' logs.</p>\n";
echo "<button onclick='testFetch()'>Test Fetch from Browser</button>\n";
echo "<div id='result' style='margin-top:1rem; padding:1rem; border:1px solid #ccc; background:#f9f9f9;'></div>\n";

echo "<script>
async function testFetch() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = 'Fetching...';
    try {
        const resp = await fetch('/carwash_project/backend/api/services/get.php', { credentials: 'same-origin' });
        const json = await resp.json();
        resultDiv.innerHTML = '<strong>Status:</strong> ' + resp.status + '<br><strong>JSON:</strong><pre>' + JSON.stringify(json, null, 2) + '</pre>';
        console.log('Browser fetch result:', json);
    } catch (e) {
        resultDiv.innerHTML = '<strong style=\"color:red;\">Error:</strong> ' + e.message;
        console.error('Browser fetch error:', e);
    }
}
</script>\n";
?>
