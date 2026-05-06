<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = createPasswordResetRequest($conn, $email);

        if ($result['success']) {
            $success = 'If that email exists in our system, a password reset link has been sent.';
        } else {
            $error = $result['error'] ?: 'We could not send the reset email right now. Check your SMTP settings and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ProductSecure</title>
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
            <span style="--beam-height: 250px; --beam-delay: 0s;"></span>
            <span style="--beam-height: 360px; --beam-delay: .4s;"></span>
            <span style="--beam-height: 220px; --beam-delay: 1.2s;"></span>
            <span style="--beam-height: 500px; --beam-delay: .8s;"></span>
            <span style="--beam-height: 260px; --beam-delay: 1.6s;"></span>
            <span style="--beam-height: 520px; --beam-delay: .2s;"></span>
            <span style="--beam-height: 285px; --beam-delay: 1.9s;"></span>
        </div>

        <a href="index.php" class="auth-floating-brand">
            <span class="brand-mark"><i class="fas fa-key"></i></span>
            <span>
                <strong>Password Recovery</strong>
                <span>Secure email reset flow</span>
            </span>
        </a>

        <div class="auth-card-wrap">
            <section class="auth-card">
                <div class="auth-switch">
                    <a href="index.php">Login</a>
                    <a href="forgot-password.php" class="active">Forgot Password</a>
                </div>

                <div class="auth-copy">
                    <h1>Reset Password</h1>
                    <p>Enter the email linked to your account and we’ll send a secure password reset link.</p>
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
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="auth-field">
                        <span class="auth-field-title">Account Email</span>
                        <div class="auth-input-shell">
                            <input type="email" name="email" placeholder="Enter your registered email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn auth-submit btn-block">
                        <span>Send Reset Link</span>
                    </button>
                </form>

                <div class="auth-links">
                    <span>Remembered it? <a href="index.php">Back to login</a></span>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
