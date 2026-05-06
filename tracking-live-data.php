<?php
require_once 'includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

function respondTrackingData($payload, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($payload);
    exit();
}

if (!isAuthenticated()) {
    respondTrackingData([
        'success' => false,
        'message' => 'Please log in before opening live tracking.'
    ], 401);
}

if (!canManageTrackedLocations()) {
    respondTrackingData([
        'success' => false,
        'message' => 'Only manufacturers and distributors can access live tracking data.'
    ], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respondTrackingData([
        'success' => false,
        'message' => 'Only GET requests are supported for tracking data.'
    ], 405);
}

try {
    $payload = getTrackingRefreshPayload(
        $conn,
        trim((string) ($_GET['product_id'] ?? '')),
        (int) $_SESSION['user_id'],
        (string) ($_SESSION['user_type'] ?? 'member'),
        90
    );

    respondTrackingData([
        'success' => true,
        'stats' => $payload['stats'],
        'selected_product_id' => $payload['selected_product_id'],
        'selected_product' => $payload['selected_product'],
        'latest_location' => $payload['latest_location'],
        'location_history' => $payload['location_history'],
        'tracked_products' => $payload['tracked_products']
    ]);
} catch (Exception $e) {
    respondTrackingData([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}
?>
