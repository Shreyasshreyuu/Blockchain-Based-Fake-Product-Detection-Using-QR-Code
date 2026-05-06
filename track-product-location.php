<?php
require_once 'includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

function respondLocationJson($payload, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($payload);
    exit();
}

if (!isAuthenticated()) {
    respondLocationJson([
        'success' => false,
        'message' => 'Please log in before updating product locations.'
    ], 401);
}

if (!canManageTrackedLocations()) {
    respondLocationJson([
        'success' => false,
        'message' => 'Only manufacturers and distributors can update live product locations.'
    ], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondLocationJson([
        'success' => false,
        'message' => 'Only POST requests are supported for location tracking.'
    ], 405);
}

$payload = [];
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($content_type, 'application/json') !== false) {
    $payload = json_decode((string) file_get_contents('php://input'), true);
} else {
    $payload = $_POST;
}

$product_id = trim((string) ($payload['product_id'] ?? ''));

if ($product_id === '') {
    respondLocationJson([
        'success' => false,
        'message' => 'A product ID is required before saving a live location update.'
    ], 422);
}

try {
    $latest_location = saveProductLocationPing(
        $conn,
        $product_id,
        (int) $_SESSION['user_id'],
        (string) ($_SESSION['user_type'] ?? ''),
        array_merge((array) $payload, [
            'tracked_by_name' => $_SESSION['username'] ?? 'Operator'
        ])
    );

    $product = getTrackableProductForUser(
        $conn,
        $product_id,
        (int) $_SESSION['user_id'],
        (string) ($_SESSION['user_type'] ?? '')
    );

    respondLocationJson([
        'success' => true,
        'message' => 'Live location saved successfully.',
        'product' => $product ? [
            'product_id' => $product['product_id'],
            'product_name' => $product['product_name'],
            'brand' => $product['brand'],
            'category' => $product['category']
        ] : null,
        'latest_location' => $latest_location,
        'location_history' => getProductLocationHistory($conn, $product_id, 5)
    ]);
} catch (Exception $e) {
    respondLocationJson([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}
?>
