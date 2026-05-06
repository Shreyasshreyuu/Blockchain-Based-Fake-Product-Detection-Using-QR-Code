<?php
require_once 'includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

function respondJson($payload) {
    echo json_encode($payload);
    exit();
}

function extractProductIdFromQr($qr_data) {
    $qr_data = trim((string) $qr_data);

    if ($qr_data === '') {
        return '';
    }

    $parsed = json_decode($qr_data, true);

    if (is_array($parsed) && !empty($parsed['product_id'])) {
        return trim((string) $parsed['product_id']);
    }

    if (preg_match('/(PRD\d+)/', $qr_data, $matches)) {
        return $matches[1];
    }

    return $qr_data;
}

$qr_data = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($content_type, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $qr_data = $input['qr_data'] ?? '';
    } else {
        $qr_data = $_POST['qr_data'] ?? '';
    }
} else {
    $qr_data = $_GET['qr_data'] ?? '';
}

$product_id = extractProductIdFromQr($qr_data);

if ($product_id === '') {
    respondJson([
        'success' => false,
        'status' => 'ERROR',
        'message' => 'No QR data or product ID was provided.',
        'verification_time' => date('Y-m-d H:i:s'),
        'verification_time_formatted' => formatDateTimeReadable(date('Y-m-d H:i:s'))
    ]);
}

try {
    $product = fetchOneRow(
        $conn,
        "SELECT p.*, u.username AS manufacturer_name
         FROM products p
         LEFT JOIN users u ON u.id = p.manufacturer_id
         WHERE p.product_id = ?",
        's',
        [$product_id]
    );

    if (!$product) {
        respondJson([
            'success' => false,
            'status' => 'FAKE',
            'message' => 'This product ID is not registered in the system.',
            'product_id' => $product_id,
            'trust_score' => 8,
            'risk_label' => 'High risk',
            'risk_tone' => 'danger',
            'freshness_label' => 'Unknown batch',
            'freshness_tone' => 'warning',
            'highlights' => [
                'No matching product record was found.',
                'No blockchain history is available for this ID.',
                'Treat the item as suspicious until the manufacturer confirms it.'
            ],
            'recommendation' => 'Do not accept this product without a valid registered QR code and manufacturer confirmation.',
            'blockchain_count' => 0,
            'proof_summary' => [
                'latest' => null,
                'origin' => null,
                'verification' => null,
                'real_chain_entry_count' => 0,
                'real_chain_record_count' => 0,
                'real_chain_confirmed' => false
            ],
            'can_update_location' => false,
            'latest_location' => null,
            'location_history' => [],
            'verification_time' => date('Y-m-d H:i:s'),
            'verification_time_formatted' => formatDateTimeReadable(date('Y-m-d H:i:s'))
        ]);
    }

    $can_update_location = false;
    $latest_location = null;
    $location_history = [];

    if (isAuthenticated() && canManageTrackedLocations()) {
        $trackable_product = getTrackableProductForUser(
            $conn,
            $product_id,
            (int) $_SESSION['user_id'],
            (string) ($_SESSION['user_type'] ?? '')
        );

        if ($trackable_product) {
            $can_update_location = true;
            $latest_location = getLatestProductLocation($conn, $product_id);
            $location_history = getProductLocationHistory($conn, $product_id, 5);
        }
    }

    if (isset($_SESSION['user_id'])) {
        addToBlockchain($conn, $product_id, [
            'action' => 'PRODUCT_VERIFIED',
            'verifier_id' => (int) $_SESSION['user_id'],
            'verifier_name' => $_SESSION['username'] ?? 'Unknown',
            'status' => 'GENUINE',
            'timestamp' => time()
        ]);
    }

    $insights = buildVerificationInsights($conn, $product);
    $verification_time = date('Y-m-d H:i:s');

    $product['manufacturer_date_formatted'] = formatDateReadable($product['manufacturer_date']);
    $product['expiry_date_formatted'] = formatDateReadable($product['expiry_date']);

    respondJson([
        'success' => true,
        'status' => $insights['status'],
        'message' => $insights['status'] === 'GENUINE'
            ? 'Product verified successfully with blockchain evidence.'
            : 'Product record was found, but blockchain confidence is too weak.',
        'product' => $product,
        'blockchain_count' => $insights['ledger_entries'],
        'creation_entries' => $insights['creation_entries'],
        'verification_checks' => $insights['verification_checks'],
        'blockchain_details' => $insights['details'],
        'proof_summary' => $insights['proof_summary'],
        'can_update_location' => $can_update_location,
        'latest_location' => $latest_location,
        'location_history' => $location_history,
        'trust_score' => $insights['trust_score'],
        'risk_label' => $insights['risk']['label'],
        'risk_tone' => $insights['risk']['tone'],
        'freshness_label' => $insights['freshness']['label'],
        'freshness_tone' => $insights['freshness']['tone'],
        'highlights' => $insights['highlights'],
        'recommendation' => $insights['recommendation'],
        'product_age_days' => $insights['product_age_days'],
        'is_expired' => $insights['is_expired'],
        'scanned_by' => $_SESSION['username'] ?? 'Guest',
        'verification_time' => $verification_time,
        'verification_time_formatted' => formatDateTimeReadable($verification_time)
    ]);
} catch (Exception $e) {
    error_log('Verification error: ' . $e->getMessage());

    respondJson([
        'success' => false,
        'status' => 'ERROR',
        'message' => 'Verification error: ' . $e->getMessage(),
        'verification_time' => date('Y-m-d H:i:s'),
        'verification_time_formatted' => formatDateTimeReadable(date('Y-m-d H:i:s'))
    ]);
}
?>
