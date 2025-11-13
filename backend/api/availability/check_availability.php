<?php

require_once '../includes/api_bootstrap.php';


try {
    $api = new AvailabilityAPI($conn);
    $data = json_decode(file_get_contents('php://input'), true);

    // Note: this endpoint is read-only and computes availability based on inputs.
    // It does not change server-side state. CSRF protection is not required for
    // safe GET/POST read-only operations. If this endpoint is later changed to
    // perform state-changing operations, add CSRF validation after session start.

    $availability = $api->checkAvailability(
        $data['carwash_id'],
        $data['service_ids'],
        $data['date']
    );

    echo json_encode([
        'success' => true,
        'availability' => $availability
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
