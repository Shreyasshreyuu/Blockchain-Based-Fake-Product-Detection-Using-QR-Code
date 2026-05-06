<?php
require_once 'includes/config.php';
requireAuth();

$profile = getUserProfileOverview($conn, (int) $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="page-shell stack">
        <section class="page-topper">
            <p class="eyebrow" style="color: var(--primary-deep); background: rgba(20, 86, 240, 0.08);">
                <i class="fas fa-user-gear"></i> Account profile
            </p>
            <h1 class="page-title">Your ProductSecure identity and activity snapshot.</h1>
            <p class="page-subtitle">A cleaner profile view with role context, contribution stats, and direct shortcuts.</p>
        </section>

        <?php if ($profile): ?>
            <section class="profile-layout">
                <article class="card profile-card">
                    <div class="profile-identity">
                        <span class="profile-avatar"><?php echo htmlspecialchars(getInitials($profile['username'])); ?></span>
                        <div class="profile-meta">
                            <h2 class="section-title"><?php echo htmlspecialchars($profile['username']); ?></h2>
                            <span class="pill pill-info"><?php echo htmlspecialchars(ucfirst($profile['user_type'])); ?></span>
                            <span class="helper-note"><?php echo htmlspecialchars($profile['email']); ?></span>
                        </div>
                    </div>

                    <div class="kpi-row">
                        <div class="kpi-card">
                            <span class="stat-kicker">Member since</span>
                            <strong class="number-value"><?php echo htmlspecialchars($profile['joined_formatted']); ?></strong>
                        </div>
                        <div class="kpi-card">
                            <span class="stat-kicker">Verification checks</span>
                            <strong class="number-value"><?php echo (int) $profile['scan_count']; ?></strong>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-card">
                            <strong>Role focus</strong>
                            <span class="detail-value">
                                <?php echo $profile['user_type'] === 'manufacturer'
                                    ? 'Register and monitor product identity.'
                                    : 'Validate products and inspect trust evidence.'; ?>
                            </span>
                        </div>
                        <div class="detail-card">
                            <strong>Registered products</strong>
                            <span class="detail-value"><?php echo (int) $profile['product_count']; ?></span>
                        </div>
                    </div>
                </article>

                <article class="card">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">Quick actions</h2>
                            <p>Jump directly into the parts of the platform you use most.</p>
                        </div>
                    </div>

                    <div class="stack">
                        <?php if (($profile['user_type'] ?? '') === 'manufacturer'): ?>
                            <a href="generate-qr.php" class="btn btn-primary">
                                <i class="fas fa-qrcode"></i>
                                <span>Create a new QR identity</span>
                            </a>
                            <a href="my-products.php" class="btn btn-ghost">
                                <i class="fas fa-boxes-stacked"></i>
                                <span>Open my products</span>
                            </a>
                        <?php else: ?>
                            <a href="scan-qr.php" class="btn btn-primary">
                                <i class="fas fa-camera-retro"></i>
                                <span>Start a smart scan</span>
                            </a>
                        <?php endif; ?>

                        <a href="analytics.php" class="btn btn-ghost">
                            <i class="fas fa-chart-line"></i>
                            <span>Review analytics</span>
                        </a>
                        <a href="ledger.php" class="btn btn-ghost">
                            <i class="fas fa-link"></i>
                            <span>Inspect ledger</span>
                        </a>
                    </div>
                </article>
            </section>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <strong>Profile details could not be loaded.</strong>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
