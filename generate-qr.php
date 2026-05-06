<?php
require_once 'includes/config.php';
requireAuth();
requireManufacturer();

$success = '';
$error = '';
$qr_code_url = '';
$generated_hash = '';
$product_details = [];
$blockchain_status = getRealBlockchainHealth();
$blockchain_readiness = getRealBlockchainReadiness();

$form_values = [
    'product_name' => trim($_POST['product_name'] ?? ''),
    'brand' => trim($_POST['brand'] ?? ''),
    'category' => $_POST['category'] ?? '',
    'manufacturer_date' => $_POST['manufacturer_date'] ?? date('Y-m-d'),
    'expiry_date' => $_POST['expiry_date'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_started = false;
    $generated_qr_file = '';

    try {
        if (!isRealBlockchainEnabled()) {
            throw new Exception('Real blockchain mode is disabled in the project configuration.');
        }

        if (!$blockchain_status) {
            throw new Exception($blockchain_readiness['message'] ?? 'Real blockchain is not active for QR generation.');
        }

        $product_id = 'PRD' . time() . rand(100, 999);
        $manufacturer_id = (int) $_SESSION['user_id'];

        if (
            $form_values['product_name'] === '' ||
            $form_values['brand'] === '' ||
            $form_values['category'] === '' ||
            $form_values['manufacturer_date'] === ''
        ) {
            throw new Exception('Please complete all required product details.');
        }

        if (!empty($form_values['expiry_date']) && $form_values['expiry_date'] < $form_values['manufacturer_date']) {
            throw new Exception('Expiry date cannot be earlier than the manufacturer date.');
        }

        $expiry_date = $form_values['expiry_date'] !== '' ? $form_values['expiry_date'] : null;
        $conn->begin_transaction();
        $transaction_started = true;

        $sql = "INSERT INTO products (product_id, product_name, brand, category, manufacturer_date, expiry_date, manufacturer_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param(
            'ssssssi',
            $product_id,
            $form_values['product_name'],
            $form_values['brand'],
            $form_values['category'],
            $form_values['manufacturer_date'],
            $expiry_date,
            $manufacturer_id
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to save product: ' . $stmt->error);
        }

        $generated_hash = addToBlockchain($conn, $product_id, [
            'action' => 'PRODUCT_CREATED',
            'product_name' => $form_values['product_name'],
            'brand' => $form_values['brand'],
            'manufacturer_id' => $manufacturer_id,
            'timestamp' => time()
        ], [
            'require_real_chain' => true,
            'throw_on_failure' => true
        ]);

        if (!$generated_hash) {
            throw new Exception('A real blockchain transaction hash was not created for this QR code.');
        }

        $latest_ledger_entry = getLatestProductLedgerEntry($conn, $product_id);
        $ledger_payload = $latest_ledger_entry ? parseBlockchainPayload($latest_ledger_entry['block_data']) : [];
        $chain_details = $ledger_payload['chain'] ?? [];

        $qr_payload = [
            'product_id' => $product_id,
            'system' => 'ProductSecure',
            'timestamp' => time(),
            'hash' => $generated_hash,
            'tx_hash' => $generated_hash,
            'chain' => [
                'mode' => $chain_details['mode'] ?? 'local-hardhat',
                'chain_id' => $chain_details['chain_id'] ?? null,
                'contract_address' => $chain_details['contract_address'] ?? null,
                'block_number' => $chain_details['block_number'] ?? null,
                'payload_hash' => $chain_details['payload_hash'] ?? null
            ]
        ];

        $qr_code_url = generateQRCodeImage($qr_payload, $product_id);
        $generated_qr_file = $qr_code_url;
        $conn->commit();
        $transaction_started = false;

        $product_details = [
            'product_id' => $product_id,
            'product_name' => $form_values['product_name'],
            'brand' => $form_values['brand'],
            'category' => $form_values['category'],
            'manufacturer_date' => formatDateReadable($form_values['manufacturer_date']),
            'expiry_date' => formatDateReadable($expiry_date),
            'qr_url' => $qr_code_url,
            'hash_short' => truncateHash($generated_hash),
            'hash_full' => $generated_hash,
            'block_number' => $chain_details['block_number'] ?? null,
            'chain_id' => $chain_details['chain_id'] ?? null,
            'contract_address' => $chain_details['contract_address'] ?? null,
            'contract_short' => !empty($chain_details['contract_address']) ? truncateHash($chain_details['contract_address'], 16, 8) : 'N/A',
            'verification_link' => 'scan-qr.php?product=' . urlencode($product_id),
            'tracking_link' => 'tracking.php?product=' . urlencode($product_id) . '&capture=1'
        ];

        $success = 'Product registered successfully and a real blockchain transaction hash was created for this QR identity.';
    } catch (Exception $e) {
        if ($transaction_started) {
            $conn->rollback();
        }

        if ($generated_qr_file !== '' && file_exists($generated_qr_file)) {
            @unlink($generated_qr_file);
        }

        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create QR - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="page-shell stack">
        <section class="page-topper">
            <p class="eyebrow" style="color: var(--primary-deep); background: rgba(20, 86, 240, 0.08);">
                <i class="fas fa-qrcode"></i> QR identity builder
            </p>
            <h1 class="page-title">Register a product beautifully and generate a secure QR identity.</h1>
            <p class="page-subtitle">
                Use the live preview, apply quick expiry presets, and ship each product with a QR code that links back to your trust flow.
            </p>
        </section>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-circle-check"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$blockchain_status): ?>
            <div class="alert alert-error">
                <i class="fas fa-link-slash"></i>
                <div><?php echo htmlspecialchars($blockchain_readiness['message'] ?? 'Real blockchain is required for new QR generation.'); ?></div>
            </div>
        <?php endif; ?>

        <section class="qr-layout">
            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Product registration</h2>
                        <p>Fill in the essential batch details, then generate a blockchain-backed QR code.</p>
                    </div>
                    <div class="quick-chips">
                        <span class="pill pill-info">Manufacturer only</span>
                        <?php if ($blockchain_status): ?>
                            <span class="pill pill-success">Real blockchain active</span>
                            <span class="pill pill-info">Chain ID <?php echo (int) ($blockchain_status['chainId'] ?? 0); ?></span>
                        <?php else: ?>
                            <span class="pill pill-warning">
                                <?php
                                $status_labels = [
                                    'deployment-missing' => 'Contract not deployed',
                                    'node-down' => 'Local node offline',
                                    'api-down' => 'API bridge offline'
                                ];
                                echo htmlspecialchars($status_labels[$blockchain_readiness['code'] ?? ''] ?? 'QR generation locked to real chain');
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="quick-chips" style="margin-bottom: 18px;">
                    <button type="button" class="quick-chip category-chip" data-category="Electronics">Electronics</button>
                    <button type="button" class="quick-chip category-chip" data-category="Clothing">Clothing</button>
                    <button type="button" class="quick-chip category-chip" data-category="Food&Beverages">Food & Beverages</button>
                    <button type="button" class="quick-chip category-chip" data-category="Cosmetics">Cosmetics</button>
                    <button type="button" class="quick-chip category-chip" data-category="Pharmaceuticals">Pharmaceuticals</button>
                </div>

                <form method="POST" id="productForm" class="form-grid">
                    <div class="field">
                        <label for="product_name">Product name</label>
                        <input id="product_name" type="text" name="product_name" placeholder="Premium product name" required value="<?php echo htmlspecialchars($form_values['product_name']); ?>">
                    </div>

                    <div class="field">
                        <label for="brand">Brand</label>
                        <input id="brand" type="text" name="brand" placeholder="Brand or manufacturer name" required value="<?php echo htmlspecialchars($form_values['brand']); ?>">
                    </div>

                    <div class="field">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select category</option>
                            <?php
                            $category_options = ['Electronics', 'Clothing', 'Food&Beverages', 'Cosmetics', 'Pharmaceuticals', 'Others'];
                            foreach ($category_options as $option):
                            ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($form_values['category'] === $option) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(str_replace('&', ' & ', $option)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="manufacturer_date">Manufacturer date</label>
                            <input id="manufacturer_date" type="date" name="manufacturer_date" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form_values['manufacturer_date']); ?>">
                        </div>

                        <div class="field">
                            <label for="expiry_date">Expiry date</label>
                            <input id="expiry_date" type="date" name="expiry_date" min="<?php echo htmlspecialchars($form_values['manufacturer_date']); ?>" value="<?php echo htmlspecialchars($form_values['expiry_date']); ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label>Quick expiry presets</label>
                        <div class="quick-chips">
                            <button type="button" class="quick-chip expiry-chip" data-days="0">No expiry</button>
                            <button type="button" class="quick-chip expiry-chip" data-days="90">90 days</button>
                            <button type="button" class="quick-chip expiry-chip" data-days="180">6 months</button>
                            <button type="button" class="quick-chip expiry-chip" data-days="365">12 months</button>
                        </div>
                        <small>Presets use the manufacturer date as the starting point.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" <?php echo $blockchain_status ? '' : 'disabled'; ?>>
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span><?php echo $blockchain_status ? 'Generate secure QR' : 'Start blockchain to generate QR'; ?></span>
                    </button>
                </form>
            </article>

            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Live product preview</h2>
                        <p>Preview the identity card before you generate the final QR asset.</p>
                    </div>
                </div>

                <?php if (!empty($product_details)): ?>
                    <div class="stack">
                        <div class="qr-frame">
                            <img src="<?php echo htmlspecialchars($product_details['qr_url']); ?>" alt="Generated QR code">
                        </div>

                        <div class="preview-list">
                            <div class="preview-item">
                                <strong><?php echo htmlspecialchars($product_details['product_name']); ?></strong>
                                <span><?php echo htmlspecialchars($product_details['brand']); ?> · <?php echo htmlspecialchars($product_details['category']); ?></span>
                            </div>
                            <div class="preview-item">
                                <strong>Product ID</strong>
                                <span id="generatedProductId"><?php echo htmlspecialchars($product_details['product_id']); ?></span>
                            </div>
                            <div class="preview-item">
                                <strong>On-chain transaction hash</strong>
                                <span><?php echo htmlspecialchars($product_details['hash_short']); ?></span>
                            </div>
                            <div class="preview-item">
                                <strong>Chain proof</strong>
                                <span>Chain <?php echo (int) ($product_details['chain_id'] ?? 0); ?> | Block <?php echo (int) ($product_details['block_number'] ?? 0); ?> | <?php echo htmlspecialchars($product_details['contract_short']); ?></span>
                            </div>
                            <div class="preview-item">
                                <strong>Batch dates</strong>
                                <span><?php echo htmlspecialchars($product_details['manufacturer_date']); ?> · Expires <?php echo htmlspecialchars($product_details['expiry_date']); ?></span>
                            </div>
                            <div class="preview-item">
                                <strong>Live tracking</strong>
                                <span>Manufacturers and distributors can now post fresh live location updates for this product from Smart Scan.</span>
                            </div>
                        </div>

                        <div class="action-row">
                            <a href="<?php echo htmlspecialchars($product_details['qr_url']); ?>" download="<?php echo htmlspecialchars($product_details['product_id']); ?>.png" class="btn btn-success">
                                <i class="fas fa-download"></i>
                                <span>Download QR</span>
                            </a>
                            <button type="button" class="btn btn-ghost" data-copy="<?php echo htmlspecialchars($product_details['product_id']); ?>">
                                <i class="fas fa-copy"></i>
                                <span>Copy ID</span>
                            </button>
                            <button type="button" class="btn btn-ghost" data-copy="<?php echo htmlspecialchars($product_details['hash_full']); ?>">
                                <i class="fas fa-hashtag"></i>
                                <span>Copy tx hash</span>
                            </button>
                            <a href="<?php echo htmlspecialchars($product_details['verification_link']); ?>" class="btn btn-info">
                                <i class="fas fa-camera-retro"></i>
                                <span>Open verifier</span>
                            </a>
                            <a href="<?php echo htmlspecialchars($product_details['tracking_link']); ?>" class="btn btn-ghost">
                                <i class="fas fa-location-crosshairs"></i>
                                <span>Track live now</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stack" id="livePreview">
                        <div class="qr-frame">
                            <div class="empty-state" style="height: 100%;">
                                <i class="fas fa-qrcode"></i>
                                <strong>QR identity will appear here</strong>
                                <p class="mb-0">The preview updates while you type, then becomes downloadable after creation.</p>
                            </div>
                        </div>

                        <div class="preview-list">
                            <div class="preview-item">
                                <strong id="previewName">Your product name</strong>
                                <span id="previewBrand">Brand name · Category</span>
                            </div>
                            <div class="preview-item">
                                <strong>Generated product ID</strong>
                                <span>Created automatically after submission</span>
                            </div>
                            <div class="preview-item">
                                <strong>Batch timing</strong>
                                <span id="previewDates">Manufacturer and expiry dates will appear here</span>
                            </div>
                            <div class="preview-item">
                                <strong>Verification promise</strong>
                                <span>Scannable through the smart verification page with trust scoring and ledger history.</span>
                            </div>
                            <div class="preview-item">
                                <strong>Tracking promise</strong>
                                <span>After the QR is generated, only manufacturers and distributors can capture live product location updates.</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </main>

    <div class="toast-stack" id="toastStack"></div>

    <script>
        const productNameInput = document.getElementById('product_name');
        const brandInput = document.getElementById('brand');
        const categoryInput = document.getElementById('category');
        const manufacturerDateInput = document.getElementById('manufacturer_date');
        const expiryDateInput = document.getElementById('expiry_date');

        function formatDateForPreview(value) {
            if (!value) {
                return 'Not set';
            }

            const date = new Date(value + 'T00:00:00');
            return date.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function updatePreview() {
            const previewName = document.getElementById('previewName');
            const previewBrand = document.getElementById('previewBrand');
            const previewDates = document.getElementById('previewDates');

            if (!previewName || !previewBrand || !previewDates) {
                return;
            }

            const productName = productNameInput.value.trim() || 'Your product name';
            const brand = brandInput.value.trim() || 'Brand name';
            const category = categoryInput.value || 'Category';
            const manufacturerDate = formatDateForPreview(manufacturerDateInput.value);
            const expiryDate = expiryDateInput.value ? formatDateForPreview(expiryDateInput.value) : 'No expiry';

            previewName.textContent = productName;
            previewBrand.textContent = `${brand} · ${category}`;
            previewDates.textContent = `${manufacturerDate} · Expires ${expiryDate}`;
        }

        function pushToast(message, tone = 'info') {
            const stack = document.getElementById('toastStack');
            const toast = document.createElement('div');
            toast.className = `toast ${tone}`;
            toast.textContent = message;
            stack.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 2600);
        }

        function setExpiryFromPreset(days) {
            const manufacturerDate = manufacturerDateInput.value || new Date().toISOString().split('T')[0];
            const baseDate = new Date(manufacturerDate + 'T00:00:00');

            if (days === 0) {
                expiryDateInput.value = '';
                updatePreview();
                return;
            }

            baseDate.setDate(baseDate.getDate() + days);
            expiryDateInput.value = baseDate.toISOString().split('T')[0];
            updatePreview();
        }

        document.querySelectorAll('.category-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                categoryInput.value = chip.dataset.category;
                updatePreview();
                pushToast(`Category set to ${chip.dataset.category}`, 'info');
            });
        });

        document.querySelectorAll('.expiry-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                setExpiryFromPreset(Number(chip.dataset.days));
            });
        });

        [productNameInput, brandInput, categoryInput, manufacturerDateInput, expiryDateInput].forEach((element) => {
            if (!element) {
                return;
            }

            element.addEventListener('input', updatePreview);
            element.addEventListener('change', updatePreview);
        });

        manufacturerDateInput?.addEventListener('change', () => {
            expiryDateInput.min = manufacturerDateInput.value;
            if (expiryDateInput.value && expiryDateInput.value < manufacturerDateInput.value) {
                expiryDateInput.value = manufacturerDateInput.value;
            }
            updatePreview();
        });

        document.getElementById('productForm')?.addEventListener('submit', (event) => {
            if (expiryDateInput.value && expiryDateInput.value < manufacturerDateInput.value) {
                event.preventDefault();
                pushToast('Expiry date cannot be before the manufacturer date.', 'error');
            }
        });

        document.querySelectorAll('[data-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.copy);
                    pushToast('Product ID copied to clipboard.', 'success');
                } catch (error) {
                    pushToast('Could not copy the product ID on this device.', 'error');
                }
            });
        });

        updatePreview();
    </script>
</body>
</html>
