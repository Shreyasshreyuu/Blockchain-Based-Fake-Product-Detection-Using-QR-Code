<?php
require_once 'includes/config.php';
requireAuth();
requireManufacturer();

$user_id = (int) $_SESSION['user_id'];

$sql = "SELECT p.*,
        COUNT(pl.id) AS ledger_entries,
        SUM(CASE WHEN pl.block_data LIKE '%PRODUCT_VERIFIED%' THEN 1 ELSE 0 END) AS verification_checks,
        MAX(pl.timestamp) AS last_activity
    FROM products p
    LEFT JOIN product_ledger pl ON pl.product_id = p.product_id
    WHERE p.manufacturer_id = ?
    GROUP BY p.product_id
    ORDER BY p.created_at DESC, p.product_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$products_result = $stmt->get_result();

$products = [];
$expiring_soon = 0;
$verification_total = 0;
$trust_total = 0;

while ($row = $products_result->fetch_assoc()) {
    $row['ledger_entries'] = (int) $row['ledger_entries'];
    $row['verification_checks'] = (int) $row['verification_checks'];
    $row['manufacturer_date_formatted'] = formatDateReadable($row['manufacturer_date']);
    $row['expiry_date_formatted'] = formatDateReadable($row['expiry_date']);
    $row['last_activity_formatted'] = formatDateTimeReadable($row['last_activity']);
    $row['freshness'] = getFreshnessLabel($row);
    $row['trust_score'] = calculateTrustScore($row, $row['ledger_entries'], $row['verification_checks']);
    $row['risk'] = getRiskProfile('GENUINE', $row['trust_score'], $row['freshness']['label'] === 'Expired');

    if ($row['freshness']['label'] === 'Expiring soon') {
        $expiring_soon++;
    }

    $verification_total += $row['verification_checks'];
    $trust_total += $row['trust_score'];
    $products[] = $row;
}

foreach ($products as &$product) {
    $product['latest_location'] = getLatestProductLocation($conn, $product['product_id']);
}
unset($product);

$product_count = count($products);
$average_trust = $product_count > 0 ? (int) round($trust_total / $product_count) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="page-shell stack">
        <section class="page-topper">
            <p class="eyebrow" style="color: var(--primary-deep); background: rgba(20, 86, 240, 0.08);">
                <i class="fas fa-boxes-stacked"></i> Product portfolio
            </p>
            <h1 class="page-title">Manage every registered product from one polished workspace.</h1>
            <p class="page-subtitle">
                Search faster, filter by category, and monitor trust, verification volume, and expiry signals at a glance.
            </p>
        </section>

        <section class="metric-grid">
            <article class="card metric-card">
                <span class="stat-kicker">Registered products</span>
                <strong class="metric-value"><?php echo $product_count; ?></strong>
                <p class="mb-0">Products currently owned by your manufacturer account.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Verification checks</span>
                <strong class="metric-value"><?php echo $verification_total; ?></strong>
                <p class="mb-0">Verification events logged for your products.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Average trust score</span>
                <strong class="metric-value"><?php echo $average_trust; ?>/99</strong>
                <p class="mb-0">Average trust posture across your registered catalog.</p>
            </article>
            <article class="card metric-card">
                <span class="stat-kicker">Expiring soon</span>
                <strong class="metric-value"><?php echo $expiring_soon; ?></strong>
                <p class="mb-0">Batches that deserve attention in the next 30 days.</p>
            </article>
        </section>

        <section class="table-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Product inventory table</h2>
                    <p>Filter by category or search by name, brand, or product ID.</p>
                </div>
                <a href="generate-qr.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i>
                    <span>Add product</span>
                </a>
            </div>

            <div class="filters-row" style="margin-bottom: 18px;">
                <div class="search-wrap">
                    <input type="text" id="productSearch" class="search-input" placeholder="Search products, brands, or IDs">
                    <select id="categoryFilter" class="filter-select">
                        <option value="">All categories</option>
                        <?php
                        $category_values = array_unique(array_map(static fn($product) => $product['category'], $products));
                        sort($category_values);
                        foreach ($category_values as $category):
                        ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (!empty($products)): ?>
                <div class="scroll-x">
                    <table class="data-table" id="productsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Trust</th>
                                <th>Activity</th>
                                <th>QR asset</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <?php $qr_file = 'qrcodes/' . $product['product_id'] . '.png'; ?>
                                <tr
                                    data-search="<?php echo htmlspecialchars(strtolower($product['product_name'] . ' ' . $product['brand'] . ' ' . $product['product_id'])); ?>"
                                    data-category="<?php echo htmlspecialchars($product['category']); ?>"
                                >
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                                        <span class="helper-note"><?php echo htmlspecialchars($product['brand']); ?> · <?php echo htmlspecialchars($product['product_id']); ?></span><br>
                                        <span class="helper-note">Manufactured <?php echo htmlspecialchars($product['manufacturer_date_formatted']); ?></span>
                                    </td>
                                    <td>
                                        <span class="pill pill-info"><?php echo htmlspecialchars($product['category']); ?></span><br>
                                        <span class="pill pill-<?php echo htmlspecialchars($product['freshness']['tone']); ?>" style="margin-top: 8px;"><?php echo htmlspecialchars($product['freshness']['label']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo (int) $product['trust_score']; ?>/99</strong><br>
                                        <span class="pill pill-<?php echo htmlspecialchars($product['risk']['tone']); ?>"><?php echo htmlspecialchars($product['risk']['label']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo (int) $product['verification_checks']; ?> checks</strong><br>
                                        <span class="helper-note"><?php echo (int) $product['ledger_entries']; ?> ledger events</span><br>
                                        <span class="helper-note"><?php echo htmlspecialchars($product['last_activity_formatted']); ?></span><br>
                                        <span class="helper-note">
                                            <?php
                                            if (!empty($product['latest_location'])) {
                                                echo 'Latest live location ' . htmlspecialchars($product['latest_location']['relative_time']);
                                            } else {
                                                echo 'No live location updates yet';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-row">
                                            <?php if (file_exists($qr_file)): ?>
                                                <a href="<?php echo htmlspecialchars($qr_file); ?>" download class="btn btn-ghost btn-sm">
                                                    <i class="fas fa-download"></i>
                                                    <span>Download</span>
                                                </a>
                                            <?php endif; ?>
                                            <a href="scan-qr.php?product=<?php echo urlencode($product['product_id']); ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-camera-retro"></i>
                                                <span>Verify</span>
                                            </a>
                                            <a href="tracking.php?product=<?php echo urlencode($product['product_id']); ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-location-crosshairs"></i>
                                                <span>Track live</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="table-empty">
                    <i class="fas fa-box-open"></i>
                    <strong>No products found</strong>
                    <p class="mb-0">Create your first product to start using the new QR and analytics workflow.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        const productSearch = document.getElementById('productSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const tableRows = Array.from(document.querySelectorAll('#productsTable tbody tr'));

        function filterProducts() {
            const searchValue = (productSearch?.value || '').trim().toLowerCase();
            const categoryValue = categoryFilter?.value || '';

            tableRows.forEach((row) => {
                const matchesSearch = row.dataset.search.includes(searchValue);
                const matchesCategory = !categoryValue || row.dataset.category === categoryValue;
                row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
            });
        }

        productSearch?.addEventListener('input', filterProducts);
        categoryFilter?.addEventListener('change', filterProducts);
    </script>
</body>
</html>
