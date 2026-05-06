<?php
require_once 'includes/config.php';
requireAuth();

$entries = getLedgerEntries($conn, 120);
$summary = getLedgerSummary($entries);
$blockchain_health = getRealBlockchainHealth();
$style_version = @filemtime(__DIR__ . '/assets/css/style.css') ?: time();
$real_chain_entries = 0;
$fallback_entries = 0;

foreach ($entries as $entry) {
    if (!empty($entry['proof']['is_real'])) {
        $real_chain_entries++;
    } elseif (!empty($entry['proof']['transaction_hash'])) {
        $fallback_entries++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo (int) $style_version; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="page-shell stack">
        <section class="page-topper">
            <p class="eyebrow" style="color: var(--primary-deep); background: rgba(20, 86, 240, 0.08);">
                <i class="fas fa-link"></i> Blockchain ledger explorer
            </p>
            <h1 class="page-title">Inspect the chain activity behind every product action.</h1>
            <p class="page-subtitle">
                Search the latest proof trail, inspect transaction continuity, and copy the exact on-chain evidence behind each event.
            </p>
        </section>

        <section class="metric-grid">
            <article class="card metric-card">
                <span class="stat-kicker">Total entries</span>
                <strong class="metric-value"><?php echo (int) $summary['total_entries']; ?></strong>
                <p class="mb-0">Most recent blockchain records loaded into the explorer.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Real chain proofs</span>
                <strong class="metric-value"><?php echo (int) $real_chain_entries; ?></strong>
                <p class="mb-0"><?php echo (int) $fallback_entries; ?> fallback ledger records remain in this explorer slice.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Verification events</span>
                <strong class="metric-value"><?php echo (int) $summary['verification_events']; ?></strong>
                <p class="mb-0">Product verification checks found in this slice.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Chain continuity</span>
                <strong class="metric-value"><?php echo (int) $summary['continuity_score']; ?>%</strong>
                <p class="mb-0">Percentage of entries whose previous hash matches the expected chain.</p>
            </article>
        </section>

        <section class="card ledger-health-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Blockchain status</h2>
                    <p>Live status from the local blockchain bridge that powers QR generation and verification proof.</p>
                </div>
                <?php if ($blockchain_health): ?>
                    <span class="pill pill-success"><span class="status-dot"></span> Real blockchain active</span>
                <?php else: ?>
                    <span class="pill pill-warning"><span class="status-dot"></span> Using local ledger view only</span>
                <?php endif; ?>
            </div>

            <?php if ($blockchain_health): ?>
                <div class="quick-chips" style="margin-bottom: 16px;">
                    <span class="pill pill-info">Chain <?php echo (int) ($blockchain_health['chainId'] ?? 0); ?></span>
                    <span class="pill pill-info">Latest block <?php echo (int) ($blockchain_health['blockNumber'] ?? 0); ?></span>
                    <?php if (!empty($blockchain_health['contractAddress'])): ?>
                        <span class="pill pill-success"><?php echo htmlspecialchars(truncateHash($blockchain_health['contractAddress'], 16, 8)); ?></span>
                    <?php endif; ?>
                </div>

                <div class="detail-grid ledger-proof-grid">
                    <div class="detail-card">
                        <strong>Mode</strong>
                        <span class="detail-value"><?php echo htmlspecialchars($blockchain_health['mode'] ?? 'local-hardhat'); ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>RPC endpoint</strong>
                        <span class="detail-value proof-code"><?php echo htmlspecialchars($blockchain_health['rpcUrl'] ?? 'http://127.0.0.1:8545'); ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>Contract address</strong>
                        <span class="detail-value proof-code"><?php echo htmlspecialchars($blockchain_health['contractAddress'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-card">
                        <strong>Latest network state</strong>
                        <span class="detail-value">Block <?php echo (int) ($blockchain_health['blockNumber'] ?? 0); ?> on chain <?php echo (int) ($blockchain_health['chainId'] ?? 0); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div>
                        <strong>Real blockchain proof is currently unavailable.</strong>
                        <p class="mb-0">Run <code>npm run blockchain:start</code> to bring the local chain, contract deployment, and API bridge back online.</p>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="table-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Ledger records</h2>
                    <p>Search by product ID, product name, manufacturer, transaction hash, or contract address.</p>
                </div>
                <?php if (!empty($summary['latest_hash'])): ?>
                    <span class="pill pill-info">Latest tx <?php echo htmlspecialchars(truncateHash($summary['latest_hash'])); ?></span>
                <?php endif; ?>
            </div>

            <div class="filters-row" style="margin-bottom: 18px;">
                <div class="search-wrap">
                    <input type="text" id="ledgerSearch" class="search-input" placeholder="Search ledger entries">
                </div>
            </div>

            <?php if (!empty($entries)): ?>
                <div class="ledger-explorer" id="ledgerExplorer">
                    <?php foreach ($entries as $entry): ?>
                        <?php
                        $proof = $entry['proof'] ?? [];
                        $proof_tone = !empty($proof['is_real']) ? 'success' : 'warning';
                        $proof_label = !empty($proof['is_real']) ? 'Real on-chain proof' : 'Fallback ledger proof';
                        $search_blob = strtolower(
                            implode(
                                ' ',
                                [
                                    $entry['action_label'],
                                    $entry['product_id'],
                                    $entry['product_name'],
                                    $entry['manufacturer_name'],
                                    $entry['transaction_hash'],
                                    $entry['previous_hash'],
                                    $proof['contract_address'] ?? '',
                                    $proof['payload_hash'] ?? ''
                                ]
                            )
                        );
                        ?>
                        <article class="card ledger-entry-card" data-search="<?php echo htmlspecialchars($search_blob); ?>">
                            <div class="ledger-entry-top">
                                <div class="ledger-entry-headline">
                                    <div class="quick-chips">
                                        <span class="pill pill-<?php echo htmlspecialchars($entry['tone']); ?>"><?php echo htmlspecialchars($entry['action_label']); ?></span>
                                        <span class="pill pill-<?php echo $proof_tone; ?>"><?php echo htmlspecialchars($proof_label); ?></span>
                                        <span class="pill pill-<?php echo !empty($entry['link_valid']) ? 'success' : 'danger'; ?>">
                                            <?php echo !empty($entry['link_valid']) ? 'Chain aligned' : 'Link needs review'; ?>
                                        </span>
                                    </div>
                                    <h3 class="card-title"><?php echo htmlspecialchars($entry['product_name'] ?: $entry['product_id']); ?></h3>
                                    <p class="helper-note mb-0"><?php echo htmlspecialchars($entry['summary']); ?></p>
                                </div>
                                <div class="ledger-entry-stamp">
                                    <strong><?php echo htmlspecialchars($entry['timestamp_formatted']); ?></strong>
                                    <span class="helper-note"><?php echo htmlspecialchars($entry['manufacturer_name'] ?: 'Unknown manufacturer'); ?></span>
                                </div>
                            </div>

                            <div class="detail-grid ledger-proof-grid">
                                <div class="detail-card">
                                    <strong>Product ID</strong>
                                    <span class="detail-value proof-code"><?php echo htmlspecialchars($entry['product_id']); ?></span>
                                </div>
                                <div class="detail-card hash-detail-card">
                                    <strong>Transaction hash</strong>
                                    <span class="detail-value proof-code proof-code-block"><?php echo htmlspecialchars($proof['transaction_hash'] ?? $entry['transaction_hash']); ?></span>
                                </div>
                                <div class="detail-card hash-detail-card">
                                    <strong>Previous hash</strong>
                                    <span class="detail-value proof-code proof-code-block"><?php echo htmlspecialchars($entry['previous_hash']); ?></span>
                                </div>
                                <div class="detail-card">
                                    <strong>Contract</strong>
                                    <span class="detail-value proof-code"><?php echo htmlspecialchars($proof['contract_address'] ?? 'Not available'); ?></span>
                                </div>
                                <div class="detail-card">
                                    <strong>Chain and block</strong>
                                    <span class="detail-value">
                                        <?php echo !empty($proof['chain_id']) ? 'Chain ' . (int) $proof['chain_id'] : 'No chain ID'; ?>
                                        <?php echo !empty($proof['block_number']) ? ' | Block ' . (int) $proof['block_number'] : ''; ?>
                                    </span>
                                </div>
                                <div class="detail-card hash-detail-card">
                                    <strong>Payload hash</strong>
                                    <span class="detail-value proof-code proof-code-block"><?php echo htmlspecialchars($proof['payload_hash'] ?? 'Not available'); ?></span>
                                </div>
                            </div>

                            <div class="action-row" style="margin-top: 16px;">
                                <button type="button" class="btn btn-ghost btn-sm" data-copy-text="<?php echo htmlspecialchars($proof['transaction_hash'] ?? $entry['transaction_hash']); ?>" data-copy-success="Transaction hash copied to clipboard.">
                                    <i class="fas fa-copy"></i>
                                    <span>Copy tx hash</span>
                                </button>
                                <?php if (!empty($proof['contract_address'])): ?>
                                    <button type="button" class="btn btn-ghost btn-sm" data-copy-text="<?php echo htmlspecialchars($proof['contract_address']); ?>" data-copy-success="Contract address copied to clipboard.">
                                        <i class="fas fa-diagram-project"></i>
                                        <span>Copy contract</span>
                                    </button>
                                <?php endif; ?>
                                <?php if (!empty($proof['payload_hash'])): ?>
                                    <button type="button" class="btn btn-ghost btn-sm" data-copy-text="<?php echo htmlspecialchars($proof['payload_hash']); ?>" data-copy-success="Payload hash copied to clipboard.">
                                        <i class="fas fa-fingerprint"></i>
                                        <span>Copy payload hash</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="table-empty">
                    <i class="fas fa-link-slash"></i>
                    <strong>No ledger entries found.</strong>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <div class="toast-stack" id="toastStack" aria-live="polite"></div>

    <script>
        const ledgerSearch = document.getElementById('ledgerSearch');
        const ledgerCards = Array.from(document.querySelectorAll('#ledgerExplorer .ledger-entry-card'));
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

        ledgerSearch?.addEventListener('input', () => {
            const searchValue = ledgerSearch.value.trim().toLowerCase();

            ledgerCards.forEach((card) => {
                card.style.display = card.dataset.search.includes(searchValue) ? '' : 'none';
            });
        });

        attachCopyHandlers(document);
    </script>
</body>
</html>
