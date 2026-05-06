<?php
require_once 'includes/config.php';
requireAuth();

$quick_products = getQuickScanProducts($conn, 6);
$prefill_product = trim($_GET['product'] ?? $_GET['product_id'] ?? '');
$blockchain_status = getRealBlockchainHealth();
$can_manage_tracking = canManageTrackedLocations();
$tracking_role_label = ucfirst($_SESSION['user_type'] ?? 'member');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Scan - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="page-shell stack">
        <section class="hero">
            <div class="hero-grid">
                <div>
                    <p class="eyebrow"><i class="fas fa-camera-retro"></i> Smart verification studio</p>
                    <h1>Scan, upload, or search a product ID and get a richer trust verdict.</h1>
                    <p>
                        The scanner now combines live camera reading, image upload, manual lookup, trust scoring, expiry context, and recent device history in one polished flow.
                    </p>

                    <div class="quick-chips" style="margin-top: 22px;">
                        <?php if ($blockchain_status): ?>
                            <span class="pill pill-success">Real blockchain active</span>
                            <span class="pill pill-info">Contract live on local chain</span>
                        <?php else: ?>
                            <span class="pill pill-warning">Fallback ledger mode</span>
                        <?php endif; ?>
                        <?php if ($can_manage_tracking): ?>
                            <span class="pill pill-info"><?php echo htmlspecialchars($tracking_role_label); ?> live tracking enabled</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$blockchain_status && isRealBlockchainEnabled()): ?>
                        <p class="subtle-copy" style="margin-top: 14px;">
                            For live on-chain scan evidence, start the local blockchain node, deploy the contract, and run the blockchain API bridge.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <small>Verification modes</small>
                        <strong>Camera, upload, and manual</strong>
                    </div>
                    <div class="hero-stat">
                        <small>Result depth</small>
                        <strong>Risk, freshness, ledger trail</strong>
                    </div>
                    <div class="hero-stat">
                        <small>Best for</small>
                        <strong>Retail checks and field validation</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="scan-layout">
            <div class="stack">
                <article class="card">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">Live camera scanner</h2>
                            <p>Use your camera for instant verification or switch to image/manual mode below.</p>
                        </div>
                        <span class="pill pill-info"><span class="status-dot"></span> Camera and upload ready</span>
                    </div>

                    <div class="scanner-stage">
                        <div id="qr-reader"></div>
                    </div>

                    <div class="scanner-controls" style="margin-top: 16px;">
                        <button id="startScanner" type="button" class="btn btn-success">
                            <i class="fas fa-play"></i>
                            <span>Start scanner</span>
                        </button>
                        <button id="stopScanner" type="button" class="btn btn-danger" disabled>
                            <i class="fas fa-stop"></i>
                            <span>Stop</span>
                        </button>
                        <button id="switchCamera" type="button" class="btn btn-ghost">
                            <i class="fas fa-rotate"></i>
                            <span>Switch camera</span>
                        </button>
                    </div>

                    <p class="scanner-hint mb-0">Tip: if a product page sends you here automatically, the verifier can check the product ID without turning on the camera.</p>
                </article>

                <article class="card">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">Upload or manual lookup</h2>
                            <p>Drag in a QR image or type a product ID when scanning is not convenient.</p>
                        </div>
                    </div>

                    <label class="upload-drop" id="uploadDrop">
                        <i class="fas fa-cloud-arrow-up" style="font-size: 42px; color: var(--primary);"></i>
                        <strong>Drop a QR image here or click to choose a file</strong>
                        <span class="helper-note">Supported image formats from your phone or desktop.</span>
                        <input type="file" id="fileInput" class="hidden-input" accept="image/*">
                    </label>

                    <div id="uploadPreview" class="upload-preview" style="display: none;">
                        <strong id="fileName">Selected image</strong>
                        <img id="previewImage" alt="QR preview">
                        <div class="action-row" style="margin-top: 14px;">
                            <button type="button" id="processFile" class="btn btn-primary">
                                <i class="fas fa-magnifying-glass"></i>
                                <span>Verify uploaded image</span>
                            </button>
                            <button type="button" id="clearFile" class="btn btn-ghost">
                                <i class="fas fa-trash"></i>
                                <span>Clear</span>
                            </button>
                        </div>
                    </div>

                    <div class="field" style="margin-top: 22px;">
                        <label for="manualProductId">Manual product lookup</label>
                        <div class="action-row">
                            <input id="manualProductId" class="search-input" type="text" placeholder="Enter a product ID like PRD123456789">
                            <button type="button" id="manualVerify" class="btn btn-primary">
                                <i class="fas fa-bolt"></i>
                                <span>Verify now</span>
                            </button>
                        </div>
                        <small>Useful when you already know the product code from packaging or internal records.</small>
                    </div>

                    <?php if (!empty($quick_products)): ?>
                        <div class="field" style="margin-top: 22px;">
                            <label>Quick verify recent products</label>
                            <div class="quick-chips">
                                <?php foreach ($quick_products as $product): ?>
                                    <button
                                        type="button"
                                        class="quick-chip quick-product"
                                        data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>"
                                    >
                                        <?php echo htmlspecialchars($product['product_id']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>
            </div>

            <div class="stack">
                <article class="card" id="scanResult">
                    <div class="result-placeholder">
                        <i class="fas fa-shield-heart"></i>
                        <strong>Verification results will appear here</strong>
                        <p class="mb-0">Scan or verify a product ID to see trust score, risk level, blockchain events, and next-step guidance.</p>
                    </div>
                </article>

                <article class="card">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">Recent scans on this device</h2>
                            <p>Quickly reopen products you verified during this session.</p>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm" id="clearHistory">
                            <i class="fas fa-broom"></i>
                            <span>Clear history</span>
                        </button>
                    </div>

                    <div class="recent-scans" id="recentScansList">
                        <div class="helper-note">No local scans yet.</div>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <div class="toast-stack" id="toastStack"></div>

    <script>
        let html5QrCode = null;
        let cameras = [];
        let currentCameraIndex = 0;
        let isScanning = false;
        let selectedFile = null;
        const scanHistoryKey = 'productsecure_recent_scans';
        const prefillProduct = <?php echo json_encode($prefill_product); ?>;
        const canManageTracking = <?php echo json_encode($can_manage_tracking); ?>;
        const trackingRoleLabel = <?php echo json_encode($tracking_role_label); ?>;

        const resultContainer = document.getElementById('scanResult');
        const manualInput = document.getElementById('manualProductId');
        const fileInput = document.getElementById('fileInput');
        const uploadDrop = document.getElementById('uploadDrop');
        const uploadPreview = document.getElementById('uploadPreview');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function pushToast(message, tone = 'info') {
            const stack = document.getElementById('toastStack');
            const toast = document.createElement('div');
            toast.className = `toast ${tone}`;
            toast.textContent = message;
            stack.appendChild(toast);

            setTimeout(() => toast.remove(), 2800);
        }

        async function copyToClipboard(value, successMessage = 'Copied to clipboard.') {
            if (!value) {
                pushToast('Nothing is available to copy here.', 'info');
                return;
            }

            try {
                await navigator.clipboard.writeText(String(value));
                pushToast(successMessage, 'success');
            } catch (error) {
                pushToast('Clipboard access is not available on this device.', 'error');
            }
        }

        function attachCopyHandlers(scope = document) {
            scope.querySelectorAll('[data-copy-text]').forEach((button) => {
                if (button.dataset.copyBound === 'true') {
                    return;
                }

                button.dataset.copyBound = 'true';
                button.addEventListener('click', () => {
                    copyToClipboard(
                        button.dataset.copyText || '',
                        button.dataset.copySuccess || 'Copied to clipboard.'
                    );
                });
            });
        }

        function buildProofKey(entry) {
            return entry?.proof?.transaction_hash || `${entry?.action || 'proof'}-${entry?.timestamp || ''}`;
        }

        function renderProofCard(entry, title, caption = '') {
            if (!entry || !entry.proof || !entry.proof.transaction_hash) {
                return '';
            }

            const proof = entry.proof;
            const proofTone = proof.is_real ? 'success' : 'warning';
            const proofLabel = proof.is_real ? 'Real on-chain proof' : 'Fallback ledger proof';

            return `
                <article class="card proof-card">
                    <div class="section-header">
                        <div>
                            <h3 class="card-title">${escapeHtml(title)}</h3>
                            <p class="helper-note">${escapeHtml(caption || entry.summary || '')}</p>
                        </div>
                        <span class="helper-note">${escapeHtml(entry.timestamp_formatted || entry.timestamp || '')}</span>
                    </div>
                    <div class="quick-chips" style="margin-bottom: 14px;">
                        <span class="pill pill-${proofTone}">${escapeHtml(proofLabel)}</span>
                        ${proof.chain_id ? `<span class="pill pill-info">Chain ${escapeHtml(proof.chain_id)}</span>` : ''}
                        ${proof.block_number ? `<span class="pill pill-info">Block ${escapeHtml(proof.block_number)}</span>` : ''}
                    </div>
                    <div class="detail-grid proof-detail-grid">
                        <div class="detail-card">
                            <strong>Action</strong>
                            <span class="detail-value">${escapeHtml(entry.action_label || entry.action || 'Blockchain activity')}</span>
                        </div>
                        <div class="detail-card">
                            <strong>Transaction hash</strong>
                            <span class="detail-value proof-code">${escapeHtml(proof.transaction_hash_short || proof.transaction_hash || 'N/A')}</span>
                        </div>
                        <div class="detail-card">
                            <strong>Contract</strong>
                            <span class="detail-value proof-code">${escapeHtml(proof.contract_address_short || proof.contract_address || 'N/A')}</span>
                        </div>
                        <div class="detail-card">
                            <strong>Payload hash</strong>
                            <span class="detail-value proof-code">${escapeHtml(proof.payload_hash_short || proof.payload_hash || 'N/A')}</span>
                        </div>
                    </div>
                    <div class="action-row" style="margin-top: 14px;">
                        <button type="button" class="btn btn-ghost btn-sm" data-copy-text="${escapeHtml(proof.transaction_hash || '')}" data-copy-success="Transaction hash copied to clipboard.">
                            <i class="fas fa-copy"></i>
                            <span>Copy tx hash</span>
                        </button>
                        ${proof.contract_address ? `
                            <button type="button" class="btn btn-ghost btn-sm" data-copy-text="${escapeHtml(proof.contract_address)}" data-copy-success="Contract address copied to clipboard.">
                                <i class="fas fa-diagram-project"></i>
                                <span>Copy contract</span>
                            </button>
                        ` : ''}
                    </div>
                </article>
            `;
        }

        function getGeolocationErrorMessage(error) {
            if (!error || typeof error.code !== 'number') {
                return error?.message || 'We could not capture your device location.';
            }

            if (error.code === 1) {
                return 'Location permission was denied on this device.';
            }

            if (error.code === 2) {
                return 'The device could not determine its GPS position.';
            }

            if (error.code === 3) {
                return 'The location request timed out before coordinates were captured.';
            }

            return error.message || 'We could not capture your device location.';
        }

        function renderLocationPanel(result) {
            if (!canManageTracking || !result?.can_update_location) {
                return '';
            }

            const product = result.product || {};
            const productId = product.product_id || result.product_id || '';
            const latestLocation = result.latest_location || null;
            const locationHistory = Array.isArray(result.location_history) ? result.location_history : [];

            return `
                <div class="card tracking-action-card">
                    <div class="section-header">
                        <div>
                            <h3 class="card-title">Live location tracking</h3>
                            <p class="helper-note">Capture the current GPS position for this product. Only manufacturers and distributors can save live location updates.</p>
                        </div>
                        <span class="pill pill-info">${escapeHtml(trackingRoleLabel)} access</span>
                    </div>

                    ${latestLocation ? `
                        <div class="detail-grid tracking-result-grid">
                            <div class="detail-card">
                                <strong>Latest coordinates</strong>
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
                        </div>
                    ` : `
                        <div class="detail-card">
                            <strong>No live location saved yet</strong>
                            <span class="detail-value">Capture the first field position for ${escapeHtml(product.product_name || productId || 'this product')} after verification.</span>
                        </div>
                    `}

                    <div class="field" style="margin-top: 18px;">
                        <label for="locationLabelInput">Checkpoint label</label>
                        <input id="locationLabelInput" class="search-input" type="text" placeholder="Warehouse A / Delivery van / Retail shelf">
                        <small>Add a short location label to make the tracking feed easier to understand later.</small>
                    </div>

                    ${locationHistory.length ? `
                        <div class="tracking-mini-list">
                            ${locationHistory.map((location) => `
                                <div class="tracking-mini-card">
                                    <strong>${escapeHtml(location.location_label || 'Live coordinates captured')}</strong>
                                    <span>${escapeHtml(location.coordinates_label || 'Coordinates unavailable')}</span>
                                    <span>${escapeHtml(location.relative_time || location.recorded_at_formatted || 'Just now')} | ${escapeHtml(location.tracker_label || 'Unknown operator')}</span>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}

                    <div class="action-row" style="margin-top: 16px;">
                        <button type="button" class="btn btn-success" id="shareLiveLocation" data-product-id="${escapeHtml(productId)}">
                            <i class="fas fa-location-crosshairs"></i>
                            <span>Share current location</span>
                        </button>
                        <a href="tracking.php?product=${encodeURIComponent(productId)}" class="btn btn-info">
                            <i class="fas fa-map-location-dot"></i>
                            <span>Open tracking</span>
                        </a>
                        ${latestLocation?.google_maps_url ? `
                            <a href="${escapeHtml(latestLocation.google_maps_url)}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">
                                <i class="fas fa-up-right-from-square"></i>
                                <span>Open latest map</span>
                            </a>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        async function updateLiveLocation(result) {
            const productId = result?.product?.product_id || result?.product_id || '';

            if (!productId) {
                pushToast('A product ID is required before saving live location.', 'error');
                return;
            }

            if (!navigator.geolocation) {
                pushToast('Geolocation is not supported on this device.', 'error');
                return;
            }

            const shareButton = document.getElementById('shareLiveLocation');
            const locationLabelInput = document.getElementById('locationLabelInput');
            const previousLabel = shareButton?.innerHTML;

            if (shareButton) {
                shareButton.disabled = true;
                shareButton.innerHTML = '<i class="fas fa-satellite-dish"></i><span>Capturing location...</span>';
            }

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
                        product_id: productId,
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy || '',
                        location_label: (locationLabelInput?.value || '').trim() || `${trackingRoleLabel} smart scan update`,
                        source: 'smart-scan',
                        device_label: navigator.platform || navigator.userAgent || 'Browser device'
                    })
                });

                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Failed to save the live location update.');
                }

                pushToast('Live location updated successfully.', 'success');
                renderResult({
                    ...result,
                    can_update_location: true,
                    latest_location: payload.latest_location,
                    location_history: payload.location_history
                });
            } catch (error) {
                pushToast(getGeolocationErrorMessage(error), 'error');

                if (shareButton) {
                    shareButton.disabled = false;
                    shareButton.innerHTML = previousLabel || '<i class="fas fa-location-crosshairs"></i><span>Share current location</span>';
                }
            }
        }

        function setLoading(copy = 'Verifying product against the ledger...') {
            resultContainer.innerHTML = `
                <div class="stack">
                    <span class="pill pill-info"><span class="status-dot"></span> Verification running</span>
                    <h2 class="section-title">Checking product trust signals</h2>
                    <p class="mb-0">${escapeHtml(copy)}</p>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: 72%;"></div>
                    </div>
                </div>
            `;
        }

        function setEmptyResult() {
            resultContainer.innerHTML = `
                <div class="result-placeholder">
                    <i class="fas fa-shield-heart"></i>
                    <strong>Verification results will appear here</strong>
                    <p class="mb-0">Scan or verify a product ID to see trust score, risk level, blockchain events, and next-step guidance.</p>
                </div>
            `;
        }

        async function loadCameras() {
            if (typeof Html5Qrcode === 'undefined') {
                pushToast('Scanner library could not be loaded. Manual lookup still works.', 'error');
                return [];
            }

            try {
                cameras = await Html5Qrcode.getCameras();
            } catch (error) {
                cameras = [];
            }

            return cameras;
        }

        function getCameraConfig() {
            if (cameras.length > 0) {
                return { deviceId: { exact: cameras[currentCameraIndex].id } };
            }

            return { facingMode: 'environment' };
        }

        async function startScanner() {
            if (isScanning) {
                return;
            }

            await loadCameras();

            if (typeof Html5Qrcode === 'undefined') {
                return;
            }

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode('qr-reader');
            }

            try {
                await html5QrCode.start(
                    getCameraConfig(),
                    { fps: 10, qrbox: { width: 240, height: 240 } },
                    async (decodedText) => {
                        await stopScanner();
                        verifyQrData(decodedText);
                    }
                );

                isScanning = true;
                document.getElementById('startScanner').disabled = true;
                document.getElementById('stopScanner').disabled = false;
                pushToast('Scanner started. Point the camera at a QR code.', 'success');
            } catch (error) {
                pushToast('Camera access failed. Try upload or manual lookup instead.', 'error');
            }
        }

        async function stopScanner() {
            if (!html5QrCode || !isScanning) {
                document.getElementById('startScanner').disabled = false;
                document.getElementById('stopScanner').disabled = true;
                isScanning = false;
                return;
            }

            try {
                await html5QrCode.stop();
                await html5QrCode.clear();
            } catch (error) {
                // ignore clean-up issues
            }

            html5QrCode = null;
            isScanning = false;
            document.getElementById('startScanner').disabled = false;
            document.getElementById('stopScanner').disabled = true;
        }

        async function switchCamera() {
            await loadCameras();

            if (cameras.length <= 1) {
                pushToast('No alternate camera found on this device.', 'info');
                return;
            }

            currentCameraIndex = (currentCameraIndex + 1) % cameras.length;

            if (isScanning) {
                await stopScanner();
                await startScanner();
            } else {
                pushToast(`Ready to use ${cameras[currentCameraIndex].label || 'the next camera'}.`, 'info');
            }
        }

        async function verifyQrData(qrData) {
            const trimmed = String(qrData || '').trim();

            if (!trimmed) {
                pushToast('No QR or product data was detected.', 'error');
                return;
            }

            setLoading();

            try {
                const formData = new FormData();
                formData.append('qr_data', trimmed);

                const response = await fetch('verify-product.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                renderResult(result);
            } catch (error) {
                resultContainer.innerHTML = `
                    <div class="stack">
                        <span class="pill pill-danger">Verification failed</span>
                        <h2 class="section-title">We could not contact the verification service.</h2>
                        <p class="mb-0">Check your local server and try again.</p>
                    </div>
                `;
            }
        }

        function renderResult(result) {
            if (!result || !result.status) {
                resultContainer.innerHTML = `
                    <div class="stack">
                        <span class="pill pill-danger">Verification failed</span>
                        <h2 class="section-title">The verifier returned an empty response.</h2>
                    </div>
                `;
                return;
            }

            const toneClass = result.status === 'GENUINE' ? 'success' : (result.status === 'FAKE' ? 'danger' : 'warning');
            const trustScore = Number(result.trust_score || 0);
            const highlights = Array.isArray(result.highlights) ? result.highlights : [];
            const blockchainDetails = Array.isArray(result.blockchain_details) ? result.blockchain_details : [];
            const proofSummary = result.proof_summary || {};
            const product = result.product || {};
            const locationPanelHtml = renderLocationPanel(result);
            const proofCards = [];
            const renderedProofKeys = new Set();

            function pushProofCard(entry, title, caption) {
                if (!entry || !entry.proof || !entry.proof.transaction_hash) {
                    return;
                }

                const key = buildProofKey(entry);

                if (renderedProofKeys.has(key)) {
                    return;
                }

                renderedProofKeys.add(key);
                proofCards.push(renderProofCard(entry, title, caption));
            }

            pushProofCard(
                proofSummary.origin,
                'Origin proof',
                'Created when the manufacturer generated the QR identity.'
            );
            pushProofCard(
                proofSummary.latest,
                'Latest proof',
                'Most recent ledger-backed proof linked to this product.'
            );

            const proofCardsHtml = proofCards.join('');

            resultContainer.innerHTML = `
                <div class="stack">
                    <div class="panel-header">
                        <div>
                            <span class="pill pill-${toneClass}">${escapeHtml(result.status)}</span>
                            <h2 class="section-title" style="margin-top: 12px;">${escapeHtml(result.message || 'Verification complete')}</h2>
                            <p class="mb-0">${escapeHtml(result.recommendation || 'Review the product details below.')}</p>
                        </div>
                        <span class="helper-note">${escapeHtml(result.verification_time_formatted || result.verification_time || '')}</span>
                    </div>

                    <div class="score-shell">
                        <div class="score-ring" style="--score: ${Math.max(0, Math.min(100, trustScore))};">
                            <div class="text-right">
                                <strong>${trustScore}</strong>
                                <small>trust</small>
                            </div>
                        </div>

                        <div class="stack" style="gap: 12px;">
                            <div class="quick-chips">
                                <span class="pill pill-${escapeHtml(result.risk_tone || toneClass)}">${escapeHtml(result.risk_label || 'Risk profile')}</span>
                                <span class="pill pill-${escapeHtml(result.freshness_tone || 'info')}">${escapeHtml(result.freshness_label || 'Batch timing')}</span>
                                <span class="pill pill-info">${escapeHtml(result.blockchain_count || 0)} ledger entries</span>
                                ${proofSummary.real_chain_confirmed ? `<span class="pill pill-success">${escapeHtml(proofSummary.real_chain_record_count || 0)} on-chain records confirmed</span>` : ''}
                            </div>
                            <div class="detail-grid">
                                <div class="detail-card">
                                    <strong>Product</strong>
                                    <span class="detail-value">${escapeHtml(product.product_name || 'Unknown product')}</span>
                                </div>
                                <div class="detail-card">
                                    <strong>Brand</strong>
                                    <span class="detail-value">${escapeHtml(product.brand || 'Unknown')}</span>
                                </div>
                                <div class="detail-card">
                                    <strong>Product ID</strong>
                                    <span class="detail-value">${escapeHtml(product.product_id || result.product_id || 'N/A')}</span>
                                </div>
                                <div class="detail-card">
                                    <strong>Manufacturer</strong>
                                    <span class="detail-value">${escapeHtml(product.manufacturer_name || 'Unknown')}</span>
                                </div>
                                <div class="detail-card">
                                    <strong>Category</strong>
                                    <span class="detail-value">${escapeHtml(product.category || 'Not available')}</span>
                                </div>
                                <div class="detail-card">
                                    <strong>Dates</strong>
                                    <span class="detail-value">${escapeHtml(product.manufacturer_date_formatted || product.manufacturer_date || 'N/A')} | ${escapeHtml(product.expiry_date_formatted || product.expiry_date || 'No expiry')}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    ${proofCardsHtml ? `
                        <div class="stack" style="gap: 14px;">
                            ${proofCardsHtml}
                        </div>
                    ` : ''}

                    ${locationPanelHtml}

                    ${highlights.length ? `
                        <div class="card" style="padding: 18px;">
                            <h3 class="card-title">Key verification highlights</h3>
                            <div class="mini-list" style="margin-top: 12px;">
                                ${highlights.map((item) => `<div class="insight-item"><i class="fas fa-check"></i><div>${escapeHtml(item)}</div></div>`).join('')}
                            </div>
                        </div>
                    ` : ''}

                    ${blockchainDetails.length ? `
                        <div>
                            <h3 class="card-title" style="margin-bottom: 12px;">Recent blockchain events</h3>
                            <div class="blockchain-list">
                                ${blockchainDetails.map((entry) => `
                                    <div class="blockchain-item">
                                        <div class="blockchain-top">
                                            <strong>${escapeHtml(entry.action_label || entry.action || 'Blockchain activity')}</strong>
                                            <span class="helper-note">${escapeHtml(entry.timestamp_formatted || entry.timestamp || '')}</span>
                                        </div>
                                        <div>${escapeHtml(entry.summary || '')}</div>
                                        ${entry.proof && entry.proof.transaction_hash ? `
                                            <div class="proof-mini-grid">
                                                <span class="helper-note proof-code">Tx ${escapeHtml(entry.proof.transaction_hash_short || entry.proof.transaction_hash || '')}</span>
                                                ${entry.proof.block_number ? `<span class="helper-note">Block ${escapeHtml(entry.proof.block_number)}</span>` : ''}
                                                ${entry.proof.chain_id ? `<span class="helper-note">Chain ${escapeHtml(entry.proof.chain_id)}</span>` : ''}
                                                ${entry.proof.contract_address_short ? `<span class="helper-note proof-code">${escapeHtml(entry.proof.contract_address_short)}</span>` : ''}
                                            </div>
                                            <div class="action-row" style="margin-top: 10px;">
                                                <button type="button" class="btn btn-ghost btn-sm" data-copy-text="${escapeHtml(entry.proof.transaction_hash || '')}" data-copy-success="Transaction hash copied to clipboard.">
                                                    <i class="fas fa-copy"></i>
                                                    <span>Copy tx hash</span>
                                                </button>
                                            </div>
                                        ` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}

                    <div class="result-actions">
                        <button type="button" class="btn btn-primary" id="scanAgain">
                            <i class="fas fa-redo"></i>
                            <span>Scan another</span>
                        </button>
                        <button type="button" class="btn btn-ghost" data-copy-text="${escapeHtml(product.product_id || result.product_id || '')}" data-copy-success="Product ID copied to clipboard.">
                            <i class="fas fa-copy"></i>
                            <span>Copy product ID</span>
                        </button>
                        <a href="analytics.php" class="btn btn-info">
                            <i class="fas fa-chart-line"></i>
                            <span>Open analytics</span>
                        </a>
                    </div>
                </div>
            `;

            document.getElementById('scanAgain')?.addEventListener('click', () => {
                setEmptyResult();
                manualInput.value = '';
            });

            document.getElementById('shareLiveLocation')?.addEventListener('click', () => {
                updateLiveLocation(result);
            });

            attachCopyHandlers(resultContainer);
            saveRecentScan(result);
            renderRecentScans();
        }

        function getRecentScans() {
            try {
                return JSON.parse(localStorage.getItem(scanHistoryKey) || '[]');
            } catch (error) {
                return [];
            }
        }

        function saveRecentScan(result) {
            const existing = getRecentScans();
            const productId = result.product?.product_id || result.product_id || 'Unknown';
            const nextEntry = {
                product_id: productId,
                product_name: result.product?.product_name || 'Unknown product',
                status: result.status,
                trust_score: Number(result.trust_score || 0),
                verification_time: result.verification_time_formatted || result.verification_time || new Date().toLocaleString()
            };

            const filtered = existing.filter((item) => item.product_id !== nextEntry.product_id);
            filtered.unshift(nextEntry);
            localStorage.setItem(scanHistoryKey, JSON.stringify(filtered.slice(0, 6)));
        }

        function renderRecentScans() {
            const recentScansList = document.getElementById('recentScansList');
            const scans = getRecentScans();

            if (!scans.length) {
                recentScansList.innerHTML = '<div class="helper-note">No local scans yet.</div>';
                return;
            }

            recentScansList.innerHTML = scans.map((scan) => `
                <button type="button" class="recent-scan-card" data-product-id="${escapeHtml(scan.product_id)}">
                    <strong>${escapeHtml(scan.product_name)}</strong>
                    <div class="helper-note">${escapeHtml(scan.product_id)} | ${escapeHtml(scan.status)} | trust ${escapeHtml(scan.trust_score)}</div>
                    <div class="helper-note">${escapeHtml(scan.verification_time)}</div>
                </button>
            `).join('');

            recentScansList.querySelectorAll('[data-product-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    manualInput.value = button.dataset.productId;
                    verifyQrData(button.dataset.productId);
                });
            });
        }

        async function processSelectedFile() {
            if (!selectedFile) {
                pushToast('Choose an image before trying to verify it.', 'info');
                return;
            }

            if (typeof Html5Qrcode === 'undefined') {
                pushToast('QR image decoding is not available right now.', 'error');
                return;
            }

            setLoading('Reading the uploaded QR image...');

            try {
                await stopScanner();
                const tempScanner = new Html5Qrcode('qr-reader');
                const decodedText = await tempScanner.scanFile(selectedFile, true);
                await tempScanner.clear();
                await verifyQrData(decodedText);
            } catch (error) {
                pushToast('Could not read the QR from that image. Try a clearer image or use manual lookup.', 'error');
                setEmptyResult();
            }
        }

        function clearSelectedFile() {
            selectedFile = null;
            fileInput.value = '';
            uploadPreview.style.display = 'none';
            previewImage.removeAttribute('src');
        }

        function selectFile(file) {
            if (!file) {
                return;
            }

            selectedFile = file;
            fileName.textContent = file.name;
            uploadPreview.style.display = 'block';

            const reader = new FileReader();
            reader.onload = (event) => {
                previewImage.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }

        document.getElementById('startScanner').addEventListener('click', startScanner);
        document.getElementById('stopScanner').addEventListener('click', stopScanner);
        document.getElementById('switchCamera').addEventListener('click', switchCamera);
        document.getElementById('manualVerify').addEventListener('click', () => verifyQrData(manualInput.value));
        document.getElementById('processFile').addEventListener('click', processSelectedFile);
        document.getElementById('clearFile').addEventListener('click', clearSelectedFile);
        document.getElementById('clearHistory').addEventListener('click', () => {
            localStorage.removeItem(scanHistoryKey);
            renderRecentScans();
            pushToast('Local scan history cleared.', 'info');
        });

        manualInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                verifyQrData(manualInput.value);
            }
        });

        fileInput.addEventListener('change', (event) => {
            selectFile(event.target.files[0]);
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            uploadDrop.addEventListener(eventName, (event) => {
                event.preventDefault();
                uploadDrop.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            uploadDrop.addEventListener(eventName, (event) => {
                event.preventDefault();
                uploadDrop.classList.remove('dragover');
            });
        });

        uploadDrop.addEventListener('drop', (event) => {
            const file = event.dataTransfer.files[0];
            if (file) {
                selectFile(file);
            }
        });

        document.querySelectorAll('.quick-product').forEach((button) => {
            button.addEventListener('click', () => {
                manualInput.value = button.dataset.productId;
                verifyQrData(button.dataset.productId);
            });
        });

        window.addEventListener('beforeunload', () => {
            stopScanner();
        });

        renderRecentScans();

        if (prefillProduct) {
            manualInput.value = prefillProduct;
            verifyQrData(prefillProduct);
        }
    </script>
</body>
</html>
