<?php
// Runner that includes the diagnostic script with a test_user_id and returns a compact JSON summary
parse_str('test_user_id=14', $_GET);
ob_start();
include 'c:/xampp/htdocs/carwash_project/backend/dashboard/test_customer_dashboard.php';
ob_end_clean();
// $checks and $coreQueryResults and $sessionDump should be available
$out = [
    'mode' => $mode ?? null,
    'testedUserId' => $testedUserId ?? null,
    'dbInfo' => $dbInfo ?? null,
    'checks' => $checks ?? [],
    'core' => $coreQueryResults ?? [],
];
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT);
