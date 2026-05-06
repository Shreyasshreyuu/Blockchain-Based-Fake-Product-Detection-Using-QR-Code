<?php
require_once 'includes/config.php';
requireAuth();

$user_id = (int) $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'member';

$metrics = getDashboardMetrics($conn, $user_id, $user_type);
$categories = getCategoryBreakdown($conn, $user_type, $user_id, 8);
$top_products = getTopTrustedProducts($conn, $user_type, $user_id, 6);
$trend = getActivityTrend($conn, $user_type, $user_id, 7);
$activity = getRecentActivityFeed($conn, $user_type, $user_id, 5);

$verification_share = $metrics['blockchain_entries'] > 0
    ? (int) round(($metrics['verification_checks'] / $metrics['blockchain_entries']) * 100)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="page-shell stack">
        <section class="hero">
            <div class="hero-grid">
                <div>
                    <p class="eyebrow"><i class="fas fa-chart-line"></i> Advanced analytics</p>
                    <h1>See how trust is growing across your product network.</h1>
                    <p>
                        This page turns the old placeholder into an operational dashboard for product coverage, verification momentum,
                        category mix, and blockchain activity quality.
                    </p>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <small>Network score</small>
                        <strong><?php echo (int) $metrics['network_score']; ?>/99</strong>
                    </div>
                    <div class="hero-stat">
                        <small>Verification share</small>
                        <strong><?php echo $verification_share; ?>%</strong>
                    </div>
                    <div class="hero-stat">
                        <small>Protected categories</small>
                        <strong><?php echo (int) $metrics['active_categories']; ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="metric-grid">
            <article class="card metric-card">
                <span class="stat-kicker">Registered products</span>
                <strong class="metric-value"><?php echo (int) $metrics['total_products']; ?></strong>
                <p class="mb-0">Products inside the analytics scope.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Ledger events</span>
                <strong class="metric-value"><?php echo (int) $metrics['blockchain_entries']; ?></strong>
                <p class="mb-0">Combined creation and verification activity.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Verification checks</span>
                <strong class="metric-value"><?php echo (int) $metrics['verification_checks']; ?></strong>
                <p class="mb-0">Recorded product checks across the platform.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Batches needing review</span>
                <strong class="metric-value"><?php echo (int) $metrics['expiring_soon']; ?></strong>
                <p class="mb-0">Products expiring within 30 days.</p>
            </article>
        </section>

        <section class="grid-2">
            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">7-day activity trend</h2>
                        <p>Track whether registrations and verifications are moving up or flattening out.</p>
                    </div>
                </div>

                <div class="trend-chart">
                    <?php foreach ($trend as $point): ?>
                        <div class="trend-bar">
                            <small><?php echo (int) $point['verifications']; ?> checks</small>
                            <div class="trend-bar-fill" style="height: <?php echo max(16, (int) $point['height'] * 1.4); ?>px;"></div>
                            <strong><?php echo htmlspecialchars($point['label']); ?></strong>
                            <small><?php echo (int) $point['entries']; ?> events</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Category distribution</h2>
                        <p>See where product concentration is highest across the tracked portfolio.</p>
                    </div>
                </div>

                <?php if (!empty($categories)): ?>
                    <div class="bar-list">
                        <?php foreach ($categories as $category): ?>
                            <div class="bar-row">
                                <div class="bar-meta">
                                    <strong><?php echo htmlspecialchars($category['category']); ?></strong>
                                    <span class="helper-note"><?php echo (int) $category['total']; ?> products</span>
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
                        <strong>No category data to chart yet.</strong>
                    </div>
                <?php endif; ?>
            </article>
        </section>

        <section class="grid-2">
            <article class="table-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Top products by trust score</h2>
                        <p>Products that are best supported by ledger activity and recent verification behavior.</p>
                    </div>
                </div>

                <?php if (!empty($top_products)): ?>
                    <div class="scroll-x">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Trust</th>
                                    <th>Ledger</th>
                                    <th>Freshness</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                                            <span class="helper-note"><?php echo htmlspecialchars($product['brand']); ?> · <?php echo htmlspecialchars($product['product_id']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo (int) $product['trust_score']; ?>/99</strong><br>
                                            <span class="pill pill-<?php echo htmlspecialchars($product['risk']['tone']); ?>"><?php echo htmlspecialchars($product['risk']['label']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo (int) $product['ledger_entries']; ?> entries</strong><br>
                                            <span class="helper-note"><?php echo (int) $product['verification_checks']; ?> verification checks</span>
                                        </td>
                                        <td>
                                            <span class="pill pill-<?php echo htmlspecialchars($product['freshness']['tone']); ?>"><?php echo htmlspecialchars($product['freshness']['label']); ?></span><br>
                                            <span class="helper-note"><?php echo htmlspecialchars($product['expiry_date_formatted']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="table-empty">
                        <i class="fas fa-box"></i>
                        <strong>No products available for ranking yet.</strong>
                    </div>
                <?php endif; ?>
            </article>

            <article class="card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Verification funnel</h2>
                        <p>Understand how protected products turn into active trust signals.</p>
                    </div>
                </div>

                <div class="bar-list">
                    <div class="bar-row">
                        <div class="bar-meta">
                            <strong>Protected products</strong>
                            <span class="helper-note"><?php echo (int) $metrics['total_products']; ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: 100%;"></div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-meta">
                            <strong>Products with ledger presence</strong>
                            <span class="helper-note"><?php echo (int) $metrics['verified_products']; ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?php echo max(6, (int) $metrics['coverage_rate']); ?>%;"></div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-meta">
                            <strong>Verification checks</strong>
                            <span class="helper-note"><?php echo (int) $metrics['verification_checks']; ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?php echo max(8, $verification_share); ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="mini-grid" style="margin-top: 22px;">
                    <div class="mini-stat">
                        <span class="stat-kicker">Recent registrations</span>
                        <strong class="number-value"><?php echo (int) $metrics['recent_products']; ?></strong>
                    </div>
                    <div class="mini-stat">
                        <span class="stat-kicker">Coverage rate</span>
                        <strong class="number-value"><?php echo (int) $metrics['coverage_rate']; ?>%</strong>
                    </div>
                </div>
            </article>
        </section>

        <section class="card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Latest verification activity</h2>
                    <p>Recent events help explain spikes in the charts above.</p>
                </div>
            </div>

            <?php if (!empty($activity)): ?>
                <div class="timeline">
                    <?php foreach ($activity as $entry): ?>
                        <div class="timeline-item">
                            <span class="timeline-dot"></span>
                            <div class="timeline-card">
                                <div class="panel-header">
                                    <div>
                                        <strong><?php echo htmlspecialchars($entry['action_label']); ?></strong>
                                        <p><?php echo htmlspecialchars($entry['summary']); ?></p>
                                    </div>
                                    <span class="pill pill-<?php echo htmlspecialchars($entry['tone']); ?>"><?php echo htmlspecialchars($entry['timestamp_formatted']); ?></span>
                                </div>
                                <span class="helper-note"><?php echo htmlspecialchars($entry['product_id']); ?> · <?php echo htmlspecialchars($entry['hash_short']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-link"></i>
                    <strong>No activity has been recorded yet.</strong>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
