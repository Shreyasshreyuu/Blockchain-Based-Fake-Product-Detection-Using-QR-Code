<?php
$current_page = basename($_SERVER['PHP_SELF']);
$links = [
    ['href' => 'dashboard.php', 'icon' => 'fa-house', 'label' => 'Dashboard', 'match' => ['dashboard.php']],
    ['href' => 'scan-qr.php', 'icon' => 'fa-camera-retro', 'label' => 'Smart Scan', 'match' => ['scan-qr.php', 'verify-product.php']],
    ['href' => 'ledger.php', 'icon' => 'fa-link', 'label' => 'Ledger', 'match' => ['ledger.php']],
    ['href' => 'analytics.php', 'icon' => 'fa-chart-line', 'label' => 'Analytics', 'match' => ['analytics.php']]
];

if (isManufacturer()) {
    array_splice($links, 1, 0, [
        ['href' => 'generate-qr.php', 'icon' => 'fa-qrcode', 'label' => 'Create QR', 'match' => ['generate-qr.php']],
        ['href' => 'my-products.php', 'icon' => 'fa-boxes-stacked', 'label' => 'Products', 'match' => ['my-products.php']]
    ]);
}

if (canManageTrackedLocations()) {
    array_splice($links, isManufacturer() ? 3 : 2, 0, [
        ['href' => 'tracking.php', 'icon' => 'fa-location-crosshairs', 'label' => 'Tracking', 'match' => ['tracking.php', 'track-product-location.php']]
    ]);
}
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-brand">
            <span class="brand-mark"><i class="fas fa-shield-halved"></i></span>
            <span class="nav-brand-copy">
                <strong>ProductSecure</strong>
                <small>Authenticity intelligence</small>
            </span>
        </a>

        <div class="nav-links">
            <?php foreach ($links as $link): ?>
                <?php $is_active = in_array($current_page, $link['match'], true); ?>
                <a href="<?php echo $link['href']; ?>" class="nav-link <?php echo $is_active ? 'active' : ''; ?>">
                    <i class="fas <?php echo $link['icon']; ?>"></i>
                    <span><?php echo htmlspecialchars($link['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="nav-meta">
            <a href="profile.php" class="nav-user <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <span class="nav-avatar"><?php echo htmlspecialchars(getInitials($_SESSION['username'] ?? 'User')); ?></span>
                <span class="nav-user-copy">
                    <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong>
                    <small><?php echo htmlspecialchars(ucfirst($_SESSION['user_type'] ?? 'member')); ?></small>
                </span>
            </a>
            <a href="logout.php" class="btn btn-ghost btn-sm">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>
