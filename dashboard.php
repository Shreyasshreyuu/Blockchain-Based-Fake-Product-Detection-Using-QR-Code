<?php
require_once 'includes/config.php';
requireAuth();

$user_id = (int) $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'member';
$username = $_SESSION['username'] ?? 'User';

$metrics = getDashboardMetrics($conn, $user_id, $user_type);
$categories = getCategoryBreakdown($conn, $user_type, $user_id, 6);
$recent_activity = getRecentActivityFeed($conn, $user_type, $user_id, 6);
$top_products = getTopTrustedProducts($conn, $user_type, $user_id, 5);
$trend = getActivityTrend($conn, $user_type, $user_id, 7);
$blockchain_readiness = getRealBlockchainReadiness();
$blockchain_status = $blockchain_readiness['health'] ?? null;
$blockchain_ready = !empty($blockchain_readiness['ready']);
$readiness_signal_count = 0;

foreach (['deployment_exists', 'node_reachable', 'api_reachable'] as $readiness_key) {
    if (!empty($blockchain_readiness[$readiness_key])) {
        $readiness_signal_count++;
    }
}

$blockchain_readiness_score = (int) round(($readiness_signal_count / 3) * 100);

$role_copy = [
    'manufacturer' => 'Create secure QR identities, monitor verification momentum, and keep expiring batches visible.',
    'distributor' => 'Track verification activity, inspect the ledger, and validate incoming stock with confidence.',
    'consumer' => 'Scan, inspect trust signals, and spot suspicious products before they reach your hands.'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="page-shell stack">
        <section class="hero">
            <div class="hero-grid">
                <div>
                    <p class="eyebrow"><i class="fas fa-wave-square"></i> Verification command center</p>
                    <h1>Welcome back, <?php echo htmlspecialchars($username); ?>.</h1>
                    <p>
                        <?php echo htmlspecialchars($role_copy[$user_type] ?? 'Review platform activity, explore verification intelligence, and act on the signals that matter most.'); ?>
                    </p>

                    <div class="quick-chips" style="margin-top: 22px;">
                        <?php if ($blockchain_ready): ?>
                            <span class="pill pill-success">Real blockchain active</span>
                            <span class="pill pill-info">Chain ID <?php echo (int) ($blockchain_status['chainId'] ?? 0); ?></span>
                            <span class="pill pill-info">Block <?php echo (int) ($blockchain_status['blockNumber'] ?? 0); ?></span>
                        <?php else: ?>
                            <span class="pill pill-warning"><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', (string) ($blockchain_readiness['code'] ?? 'offline')))); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$blockchain_ready && isRealBlockchainEnabled()): ?>
                        <p class="subtle-copy" style="margin-top: 14px;">
                            <?php echo htmlspecialchars($blockchain_readiness['message'] ?? 'Start the blockchain stack to enable real on-chain verification.'); ?>
                        </p>
                    <?php endif; ?>

                    <div class="hero-actions" style="margin-top: 24px;">
                        <?php if ($user_type === 'manufacturer'): ?>
                            <a href="generate-qr.php" class="btn btn-secondary">
                                <i class="fas fa-qrcode"></i>
                                <span>Create new QR</span>
                            </a>
                            <a href="my-products.php" class="btn btn-ghost">
                                <i class="fas fa-boxes-stacked"></i>
                                <span>Review products</span>
                            </a>
                        <?php else: ?>
                            <a href="scan-qr.php" class="btn btn-secondary">
                                <i class="fas fa-camera-retro"></i>
                                <span>Start smart scan</span>
                            </a>
                            <a href="ledger.php" class="btn btn-ghost">
                                <i class="fas fa-link"></i>
                                <span>Inspect ledger</span>
                            </a>
                        <?php endif; ?>
                        <a href="analytics.php" class="btn btn-ghost">
                            <i class="fas fa-chart-line"></i>
                            <span>Open analytics</span>
                        </a>
                    </div>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <small>Network trust score</small>
                        <strong><?php echo (int) $metrics['network_score']; ?> / 99</strong>
                    </div>
                    <div class="hero-stat">
                        <small>Coverage rate</small>
                        <strong><?php echo (int) $metrics['coverage_rate']; ?>%</strong>
                    </div>
                    <div class="hero-stat">
                        <small>Expiring soon</small>
                        <strong><?php echo (int) $metrics['expiring_soon']; ?> batch<?php echo ((int) $metrics['expiring_soon'] === 1) ? '' : 'es'; ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="metric-grid">
            <article class="card metric-card">
                <span class="stat-kicker">Protected products</span>
                <strong class="metric-value"><?php echo (int) $metrics['total_products']; ?></strong>
                <p class="mb-0">Products currently registered in your active view.</p>
            </article>

            <article class="card metric-card">
                <span class="stat-kicker">Blockchain events</span>
                <strong class="metric-value"><?php echo (int) $metrics['blockchain_entries']; ?></strong>
                <p class="mb-0">Creation and verification events linked into the ledger.</p>
            </article>

            <article class="card metric-card">
                <span class="stat-kicker">Verification checks</span>
                <strong class="metric-value"><?php echo (int) $metrics['verification_checks']; ?></strong>
                <p class="mb-0">Recorded verification activity tied to product lookups.</p>
            </article>

            <article class="card metric-card">
                <span class="stat-kicker">Active categories</span>
                <strong class="metric-value"><?php echo (int) $metrics['active_categories']; ?></strong>
                <p class="mb-0"><?php echo (int) $metrics['recent_products']; ?> product registrations in the last 7 days.</p>
            </article>
        </section>

        <section class="grid-2">
            <article class="card dashboard-status-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Live blockchain status</h2>
                        <p>See whether the local chain, contract deployment, and API bridge are ready for real proof-backed QR creation.</p>
                    </div>
                    <?php if ($blockchain_ready): ?>
                        <span class="pill pill-success"><span class="status-dot"></span> Ready now</span>
                    <?php else: ?>
                        <span class="pill pill-warning"><span class="status-dot"></span> Attention needed</span>
                    <?php endif; ?>
                </div>

                <div class="quick-chips" style="margin-bottom: 16px;">
                    <span class="pill pill-<?php echo $blockchain_ready ? 'success' : 'warning'; ?>">
                        <?php echo $blockchain_ready ? 'Real on-chain verification' : 'Real chain unavailable'; ?>
                    </span>
                    <?php if ($blockchain_ready): ?>
                        <span class="pill pill-info">Chain <?php echo (int) ($blockchain_status['chainId'] ?? 0); ?></span>
                        <span class="pill pill-info">Latest block <?php echo (int) ($blockchain_status['blockNumber'] ?? 0); ?></span>
                    <?php endif; ?>
                </div>

                <div class="detail-grid dashboard-status-grid">
                    <div class="detail-card">
                        <strong>Overall state</strong>
                        <span class="detail-value"><?php echo $blockchain_ready ? 'Live and accepting writes' : 'Waiting for local services'; ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>Readiness code</strong>
                        <span class="detail-value"><?php echo htmlspecialchars(strtoupper((string) ($blockchain_readiness['code'] ?? 'UNKNOWN'))); ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>Node status</strong>
                        <span class="detail-value"><?php echo !empty($blockchain_readiness['node_reachable']) ? 'RPC reachable' : 'RPC offline'; ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>API bridge</strong>
                        <span class="detail-value"><?php echo !empty($blockchain_readiness['api_reachable']) ? 'API responding' : 'API offline'; ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>Deployment file</strong>
                        <span class="detail-value"><?php echo !empty($blockchain_readiness['deployment_exists']) ? 'Contract deployment found' : 'Deployment missing'; ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>RPC endpoint</strong>
                        <span class="detail-value proof-code"><?php echo htmlspecialchars($blockchain_readiness['rpc_url'] ?? 'http://127.0.0.1:8545'); ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>Bridge endpoint</strong>
                        <span class="detail-value proof-code"><?php echo htmlspecialchars($blockchain_readiness['api_url'] ?? 'http://127.0.0.1:3001/api/blockchain'); ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>Contract address</strong>
                        <span class="detail-value proof-code"><?php echo htmlspecialchars($blockchain_status['contractAddress'] ?? 'Not available'); ?></span>
                    </div>
                </div>

                <div class="dashboard-command-strip">
                    <div>
                        <strong>Quick recovery command</strong>
                        <p class="helper-note mb-0">Run the one-command startup if the widget reports an offline service.</p>
                    </div>
                    <div class="action-row">
                        <button type="button" class="btn btn-ghost btn-sm" data-copy-text="npm run blockchain:start" data-copy-success="Startup command copied to clipboard.">
                            <i class="fas fa-copy"></i>
                            <span>Copy start command</span>
                        </button>
                        <?php if (!empty($blockchain_status['contractAddress'])): ?>
                            <button type="button" class="btn btn-ghost btn-sm" data-copy-text="<?php echo htmlspecialchars($blockchain_status['contractAddress']); ?>" data-copy-success="Contract address copied to clipboard.">
                                <i class="fas fa-diagram-project"></i>
                                <span>Copy contract</span>
                            </button>
                        <?php endif; ?>
                        <a href="ledger.php" class="btn btn-info btn-sm">
                            <i class="fas fa-link"></i>
                            <span>Open ledger explorer</span>
                        </a>
                    </div>
                </div>
            </article>

            <article class="card dashboard-proof-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Proof signal snapshot</h2>
                        <p>A fast view of whether your dashboard activity is leaning toward real chain-backed trust or fallback-only records.</p>
                    </div>
                </div>

                <div class="mini-grid">
                    <div class="mini-stat">
                        <span class="stat-kicker">Ledger coverage</span>
                        <strong class="number-value"><?php echo (int) $metrics['coverage_rate']; ?>%</strong>
                    </div>
                    <div class="mini-stat">
                        <span class="stat-kicker">Stack readiness</span>
                        <strong class="number-value"><?php echo (int) $blockchain_readiness_score; ?>%</strong>
                    </div>
                </div>

                <div class="bar-list" style="margin-top: 20px;">
                    <div class="bar-row">
                        <div class="bar-meta">
                            <strong>Protected products vs. verifiable events</strong>
                            <span class="helper-note"><?php echo (int) $metrics['total_products']; ?> products &middot; <?php echo (int) $metrics['blockchain_entries']; ?> ledger events</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?php echo max(8, min(100, (int) $metrics['coverage_rate'])); ?>%;"></div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-meta">
                            <strong>Verification momentum</strong>
                            <span class="helper-note"><?php echo (int) $metrics['verification_checks']; ?> checks &middot; network score <?php echo (int) $metrics['network_score']; ?>/99</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?php echo max(8, min(100, (int) round(((int) $metrics['network_score'] / 99) * 100))); ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-proof-callout">
                    <span class="pill pill-<?php echo $blockchain_ready ? 'success' : 'warning'; ?>">
                        <?php echo $blockchain_ready ? 'New QR codes can mint real transaction hashes now.' : 'QR generation needs the blockchain stack online for real hashes.'; ?>
                    </span>
                    <p class="mb-0"><?php echo htmlspecialchars($blockchain_readiness['message'] ?? ''); ?></p>
                </div>
            </article>
        </section>

        <section class="grid-3">
            <article class="card">
                <span class="pill pill-success">Trust pulse</span>
                <h2 class="section-title" style="margin-top: 14px;">Coverage is holding strong.</h2>
                <p class="mb-0">
                    <?php echo (int) $metrics['coverage_rate']; ?>% of products in this view already have blockchain presence, which keeps authenticity checks fast and credible.
                </p>
            </article>

            <article class="card">
                <span class="pill pill-warning">Expiry watch</span>
                <h2 class="section-title" style="margin-top: 14px;">Batch attention window</h2>
                <p class="mb-0">
                    <?php echo (int) $metrics['expiring_soon']; ?> batches are nearing expiry, while <?php echo (int) $metrics['recent_products']; ?> fresh registrations landed in the last week.
                </p>
            </article>

            <article class="card">
                <span class="pill pill-info">Next best move</span>
                <h2 class="section-title" style="margin-top: 14px;">Keep verification momentum high.</h2>
                <p class="mb-0">
                    Open analytics for patterns or scan a product now to add one more trust event to the ledger.
                </p>
            </article>
        </section>

        <section class="grid-2">
            <article class="table-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Top trust-ready products</h2>
                        <p>Products with the strongest ledger footprint and verification activity.</p>
                    </div>
                    <a href="<?php echo $user_type === 'manufacturer' ? 'my-products.php' : 'scan-qr.php'; ?>" class="btn btn-ghost btn-sm">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                        <span>Open full view</span>
                    </a>
                </div>

                <?php if (!empty($top_products)): ?>
                    <div class="scroll-x">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Trust</th>
                                    <th>Latest activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                                            <span class="helper-note"><?php echo htmlspecialchars($product['brand']); ?> &middot; <?php echo htmlspecialchars($product['product_id']); ?></span>
                                        </td>
                                        <td>
                                            <span class="pill pill-info"><?php echo htmlspecialchars($product['category']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo (int) $product['trust_score']; ?>/99</strong><br>
                                            <span class="pill pill-<?php echo htmlspecialchars($product['risk']['tone']); ?>"><?php echo htmlspecialchars($product['risk']['label']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['last_activity_formatted']); ?></strong><br>
                                            <span class="helper-note"><?php echo (int) $product['verification_checks']; ?> checks &middot; <?php echo (int) $product['ledger_entries']; ?> entries</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="table-empty">
                        <i class="fas fa-box-open"></i>
                        <strong>No products yet</strong>
                        <p class="mb-0">Create your first product to start building trust metrics.</p>
                    </div>
                <?php endif; ?>
            </article>

            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Category mix</h2>
                        <p>Where your protected inventory is concentrated right now.</p>
                    </div>
                </div>

                <?php if (!empty($categories)): ?>
                    <div class="bar-list">
                        <?php foreach ($categories as $category): ?>
                            <div class="bar-row">
                                <div class="bar-meta">
                                    <strong><?php echo htmlspecialchars($category['category']); ?></strong>
                                    <span class="helper-note"><?php echo (int) $category['total']; ?> items &middot; <?php echo (int) $category['percentage']; ?>%</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo (int) $category['percentage']; ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-pie"></i>
                        <strong>No category data available yet.</strong>
                    </div>
                <?php endif; ?>

                <div class="mini-grid" style="margin-top: 22px;">
                    <div class="mini-stat">
                        <span class="stat-kicker">Verified products</span>
                        <strong class="number-value"><?php echo (int) $metrics['verified_products']; ?></strong>
                    </div>
                    <div class="mini-stat">
                        <span class="stat-kicker">Coverage rate</span>
                        <strong class="number-value"><?php echo (int) $metrics['coverage_rate']; ?>%</strong>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid-2">
            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Recent blockchain activity</h2>
                        <p>See what just happened across your verification flow.</p>
                    </div>
                </div>

                <?php if (!empty($recent_activity)): ?>
                    <div class="timeline">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="timeline-item">
                                <span class="timeline-dot"></span>
                                <div class="timeline-card">
                                    <div class="panel-header">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['action_label']); ?></strong>
                                            <p><?php echo htmlspecialchars($activity['summary']); ?></p>
                                        </div>
                                        <span class="pill pill-<?php echo htmlspecialchars($activity['tone']); ?>"><?php echo htmlspecialchars($activity['timestamp_formatted']); ?></span>
                                    </div>
                                    <p class="helper-note mb-0">
                                        <?php echo htmlspecialchars($activity['product_id']); ?> &middot; <?php echo htmlspecialchars($activity['hash_short']); ?>
                                    </p>
                                    <?php if (!empty($activity['proof']['transaction_hash'])): ?>
                                        <div class="proof-mini-grid">
                                            <span class="helper-note proof-code">Tx <?php echo htmlspecialchars($activity['proof']['transaction_hash_short'] ?? $activity['hash_short']); ?></span>
                                            <?php if (!empty($activity['proof']['block_number'])): ?>
                                                <span class="helper-note">Block <?php echo (int) $activity['proof']['block_number']; ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($activity['proof']['chain_id'])): ?>
                                                <span class="helper-note">Chain <?php echo (int) $activity['proof']['chain_id']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="action-row" style="margin-top: 12px;">
                                            <button type="button" class="btn btn-ghost btn-sm" data-copy-text="<?php echo htmlspecialchars($activity['proof']['transaction_hash']); ?>" data-copy-success="Transaction hash copied to clipboard.">
                                                <i class="fas fa-copy"></i>
                                                <span>Copy tx hash</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-link"></i>
                        <strong>No blockchain activity yet.</strong>
                    </div>
                <?php endif; ?>
            </article>

            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">7-day momentum</h2>
                        <p>Daily blockchain entries help you see whether verification activity is accelerating.</p>
                    </div>
                </div>

                <div class="trend-chart">
                    <?php foreach ($trend as $point): ?>
                        <div class="trend-bar">
                            <small><?php echo (int) $point['entries']; ?> entries</small>
                            <div class="trend-bar-fill" style="height: <?php echo max(16, (int) $point['height'] * 1.4); ?>px;"></div>
                            <strong><?php echo htmlspecialchars($point['label']); ?></strong>
                            <small><?php echo (int) $point['verifications']; ?> checks</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
    </main>

    <div class="toast-stack" id="toastStack" aria-live="polite"></div>

    <script>
        const toastStack = document.getElementById('toastStack');

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

        document.querySelectorAll('[data-copy-text]').forEach((button) => {
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
    </script>
</body>
</html>
