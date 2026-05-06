<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$selector = trim($_GET['selector'] ?? $_POST['selector'] ?? '');
$validator = trim($_GET['validator'] ?? $_POST['validator'] ?? '');
$error = '';
$success = '';

$request_valid = ($selector !== '' && $validator !== '' && ctype_xdigit($selector) && ctype_xdigit($validator));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$request_valid) {
        $error = 'This password reset link is invalid.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $result = resetPasswordFromToken($conn, $selector, $validator, $password);

        if ($result['success']) {
            $success = 'Password updated successfully. You can now sign in with your new password.';
        } else {
            $error = $result['error'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $request_valid) {
    $request_valid = findValidPasswordResetRequest($conn, $selector, $validator) !== null;

    if (!$request_valid) {
        $error = 'This password reset link is invalid or has expired.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="auth-body">
    <div class="auth-scene">
        <div class="auth-floor-glow"></div>
        <div class="auth-floor-grid"></div>
        <div class="auth-orb auth-orb-one"></div>
        <div class="auth-orb auth-orb-two"></div>

        <div class="auth-beams" aria-hidden="true">
            <span style="--beam-height: 260px; --beam-delay: 0s;"></span>
            <span style="--beam-height: 380px; --beam-delay: .5s;"></span>
            <span style="--beam-height: 235px; --beam-delay: 1.2s;"></span>
            <span style="--beam-height: 510px; --beam-delay: .8s;"></span>
            <span style="--beam-height: 290px; --beam-delay: 1.4s;"></span>
            <span style="--beam-height: 480px; --beam-delay: .2s;"></span>
            <span style="--beam-height: 260px; --beam-delay: 1.8s;"></span>
        </div>

        <a href="index.php" class="auth-floating-brand">
            <span class="brand-mark"><i class="fas fa-lock"></i></span>
            <span>
                <strong>Set New Password</strong>
                <span>Secure access restoration</span>
            </span>
        </a>

        <div class="auth-card-wrap">
            <section class="auth-card">
                <div class="auth-switch">
                    <a href="index.php">Login</a>
                    <a href="reset-password.php" class="active">New Password</a>
                </div>

                <div class="auth-copy">
                    <h1>Create New Password</h1>
                    <p>Choose a strong password to restore access to your ProductSecure account.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 18px;">
                        <i class="fas fa-circle-exclamation"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="auth-success-banner" style="margin-bottom: 18px;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <div class="auth-links">
                        <span><a href="index.php">Return to login</a></span>
                    </div>
                <?php elseif ($request_valid): ?>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector); ?>">
                        <input type="hidden" name="validator" value="<?php echo htmlspecialchars($validator); ?>">

                        <div class="auth-field">
                            <span class="auth-field-title">New Password</span>
                            <div class="auth-input-shell">
                                <input type="password" name="password" placeholder="Enter a new password" required>
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>

                        <div class="auth-field">
                            <span class="auth-field-title">Confirm New Password</span>
                            <div class="auth-input-shell">
                                <input type="password" name="confirm_password" placeholder="Repeat the new password" required>
                                <i class="fas fa-shield-heart"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn auth-submit btn-block">
                            <span>Update Password</span>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="auth-links">
                        <span><a href="forgot-password.php">Request a fresh reset link</a></span>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</body>
</html>
