<?php
require_once 'includes/config.php';
requireAuth();
requireTrackingManager();

$user_id = (int) $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'member';
$selected_product_id = trim($_GET['product'] ?? $_GET['product_id'] ?? '');
$prompt_capture = isset($_GET['capture']) && $_GET['capture'] === '1';
$capture_success = isset($_GET['updated']) && $_GET['updated'] === '1';
$tracking_stats = getLocationTrackingStats($conn, $user_id, $user_type);
$tracked_products = getTrackableProductsOverview($conn, $user_id, $user_type, 90);
$selected_product = null;
$selected_latest_location = null;
$selected_history = [];

if ($selected_product_id !== '') {
    $selected_product = getTrackableProductForUser($conn, $selected_product_id, $user_id, $user_type);
}

if (!$selected_product && !empty($tracked_products)) {
    $selected_product_id = $tracked_products[0]['product_id'];
    $selected_product = getTrackableProductForUser($conn, $selected_product_id, $user_id, $user_type);
}

if ($selected_product) {
    $selected_latest_location = getLatestProductLocation($conn, $selected_product['product_id']);
    $selected_history = getProductLocationHistory($conn, $selected_product['product_id'], 8);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="page-shell stack">
        <?php if ($capture_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-circle-check"></i>
                <div>Live location captured successfully. The map and tracking history are now updated for this product.</div>
            </div>
        <?php endif; ?>

        <section class="page-topper">
            <p class="eyebrow" style="color: var(--primary-deep); background: rgba(20, 86, 240, 0.08);">
                <i class="fas fa-location-crosshairs"></i> Live product tracking
            </p>
            <h1 class="page-title">Monitor the freshest QR-linked location update for every protected product.</h1>
            <p class="page-subtitle">
                Manufacturers and distributors can capture field position from the smart scanner, then review the latest coordinates, update freshness, and history from one clean workspace.
            </p>
        </section>

        <section class="metric-grid">
            <article class="card metric-card">
                <span class="stat-kicker">Tracked products</span>
                <strong class="metric-value" id="trackingMetricTrackedProducts"><?php echo (int) $tracking_stats['tracked_products']; ?></strong>
                <p class="mb-0" id="trackingMetricCoverage"><?php echo (int) $tracking_stats['coverage_rate']; ?>% of visible products have at least one live location update.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Total location pings</span>
                <strong class="metric-value" id="trackingMetricTotalPings"><?php echo (int) $tracking_stats['total_pings']; ?></strong>
                <p class="mb-0">Every authorized location update captured across your current scope.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Fresh in 24h</span>
                <strong class="metric-value" id="trackingMetricFreshUpdates"><?php echo (int) $tracking_stats['fresh_updates']; ?></strong>
                <p class="mb-0">Recent pings that still reflect live movement or stock handoff activity.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Latest update</span>
                <strong class="metric-value" style="font-size: 1.45rem;" id="trackingMetricLatestPing"><?php echo htmlspecialchars($tracking_stats['latest_ping_formatted']); ?></strong>
                <p class="mb-0">Newest location event visible to your account.</p>
            </article>
        </section>

        <section class="grid-2">
            <article class="card tracking-focus-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Focused product view</h2>
                        <p>Open any product from the right-hand list to inspect its latest location and recent movement history.</p>
                    </div>
                    <div class="quick-chips">
                        <?php if ($selected_product): ?>
                            <span class="pill pill-info" id="trackingSelectedProductBadge"><?php echo htmlspecialchars($selected_product['product_id']); ?></span>
                        <?php endif; ?>
                        <span class="pill pill-success" id="trackingAutoRefreshBadge"><span class="status-dot"></span> Auto refresh on</span>
                    </div>
                </div>

                <?php if ($selected_product): ?>
                    <div class="stack" style="gap: 18px;">
                        <div class="tracking-focus-hero">
                            <div>
                                <h3 class="card-title" id="trackingSelectedProductName"><?php echo htmlspecialchars($selected_product['product_name']); ?></h3>
                                <p class="mb-0" id="trackingSelectedProductMeta"><?php echo htmlspecialchars($selected_product['brand']); ?> &middot; <?php echo htmlspecialchars($selected_product['category']); ?></p>
                            </div>
                            <div class="quick-chips" id="trackingSelectedStatusChips">
                                <span class="pill pill-success"><?php echo htmlspecialchars(ucfirst($user_type)); ?> access</span>
                                <?php if ($selected_latest_location): ?>
                                    <span class="pill pill-info"><?php echo htmlspecialchars($selected_latest_location['relative_time']); ?></span>
                                <?php else: ?>
                                    <span class="pill pill-warning">No live location yet</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card tracking-action-card">
                            <div class="section-header">
                                <div>
                                    <h3 class="card-title">Capture or refresh live location</h3>
                                    <p class="helper-note">Manufacturers can start tracking immediately after QR generation, and distributors can refresh the same product during delivery.</p>
                                </div>
                                <span class="pill pill-info"><?php echo htmlspecialchars(ucfirst($user_type)); ?> live tracking</span>
                            </div>

                            <div class="field">
                                <label for="trackingLocationLabel">Location label</label>
                                <input id="trackingLocationLabel" class="search-input" type="text" placeholder="Factory gate / Delivery hub / Retail store">
                                <small>This label helps the manufacturer understand where the product was last seen.</small>
                            </div>

                            <div class="action-row" style="margin-top: 16px;">
                                <button type="button" class="btn btn-primary" id="captureLiveLocation" data-product-id="<?php echo htmlspecialchars($selected_product['product_id']); ?>">
                                    <i class="fas fa-location-crosshairs"></i>
                                    <span><?php echo $selected_latest_location ? 'Refresh live location' : 'Capture first live location'; ?></span>
                                </button>
                                <button type="button" class="btn btn-ghost" id="copyTrackingProductId" data-copy-text="<?php echo htmlspecialchars($selected_product['product_id']); ?>" data-copy-success="Product ID copied to clipboard.">
                                    <i class="fas fa-copy"></i>
                                    <span>Copy product ID</span>
                                </button>
                                <a href="scan-qr.php?product=<?php echo urlencode($selected_product['product_id']); ?>" class="btn btn-info">
                                    <i class="fas fa-camera-retro"></i>
                                    <span>Open smart scan</span>
                                </a>
                            </div>
                        </div>

                        <div id="trackingSelectedLiveState">
                            <?php if ($selected_latest_location): ?>
                                <div class="detail-grid tracking-detail-grid">
                                    <div class="detail-card">
                                        <strong>Coordinates</strong>
                                        <span class="detail-value proof-code"><?php echo htmlspecialchars($selected_latest_location['coordinates_label']); ?></span>
                                    </div>
                                    <div class="detail-card">
                                        <strong>Recorded</strong>
                                        <span class="detail-value"><?php echo htmlspecialchars($selected_latest_location['recorded_at_formatted']); ?></span>
                                    </div>
                                    <div class="detail-card">
                                        <strong>Operator</strong>
                                        <span class="detail-value"><?php echo htmlspecialchars($selected_latest_location['tracker_label']); ?></span>
                                    </div>
                                    <div class="detail-card">
                                        <strong>Accuracy</strong>
                                        <span class="detail-value"><?php echo htmlspecialchars($selected_latest_location['accuracy_label']); ?></span>
                                    </div>
                                    <div class="detail-card">
                                        <strong>Source</strong>
                                        <span class="detail-value"><?php echo htmlspecialchars($selected_latest_location['source_label']); ?></span>
                                    </div>
                                    <div class="detail-card">
                                        <strong>Label</strong>
                                        <span class="detail-value"><?php echo htmlspecialchars($selected_latest_location['location_label']); ?></span>
                                    </div>
                                </div>

                                <div class="action-row">
                                    <?php if (!empty($selected_latest_location['google_maps_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($selected_latest_location['google_maps_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                            <i class="fas fa-map-location-dot"></i>
                                            <span>Open in Google Maps</span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($selected_latest_location['openstreetmap_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($selected_latest_location['openstreetmap_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">
                                            <i class="fas fa-map"></i>
                                            <span>Open in OpenStreetMap</span>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-ghost btn-sm" data-copy-text="<?php echo htmlspecialchars($selected_latest_location['coordinates_label']); ?>" data-copy-success="Coordinates copied to clipboard.">
                                        <i class="fas fa-copy"></i>
                                        <span>Copy coordinates</span>
                                    </button>
                                    <a href="scan-qr.php?product=<?php echo urlencode($selected_product['product_id']); ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-camera-retro"></i>
                                        <span>Update from scanner</span>
                                    </a>
                                </div>

                                <?php if (!empty($selected_latest_location['google_maps_embed_url'])): ?>
                                    <div class="tracking-map-shell">
                                        <iframe
                                            src="<?php echo htmlspecialchars($selected_latest_location['google_maps_embed_url'] ?? ''); ?>"
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade"
                                            title="Tracked product map"
                                        ></iframe>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($selected_history)): ?>
                                    <div class="tracking-history">
                                        <h3 class="card-title">Recent location history</h3>
                                        <div class="timeline" style="margin-top: 14px;">
                                            <?php foreach ($selected_history as $location): ?>
                                                <div class="timeline-item">
                                                    <span class="timeline-dot"></span>
                                                    <div class="timeline-card">
                                                        <div class="panel-header">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($location['location_label']); ?></strong>
                                                                <p><?php echo htmlspecialchars($location['tracker_label']); ?> &middot; <?php echo htmlspecialchars($location['accuracy_label']); ?></p>
                                                            </div>
                                                            <span class="pill pill-info"><?php echo htmlspecialchars($location['relative_time']); ?></span>
                                                        </div>
                                                        <p class="helper-note mb-0">
                                                            <?php echo htmlspecialchars($location['coordinates_label']); ?> &middot; <?php echo htmlspecialchars($location['recorded_at_formatted']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-location-dot"></i>
                                    <strong>No location updates yet for this product.</strong>
                                    <p class="mb-0">Use the capture button above to save the first live location instantly, or open Smart Scan to update it during delivery.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-location-crosshairs"></i>
                        <strong>No products available for live tracking.</strong>
                        <p class="mb-0">Generate a product first, then use the scanner to share authorized location updates.</p>
                    </div>
                <?php endif; ?>
            </article>

            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Tracked product list</h2>
                        <p>Search by product name, brand, product ID, or manufacturer to jump to the product you need.</p>
                    </div>
                </div>

                <div class="filters-row" style="margin-bottom: 18px;">
                    <div class="search-wrap">
                        <input type="text" id="trackingSearch" class="search-input" placeholder="Search tracked products">
                    </div>
                </div>

                <?php if (!empty($tracked_products)): ?>
                    <div class="tracking-product-list" id="trackingProductList">
                        <?php foreach ($tracked_products as $product): ?>
                            <?php
                            $search_blob = strtolower(
                                implode(' ', [
                                    $product['product_name'],
                                    $product['brand'],
                                    $product['product_id'],
                                    $product['manufacturer_name'] ?? ''
                                ])
                            );
                            ?>
                            <article class="tracking-product-card" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>" data-search="<?php echo htmlspecialchars($search_blob); ?>">
                                <div class="tracking-product-top">
                                    <div>
                                        <h3 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                        <p class="helper-note mb-0"><?php echo htmlspecialchars($product['brand']); ?> &middot; <?php echo htmlspecialchars($product['product_id']); ?></p>
                                    </div>
                                    <span class="pill pill-<?php echo !empty($product['latest_location']) ? 'success' : 'warning'; ?>">
                                        <?php echo !empty($product['latest_location']) ? htmlspecialchars($product['latest_location']['relative_time']) : 'No live ping'; ?>
                                    </span>
                                </div>

                                <div class="mini-grid" style="margin-top: 14px;">
                                    <div class="mini-stat">
                                        <span class="stat-kicker">Manufacturer</span>
                                        <strong class="number-value" style="font-size: 1rem;"><?php echo htmlspecialchars($product['manufacturer_name'] ?: 'Unknown'); ?></strong>
                                    </div>
                                    <div class="mini-stat">
                                        <span class="stat-kicker">Location pings</span>
                                        <strong class="number-value"><?php echo (int) $product['location_pings']; ?></strong>
                                    </div>
                                </div>

                                <?php if (!empty($product['latest_location'])): ?>
                                    <div class="tracking-snippet">
                                        <strong><?php echo htmlspecialchars($product['latest_location']['coordinates_label']); ?></strong>
                                        <span><?php echo htmlspecialchars($product['latest_location']['accuracy_label']); ?> &middot; <?php echo htmlspecialchars($product['latest_location']['tracker_label']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="tracking-snippet">
                                        <strong>Tracking ready</strong>
                                        <span>This product has no location ping yet. Use the scanner to capture the first live update.</span>
                                    </div>
                                <?php endif; ?>

                                <div class="action-row" style="margin-top: 14px;">
                                    <a href="tracking.php?product=<?php echo urlencode($product['product_id']); ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-satellite-dish"></i>
                                        <span>Open tracker</span>
                                    </a>
                                    <a href="scan-qr.php?product=<?php echo urlencode($product['product_id']); ?>" class="btn btn-ghost btn-sm">
                                        <i class="fas fa-camera-retro"></i>
                                        <span>Update from scan</span>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-location-arrow"></i>
                        <strong>No trackable products found.</strong>
                        <p class="mb-0">Manufacturers can create products first, and distributors can start tracking once a QR-linked product exists.</p>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </main>

    <div class="toast-stack" id="toastStack" aria-live="polite"></div>

    <script>
        const trackingSearch = document.getElementById('trackingSearch');
        let trackingCards = Array.from(document.querySelectorAll('#trackingProductList .tracking-product-card'));
        const trackingProductList = document.getElementById('trackingProductList');
        const trackingLiveState = document.getElementById('trackingSelectedLiveState');
        const trackingSelectedStatusChips = document.getElementById('trackingSelectedStatusChips');
        const trackingSelectedProductBadge = document.getElementById('trackingSelectedProductBadge');
        const trackingSelectedProductName = document.getElementById('trackingSelectedProductName');
        const trackingSelectedProductMeta = document.getElementById('trackingSelectedProductMeta');
        const trackingMetricTrackedProducts = document.getElementById('trackingMetricTrackedProducts');
        const trackingMetricCoverage = document.getElementById('trackingMetricCoverage');
        const trackingMetricTotalPings = document.getElementById('trackingMetricTotalPings');
        const trackingMetricFreshUpdates = document.getElementById('trackingMetricFreshUpdates');
        const trackingMetricLatestPing = document.getElementById('trackingMetricLatestPing');
        const toastStack = document.getElementById('toastStack');
        const captureButton = document.getElementById('captureLiveLocation');
        const locationLabelInput = document.getElementById('trackingLocationLabel');
        const selectedProductId = <?php echo json_encode($selected_product['product_id'] ?? ''); ?>;
        const selectedUserRoleLabel = <?php echo json_encode(ucfirst($user_type)); ?>;
        const shouldPromptCapture = <?php echo json_encode($prompt_capture && $selected_product_id !== '' && !$selected_latest_location); ?>;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function pushToast(message, tone = 'info') {
            if (!toastStack) {
                return;
            }

            const toast = document.createElement('div');
            toast.className = `toast ${tone}`;
            toast.textContent = message;
            toastStack.appendChild(toast);

            window.setTimeout(() => {
                toast.remove();
            }, 2600);
        }

        function attachCopyHandlers(scope = document) {
            scope.querySelectorAll('[data-copy-text]').forEach((button) => {
                if (button.dataset.copyBound === 'true') {
                    return;
                }

                button.dataset.copyBound = 'true';
                button.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(String(button.dataset.copyText || ''));
                        pushToast(button.dataset.copySuccess || 'Copied to clipboard.', 'success');
                    } catch (error) {
                        pushToast('Clipboard access is not available on this device.', 'error');
                    }
                });
            });
        }

        function applyTrackingSearch() {
            const searchValue = trackingSearch?.value.trim().toLowerCase() || '';

            trackingCards.forEach((card) => {
                card.style.display = card.dataset.search.includes(searchValue) ? '' : 'none';
            });
        }

        function getGeolocationErrorMessage(error) {
            if (!error || typeof error.code !== 'number') {
                return error?.message || 'We could not capture your live location.';
            }

            if (error.code === 1) {
                return 'Location permission was denied on this device.';
            }

            if (error.code === 2) {
                return 'The browser could not determine the current GPS position.';
            }

            if (error.code === 3) {
                return 'The location request timed out before coordinates were captured.';
            }

            return error.message || 'We could not capture your live location.';
        }

        function renderTrackingLiveState(productId, latestLocation, locationHistory = []) {
            if (!trackingLiveState) {
                return;
            }

            if (!latestLocation) {
                trackingLiveState.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-location-dot"></i>
                        <strong>No location updates yet for this product.</strong>
                        <p class="mb-0">Use the capture button above to save the first live location instantly, or open Smart Scan to update it during delivery.</p>
                    </div>
                `;
                attachCopyHandlers(trackingLiveState);
                return;
            }

            trackingLiveState.innerHTML = `
                <div class="detail-grid tracking-detail-grid">
                    <div class="detail-card">
                        <strong>Coordinates</strong>
                        <span class="detail-value proof-code">${escapeHtml(latestLocation.coordinates_label || 'Coordinates unavailable')}</span>
                    </div>
                    <div class="detail-card">
                        <strong>Recorded</strong>
                        <span class="detail-value">${escapeHtml(latestLocation.recorded_at_formatted || latestLocation.relative_time || 'Just now')}</span>
                    </div>
                    <div class="detail-card">
                        <strong>Operator</strong>
                        <span class="detail-value">${escapeHtml(latestLocation.tracker_label || 'Unknown operator')}</span>
                    </div>
                    <div class="detail-card">
                        <strong>Accuracy</strong>
                        <span class="detail-value">${escapeHtml(latestLocation.accuracy_label || 'Accuracy unavailable')}</span>
                    </div>
                    <div class="detail-card">
                        <strong>Source</strong>
                        <span class="detail-value">${escapeHtml(latestLocation.source_label || 'Tracking page')}</span>
                    </div>
                    <div class="detail-card">
                        <strong>Label</strong>
                        <span class="detail-value">${escapeHtml(latestLocation.location_label || 'Live coordinates captured')}</span>
                    </div>
                </div>

                <div class="action-row">
                    ${latestLocation.google_maps_url ? `
                        <a href="${escapeHtml(latestLocation.google_maps_url)}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                            <i class="fas fa-map-location-dot"></i>
                            <span>Open in Google Maps</span>
                        </a>
                    ` : ''}
                    ${latestLocation.openstreetmap_url ? `
                        <a href="${escapeHtml(latestLocation.openstreetmap_url)}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">
                            <i class="fas fa-map"></i>
                            <span>Open in OpenStreetMap</span>
                        </a>
                    ` : ''}
                    <button type="button" class="btn btn-ghost btn-sm" data-copy-text="${escapeHtml(latestLocation.coordinates_label || '')}" data-copy-success="Coordinates copied to clipboard.">
                        <i class="fas fa-copy"></i>
                        <span>Copy coordinates</span>
                    </button>
                    <a href="scan-qr.php?product=${encodeURIComponent(productId)}" class="btn btn-info btn-sm">
                        <i class="fas fa-camera-retro"></i>
                        <span>Update from scanner</span>
                    </a>
                </div>

                ${latestLocation.google_maps_embed_url ? `
                    <div class="tracking-map-shell">
                        <iframe
                            src="${escapeHtml(latestLocation.google_maps_embed_url)}"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Tracked product map"
                        ></iframe>
                    </div>
                ` : ''}

                ${locationHistory.length ? `
                    <div class="tracking-history">
                        <h3 class="card-title">Recent location history</h3>
                        <div class="timeline" style="margin-top: 14px;">
                            ${locationHistory.map((location) => `
                                <div class="timeline-item">
                                    <span class="timeline-dot"></span>
                                    <div class="timeline-card">
                                        <div class="panel-header">
                                            <div>
                                                <strong>${escapeHtml(location.location_label || 'Live coordinates captured')}</strong>
                                                <p>${escapeHtml(location.tracker_label || 'Unknown operator')} &middot; ${escapeHtml(location.accuracy_label || 'Accuracy unavailable')}</p>
                                            </div>
                                            <span class="pill pill-info">${escapeHtml(location.relative_time || 'Just now')}</span>
                                        </div>
                                        <p class="helper-note mb-0">${escapeHtml(location.coordinates_label || 'Coordinates unavailable')} &middot; ${escapeHtml(location.recorded_at_formatted || '')}</p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            `;

            attachCopyHandlers(trackingLiveState);
        }

        function renderTrackedProductList(products = []) {
            if (!trackingProductList) {
                return;
            }

            trackingProductList.innerHTML = products.map((product) => {
                const latestLocation = product.latest_location || null;
                const searchBlob = [
                    product.product_name || '',
                    product.brand || '',
                    product.product_id || '',
                    product.manufacturer_name || ''
                ].join(' ').toLowerCase();

                return `
                    <article class="tracking-product-card" data-product-id="${escapeHtml(product.product_id || '')}" data-search="${escapeHtml(searchBlob)}">
                        <div class="tracking-product-top">
                            <div>
                                <h3 class="card-title">${escapeHtml(product.product_name || 'Unknown product')}</h3>
                                <p class="helper-note mb-0">${escapeHtml(product.brand || 'Unknown brand')} &middot; ${escapeHtml(product.product_id || '')}</p>
                            </div>
                            <span class="pill pill-${latestLocation ? 'success' : 'warning'}">${escapeHtml(latestLocation?.relative_time || 'No live ping')}</span>
                        </div>

                        <div class="mini-grid" style="margin-top: 14px;">
                            <div class="mini-stat">
                                <span class="stat-kicker">Manufacturer</span>
                                <strong class="number-value" style="font-size: 1rem;">${escapeHtml(product.manufacturer_name || 'Unknown')}</strong>
                            </div>
                            <div class="mini-stat">
                                <span class="stat-kicker">Location pings</span>
                                <strong class="number-value">${escapeHtml(product.location_pings || 0)}</strong>
                            </div>
                        </div>

                        ${latestLocation ? `
                            <div class="tracking-snippet">
                                <strong>${escapeHtml(latestLocation.coordinates_label || 'Coordinates unavailable')}</strong>
                                <span>${escapeHtml(latestLocation.accuracy_label || 'Accuracy unavailable')} &middot; ${escapeHtml(latestLocation.tracker_label || 'Unknown operator')}</span>
                            </div>
                        ` : `
                            <div class="tracking-snippet">
                                <strong>Tracking ready</strong>
                                <span>This product has no location ping yet. Use the scanner or tracker to capture the first live update.</span>
                            </div>
                        `}

                        <div class="action-row" style="margin-top: 14px;">
                            <a href="tracking.php?product=${encodeURIComponent(product.product_id || '')}" class="btn btn-primary btn-sm">
                                <i class="fas fa-satellite-dish"></i>
                                <span>Open tracker</span>
                            </a>
                            <a href="scan-qr.php?product=${encodeURIComponent(product.product_id || '')}" class="btn btn-ghost btn-sm">
                                <i class="fas fa-camera-retro"></i>
                                <span>Update from scan</span>
                            </a>
                        </div>
                    </article>
                `;
            }).join('');

            trackingCards = Array.from(document.querySelectorAll('#trackingProductList .tracking-product-card'));
            applyTrackingSearch();
        }

        function applyTrackingPayload(payload) {
            if (!payload || !payload.success) {
                return;
            }

            const stats = payload.stats || {};
            const selectedProduct = payload.selected_product || null;
            const latestLocation = payload.latest_location || null;
            const locationHistory = Array.isArray(payload.location_history) ? payload.location_history : [];

            if (trackingMetricTrackedProducts) {
                trackingMetricTrackedProducts.textContent = String(stats.tracked_products ?? trackingMetricTrackedProducts.textContent);
            }

            if (trackingMetricCoverage) {
                trackingMetricCoverage.textContent = `${stats.coverage_rate ?? 0}% of visible products have at least one live location update.`;
            }

            if (trackingMetricTotalPings) {
                trackingMetricTotalPings.textContent = String(stats.total_pings ?? trackingMetricTotalPings.textContent);
            }

            if (trackingMetricFreshUpdates) {
                trackingMetricFreshUpdates.textContent = String(stats.fresh_updates ?? trackingMetricFreshUpdates.textContent);
            }

            if (trackingMetricLatestPing) {
                trackingMetricLatestPing.textContent = String(stats.latest_ping_formatted ?? trackingMetricLatestPing.textContent);
            }

            if (selectedProduct) {
                if (trackingSelectedProductBadge) {
                    trackingSelectedProductBadge.textContent = selectedProduct.product_id || '';
                }

                if (trackingSelectedProductName) {
                    trackingSelectedProductName.textContent = selectedProduct.product_name || 'Unknown product';
                }

                if (trackingSelectedProductMeta) {
                    trackingSelectedProductMeta.textContent = `${selectedProduct.brand || 'Unknown brand'} · ${selectedProduct.category || 'Unknown category'}`;
                }

                if (trackingSelectedStatusChips) {
                    trackingSelectedStatusChips.innerHTML = `
                        <span class="pill pill-success">${escapeHtml(selectedUserRoleLabel)} access</span>
                        <span class="pill pill-${latestLocation ? 'info' : 'warning'}">${escapeHtml(latestLocation?.relative_time || 'No live location yet')}</span>
                    `;
                }

                if (captureButton) {
                    const captureLabel = captureButton.querySelector('span');
                    if (captureLabel) {
                        captureLabel.textContent = latestLocation ? 'Refresh live location' : 'Capture first live location';
                    }
                }

                renderTrackingLiveState(selectedProduct.product_id || selectedProductId, latestLocation, locationHistory);
            }

            if (Array.isArray(payload.tracked_products)) {
                renderTrackedProductList(payload.tracked_products);
            }
        }

        async function refreshTrackingData(silent = true) {
            if (!selectedProductId) {
                return;
            }

            try {
                const response = await fetch(`tracking-live-data.php?product_id=${encodeURIComponent(selectedProductId)}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Could not refresh tracking data.');
                }

                applyTrackingPayload(payload);
            } catch (error) {
                if (!silent) {
                    pushToast(error.message || 'Could not refresh tracking data.', 'error');
                }
            }
        }

        async function captureLiveLocation(autoStart = false) {
            if (!captureButton || !selectedProductId) {
                return;
            }

            if (!navigator.geolocation) {
                pushToast('Geolocation is not supported on this device.', 'error');
                return;
            }

            const originalMarkup = captureButton.innerHTML;
            captureButton.disabled = true;
            captureButton.innerHTML = '<i class="fas fa-satellite-dish"></i><span>Capturing location...</span>';

            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        resolve,
                        reject,
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                    );
                });

                const response = await fetch('track-product-location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: selectedProductId,
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy || '',
                        location_label: (locationLabelInput?.value || '').trim() || 'Manufacturer live tracking update',
                        source: autoStart ? 'tracking-autostart' : 'tracking-page',
                        device_label: navigator.platform || navigator.userAgent || 'Browser device'
                    })
                });

                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Failed to save the live location update.');
                }

                pushToast('Live location updated successfully.', 'success');
                await refreshTrackingData(true);

                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.delete('capture');
                nextUrl.searchParams.delete('updated');
                window.history.replaceState({}, '', nextUrl.toString());
            } catch (error) {
                pushToast(getGeolocationErrorMessage(error), 'error');
            } finally {
                captureButton.disabled = false;
                captureButton.innerHTML = originalMarkup;
            }
        }

        attachCopyHandlers(document);
        applyTrackingSearch();

        trackingSearch?.addEventListener('input', applyTrackingSearch);

        captureButton?.addEventListener('click', () => {
            captureLiveLocation(false);
        });

        if (shouldPromptCapture) {
            window.setTimeout(() => {
                pushToast('Allow location access once to start live tracking for this new QR product.', 'info');
                captureLiveLocation(true);
            }, 450);
        }

        if (selectedProductId) {
            window.setInterval(() => {
                refreshTrackingData(true);
            }, 8000);
        }
    </script>
</body>
</html>
